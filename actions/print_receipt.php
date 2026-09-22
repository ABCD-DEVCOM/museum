<?php
/*
 * Script: Museum Receipt Print Endpoint
 * Description: Valida permissões e engatilha a geração do PDF via mPDF.
 */

declare(strict_types=1);

session_start();
$central_path = "../../../../central/";

// 1. Proteção de Acesso
if (!isset($_SESSION["permiso"])) {
    header("Location: {$central_path}common/error_page.php");
    die;
}

require_once("{$central_path}config_inc_check.php");
require_once("{$central_path}config.php");
include("{$central_path}common/get_post.php");

// 2. Carregar Autoload do Composer (Requisito para o mPDF no ABCD v4)
$autoloadPath = rtrim($ABCD_scripts_path, '/\\') . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// 3. Importar a classe geradora do plugin
require_once '../src/Reports/ReceiptGenerator.php';

// 4. Capturar e validar o MFN
$mfn = $_REQUEST['Mfn'] ?? $_REQUEST['mfn'] ?? '';

if (empty($mfn) || !is_numeric($mfn)) {
    die("<div style='color:#d9534f; font-family:sans-serif; padding:20px; font-weight:bold;'>Erro: MFN inválido ou ausente. O sistema não pode localizar o recibo.</div>");
}

// 5. Acionar a renderização
try {
    \ABCD\Plugins\Museum\Reports\ReceiptGenerator::generateReceiptPdf($mfn);
} catch (Exception $e) {
    echo "<div style='font-family:sans-serif; padding:20px;'>";
    echo "<h3 style='color:#d9534f;'>Falha na Geração do PDF</h3>";
    echo "<strong>Motivo:</strong> " . htmlspecialchars($e->getMessage()) . "<br><br>";
    echo "<a href='javascript:history.back()' style='padding:5px 15px; background-color:#0056b3; color:white; text-decoration:none; border-radius:4px;'>Voltar</a>";
    echo "</div>";
}
