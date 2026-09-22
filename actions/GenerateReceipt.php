<?php
// File: content/plugins/museum/Actions/GenerateReceipt.php
declare(strict_types=1);

namespace ABCD\Plugins\Museum\Actions;

// Assuming mPDF is available via Composer autoload in ABCD v4
use Mpdf\Mpdf;

class GenerateReceipt
{
    public static function createPdf(string $receiptMfn): void
    {
        // 1. Fetch HTML from WXIS using a specific PFT
        // (Pseudo-code calling ABCD's WXIS wrapper)
        $rawHtml = \WxisWrapper::getRecordFormatted('spec_receipts', $receiptMfn, 'receipt_pdf.pft');

        // 2. Initialize mPDF
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
        ]);

        // 3. Optional: Add a standard header/footer
        $mpdf->SetHTMLHeader('<div style="text-align: right; border-bottom: 1px solid #000; padding-bottom: 5px;"><strong>Official Museum Receipt</strong></div>');
        $mpdf->SetHTMLFooter('<div style="text-align: center; font-size: 10px;">Page {PAGENO} of {nbpg}</div>');

        // 4. Inject CSS specifically designed for PDFs
        $css = file_get_contents(ABCD_CONTENT_PATH . 'plugins/museum/assets/pdf_styles.css');
        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

        // 5. Write the content
        $mpdf->WriteHTML($rawHtml, \Mpdf\HTMLParserMode::HTML_BODY);

        // 6. Output to browser (I = Inline in browser, D = Download)
        $mpdf->Output('Receipt_' . date('Ymd') . '.pdf', \Mpdf\Output\Destination::INLINE);
        exit;
    }
}