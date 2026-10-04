<?php
/*
 * Script: Museum Module Installer
 * Author: Roger Craveiro Guilherme
 * Description: Recursively copies the predefined structures (def, pfts) from the plugin's install folder to ABCD bases.
 */

declare(strict_types=1);
session_start();
$central_path = "../../../central/";

// 1. Verificação de Segurança Nativa do ABCD (Substitui a trava do PluginBridge)
if (!isset($_SESSION["permiso"]["CENTRAL_ALL"]) && !isset($_SESSION["permiso"]["MUSEUM_ADMIN"])) {
    header("HTTP/1.1 403 Forbidden");
    die("<div style='color:red; font-family:sans-serif; padding:20px;'>Access Denied. Only Administrators can install module databases.</div>");
}

// 2. Carregamento do Core (Isso traz o config.php, o PluginBridge e o LanguageManager para a memória)
require_once("{$central_path}config_inc_check.php");
require_once("{$central_path}config.php");
include("{$central_path}common/get_post.php");

$ABCD_lang = $_SESSION["lang"] ?? 'pt';
global $msgstr, $langManager;
include("{$central_path}lang/dbadmin.php");
include("{$central_path}lang/admin.php");

// 3. Carregamento das Traduções Protegidas do Museu
if (isset($langManager)) {
    $plugin_lang = $langManager->loadPluginTranslations(__DIR__, 'museum', 'museum.tab', $ABCD_lang);
    if (is_array($msgstr) && is_array($plugin_lang)) {
        $msgstr = array_merge($msgstr, $plugin_lang);
    }
}

global $db_path, $Wxis, $xWxis, $ABCD_scripts_path;

// 4. Motor Robusto de Cópia de Diretórios
function museum_recurse_copy($src, $dst)
{
    if (!is_dir($src)) return;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        $subPath = $iterator->getSubPathName();
        $target = $dst . DIRECTORY_SEPARATOR . $subPath;
        if ($item->isDir()) {
            @mkdir($target, 0775, true);
        } else {
            @mkdir(dirname($target), 0775, true);
            copy($item->getPathname(), $target);
        }
    }
}

include("{$central_path}common/header.php");
?>
<div class="middle homepage" style="padding:40px; font-family: 'Segoe UI', sans-serif;">
    <div class="mainBox" style="max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px;">
            <i class="fas fa-cogs" style="color: #007bff;"></i> <?php echo $msgstr["museum_install_title"] ?? "Museum Module Installation"; ?>
        </h2>

        <?php
        $museum_databases = [
            'spec_receipts' => [
                'description' => 'Museum Entry and Exit Receipts',
                'fst' => ["10 0 mpu,v10", "20 0 mpu,v20", "30 0 mpu,v30", "50 0 mpu,(|OBJ_|v50|%|/)/"]
            ],
            'spec_movements' => [
                'description' => 'Location Audit Trail',
                'fst' => ["10 0 mpu,v10", "20 0 mpu,v20", "30 0 mpu,v30", "40 0 mpu,v40"]
            ],
            'spec_loans' => [
                'description' => 'Museum Loans and Acquisitions',
                'fst' => ["10 0 mpu,v10", "20 0 mpu,v20", "30 0 mpu,v30"]
            ]
        ];

        $bases_dat = $db_path . "bases.dat";
        $bases_registered = file_exists($bases_dat) ? file_get_contents($bases_dat) : '';
        $plugin_install_dir = __DIR__ . DIRECTORY_SEPARATOR . 'install';

        foreach ($museum_databases as $db_name => $db_data) {
            echo "<h3 style='margin-top: 25px; color: #444;'>" . ($msgstr["museum_install_checking"] ?? "Checking database:") . " <strong style='color:#007bff;'>{$db_name}</strong></h3>";
            $base_dir = rtrim($db_path, '/\\') . DIRECTORY_SEPARATOR . $db_name;

            if (is_dir($base_dir)) {
                echo "<p style='color: #28a745; font-weight: bold;'><i class='fas fa-check-circle'></i> " . ($msgstr["museum_install_exists"] ?? "Directory already exists. Skipping creation.") . "</p>";
                continue;
            }

            @mkdir($base_dir, 0775, true);
            @mkdir($base_dir . DIRECTORY_SEPARATOR . 'data', 0775, true);
            @mkdir($base_dir . DIRECTORY_SEPARATOR . 'def', 0775, true);
            @mkdir($base_dir . DIRECTORY_SEPARATOR . 'pfts', 0775, true);

            echo "<p style='color: #555;'><i class='fas fa-copy'></i> " . ($msgstr["museum_install_copying"] ?? "Copying structural files (def, pfts)...") . "</p>";

            $source_dir = $plugin_install_dir . DIRECTORY_SEPARATOR . $db_name;
            if (is_dir($source_dir)) {
                museum_recurse_copy($source_dir, $base_dir);
            }

            if (file_exists($plugin_install_dir . DIRECTORY_SEPARATOR . $db_name . '.fdt')) {
                copy($plugin_install_dir . DIRECTORY_SEPARATOR . $db_name . '.fdt', $base_dir . DIRECTORY_SEPARATOR . 'def' . DIRECTORY_SEPARATOR . $db_name . '.fdt');
            }

            // Copy pre-defined .fst if it exists in install/data/
            if (file_exists($plugin_install_dir . DIRECTORY_SEPARATOR . $db_name . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $db_name . '.fst')) {
                copy($plugin_install_dir . DIRECTORY_SEPARATOR . $db_name . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $db_name . '.fst', $base_dir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $db_name . '.fst');
            } else {
                $fst_content = implode("\n", $db_data['fst']);
                file_put_contents($base_dir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $db_name . '.fst', $fst_content);
            }

            // Copy pre-defined .par from install/ or generate dynamically
            if (file_exists($plugin_install_dir . DIRECTORY_SEPARATOR . $db_name . '.par')) {
                copy($plugin_install_dir . DIRECTORY_SEPARATOR . $db_name . '.par', $db_path . "par" . DIRECTORY_SEPARATOR . "{$db_name}.par");
            } else {
                $par_content = "{$db_name}.*=%path_database%{$db_name}/data/{$db_name}.*";
                file_put_contents($db_path . "par" . DIRECTORY_SEPARATOR . "{$db_name}.par", $par_content);
            }

            $IsisScript = $xWxis . "administrar.xis";
            $query = "&base={$db_name}&cipar={$db_path}par/{$db_name}.par&Opcion=inicializar";
            ob_start();
            include(rtrim($ABCD_scripts_path, '/\\') . "/central/common/wxis_llamar.php");
            ob_end_clean();

            if (strpos($bases_registered, $db_name) === false) {
                file_put_contents($bases_dat, "\n{$db_name}|{$db_data['description']}", FILE_APPEND);
                $bases_registered .= "\n{$db_name}";
            }

            echo "<p style='color: #28a745; font-weight: bold;'><i class='fas fa-check-circle'></i> " . ($msgstr["museum_install_success"] ?? "Database successfully initialized and registered!") . "</p>";
        }
        ?>

        <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee;">
            <h3 style="color: #2c3e50;"><i class="fas fa-flag-checkered"></i> <?php echo $msgstr["museum_install_complete"] ?? "Installation Complete!"; ?></h3>
            <br>
            <a href="index.php" class="bt-green" style="padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 1.1em; display: inline-block;">
                <i class="fas fa-home"></i> <?php echo $msgstr["museum_install_return"] ?? "Return to Museum Dashboard"; ?>
            </a>
        </div>
    </div>
</div>
<?php include("{$central_path}common/footer.php"); ?>