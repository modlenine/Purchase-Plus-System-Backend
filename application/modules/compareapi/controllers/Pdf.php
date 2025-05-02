<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once 'TCPDF/tcpdf.php';

class Pdf extends MX_Controller
{

    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set("Asia/Bangkok");
        // $this->load->model("email_model" , "email");
    }

    public function send_compare_preview()
    {
        // HTML content
        $vendors = json_decode($this->input->post("vendor"), true);
        $items   = json_decode($this->input->post("items"), true);

        $selectedVendorIndex   = $this->input->post("selectedVendorIndex");
        $vendorSelectionReason = $this->input->post("vendorSelectionReason");
        $dataareaid            = $this->input->post("dataareaid");
        $accountnum            = $this->input->post("accountnum");
        $user_create           = $this->input->post("user_create");
        $datetime_create       = $this->input->post("datetime_create");
        $dept_create           = $this->input->post("dept_create");
        $ecode_create          = $this->input->post("ecode_create");
        $compare_formno        = $this->input->post("compare_formno");
        $compare_status        = $this->input->post("compare_status");
        $approvalStatus        = $this->input->post("approvalStatus");
        $approvalMemo          = $this->input->post("approvalMemo");
        $user_approval         = $this->input->post("user_approval");
        $ecode_approval        = $this->input->post("ecode_approval");
        $datetime_approval     = $this->input->post("datetime_approval");
        $vendorCount           = $this->input->post("vendorCount");

        $current_page = 0;
        $total_pages  = 0;

        $this->selectedVendorIndex   = $selectedVendorIndex;
        $this->vendorSelectionReason = $vendorSelectionReason;
        $this->dataareaid            = $dataareaid;
        $this->accountnum            = $accountnum;
        $this->user_create           = $user_create;
        $this->datetime_create       = $datetime_create;
        $this->dept_create           = $dept_create;
        $this->ecode_create          = $ecode_create;
        $this->compare_formno        = $compare_formno;
        $this->compare_status        = $compare_status;
        $this->approvalStatus        = $approvalStatus;
        $this->approvalMemo          = $approvalMemo;
        $this->user_approval         = $user_approval;
        $this->ecode_approval        = $ecode_approval;
        $this->datetime_approval     = $datetime_approval;
        $this->vendorCount           = $vendorCount;
        $this->current_page          = $current_page;
        $this->total_pages           = $total_pages;
        $this->vendors               = $vendors;
        $this->items                 = $items;

        // Set footer data
        // create new PDF document
        $pdf = new MYPDF(
            $this->selectedVendorIndex,
            $this->vendorSelectionReason,
            $this->dataareaid,
            $this->accountnum,
            $this->user_create,
            $this->datetime_create,
            $this->dept_create,
            $this->ecode_create,
            $this->compare_formno,
            $this->compare_status,
            $this->approvalStatus,
            $this->approvalMemo,

            $this->user_approval,
            $this->ecode_approval,
            $this->datetime_approval,
            $this->vendorCount,

            $this->current_page,
            $this->total_pages,
            $this->vendors,
            $this->items,
        );

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Your Name');
        $pdf->SetTitle('Purchase Order');
        $pdf->SetSubject('TCPDF Tutorial');
        $pdf->SetKeywords('TCPDF, PDF, example, test, guide');

        // set default header data
        // $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Purchase Order', 'Generated using TCPDF');
        $pdf->setPrintHeader(true);

        // set header and footer fonts
        $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
        $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(5, 15, 5);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);

        // set auto page breaks
        $pdf->SetAutoPageBreak(true, 50);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        // add a page
        $page = 1;
        $pdf->AddPage();
        $current_page = 1;

        $pdf->SetCellPadding(0);           // กำหนดการเพิ่มพื้นที่ระหว่างเซลล์เป็น 0
        $pdf->SetCellMargins(0, 60, 0, 0); // กำหนดการเพิ่มขอบรอบของเซลล์เป็น 0
                                           // Set font
        $pdf->SetFont('thsarabunb', '', 12);
        // $pdf->MultiCell(200, 90,'', 1, 'L', 0, 0, '' ,'', true);
        $pageHeight = $pdf->getPageHeight();
        $footerHeight = 120;
        $usableHeight = $pageHeight - $pdf->getMargins()['top'] - $footerHeight;

        $pdf->SetFont('thsarabunb', '', 12);

        // เตรียมพื้นที่วาดตาราง
        $pageWidth    = $pdf->getPageWidth();
        $leftMargin   = $pdf->getMargins()['left'];
        $rightMargin  = $pdf->getMargins()['right'];
        $contentWidth = $pageWidth - $leftMargin - $rightMargin;

        // กำหนดจำนวน Vendor
        $vendorCount = count($vendors);

                                                                              // กำหนดความกว้างของช่อง
        $productColWidth = 60;                                                // ช่องชื่อสินค้า
        $vendorColWidth  = ($contentWidth - $productColWidth) / $vendorCount; // คำนวณกว้างของ Vendor ให้พอดี

        // เตรียมตัวแปรรวมราคา
        $totalPricesPerVendor = array_fill(0, $vendorCount, 0);


        // ========================
        // วาดหัวตาราง
        // ========================
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell($productColWidth, 10, 'ชื่อสินค้า', 1, 0, 'C', 1);

        foreach ($vendors as $vendor) {
            $vendorName = isset($vendor['vendor_name']) ? $vendor['vendor_name'] : '-';
            $pdf->Cell($vendorColWidth, 10, $vendorName, 1, 0, 'C', 1);
        }
        $pdf->Ln();

        // ========================
        // วาดรายการสินค้า
        // ========================
        foreach ($items as $item) {
                        
            $pdf->SetFont('thsarabunb', '', 12);

            if (! empty($item['itemid']) || ! empty($item['itemname'])) {
                $productName = $item['itemid'] . ' / ' . $item['itemname'] . "\n(" . $item['itemdetail'] . ')';
            } else {
                $productName = $item['itemdetail'];
            }

            $productCellHeight = $pdf->getStringHeight($productColWidth, $productName) + 2;

            $currentY = $pdf->GetY();
            if (($currentY + $productCellHeight) > ($pageHeight - $footerHeight)) {
                $pdf->AddPage();
    
                // วาดหัวตารางใหม่บนหน้าถัดไป
                $pdf->SetFont('thsarabunb', '', 12);
                $pdf->SetFillColor(230, 230, 230);
                $pdf->Cell($productColWidth, 10, 'ชื่อสินค้า', 1, 0, 'C', 1);
    
                foreach ($vendors as $vendor) {
                    $vendorName = isset($vendor['vendor_name']) ? $vendor['vendor_name'] : '-';
                    $pdf->Cell($vendorColWidth, 10, $vendorName, 1, 0, 'C', 1);
                }
                $pdf->Ln();
            }
            $pdf->setCellPaddings(2, 0, 0, 0); // เว้น padding ซ้าย 2 mm
            $pdf->MultiCell($productColWidth, $productCellHeight, $productName, 1, 'L', false, 0, '', '', true, 0, false, true, $productCellHeight, 'M');

            foreach ($item['prices'] as $j => $price) {
                if (isset($item['no_quoted'][$j]) && $item['no_quoted'][$j]) {
                    $priceText = 'ไม่ได้เสนอราคา';
                } else {
                    $priceText = number_format(floatval($price), 2);
                    $totalPricesPerVendor[$j] += floatval($price);
                }

                $pdf->MultiCell($vendorColWidth, $productCellHeight, $priceText, 1, 'C', false, 0, '', '', true, 0, false, true, $productCellHeight, 'M');
            }
            $pdf->Ln();
        }

        // ========================
        // วาด Footer รวมราคาต่อ Vendor
        // ========================
        $pdf->SetFont('thsarabunb', 'B', 12);
        $pdf->Cell($productColWidth, 10, 'รวม', 1, 0, 'C', 1);

        foreach ($totalPricesPerVendor as $total) {
            $totalText = number_format(floatval($total), 2);
            $pdf->Cell($vendorColWidth, 10, $totalText, 1, 0, 'C', 1);
        }
        $pdf->Ln();
                                                        // Close and output PDF document
        $pdfOutput = $pdf->Output('Document.pdf', 'S'); // Output as string S = Save PDF , I = Preview

        // Save PDF to file
        // $filePath = 'uploads/Document-'.$purchid.'.pdf';
        // file_put_contents($filePath, $pdfOutput);

        echo base64_encode($pdfOutput);
    }

}

