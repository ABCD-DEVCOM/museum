<?php

declare(strict_types=1);

namespace ABCD\Plugins\Museum\Reports;

use Mpdf\Mpdf;
use RuntimeException;

/**
 * Class ReceiptGenerator
 * Lê as configurações da instituição em museum.def e renderiza recibos legais em PDF.
 */
class ReceiptGenerator
{
    public static function generateReceiptPdf(string $receiptMfn): void
    {
        global $db_path, $xWxis, $wxisUrl, $postMethod, $cgibin_path, $meta_encoding, $server_url, $ABCD_scripts_path, $msgstr;

        if (!class_exists('\Mpdf\Mpdf')) {
            throw new RuntimeException("Missing mPDF library. Ensure Composer dependencies are installed in ABCD v4 (mpdf/mpdf).");
        }

        // 1. Carrega as Configurações da Instituição
        $configPath = rtrim($db_path, '/\\') . DIRECTORY_SEPARATOR . 'par' . DIRECTORY_SEPARATOR . 'museum.def';
        $museumConfig = file_exists($configPath) ? parse_ini_file($configPath) : [];

        $instName = $museumConfig['INSTITUTION_NAME'] ?? 'Official Museum';
        $instId   = $museumConfig['INSTITUTION_ID'] ?? '';
        $instAddr = $museumConfig['INSTITUTION_ADDRESS'] ?? '';
        $logoUrl  = $museumConfig['LOGO_URL'] ?? '';

        // 2. Busca os dados no CISIS
        $lang = $_SESSION['lang'] ?? 'pt';
        $pftPath = rtrim($db_path, '/\\') . "/spec_receipts/pfts/{$lang}/pdf_receipt_template.pft";
        $cipar   = rtrim($db_path, '/\\') . "/par/spec_receipts.par";

        if (!file_exists($pftPath)) {
            $pftPath = rtrim($db_path, '/\\') . "/spec_receipts/pfts/en/pdf_receipt_template.pft";
        }

        $IsisScript = $xWxis . "imprime.xis";
        $formato = urlencode("@" . $pftPath);
        $query = "&base=spec_receipts&cipar={$cipar}&Opcion=rango&Mfn={$receiptMfn}&to={$receiptMfn}&Formato={$formato}";

        $wxis_llamar_path = rtrim($ABCD_scripts_path, '/\\') . "/central/common/wxis_llamar.php";
        if (file_exists($wxis_llamar_path)) {
            include($wxis_llamar_path);
        } else {
            throw new RuntimeException("wxis_llamar.php not found at {$wxis_llamar_path}");
        }

        $contenido = array_filter($contenido, function ($linha) {
            return trim($linha) !== '';
        });

        if (empty($contenido) || (isset($err_wxis) && $err_wxis !== "")) {
            throw new RuntimeException("Falha ao ler o banco de dados. O CISIS não retornou dados para o MFN {$receiptMfn}.");
        }

        $rawHtmlContent = implode("\n", $contenido);

        // 3. Inicializa o mPDF
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 38,
            'margin_bottom' => 20,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        // 4. Constrói o Cabeçalho Dinâmico
        $headerHtml = '<table width="100%" style="border-bottom: 2px solid #1a365d; padding-bottom: 10px; font-family: sans-serif; font-size: 11px;"><tr>';

        if (!empty($logoUrl)) {
            $headerHtml .= '<td width="25%" style="text-align: left; vertical-align: middle;">';
            $headerHtml .= '<img src="' . htmlspecialchars($logoUrl) . '" style="max-height: 70px; max-width: 180px;" />';
            $headerHtml .= '</td><td width="75%" style="text-align: right; vertical-align: middle;">';
        } else {
            $headerHtml .= '<td width="100%" style="text-align: right; vertical-align: middle;">';
        }

        $headerHtml .= '<strong style="font-size: 16px; color: #1a365d;">' . htmlspecialchars($instName) . '</strong><br>';
        if (!empty($instId))   $headerHtml .= '<strong>ID/Reg:</strong> ' . htmlspecialchars($instId) . '<br>';
        if (!empty($instAddr)) $headerHtml .= htmlspecialchars($instAddr);
        $headerHtml .= '</td></tr></table>';

        $mpdf->SetHTMLHeader($headerHtml);

        // 5. Tradução Blindada do Rodapé
        $footerText = 'Página {PAGENO} de {nbpg}'; // Padrão PT e ES
        if ($lang === 'en') {
            $footerText = 'Page {PAGENO} of {nbpg}';
        }
        // Tenta sobrescrever com a chave do dicionário global, se ela existir
        if (!empty($msgstr['museum_pdf_page'])) {
            $footerText = $msgstr['museum_pdf_page'];
        }

        // Rodapé com as cores oficiais do ABCD (Fundo Azul Escuro, Texto Branco)
        $mpdf->SetHTMLFooter('<div style="text-align: center; font-size: 10px; font-family: sans-serif; color: #1a365d;; padding: 8px 0;">' . htmlspecialchars($footerText) . '</div>');

        // 6. Injeta o CSS oficial que acabamos de criar
        $cssPath = rtrim($ABCD_scripts_path, '/\\') . '/content/plugins/museum/assets/css/print_receipt.css';
        if (file_exists($cssPath)) {
            $mpdf->WriteHTML(file_get_contents($cssPath), \Mpdf\HTMLParserMode::HEADER_CSS);
        }

        $mpdf->WriteHTML($rawHtmlContent, \Mpdf\HTMLParserMode::HTML_BODY);
        $mpdf->Output("Museum_Receipt_MFN{$receiptMfn}.pdf", \Mpdf\Output\Destination::INLINE);
        exit;
    }
}
