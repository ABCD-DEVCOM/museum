<?php

declare(strict_types=1);

namespace ABCD\Plugins\Museum\Reports;

use Mpdf\Mpdf;
use RuntimeException;

/**
 * Class ReceiptGenerator
 * Fetches raw HTML formatted by CISIS PFTs and renders legal PDF receipts.
 */
class ReceiptGenerator
{
    /**
     * Generates and streams a formal PDF receipt to the browser.
     * @param string $receiptMfn The MFN of the receipt in spec_receipts
     */
    public static function generateReceiptPdf(string $receiptMfn): void
    {
        global $db_path, $xWxis, $Wxis, $wxisUrl, $postMethod, $cgibin_path, $meta_encoding, $server_url, $ABCD_scripts_path;

        if (!class_exists('\Mpdf\Mpdf')) {
            throw new RuntimeException("Missing mPDF library. Ensure Composer dependencies are installed in ABCD v4 (mpdf/mpdf).");
        }

        // Language Persistence
        $lang = $_SESSION['lang'] ?? 'pt'; // Fallback to PT if session is lost

        $pftPath = $db_path . "spec_receipts/pfts/{$lang}/pdf_receipt_template.pft";
        $cipar = $db_path . "par/spec_receipts.par";

        // Fallback to English PFT if translation template is missing
        if (!file_exists($pftPath)) {
            $pftPath = $db_path . "spec_receipts/pfts/en/pdf_receipt_template.pft";
        }

        // Using Opcion=leer to fetch a direct MFN securely
        $IsisScript = $xWxis . "buscar.xis";
        $query = "&base=spec_receipts&cipar={$cipar}&Opcion=leer&Mfn={$receiptMfn}&Formato={$pftPath}";

        $wxis_llamar_path = rtrim($ABCD_scripts_path, '/\\') . "/central/common/wxis_llamar.php";
        if (file_exists($wxis_llamar_path)) {
            include($wxis_llamar_path);
        } else {
            throw new RuntimeException("wxis_llamar.php not found at {$wxis_llamar_path}");
        }

        if (empty($contenido) || (isset($err_wxis) && $err_wxis !== "")) {
            throw new RuntimeException("CISIS Data Error: " . ($err_wxis ?? 'No data returned.'));
        }

        $rawHtmlContent = implode("\n", $contenido);

        // Initialize mPDF
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 20,
            'margin_bottom' => 20,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        $mpdf->SetHTMLHeader('<div style="text-align: right; border-bottom: 1px solid #000; padding-bottom: 5px; font-family: sans-serif;"><strong>Museum Receipt</strong></div>');
        $mpdf->SetHTMLFooter('<div style="text-align: center; font-size: 10px; font-family: sans-serif;">Page {PAGENO} of {nbpg}</div>');

        // Inject specific print CSS (if available)
        $cssPath = rtrim($ABCD_scripts_path, '/\\') . '/content/plugins/museum/assets/css/print_receipt.css';
        if (file_exists($cssPath)) {
            $mpdf->WriteHTML(file_get_contents($cssPath), \Mpdf\HTMLParserMode::HEADER_CSS);
        }

        $mpdf->WriteHTML($rawHtmlContent, \Mpdf\HTMLParserMode::HTML_BODY);
        $mpdf->Output("Receipt_MFN{$receiptMfn}.pdf", \Mpdf\Output\Destination::INLINE);
        exit;
    }
}
