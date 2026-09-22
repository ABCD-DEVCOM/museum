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
     */
    public static function generateReceiptPdf(string $receiptMfn): void
    {
        global $db_path, $Wxis, $xWxis, $wxisUrl, $postMethod, $cgibin_path, $meta_encoding;

        // Ensure mPDF is loaded via Composer
        if (!class_exists('\Mpdf\Mpdf')) {
            throw new RuntimeException("Missing mPDF library. Run 'composer require mpdf/mpdf'.");
        }

        // 1. Configure the WXIS call to format the specific record using the PDF template
        $lang = $_SESSION['lang'] ?? 'en';
        $pftPath = $db_path . "spec_receipts/pfts/{$lang}/pdf_receipt_template.pft";
        $cipar = $db_path . "par/spec_receipts.par";

        if (!file_exists($pftPath)) {
            throw new RuntimeException("Template missing: {$pftPath}");
        }

        $IsisScript = $xWxis . "buscar.xis";
        $query = "&base=spec_receipts&cipar={$cipar}&Opcion=buscar&Expresion=mfn={$receiptMfn}&Formato={$pftPath}&count=1";

        // 2. Fetch the structured HTML from CISIS
        include(ABCD_CENTRAL_PATH . "common/wxis_llamar.php");

        if (empty($contenido) || (isset($err_wxis) && $err_wxis !== "")) {
            throw new RuntimeException("CISIS Error: " . ($err_wxis ?? 'No data found.'));
        }

        $rawHtmlContent = implode("\n", $contenido);

        // 3. Initialize mPDF
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 20,
            'margin_bottom' => 20
        ]);

        // 4. Inject specific print CSS (if available)
        $cssPath = ABCD_CONTENT_PATH . 'plugins/museum/assets/css/print_receipt.css';
        if (file_exists($cssPath)) {
            $mpdf->WriteHTML(file_get_contents($cssPath), \Mpdf\HTMLParserMode::HEADER_CSS);
        }

        // 5. Render and output
        $mpdf->WriteHTML($rawHtmlContent, \Mpdf\HTMLParserMode::HTML_BODY);

        // Destination::INLINE displays the PDF directly in the browser tab
        $mpdf->Output("Museum_Receipt_MFN{$receiptMfn}.pdf", \Mpdf\Output\Destination::INLINE);
        exit;
    }
}
