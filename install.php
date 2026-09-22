<?php
if (!class_exists('PluginBridge')) {
    header("HTTP/1.1 403 Forbidden");
    die("Acesso direto proibido.");
}

$bridge = PluginBridge::getInstance();
$dbPath = $bridge->get('db_path');
$abcdPath = $bridge->get('abcd_path');

// Carregar variáveis globais para localizar o executável MX
require_once $abcdPath . '/config.php';
global $cgibin_path;

// Define o executável correto de acordo com o SO do servidor
$mx = (stristr(PHP_OS, 'WIN')) ? $cgibin_path . "mx.exe" : $cgibin_path . "mx";

$installDir = realpath(__DIR__ . '/install');
$basesToCopy = ['ric_cm', 'ric_msg'];
$errors = [];

// 1. Função de cópia seletiva (Ignora a pasta 'data' original, se existir)
function recurse_copy_except_data($src, $dst)
{
    $dir = opendir($src);
    @mkdir($dst, 0755, true);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if ($file === 'data' && is_dir($src . '/' . $file)) {
                continue; // Pula a pasta data legada
            }
            if (is_dir($src . '/' . $file)) {
                recurse_copy_except_data($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

// 2. Transfere as estruturas (def, pfts, opac)
foreach ($basesToCopy as $base) {
    $sourceBase = $installDir . '/' . $base;
    $targetBase = $dbPath . $base;
    if (is_dir($sourceBase)) {
        recurse_copy_except_data($sourceBase, $targetBase);
    }
}

// 3. Compilação Nativa da Base ric_cm (Vazia)
$ric_cm_data = $dbPath . 'ric_cm/data/';
@mkdir($ric_cm_data, 0777, true);
file_put_contents($ric_cm_data . 'control_number.cn', '0');
exec($mx . " seq=nul create=" . $ric_cm_data . "ric_cm -all now");

// 4. Compilação Nativa e Importação da Base ric_msg (Vocabulário)
$ric_msg_data = $dbPath . 'ric_msg/data/';
@mkdir($ric_msg_data, 0777, true);

$isoFile = $installDir . '/ric_msg/ric_msg.iso';
if (file_exists($isoFile)) {
    // Importa o ISO construindo a base na arquitetura do host
    exec($mx . " iso=" . $isoFile . " create=" . $ric_msg_data . "ric_msg -all now");

    // Atualiza o control_number.cn com o último MFN gerado
    exec($mx . " " . $ric_msg_data . "ric_msg \"pft=mfn/\" now", $output);
    $maxMfn = !empty($output) ? max(array_filter(array_map('intval', $output))) : 0;
    file_put_contents($ric_msg_data . 'control_number.cn', $maxMfn);
} else {
    $errors[] = "Aviso: Arquivo ric_msg.iso não encontrado. O vocabulário ICA não foi pré-carregado.";
    file_put_contents($ric_msg_data . 'control_number.cn', '0');
    exec($mx . " seq=nul create=" . $ric_msg_data . "ric_msg -all now");
}

// 5. Instalação dos Parâmetros e Atualização do bases.dat
$sourceParDir = $installDir . '/par';
$targetParDir = $dbPath . 'par';

if (is_dir($sourceParDir)) {
    $parFiles = glob($sourceParDir . '/*.par');
    foreach ($parFiles as $parFile) {
        $fileName = basename($parFile);
        copy($parFile, $targetParDir . '/' . $fileName);
    }
}

$basesDatPath = $dbPath . 'bases.dat';
$basesDatContent = file_get_contents($basesDatPath);
if (strpos($basesDatContent, 'ric_cm') === false) {
    $newEntry = "\nric_cm|Records in Contexts (RiC-CM)\nric_msg|RiC Vocabularies\n";
    file_put_contents($basesDatPath, $newEntry, FILE_APPEND | LOCK_EX);
}

if (empty($errors)) {
    echo "<div style='color:green; padding:15px;'>Bases compiladas nativamente e plugin RiC-CM instalado com sucesso!</div>";
} else {
    echo "<div style='color:orange; padding:15px;'>Instalação concluída com ressalvas:<br>" . implode("<br>", $errors) . "</div>";
}
