<?php
/*
 * Script: Museum Module Dashboard
 * Author: ABCD Community
 * Requires: PHP 8.1+
 */

session_start();
$central_path = "../../../central/";

if (!isset($_SESSION["permiso"])) {
    header("Location: {$central_path}common/error_page.php");
    die;
}

require_once("{$central_path}config_inc_check.php");
require_once("{$central_path}config.php");
include("{$central_path}common/get_post.php");

$_SESSION["MODULO"] = "museum";

global $arrHttp, $msgstr, $db_path, $valortag, $lista_bases, $langManager, $ABCD_lang;

include("{$central_path}lang/dbadmin.php");
include("{$central_path}lang/admin.php");
include("{$central_path}lang/prestamo.php");
include("{$central_path}lang/acquisitions.php");
include("{$central_path}lang/lang.php");

// Correção Crítica: loadPluginTranslations aponta para a pasta do plugin
if (isset($langManager)) {
    $userLang = $_SESSION["lang"] ?? 'en';
    $plugin_lang = $langManager->loadPluginTranslations(__DIR__, 'museum', 'museum.tab', $userLang);
    if (is_array($msgstr) && is_array($plugin_lang)) {
        $msgstr = array_merge($msgstr, $plugin_lang);
    }
}

$mst_file = rtrim($db_path, '/\\') . DIRECTORY_SEPARATOR . 'spec_receipts' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'spec_receipts.mst';

if (!file_exists($mst_file)) {
    include("{$central_path}common/header.php");
?>
    <div style="max-width: 600px; margin: 50px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); font-family: 'Segoe UI', sans-serif;">
        <div style="text-align: center; color: #2c3e50;">
            <i class="fas fa-magic" style="font-size: 3em; color: #007bff; margin-bottom: 15px;"></i>
            <h2><?php echo $msgstr["museum_wizard_title"] ?? "Bem-vindo ao Módulo de Museu"; ?></h2>
            <p style="color: #666; font-size: 1.1em; margin-bottom: 25px;">
                <?php echo $msgstr["museum_wizard_desc"] ?? "Detectamos que as bases de dados auxiliares (Spectrum Receipts, Movements e Loans) ainda não foram inicializadas ou estão incompletas."; ?>
            </p>
        </div>

        <div style="background: #f8f9fa; padding: 15px; border-left: 4px solid #ffc107; margin-bottom: 25px;">
            <strong><?php echo $msgstr["museum_wizard_warn"] ?? "Atenção:"; ?></strong>
            <?php echo $msgstr["museum_wizard_warn_desc"] ?? "Esta operação criará as estruturas FDT, PFT, FST e inicializará os arquivos mestre no diretório do ABCD."; ?>
        </div>

        <div style="text-align: center;">
            <a href="install.php" style="display: inline-block; background: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-size: 1.1em; font-weight: bold;">
                <i class="fas fa-play-circle"></i> <?php echo $msgstr["museum_wizard_btn"] ?? "Executar Assistente de Instalação"; ?>
            </a>
        </div>
    </div>
<?php
    include("{$central_path}common/footer.php");
    die;
}

include("{$central_path}common/header.php");
include("{$central_path}common/institutional_info.php");

echo '<link rel="stylesheet" href="assets/css/museum.css" type="text/css">';
?>

<div class="sectionInfo">
    <div class="breadcrumb">
        <strong><?php echo $msgstr["inicio"] ?? "Home"; ?> - <?php echo $msgstr["museum_module"] ?? "Museum Management"; ?></strong>
    </div>
    <div class="actions">
        <?php include "{$central_path}common/inc_back.php"; ?>
    </div>
    <div class="spacer">&#160;</div>
</div>

<?php
$wiki_help = "Museum_Module";
include "{$central_path}common/inc_div-helper.php";
?>