class MYPDF extends TCPDF
{
    // Properties to store data
    // ตั้งค่าขนาดความกว้างของคอลัมน์
    public $selectedVendorIndex;
    public $vendorSelectionReason;
    public $dataareaid;
    public $accountnum;
    public $user_create;
    public $datetime_create;
    public $dept_create;
    public $ecode_create;
    public $compare_formno;
    public $compare_status;
    public $approvalStatus;
    public $approvalMemo;
    public $user_approval;
    public $ecode_approval;
    public $datetime_approval;
    public $vendorCount;
    public $current_page;
    public $total_pages;
    public $vendors;
    public $items;

    // Constructor to initialize properties
    public function __construct($selectedVendorIndex, $vendorSelectionReason, $dataareaid, $accountnum, $user_create, $datetime_create, $dept_create, $ecode_create, $compare_formno, $compare_status, $approvalStatus, $approvalMemo, $user_approval, $ecode_approval, $datetime_approval, $vendorCount, $current_page, $total_pages, $vendors, $items
    ) {
        parent::__construct();
        $this->selectedVendorIndex   = $selectedVendorIndex;
        $this->vendorSelectionReason = $vendorSelectionReason;
        $this->dataareaid            = $dataareaid;
        $this->accountnum            = $accountnum;
        $this->user_create           = $user_create;
        $this->datetime_create       = $datetime_create;
        $this->dept_create           = $dept_create;
        $this->ecode_create          = $ecode_create;
        $this->compare_formno        = $compare_formno; // <--- สำคัญ!!
        $this->compare_status        = $compare_status;
        $this->approvalStatus        = $approvalStatus;
        $this->approvalMemo          = $approvalMemo;
        $this->user_approval         = $user_approval;
        $this->ecode_approval        = $ecode_approval;
        $this->datetime_approval     = $datetime_approval;
        $this->vendorCount           = $vendorCount;
        $this->current_page          = $current_page;
        $this->total_pages           = $total_pages;
        $this->vendors               = $vendors;
        $this->items                 = $items;
    }

