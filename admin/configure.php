<?php
/*
 * Script: Museum Module Configuration
 * Description: Gerencia configurações globais, termos legais e parâmetros de auditoria do plugin.
 */
declare(strict_types=1);
session_start();
$central_path = "../../../../central/";

if (!isset($_SESSION["permiso"]["CENTRAL_ALL"]) && !isset($_SESSION["permiso"]["MUSEUM_ADMIN"])) {
    header("Location: {$central_path}common/error_page.php");
    die;
}

require_once("{$central_path}config_inc_check.php");
require_once("{$central_path}config.php");
include("{$central_path}common/get_post.php");

$_SESSION["MODULO"] = "museum";
$ABCD_lang = $_SESSION["lang"] ?? 'pt';

global $msgstr, $langManager, $db_path;
include("{$central_path}lang/dbadmin.php");
include("{$central_path}lang/admin.php");

if (isset($langManager)) {
    // Aponta um nível acima (..) pois estamos dentro da pasta admin/
    $plugin_lang = $langManager->loadPluginTranslations(__DIR__ . '/..', 'museum', 'museum.tab', $ABCD_lang);
    if (is_array($msgstr) && is_array($plugin_lang)) {
        $msgstr = array_merge($msgstr, $plugin_lang);
    }
}

// Arquivo de configuração padrão do plugin
$config_file = $db_path . "par/museum.def";
$success_msg = false;

// Processa o salvamento do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_museum_config'])) {
    $content = "; ABCD Museum Module Configuration\n";
    $content .= "; Updated by " . $_SESSION['login'] . " on " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($_POST as $key => $val) {
        if ($key !== 'save_museum_config') {
            // Protege aspas duplas e quebras de linha para o formato INI
            $clean_val = str_replace(["\r\n", "\r", "\n"], "\\n", stripslashes($val));
            $clean_val = str_replace('"', '\"', $clean_val);
            $content .= $key . "=\"" . $clean_val . "\"\n";
        }
    }
    
    if (!is_dir($db_path . "par")) @mkdir($db_path . "par", 0775, true);
    file_put_contents($config_file, $content);
    $success_msg = true;
}

// Carrega as configurações atuais
$current_config = file_exists($config_file) ? parse_ini_file($config_file) : [];

// Função utilitária para repopular os campos preservando as quebras de linha
function get_cfg($key, $default = '') {
    global $current_config;
    if (isset($current_config[$key])) {
        return htmlspecialchars(str_replace("\\n", "\n", $current_config[$key]));
    }
    return $default;
}

include("{$central_path}common/header.php");
include("{$central_path}common/institutional_info.php");
echo '<link rel="stylesheet" href="../assets/css/museum.css" type="text/css">';
?>

<div class="sectionInfo">
    <div class="breadcrumb">
        <strong><?php echo $msgstr["inicio"] ?? "Home"; ?> - <?php echo $msgstr["museum_module"] ?? "Museum"; ?> - <?php echo $msgstr["museum_configure"] ?? "Configure"; ?></strong>
    </div>
    <div class="actions">
        <a href="../index.php?lang=<?php echo htmlspecialchars($ABCD_lang); ?>" class="bt-tool" title="<?php echo $msgstr["back"] ?? "Back"; ?>"><i class="fas fa-arrow-left"></i></a>
    </div>
    <div class="spacer">&#160;</div>
</div>