<div class="middle homepage">

    <!-- BLOCK 1: CATALOG & INVENTORY (Base: spectrum) -->
    <div class="mainBox">
        <div class="boxContent catalogSection" style="background-color: var(--abcd-white);">
            <div class="sectionTitleIcons">
                <i class="fas fa-landmark" style="font-size: 2.5em; color: var(--abcd-steel-blue); margin-right: 15px;"></i>
                <h1><?php echo $msgstr["museum_inventory"] ?? "Object Inventory"; ?></h1>
            </div>
            <div class="sectionButtons">
                <a href="<?php echo $central_path; ?>dataentry/inicio_main.php?base=spectrum" class="menuButton-fa">
                    <i class="fas fa-edit"></i>
                    <span><strong><?php echo $msgstr["museum_dataentry"] ?? "Data Entry"; ?></strong></span>
                </a>
                <a href="browse.php?showdeleted=yes&encabezado=s&base=spectrum&cipar=spectrum.par&lang=<?php echo $userLang; ?>" class="menuButton-fa">
                    <i class="fas fa-search"></i>
                    <span><strong><?php echo $msgstr["museum_search"] ?? "Search Catalog"; ?></strong></span>
                </a>
            </div>
            <div class="spacer"></div>
        </div>
    </div>

    <!-- BLOCK 2: MUSEUM PROCEDURES (Auxiliary Bases) -->
    <div class="mainBox">
        <div class="boxContent loanSection" style="background-color: var(--abcd-white);">
            <div class="sectionTitleIcons">
                <i class="fas fa-exchange-alt" style="font-size: 2.5em; color: var(--abcd-steel-blue); margin-right: 15px;"></i>
                <h1><?php echo $msgstr["museum_procedures"] ?? "Museum Procedures"; ?></h1>
            </div>
            <div class="sectionButtons">
                <a href="browse.php?base=spec_receipts&modulo=museum&lang=<?php echo $userLang; ?>" class="menuButton-fa">
                    <i class="fas fa-file-signature"></i>
                    <span><strong><?php echo $msgstr["museum_entry_exit"] ?? "Entry & Exit Receipts"; ?></strong></span>
                </a>

                <a href="browse.php?base=spec_movements&modulo=museum&lang=<?php echo $userLang; ?>" class="menuButton-fa">
                    <i class="fas fa-route"></i>
                    <span><strong><?php echo $msgstr["museum_location_control"] ?? "Location Audit Trail"; ?></strong></span>
                </a>

                <a href="browse.php?base=spec_loans&modulo=museum&lang=<?php echo $userLang; ?>" class="menuButton-fa">
                    <i class="fas fa-handshake"></i>
                    <span><strong><?php echo $msgstr["museum_loans_acq"] ?? "Loans & Acquisitions"; ?></strong></span>
                </a>
            </div>
            <div class="spacer"></div>
        </div>
    </div>

    <!-- BLOCK 3: ADMINISTRATION & CONFIGURATION -->
    <?php if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) || isset($_SESSION["permiso"]["MUSEUM_ADMIN"]) || ($_SESSION["profile"] ?? '') === 'adm'): ?>
        <div class="mainBox">
            <div class="boxContent" style="background-color: var(--abcd-white);">
                <div class="sectionTitleIcons">
                    <i class="fas fa-cog" style="font-size: 2.5em; color: var(--abcd-steel-blue); margin-right: 15px;"></i>
                    <h1><?php echo $msgstr["museum_admin"] ?? "Administration"; ?></h1>
                </div>
                <div class="sectionButtons">
                    <a href="admin/configure.php" class="menuButton-fa">
                        <i class="fas fa-tools"></i>
                        <span><strong><?php echo $msgstr["museum_configure"] ?? "Configure Module"; ?></strong></span>
                    </a>
                    <a href="<?php echo $central_path; ?>statistics/tables_generate.php?base=spectrum&encabezado=s" class="menuButton-fa">
                        <i class="fas fa-chart-bar"></i>
                        <span><strong><?php echo $msgstr["museum_statistics"] ?? "Statistics"; ?></strong></span>
                    </a>
                </div>
                <div class="spacer"></div>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php include("{$central_path}common/footer.php"); ?>