    // Page footer
    public function Footer()
    {
        // Position at 15 mm from bottom
        $this->SetY(-60);

        // set cell padding
        $this->setCellPaddings(1, 1, 1, 1);
        // set cell margins
        $this->setCellMargins(1, 0, 1, 0);

        // Set font
        $this->SetFont('thsarabunb', '', 8);
        //section Footer


        // ดึงตำแหน่ง Y ปัจจุบัน
        $currentY = $this->getY();
        // วาดเส้นใต้ข้อความให้ยาวทั้งหน้า
        $pageWidth = $this->getPageWidth() - $this->getMargins()['left'] - $this->getMargins()['right'];
        $this->Line($this->getMargins()['left'], $currentY + 1, $pageWidth + $this->getMargins()['left'], $currentY + 1);
        // กำหนดระยะห่างระหว่างข้อความและเส้น (Ln)
        $this->Ln(4);
        //section Footer 2

// ตั้งค่าฟอนต์
$this->SetFont('thsarabunb', '', 14);

// กำหนดความกว้างของแต่ละคอลัมน์
$columnWidth = 66; // (ประมาณ 200mm / 3)

$this->SetFillColor(255, 255, 255); // สีพื้นหลังขาว

// พิมพ์หัวคอลัมน์
$this->Cell($columnWidth, 8, 'ผู้ร้องขอ', 0, 0, 'C', true);
$this->Cell($columnWidth, 8, 'เจ้าหน้าที่จัดหา', 0, 0, 'C', true);
$this->Cell($columnWidth, 8, 'ผู้อนุมัติ', 0, 1, 'C', true); // 1 = ขึ้นบรรทัดใหม่

// ดึงข้อมูล
$userRequester  = !empty($this->user_create) ? $this->user_create : "-";
$userPurchaser  = !empty($this->user_create) ? $this->user_create : "-";
$userApprover   = (!empty($this->user_approval) && $this->user_approval !== "null") ? $this->user_approval : "";

// วันที่
$dateRequester = !empty($this->datetime_create) ? $this->datetime_create : "-";
$datePurchaser = !empty($this->datetime_create) ? $this->datetime_create : "-";
$dateApprover  = (!empty($this->datetime_approval) && $this->datetime_approval !== "null") ? $this->datetime_approval : "";

// พิมพ์บรรทัดข้อมูลชื่อ
$this->Cell($columnWidth, 8, $userRequester, 0, 0, 'C', true);
$this->Cell($columnWidth, 8, $userPurchaser, 0, 0, 'C', true);
$this->Cell($columnWidth, 8, $userApprover, 0, 1, 'C', true);

// พิมพ์บรรทัดข้อมูลวันที่
$this->Cell($columnWidth, 8, "วันที่: " . $dateRequester, 0, 0, 'C', true);
$this->Cell($columnWidth, 8, "วันที่: " . $datePurchaser, 0, 0, 'C', true);
$this->Cell($columnWidth, 8, "วันที่: " . $dateApprover, 0, 1, 'C', true);

// เว้นระยะ
$this->Ln(2);


        // ดึงตำแหน่ง Y ปัจจุบัน
        $currentY = $this->getY();
        // วาดเส้นใต้ข้อความให้ยาวทั้งหน้า
        $pageWidth = $this->getPageWidth() - $this->getMargins()['left'] - $this->getMargins()['right'];
        $this->Line($this->getMargins()['left'], $currentY + 1, $pageWidth + $this->getMargins()['left'], $currentY + 1);
        // กำหนดระยะห่างระหว่างข้อความและเส้น (Ln)
        // กำหนดระยะห่างระหว่างข้อความและเส้น (Ln)
        $this->Ln(1);
        //section Footer 6

                    // ขยับลงมา 15 mm จากล่าง
    $this->SetY(-15);
    $this->SetFont('thsarabunb', '', 12);

    // พิมพ์เลขหน้า Page X / Y
    $pageNumTxt = 'Page ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages();
    $this->Cell(0, 10, $pageNumTxt, 0, 0, 'R');
    }