<div class="middle homepage" style="padding: 20px 40px;">
    <?php if ($success_msg): ?>
        <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: bold; border-left: 5px solid #28a745;">
            <i class="fas fa-check-circle"></i> <?php echo $msgstr["museum_config_saved"] ?? "Configurações salvas com sucesso!"; ?>
        </div>
    <?php endif; ?>

    <div class="mainBox" style="background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); max-width: 900px; margin: 0 auto;">
        <h2 style="color: var(--abcd-steel-blue); border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 25px;">
            <i class="fas fa-tools"></i> <?php echo $msgstr["museum_configure"] ?? "Configurações do Módulo"; ?>
        </h2>

        <form method="post" action="configure.php?lang=<?php echo htmlspecialchars($ABCD_lang); ?>">
            
            <!-- BLOCO 1: INSTITUIÇÃO -->
            <h3 style="color: #444; margin-bottom: 15px;"><i class="fas fa-university"></i> <?php echo $msgstr["museum_cfg_inst_title"] ?? "Identidade da Instituição (Recibos PDF)"; ?></h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                <div>
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;"><?php echo $msgstr["museum_cfg_inst_name"] ?? "Nome Oficial da Instituição"; ?></label>
                    <input type="text" name="INSTITUTION_NAME" class="textEntry" style="width: 100%; padding: 8px;" value="<?php echo get_cfg('INSTITUTION_NAME'); ?>">
                </div>
                <div>
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;"><?php echo $msgstr["museum_cfg_inst_id"] ?? "CNPJ / Registro Legal"; ?></label>
                    <input type="text" name="INSTITUTION_ID" class="textEntry" style="width: 100%; padding: 8px;" value="<?php echo get_cfg('INSTITUTION_ID'); ?>">
                </div>
                <div style="grid-column: 1 / -1;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;"><?php echo $msgstr["museum_cfg_inst_addr"] ?? "Endereço Completo"; ?></label>
                    <input type="text" name="INSTITUTION_ADDRESS" class="textEntry" style="width: 100%; padding: 8px;" value="<?php echo get_cfg('INSTITUTION_ADDRESS'); ?>">
                </div>
                <div style="grid-column: 1 / -1;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;"><?php echo $msgstr["museum_cfg_inst_logo"] ?? "URL da Logomarca (Ex: http://site.com/logo.png)"; ?></label>
                    <input type="text" name="LOGO_URL" class="textEntry" style="width: 100%; padding: 8px;" value="<?php echo get_cfg('LOGO_URL'); ?>">
                </div>
            </div>

            <!-- BLOCO 2: TERMOS LEGAIS PADRÃO -->
            <h3 style="color: #444; margin-bottom: 15px;"><i class="fas class fa-balance-scale"></i> <?php echo $msgstr["museum_cfg_terms_title"] ?? "Termos Legais Padrão"; ?></h3>
            <div style="margin-bottom: 30px;">
                <div style="margin-bottom: 15px;">
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;"><?php echo $msgstr["museum_cfg_term_entry"] ?? "Termo de Responsabilidade - ENTRADA de Acervo"; ?></label>
                    <textarea name="DEFAULT_TERM_ENTRY" class="textEntry" style="width: 100%; padding: 8px; height: 100px; font-family: inherit;"><?php echo get_cfg('DEFAULT_TERM_ENTRY'); ?></textarea>
                </div>
                <div>
                    <label style="font-weight: bold; display: block; margin-bottom: 5px;"><?php echo $msgstr["museum_cfg_term_exit"] ?? "Termo de Responsabilidade - SAÍDA de Acervo"; ?></label>
                    <textarea name="DEFAULT_TERM_EXIT" class="textEntry" style="width: 100%; padding: 8px; height: 100px; font-family: inherit;"><?php echo get_cfg('DEFAULT_TERM_EXIT'); ?></textarea>
                </div>
            </div>

            <!-- BLOCO 3: AUDITORIA E SEGURANÇA -->
            <h3 style="color: #444; margin-bottom: 15px;"><i class="fas fa-shield-alt"></i> <?php echo $msgstr["museum_cfg_audit_title"] ?? "Auditoria e Segurança"; ?></h3>
            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #ddd; margin-bottom: 30px;">
                <div style="margin-bottom: 10px;">
                    <input type="hidden" name="STRICT_AUDIT" value="0">
                    <input type="checkbox" name="STRICT_AUDIT" id="STRICT_AUDIT" value="1" <?php echo get_cfg('STRICT_AUDIT') == '1' ? 'checked' : ''; ?>>
                    <label for="STRICT_AUDIT" style="font-weight: bold;"><?php echo $msgstr["museum_cfg_strict_audit"] ?? "Modo de Auditoria Estrita"; ?></label>
                    <p style="margin: 5px 0 0 25px; font-size: 0.9em; color: #666;"><?php echo $msgstr["museum_cfg_strict_desc"] ?? "Exige o preenchimento de uma Referência de Autorização sempre que um objeto mudar de localização física."; ?></p>
                </div>
            </div>

            <div style="text-align: right; border-top: 1px solid #eee; padding-top: 20px;">
                <button type="submit" name="save_museum_config" class="bt-green" style="padding: 10px 30px; font-size: 1.1em; border:none; cursor: pointer; border-radius: 4px;">
                    <i class="fas fa-save"></i> <?php echo $msgstr["museum_cfg_save_btn"] ?? "Salvar Configurações"; ?>
                </button>
            </div>

        </form>
    </div>
</div>

<?php include("{$central_path}common/footer.php"); ?>