    public function Header()
    {
        // Position at 15 mm from bottom
        $this->SetY(5);
        // Set font

        // Section1
        // Vertical alignment
        $this->SetFont('thsarabunb', '', 18);
        $this->MultiCell(198, 5, 'รายการ Compare Vendor', 0, 'C', 0, 1, '', '', true);
        $this->SetFont('thsarabunb', '', 14);
        $this->MultiCell(198, 5, 'เอกสารเลขที่ : ' . $this->compare_formno, 0, 'C', 0, 1, '', '', true);
        $this->Ln(1);

        // 2. วาด Compare Status (อยู่ขวาแต่ระดับเดียวกัน)
        $this->SetFont('thsarabunb', '', 12);
        // กำหนด X ไปทางขวา (เช่น 140 หรือ 150 ขึ้นอยู่กับว่าหน้ากระดาษกว้างเท่าไหร่)
        // หรือเอาง่ายๆ ดูว่าหน้ากระดาษกว้างเท่าไหร่แล้วลดมาซัก 50-60 mm
        $pageWidth = $this->getPageWidth();
        $margins   = $this->getMargins();
        $xRight    = $pageWidth - $margins['right'] - 60; // เว้น 60mm จากขอบขวา
                                                          // กำหนดตำแหน่ง X,Y ใหม่
        $this->SetXY($xRight, 5);                         // ใช้ Y = 5 เท่ากันกับ 'รายการ Compare Vendor'
                                                          // วาด Compare Status
        $this->MultiCell(60, 5, 'Status: ' . $this->compare_status, 0, 'R', 0, 1, '', '', true);

        // วาดเส้นใต้ข้อความให้ยาวทั้งหน้า
        $y         = 12;
        $pageWidth = $this->getPageWidth() - $this->getMargins()['left'] - $this->getMargins()['right'];
        $this->Line($this->getMargins()['left'], $y + 10, $pageWidth + $this->getMargins()['left'], $y + 10);
        $this->Ln(6);
        // Section1

        // Section 2
        $this->SetFont('thsarabunb', '', 14);
        // ตั้งค่าจุดเริ่มต้น
        $x = 10;
        $y = 26; // จุด Y ปัจจุบัน หลังจาก Section 1
        $this->SetXY($x, $y);
        $dataareaidEN = getCompanynameEN($this->dataareaid);
        // ฝั่งซ้าย (Vendor Info)
        $leftText = "สังกัดบริษัท : " . $dataareaidEN;
        $this->MultiCell(100, 15, $leftText, 0, 'L', 0, 0, '', '', true); // 100 คือกว้างครึ่งหนึ่ง
                                                                          // ฝั่งขวา (PO Info)
        $rightText = "";
        $this->MultiCell(90, 15, $rightText, 0, 'L', 0, 1, '', '', true); // อีก 90 (ให้เต็มกระดาษ 198 mm)
        $this->Ln(1);                                                     // ขยับลงอีกนิดหน่อยก่อนขึ้น Section ถัดไป

        // Section 3
        $this->SetFont('thsarabunb', '', 14);
        // ตั้งค่าจุดเริ่มต้น
        $x = 10;
        $y = $this->GetY() - 8; // จุด Y ปัจจุบันหลัง Section 2
        $this->SetXY($x, $y);
                                      // เตรียมข้อมูล vendor
        $vendorList = $this->vendors; // <-- สำคัญ: decode ก่อนใช้
                                      // เขียนหัวข้อ
        $this->MultiCell(198, 8, "รายชื่อผู้จำหน่าย (Vendors)", 0, 'L', 0, 1, '', '', true);
        $this->Ln(1); // เว้นระยะ
                      // วนลูปรายชื่อ vendors
        if (! empty($vendorList)) {
            foreach ($vendorList as $index => $vendor) {
                $vendorName    = isset($vendor['vendor_name']) ? $vendor['vendor_name'] : '-';
                $vendorDisplay = ($index + 1) . ". " . $vendorName;

                // เช็กว่า เริ่มแถวใหม่ไหม
                if ($index % 2 == 0) {
                    // ถ้า index เป็นเลขคู่ (0,2,4...) ➔ เริ่มแถวใหม่ ➔ SetX ก่อน
                    $this->SetX(15);
                }

                // พิมพ์ Cell
                $this->Cell(84, 8, $vendorDisplay, 0, ($index % 2 == 1) ? 1 : 0, 'L', false, '', 1, '', 'T', true);
                // 84 = กว้าง (เหลือพื้นที่หลัง SetX(15) ประมาณ 168 mm ถ้าแบ่งครึ่ง)
            }
        } else {
            $this->SetX(15);
            $this->MultiCell(168, 8, "ไม่พบข้อมูลผู้จำหน่าย", 0, 'L', 0, 1, '', '', true);
        }
        $y         = $this->GetY() - 8;
        $pageWidth = $this->getPageWidth() - $this->getMargins()['left'] - $this->getMargins()['right'];
        $this->Line($this->getMargins()['left'], $y + 10, $pageWidth + $this->getMargins()['left'], $y + 10);
        $this->Ln(4);

        $this->SetFont('thsarabunb', 'B', 16);
        $this->MultiCell(198, 5, 'รายการสินค้า', 0, 'C', 0, 1, '', '', true);

    }


}

/* End of file Controllername.php */
