<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('TCPDF/tcpdf.php');

class Pdf extends MX_Controller {
    
    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set("Asia/Bangkok");
        $this->load->model("email_model" , "email");
    }
    

    public function send_po()
    {
        // HTML content
        $main = json_decode($this->input->post("main") , true);
        $details = json_decode($this->input->post("details") , true);
        $executive = json_decode($this->input->post("executive") , true);
        $taxid = $this->input->post("taxid");
        $userrequest = $this->input->post("userrequest");
        $datetimereq = $this->input->post("datetimereq");
        $userMgr = $this->input->post("userpostMgr");
        $dateMgr = $this->input->post("datetimepostMgr");
        $memo_pur = $this->input->post("memo_pur");
        $poemail = $this->input->post("poemail");
        $dataareaid = $this->input->post("dataareaid");

        $userSend = $this->input->post("userSend");
        $ecodeSend = $this->input->post("ecodeSend");

        $amount = $main['amount'];
        $bpc_documentdate = $main['bpc_documentdate'];
        $bpc_purchasereqno = $main['bpc_purchasereqno'];
        $bpc_remark = $main['bpc_remark']; // ค่านี้อาจจะเป็น null หรือไม่มีก็ได้
        $currencycode = $main['currencycode'];
        $deliveryaddress = $main['deliveryaddress'];
        $deliverydate = $main['deliverydate'];
        $deliveryname = $main['deliveryname'];
        $description = $main['description'];
        $email = $main['email'];
        $num = $main['num'];
        $payment = $main['payment'];
        $purchaseorderid = $main['purchaseorderid'];
        $purchid = $main['purchid'];
        $purchorderdate = $main['purchorderdate'];
        $purchorderdocnum = $main['purchorderdocnum'];
        $salesorderbalance = $main['salesorderbalance'];
        $slc_amtintext = $main['slc_amtintext'];
        $sumlinedisc = $main['sumlinedisc'];
        $sumtax = $main['sumtax'];
        $vendaddress = $main['vendaddress'];
        $vendfax = $main['vendfax'];
        $vendid = $main['vendid'];
        $vendname = $main['vendname'];
        $vendphone = $main['vendphone'];

        $runningfile = $purchid."-".getRuningCode();
        $formno = $this->input->post("formno");

        $current_page = 0;
        $total_pages = 0;

        if(floatval($sumtax) != 0){
            $taxText = "7.0%";
        }else{
            $taxText = "0.0%";
        }

        $this->datetimereq = $datetimereq;
        $this->userrequest = $userrequest;
        $this->executive = $executive;
        $this->userMgr = $userMgr;
        $this->dateMgr = $dateMgr;
        $this->salesorderbalance = $salesorderbalance;
        $this->sumlinedisc = $sumlinedisc;
        $this->taxText = $taxText;
        $this->sumtax = $sumtax;
        $this->slc_amtintext = $slc_amtintext;
        $this->amount = $amount;
        $this->memo_pur = $memo_pur;

        $this->deliveryname = $deliveryname;
        $this->deliveryaddress = $deliveryaddress;
        $this->taxid = $taxid;
        $this->vendid = $vendid;
        $this->vendname = $vendname;
        $this->vendaddress = $vendaddress;
        $this->vendphone = $vendphone;
        $this->vendfax = $vendfax;
        $this->purchid = $purchid;
        $this->bpc_documentdate = $bpc_documentdate;
        $this->payment = $payment;
        $this->deliverydate = $deliverydate;
        $this->description = $description;
        $this->bpc_purchasereqno = $bpc_purchasereqno;

        $this->current_page = $current_page;
        $this->total_pages = $total_pages;


        // Set footer data
        // create new PDF document
        $pdf = new MYPDF(
            $this->datetimereq , 
            $this->userrequest , 
            $this->executive , 
            $this->userMgr , 
            $this->dateMgr , 
            $this->salesorderbalance , 
            $this->sumlinedisc  , 
            $this->taxText , 
            $this->sumtax , 
            $this->slc_amtintext , 
            $this->amount ,
            $this->memo_pur ,

            $this->deliveryname ,
            $this->deliveryaddress,
            $this->taxid,
            $this->vendid,
            $this->vendname,
            $this->vendaddress,
            $this->vendphone,
            $this->vendfax,
            $this->purchid,
            $this->bpc_documentdate,
            $this->payment,
            $this->deliverydate,
            $this->description,
            $this->bpc_purchasereqno,

            $this->current_page,
            $this->total_pages
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
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(5, 50 , 5);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 50);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        // add a page
        $page = 1;
        $pdf->AddPage();
        $current_page = 1;


        $pdf->SetCellPadding(0); // กำหนดการเพิ่มพื้นที่ระหว่างเซลล์เป็น 0
        $pdf->SetCellMargins(0, 46, 0, 0); // กำหนดการเพิ่มขอบรอบของเซลล์เป็น 0
        // Set font
        $pdf->SetFont('thsarabunb', '', 12);
        // $pdf->MultiCell(200, 90,'', 1, 'L', 0, 0, '' ,'', true);
        $pageHeight = $pdf->getPageHeight();
        $bottomMargin = 120; // ขอบล่างของหน้า
        $currentY = $pdf->GetY();

        $columnWidths = [10, 30, 60, 25, 25, 20, 30];
        $ch = 7;

        // วนลูปสร้างข้อมูลในตาราง
        foreach ($details as $key => $rs) {
            // ตรวจสอบว่า Y มากกว่าหรือเท่ากับขอบเขตของหน้า
            if ($currentY + 10 > $pageHeight - $bottomMargin) {
                $pdf->AddPage(); // เพิ่มหน้าใหม่
                $current_page = $current_page+1;
                $pdf->SetCellMargins(0, 46, 0, 0);
                $currentY = $pdf->GetY(); // อัพเดทค่า Y ใหม่
            }

            $itemname = $rs["name"]."\n".$rs['inventbatchid'];
            $columnWidth = $columnWidths[2];
            // กำหนดขนาดของเซลล์ตามความยาวของข้อความ
            $ch = $pdf->getStringHeight($columnWidth, $itemname)+2;

            $key = $key + 1;
            $pdf->MultiCell($columnWidths[0], $ch, $key, 0, 'C', 0, 0, '', '', true , 0, false, true, $ch, 'M');
            $pdf->MultiCell($columnWidths[1], $ch, $rs["itemid"], 0, 'L', 0, 0, '', '', true , 0, false, true, $ch, 'M');
            $pdf->MultiCell($columnWidths[2], $ch, $itemname , 0, 'L', 0, 0, '', '', true , 0, false, true, $ch, 'M');
            $pdf->MultiCell($columnWidths[3], $ch, number_format($rs["qty"], 3).' '.$rs["purchunit"], 0, 'R', 0, 0, '', '', true , 0, false, true, $ch, 'M');
            $pdf->MultiCell($columnWidths[4], $ch, number_format($rs["purchprice"], 3), 0, 'R', 0, 0, '', '', true , 0, false, true, $ch, 'M');
            $pdf->MultiCell($columnWidths[5], $ch, '', 0, 'C', 0, 0, '', '', true , 0, false, true, $ch, 'M');
            $pdf->MultiCell($columnWidths[6], $ch, number_format($rs["lineamount"], 3), 0, 'R', 0, 1, '', '', true, 0, false, true, $ch, 'M'); // 1 ที่สุดท้ายเพื่อให้ขึ้นบรรทัดใหม่
            $pdf->SetCellMargins(0, 0, 0, 0); // กำหนดการเพิ่มขอบรอบของเซลล์เป็น 0
            $currentY = $pdf->GetY(); // อัพเดทค่า Y หลังจากการสร้างเนื้อหา
           
        }

        $pdf->testfooter();
        // Close and output PDF document
        $pdfOutput = $pdf->Output('Document-'.$purchid.'.pdf', 'S'); // Output as string S = Save PDF , I = Preview

        // Save PDF to file
        $filePath = 'uploads/Document-'.$purchid.'.pdf';
        file_put_contents($filePath, $pdfOutput);

       $this->email->sendto_vendor($formno , $filePath , $poemail);
        $arSaveLog = array(
            "sp_formno" => $formno,
            "sp_pono" => $purchid,
            "sp_pono_docnum" => $purchorderdocnum,
            "sp_prno" => $bpc_purchasereqno,
            "sp_vendid" => $vendid,
            "sp_vendname" => $vendname,
            "sp_userpost" => $userSend,
            "sp_ecodepost" => $ecodeSend,
            "sp_mailto" => $poemail,
            "sp_datetime" => date("Y-m-d H:i:s"),
            "sp_dataareaid" => $dataareaid
        );
        $this->db->insert("sendpo_log" , $arSaveLog);
        // echo base64_encode($pdfOutput);
        
        $output = json_encode(array(
            "msg" => "ส่ง Email สำเร็จ",
            "status" => "Send Data Success"
        ));

        echo $output;
    }

    public function send_po_preview()
    {
        // HTML content
        $main = json_decode($this->input->post("main") , true);
        $details = json_decode($this->input->post("details") , true);
        $executive = json_decode($this->input->post("executive") , true);
        $taxid = $this->input->post("taxid");
        $userrequest = $this->input->post("userrequest");
        $datetimereq = $this->input->post("datetimereq");
        $userMgr = $this->input->post("userpostMgr");
        $dateMgr = $this->input->post("datetimepostMgr");
        $memo_pur = $this->input->post("memo_pur");

        $userSend = $this->input->post("userSend");
        $ecodeSend = $this->input->post("ecodeSend");

        $amount = $main['amount'];
        $bpc_documentdate = $main['bpc_documentdate'];
        $bpc_purchasereqno = $main['bpc_purchasereqno'];
        $bpc_remark = $main['bpc_remark']; // ค่านี้อาจจะเป็น null หรือไม่มีก็ได้
        $currencycode = $main['currencycode'];
        $deliveryaddress = $main['deliveryaddress'];
        $deliverydate = $main['deliverydate'];
        $deliveryname = $main['deliveryname'];
        $description = $main['description'];
        $email = $main['email'];
        $num = $main['num'];
        $payment = $main['payment'];
        $purchaseorderid = $main['purchaseorderid'];
        $purchid = $main['purchid'];
        $purchorderdate = $main['purchorderdate'];
        $purchorderdocnum = $main['purchorderdocnum'];
        $salesorderbalance = $main['salesorderbalance'];
        $slc_amtintext = $main['slc_amtintext'];
        $sumlinedisc = $main['sumlinedisc'];
        $sumtax = $main['sumtax'];
        $vendaddress = $main['vendaddress'];
        $vendfax = $main['vendfax'];
        $vendid = $main['vendid'];
        $vendname = $main['vendname'];
        $vendphone = $main['vendphone'];

        $runningfile = $purchid."-".getRuningCode();
        $formno = $this->input->post("formno");

        $current_page = 0;
        $total_pages = 0;

        if(floatval($sumtax) != 0){
            $taxText = "7.0%";
        }else{
            $taxText = "";
        }

        $this->datetimereq = $datetimereq;
        $this->userrequest = $userrequest;
        $this->executive = $executive;
        $this->userMgr = $userMgr;
        $this->dateMgr = $dateMgr;
        $this->salesorderbalance = $salesorderbalance;
        $this->sumlinedisc = $sumlinedisc;
        $this->taxText = $taxText;
        $this->sumtax = $sumtax;
        $this->slc_amtintext = $slc_amtintext;
        $this->amount = $amount;
        $this->memo_pur = $memo_pur;

        $this->deliveryname = $deliveryname;
        $this->deliveryaddress = $deliveryaddress;
        $this->taxid = $taxid;
        $this->vendid = $vendid;
        $this->vendname = $vendname;
        $this->vendaddress = $vendaddress;
        $this->vendphone = $vendphone;
        $this->vendfax = $vendfax;
        $this->purchid = $purchid;
        $this->bpc_documentdate = $bpc_documentdate;
        $this->payment = $payment;
        $this->deliverydate = $deliverydate;
        $this->description = $description;
        $this->bpc_purchasereqno = $bpc_purchasereqno;

        $this->current_page = $current_page;
        $this->total_pages = $total_pages;


        // Set footer data
        // create new PDF document
        $pdf = new MYPDF(
            $this->datetimereq , 
            $this->userrequest , 
            $this->executive , 
            $this->userMgr , 
            $this->dateMgr , 
            $this->salesorderbalance , 
            $this->sumlinedisc  , 
            $this->taxText , 
            $this->sumtax , 
            $this->slc_amtintext , 
            $this->amount ,
            $this->memo_pur ,

            $this->deliveryname ,
            $this->deliveryaddress,
            $this->taxid,
            $this->vendid,
            $this->vendname,
            $this->vendaddress,
            $this->vendphone,
            $this->vendfax,
            $this->purchid,
            $this->bpc_documentdate,
            $this->payment,
            $this->deliverydate,
            $this->description,
            $this->bpc_purchasereqno,

            $this->current_page,
            $this->total_pages
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
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(5, 50 , 5);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 50);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        // add a page
        $page = 1;
        $pdf->AddPage();
        $current_page = 1;


        $pdf->SetCellPadding(0); // กำหนดการเพิ่มพื้นที่ระหว่างเซลล์เป็น 0
        $pdf->SetCellMargins(0, 46, 0, 0); // กำหนดการเพิ่มขอบรอบของเซลล์เป็น 0
        // Set font
        $pdf->SetFont('thsarabunb', '', 12);
        // $pdf->MultiCell(200, 90,'', 1, 'L', 0, 0, '' ,'', true);
        $pageHeight = $pdf->getPageHeight();
        $bottomMargin = 120; // ขอบล่างของหน้า
        $currentY = $pdf->GetY();

        $columnWidths = [10, 30, 60, 25, 25, 20, 30];
        $ch = 7;

        // วนลูปสร้างข้อมูลในตาราง
        foreach ($details as $key => $rs) {
            // ตรวจสอบว่า Y มากกว่าหรือเท่ากับขอบเขตของหน้า
            if ($currentY + 10 > $pageHeight - $bottomMargin) {
                $pdf->AddPage(); // เพิ่มหน้าใหม่
                $current_page = $current_page+1;
                $pdf->SetCellMargins(0, 46, 0, 0);
                $currentY = $pdf->GetY(); // อัพเดทค่า Y ใหม่
            }

            // กำหนดความกว้างของเซลล์ตามความยาวของข้อความ
            $itemname = $rs["name"]."\n".$rs['inventbatchid'];
            $columnWidth = $columnWidths[2];
            // กำหนดขนาดของเซลล์ตามความยาวของข้อความ
            $ch = $pdf->getStringHeight($columnWidth, $itemname)+2;

            $key = $key + 1;
            $pdf->MultiCell($columnWidths[0], $ch, $key, 0, 'C', 0, 0, '', '', true , 0, false, true, $ch, 'M');
            $pdf->MultiCell($columnWidths[1], $ch, $rs["itemid"], 0, 'L', 0, 0, '', '', true , 0, false, true, $ch, 'M');
            $pdf->MultiCell($columnWidths[2], $ch, $itemname , 0, 'L', 0, 0, '', '', true , 0, false, true, $ch, 'M');
            $pdf->MultiCell($columnWidths[3], $ch, number_format($rs["qty"], 3).' '.$rs["purchunit"], 0, 'R', 0, 0, '', '', true , 0, false, true, $ch, 'M');
            $pdf->MultiCell($columnWidths[4], $ch, number_format($rs["purchprice"], 3), 0, 'R', 0, 0, '', '', true , 0, false, true, $ch, 'M');
            $pdf->MultiCell($columnWidths[5], $ch, '', 0, 'C', 0, 0, '', '', true , 0, false, true, $ch, 'M');
            $pdf->MultiCell($columnWidths[6], $ch, number_format($rs["lineamount"], 3), 0, 'R', 0, 1, '', '', true, 0, false, true, $ch, 'M'); // 1 ที่สุดท้ายเพื่อให้ขึ้นบรรทัดใหม่
            $pdf->SetCellMargins(0, 0, 0, 0); // กำหนดการเพิ่มขอบรอบของเซลล์เป็น 0
            $currentY = $pdf->GetY(); // อัพเดทค่า Y หลังจากการสร้างเนื้อหา
           
        }

        $pdf->testfooter();
        // Close and output PDF document
        $pdfOutput = $pdf->Output('Document-'.$purchid.'.pdf', 'S'); // Output as string S = Save PDF , I = Preview

        // Save PDF to file
        // $filePath = 'uploads/Document-'.$purchid.'.pdf';
        // file_put_contents($filePath, $pdfOutput);

        echo base64_encode($pdfOutput);
    }

}



class MYPDF extends TCPDF {
    // Properties to store data
    // ตั้งค่าขนาดความกว้างของคอลัมน์
    public $datetimereq;
    public $userrequest;
    public $executive;
    public $userMgr;
    public $dateMgr;
    public $salesorderbalance;
    public $sumlinedisc;
    public $taxText;
    public $sumtax;
    public $slc_amtintext;
    public $amount;
    public $memo_pur;

    public $deliveryname;
    public $deliveryaddress;
    public $taxid;
    public $vendid;
    public $vendname;
    public $vendaddress;
    public $vendphone;
    public $vendfax;
    public $purchid;
    public $bpc_documentdate;
    public $payment;
    public $deliverydate;
    public $description;
    public $bpc_purchasereqno;

    public $current_page;
    public $total_pages;

    // Constructor to initialize properties
    public function __construct($datetimereq , $userrequest , $executive , 
    $userMgr , $dateMgr , $salesorderbalance , $sumlinedisc  , 
    $taxText , $sumtax , $slc_amtintext , $amount , $memo_pur , $deliveryname ,
    $deliveryaddress , $taxid , $vendid , $vendname , $vendaddress , $vendphone ,
    $vendfax , $purchid , $bpc_documentdate ,$payment , $deliverydate , $description , $bpc_purchasereqno ,$current_page , $total_pages) {
        parent::__construct();
        $this->datetimereq = $datetimereq;
        $this->userrequest = $userrequest;
        $this->executive = $executive;
        $this->userMgr = $userMgr;
        $this->dateMgr = $dateMgr;
        $this->salesorderbalance = $salesorderbalance;
        $this->sumlinedisc = $sumlinedisc;
        $this->taxText = $taxText;
        $this->sumtax = $sumtax;
        $this->slc_amtintext = $slc_amtintext;
        $this->amount = $amount;
        $this->memo_pur = $memo_pur;

        $this->deliveryname = $deliveryname;
        $this->deliveryaddress = $deliveryaddress;
        $this->taxid = $taxid;
        $this->vendid = $vendid;
        $this->vendname = $vendname;
        $this->vendaddress = $vendaddress;
        $this->vendphone = $vendphone;
        $this->vendfax = $vendfax;
        $this->purchid = $purchid;
        $this->bpc_documentdate = $bpc_documentdate;
        $this->payment = $payment;
        $this->deliverydate = $deliverydate;
        $this->description = $description;
        $this->bpc_purchasereqno = $bpc_purchasereqno;

        $this->current_page = $current_page;
        $this->total_pages = $total_pages;
    }


    // public function endPage($trigger=true) {

    //     if ($this->page > 0) { // ตรวจสอบว่าเลขหน้าไม่ใช่ 0
    //         $this->setPage($this->page);
    //     }
    //     // Position at 15 mm from bottom
    //     $this->SetY(-110);

    //     // set cell padding
    //     $this->setCellPaddings(1, 1, 1, 1);
    //     // set cell margins
    //     $this->setCellMargins(1, 0, 1, 0);

    //     // Set font
    //     $this->SetFont('thsarabunb', '', 8);
    //     //section Footer

    //     $footer1 = 'Purchase Remark : '.$this->memo_pur."\n";
    //     $footer1 .= 'ระเบียบการวางบิล-รับเช็ค และวันหยุดประจำปี : https://intranet.saleecolour.com/intranet/holidaylastyear.html';
    //     $this->SetFont('thsarabunb', '', 12);
    //     $this->MultiCell(200, 5,$footer1, 0, 'L', 0, 1, '' ,'', true);
    //     // ดึงตำแหน่ง Y ปัจจุบัน
    //     $currentY = $this->getY();
    //     // วาดเส้นใต้ข้อความให้ยาวทั้งหน้า
    //     $pageWidth = $this->getPageWidth() - $this->getMargins()['left'] - $this->getMargins()['right'];
    //     $this->Line($this->getMargins()['left'], $currentY + 1, $pageWidth + $this->getMargins()['left'], $currentY + 1);
    //     $this->Ln(2);
    //     //section Footer

    //     //section Footer 2
    //     // กำหนดข้อความ
    //     $remark21 = 'กรุณายืนยันการจัดส่งสินค้ามายังบริษัทฯเมื่อได้รับใบสั่งซื้อฉบับนี้ และแนบหลักฐานฉบับนี้มาพร้อมกับใบแจ้งหนี้เพื่อเรียกเก็บเงินจากบริษัทฯ'."\n";
    //     $remark21 .= 'Please confirm your delivery schedule with us upon receiving this order and attach this P.O. to your invoice for collection.';

    //     $sumPriceText = 'รวมเงิน / Sub Total '."\n";
    //     $sumPriceText .= 'ส่วนลด / Discount '."\n";
    //     $sumPriceText .= 'ยอดคงเหลือ / Balance '."\n";
    //     $sumPriceText .= 'ภาษีมูลค่าเพิ่ม / VAT 7%'."\n";
    //     $sumPriceText .= 'เป็นเงินทั้งสิ้น / Grand Total '."\n";

    //     $sumPrice = 'test';
    //     // ตั้งค่าฟอนต์
    //     $this->SetFont('thsarabunb', '', 12);
    //     // พิมพ์ข้อความโดยใช้ตำแหน่งปัจจุบันของหน้า
    //     $this->MultiCell(120, 30, $remark21, 0, 'L', 0, 0, '', '', true);
    //     $this->MultiCell(38, 30, $sumPriceText, 0, 'L', 0, 0, '', '', true);
    //     $this->MultiCell(38, 30, $sumPrice, 0, 'L', 0, 1, '', '', true);

    //     // ดึงตำแหน่ง Y ปัจจุบัน
    //     $currentY = $this->getY();
    //     // วาดเส้นใต้ข้อความให้ยาวทั้งหน้า
    //     $pageWidth = $this->getPageWidth() - $this->getMargins()['left'] - $this->getMargins()['right'];
    //     $this->Line($this->getMargins()['left'], $currentY + 1, $pageWidth + $this->getMargins()['left'], $currentY + 1);
    //     // กำหนดระยะห่างระหว่างข้อความและเส้น (Ln)
    //     $this->Ln(4);
    //     //section Footer 2

   
    //     //section Footer 5
    //     // if ($this->page > 0) { // ตรวจสอบว่าเลขหน้าไม่ใช่ 0 ก่อนเรียก endPage ของ parent
    //     //     parent::endPage($trigger);
    //     // }
    // }
    
    // Page footer
    public function Footer()
    {
        // Position at 15 mm from bottom
        $this->SetY(-120);

        // set cell padding
        $this->setCellPaddings(1, 1, 1, 1);
        // set cell margins
        $this->setCellMargins(1, 0, 1, 0);

        // Set font
        $this->SetFont('thsarabunb', '', 8);
        //section Footer

        $footer1 = 'Purchase Remark : '.$this->memo_pur."";
        $footer1 .= 'ระเบียบการวางบิล-รับเช็ค และวันหยุดประจำปี : https://intranet.saleecolour.com/intranet/holidaylastyear.html';
        $this->SetFont('thsarabunb', '', 12);
        $this->MultiCell(200, 16,$footer1, 0, 'L', 0, 1, '' ,'', true);
        // ดึงตำแหน่ง Y ปัจจุบัน
        $currentY = $this->getY();
        // วาดเส้นใต้ข้อความให้ยาวทั้งหน้า
        $pageWidth = $this->getPageWidth() - $this->getMargins()['left'] - $this->getMargins()['right'];
        $this->Line($this->getMargins()['left'], $currentY + 1, $pageWidth + $this->getMargins()['left'], $currentY + 1);
        $this->Ln(2);
        //section Footer

        //section Footer 2
        // กำหนดข้อความ
        $remark21 = 'กรุณายืนยันการจัดส่งสินค้ามายังบริษัทฯเมื่อได้รับใบสั่งซื้อฉบับนี้ และแนบหลักฐานฉบับนี้มาพร้อมกับใบแจ้งหนี้เพื่อเรียกเก็บเงินจากบริษัทฯ'."\n";
        $remark21 .= 'Please confirm your delivery schedule with us upon receiving this order and attach this P.O. to your invoice for collection.';

        $sumPriceText = 'รวมเงิน / Sub Total '."\n";
        $sumPriceText .= 'ส่วนลด / Discount '."\n";
        $sumPriceText .= 'ยอดคงเหลือ / Balance '."\n";
        $sumPriceText .= 'ภาษีมูลค่าเพิ่ม / '.$this->taxText."\n";
        $sumPriceText .= 'เป็นเงินทั้งสิ้น / Grand Total '."\n";

        // if($this->getPage() == $this->getNumPages()){
        //     $sumPrice = $this->getPage()."/".$this->getNumPages();
        // }else{
        //     $sumPrice = 'ว่าง';
        // }


        // ตั้งค่าฟอนต์
        $this->SetFont('thsarabunb', '', 12);
        // พิมพ์ข้อความโดยใช้ตำแหน่งปัจจุบันของหน้า
        $this->MultiCell(120, 30, $remark21, 0, 'L', 0, 0, '', '', true);
        $this->MultiCell(38, 30, $sumPriceText, 0, 'L', 0, 0, '', '', true);
        $this->MultiCell(38, 30, '', 0, 'L', 0, 1, '', '', true);

        // ดึงตำแหน่ง Y ปัจจุบัน
        $currentY = $this->getY();
        // วาดเส้นใต้ข้อความให้ยาวทั้งหน้า
        $pageWidth = $this->getPageWidth() - $this->getMargins()['left'] - $this->getMargins()['right'];
        $this->Line($this->getMargins()['left'], $currentY + 1, $pageWidth + $this->getMargins()['left'], $currentY + 1);
        // กำหนดระยะห่างระหว่างข้อความและเส้น (Ln)
        $this->Ln(4);
        //section Footer 2


        //section Footer 3
        // กำหนดข้อความ
        $vendorText = 'ผู้ขาย'."\n".'Vendor Acknowledgement';
        $userReqText = 'ผู้ขอซื้อ'."\n".'Verify Signature';
        $executeText = 'ผู้มีอำนาจลงนาม'."\n".'Authorized Signature';

        // ตั้งค่าฟอนต์
        $this->SetFont('thsarabunb', '', 12);
        // พิมพ์ข้อความโดยใช้ตำแหน่งปัจจุบันของหน้า
        $this->MultiCell(200, 5, $vendorText, 0, 'C', 0, 1, '', '', true);
        // $this->MultiCell(65, 5, $userReqText, 0, 'C', 0, 0, '', '', true);
        // $this->MultiCell(65, 5, $executeText, 0, 'C', 0, 1, '', '', true);
        // กำหนดระยะห่างระหว่างข้อความและเส้น (Ln)
        $this->Ln(1);
        //section Footer 3

        //section Footer 4
        // $executiveUser = $this->userMgr;
        // $executiveDate = '';
        // $count = 0;
        // foreach ($this->executive as $rs) {
        //     if(empty($executiveUser)){
        //         $executiveUser .= $rs['apv_approve_user'];
        //     }else{
        //         $executiveUser .= ' , '.$rs['apv_approve_user'];
        //     }
        //     $count++;
        //     if ($count % 2 == 0) {
        //         $executiveUser .= "\n";
        //     }
        //     $executiveDate = $rs['apv_approve_datetime'];
        // }
        // // ถ้ามีค่านอกเหนือจากที่หาร 2 ลงตัว ให้เพิ่มบรรทัดใหม่
        // if ($count % 2 != 0) {
        //     $executiveUser .= "\n";
        // }

        // กำหนดข้อความ
        $vendorText2 = '____________________';
        // $userReqText2 = $this->userrequest;
        // $executeText2 = $executiveUser;

        // ตั้งค่าฟอนต์
        $this->SetFont('thsarabunb', '', 12);
        // พิมพ์ข้อความโดยใช้ตำแหน่งปัจจุบันของหน้า
        $this->MultiCell(200, 10, $vendorText2, 0, 'C', 0, 1, '', '', true , 0, false, true, 25, 'M');
        // $this->MultiCell(65, 25, $userReqText2, 0, 'C', 0, 0, '', '', true , 0, false, true, 25, 'M');
        // $this->MultiCell(65, 25, $executeText2, 0, 'C', 0, 1, '', '', true , 0, false, true, 25, 'M');

        // กำหนดระยะห่างระหว่างข้อความและเส้น (Ln)
        $this->Ln(1);
        //section Footer 4


        //section Footer 5
        // กำหนดข้อความ
        $vendorText3 = 'วันที่ / Date :_____/_____/_____';
        // $userReqText3 = 'วันที่ / Date : '.$this->datetimereq;
        // $executeText3 = 'วันที่ / Date : '.$executiveDate;

        // ตั้งค่าฟอนต์
        $this->SetFont('thsarabunb', '', 12);
        // พิมพ์ข้อความโดยใช้ตำแหน่งปัจจุบันของหน้า
        $this->MultiCell(200, 5, $vendorText3, 0, 'C', 0, 1, '', '', true);
        // $this->MultiCell(65, 5, $userReqText3, 0, 'C', 0, 0, '', '', true);
        // $this->MultiCell(65, 5, $executeText3, 0, 'C', 0, 1, '', '', true);

        $this->Ln(1);
        //section Footer 5

        //section Footer 6
        // กำหนดข้อความ
        $verifyText = '*เอกสารฉบับนี้ได้ผ่านกระบวนการตรวจสอบและอนุมัติมาจากระบบ Purchase plus system'."\n".'*This document has successfully passed the review and approval process of the Purchase Plus System.';
        // ตั้งค่าฟอนต์
        $this->SetFont('thsarabunb', '', 12);
        // พิมพ์ข้อความโดยใช้ตำแหน่งปัจจุบันของหน้า
        $this->MultiCell(200, 5, $verifyText, 0, 'C', 0, 1, '', '', true);
        // $this->MultiCell(65, 5, $userReqText, 0, 'C', 0, 0, '', '', true);
        // $this->MultiCell(65, 5, $executeText, 0, 'C', 0, 1, '', '', true);
        // ดึงตำแหน่ง Y ปัจจุบัน
        $currentY = $this->getY();
        // วาดเส้นใต้ข้อความให้ยาวทั้งหน้า
        $pageWidth = $this->getPageWidth() - $this->getMargins()['left'] - $this->getMargins()['right'];
        $this->Line($this->getMargins()['left'], $currentY + 1, $pageWidth + $this->getMargins()['left'], $currentY + 1);
        // กำหนดระยะห่างระหว่างข้อความและเส้น (Ln)
        // กำหนดระยะห่างระหว่างข้อความและเส้น (Ln)
        $this->Ln(1);
        //section Footer 6
    }

    public function Header()
    {
        // Position at 15 mm from bottom
        $this->SetY(5);
        // Set font
        
        // Vertical alignment
        $this->SetFont('thsarabunb', '', 14);
        $this->MultiCell(98, 5,$this->deliveryname, 0, 'L', 0, 0, '', '', true);
        $this->MultiCell(98, 5,'PC-F-002-02-25-03-67', 0, 'R', 0, 1, '', '', true);
        $this->Ln(1);

        // Vertical alignment
        $this->SetFont('thsarabunb', '', 12);
        $this->MultiCell(98, 15,trim($this->deliveryaddress)."\n".'Tel : (66)0-23232601-8', 0, 'L', 0, 0, '' ,'', true);
        $this->SetFont('thsarabunb', '', 18);
        $this->MultiCell(98, 15,'ใบสั่งซื้อ'."\n".'Purchase Order'."\n".$this->PageNo(), 0, 'R', 0, 1, '', '', true);
        $this->Ln(1);

        $x = 10;
        $y = 30;
        $this->SetXY($x, $y);
        $this->SetFont('thsarabunb', '', 14);
        $this->MultiCell(198, 3,'เลขประจำตัวผู้เสียภาษี / Tax ID. '.$this->taxid, 0, 'C', 0, 1, '', '', true);
        // กำหนดตำแหน่งข้อความ
        // วาดเส้นใต้ข้อความให้ยาวทั้งหน้า
        $pageWidth = $this->getPageWidth() - $this->getMargins()['left'] - $this->getMargins()['right'];
        $this->Line($this->getMargins()['left'], $y + 10, $pageWidth + $this->getMargins()['left'], $y + 10);
        $this->Ln(6);
        // output the HTML content
        // $pdf->writeHTML($html, false, false, true, false, '');

        //section 2
        $vendtext = 'ผู้จำหน่าย / Vender :'.$this->vendid."\n".$this->vendname."\n".$this->vendaddress."\n".$this->vendphone;
        $this->SetFont('thsarabunb', '', 12);
        $this->MultiCell(102, 15,$vendtext.' '.$this->vendfax, 0, 'L', 0, 0, '' ,'', true);

        $sec2_2text = 'เลขที่ใบสั่งซื้อ / No. : '.$this->purchid."\n";
        $sec2_2text .='วันที่ / Date : '.conDateFromDb($this->bpc_documentdate)."\n";
        $sec2_2text .='เครดิต / Credit : '.conPayment($this->payment)."\n";
        $sec2_2text .='วันที่รับของ / Delivery Date : '.conDateFromDb($this->deliverydate)."\n";
        $sec2_2text .='แผนกที่สั่งซื้อ / Request Dept : '.$this->description;
        $this->SetFont('thsarabunb', '', 12);
        $this->MultiCell(94, 15,$sec2_2text, 0, 'L', 0, 1, '', '', true);
        $this->Ln(1);
        //section 2

        //section 3
        $x = 5;
        $y = 70;
        $this->SetXY($x, $y);
        $remark1 = 'หมายเหตุ / Remark : กรุณาเซ็นต์ยืนยันแล้วส่งกลับมาที่ purchase@saleecolour.com';
        $this->SetFont('thsarabunb', '', 12);
        $this->MultiCell(130, 5,$remark1, 0, 'L', 0, 0, '' ,'', true);

        $sec3text = 'PR No. '.$this->bpc_purchasereqno;
        $this->SetFont('thsarabunb', '', 12);
        $this->MultiCell(66, 5,$sec3text, 0, 'R', 0, 1, '', '', true);
        // วาดเส้นใต้ข้อความให้ยาวทั้งหน้า
        $pageWidth = $this->getPageWidth() - $this->getMargins()['left'] - $this->getMargins()['right'];
        $this->Line($this->getMargins()['left'], $y + 10, $pageWidth + $this->getMargins()['left'], $y + 10);
        $this->Ln(6);
        //section 3

        // ตั้งค่า font
        $this->SetFont('thsarabunb', '', 12);
        $columnWidths = [10, 30, 60, 25, 25, 20, 30];
        // ตั้งค่าตำแหน่งเริ่มต้นของตาราง
        $x = $this->GetX();
        $y = $this->GetY();
        $th = 12;
        // สร้างหัวข้อของตาราง
        $this->MultiCell($columnWidths[0], $th, 'ลำดับ'."\n".'No.', 0, 'C', 0, 0, $x, $y, true , 0, false, true, $th, 'M');
        $this->MultiCell($columnWidths[1], $th, '', 0, 'C', 0, 0, $x + array_sum(array_slice($columnWidths, 0, 1)), $y, true , 0, false, true, $th, 'M');
        $this->MultiCell($columnWidths[2], $th, 'รายการสินค้า'."\n".'Description.', 0, 'L', 0, 0, $x + array_sum(array_slice($columnWidths, 0, 2)), $y, true , 0, false, true, $th, 'M');
        $this->MultiCell($columnWidths[3], $th, 'จำนวน'."\n".'Qty.', 0, 'R', 0, 0, $x + array_sum(array_slice($columnWidths, 0, 3)), $y, true , 0, false, true, $th, 'M');
        $this->MultiCell($columnWidths[4], $th, 'ราคาต่อหน่วย'."\n".'@.', 0, 'R', 0, 0, $x + array_sum(array_slice($columnWidths, 0, 4)), $y, true , 0, false, true, $th, 'M');
        $this->MultiCell($columnWidths[5], $th, 'ส่วนลด'."\n".'Discount.', 0, 'R', 0, 0, $x + array_sum(array_slice($columnWidths, 0, 5)), $y, true , 0, false, true, $th, 'M');
        $this->MultiCell($columnWidths[6], $th, 'จำนวนเงิน'."\n".'Amount.', 0, 'R', 0, 1, $x + array_sum(array_slice($columnWidths, 0, 6)), $y, true , 0, false, true, $th, 'M'); // 1 ที่สุดท้ายเพื่อให้ขึ้นบรรทัดใหม่
        // ดึงตำแหน่ง Y ปัจจุบัน
        $currentY = $this->getY();
        // วาดเส้นใต้ข้อความให้ยาวทั้งหน้า
        $pageWidth = $this->getPageWidth() - $this->getMargins()['left'] - $this->getMargins()['right'];
        $this->Line($this->getMargins()['left'], $currentY + 1, $pageWidth + $this->getMargins()['left'], $currentY + 1);
    }

    public function testfooter()
    {
        // Position at 15 mm from bottom
        $this->SetY(-120);

        // set cell padding
        $this->setCellPaddings(1, 1, 1, 1);
        // set cell margins
        $this->setCellMargins(1, 0, 1, 0);

        // Set font
        $this->SetFont('thsarabunb', '', 8);
        //section Footer

        $footer1 = 'Purchase Remark : '.$this->memo_pur."\n";
        $footer1 .= 'ระเบียบการวางบิล-รับเช็ค และวันหยุดประจำปี : https://intranet.saleecolour.com/intranet/holidaylastyear.html';
        $this->SetFont('thsarabunb', '', 12);
        $this->MultiCell(200, 16,'', 0, 'L', 0, 1, '' ,'', true);
        // ดึงตำแหน่ง Y ปัจจุบัน
        $currentY = $this->getY();
        // วาดเส้นใต้ข้อความให้ยาวทั้งหน้า
        // $pageWidth = $this->getPageWidth() - $this->getMargins()['left'] - $this->getMargins()['right'];
        // $this->Line($this->getMargins()['left'], $currentY + 1, $pageWidth + $this->getMargins()['left'], $currentY + 1);
        $this->Ln(2);
        //section Footer

        //section Footer 2
        // กำหนดข้อความ
        $remark21 = 'กรุณายืนยันการจัดส่งสินค้ามายังบริษัทฯเมื่อได้รับใบสั่งซื้อฉบับนี้ และแนบหลักฐานฉบับนี้มาพร้อมกับใบแจ้งหนี้เพื่อเรียกเก็บเงินจากบริษัทฯ'."\n";
        $remark21 .= 'Please confirm your delivery schedule with us upon receiving this order and attach this P.O. to your invoice for collection.'."\n";
        $remarkPriceText = "\n"."\n"."\n"."\n".'( '.$this->slc_amtintext.' )';

        $sumPriceText = 'รวมเงิน / Sub Total '."\n";
        $sumPriceText .= 'ส่วนลด / Discount '."\n";
        $sumPriceText .= 'ยอดคงเหลือ / Balance '."\n";
        $sumPriceText .= 'ภาษีมูลค่าเพิ่ม / '.$this->taxText."\n";
        $sumPriceText .= 'เป็นเงินทั้งสิ้น / Grand Total '."\n";

        // if($this->getPage() == $this->getNumPages()){
        //     $sumPrice = $this->getPage()."/".$this->getNumPages();
        // }else{
        //     $sumPrice = 'ว่าง';
        // }

        $sumPrice = number_format($this->salesorderbalance , 3)."\n";
        $sumPrice .= number_format($this->sumlinedisc , 3)."\n";
        $sumPrice .= number_format($this->salesorderbalance , 3)."\n";
        $sumPrice .= number_format($this->sumtax , 3)."\n";
        $sumPrice .= number_format($this->amount , 3)."\n";

        // ตั้งค่าฟอนต์
        $this->SetFont('thsarabunb', '', 12);
        // พิมพ์ข้อความโดยใช้ตำแหน่งปัจจุบันของหน้า
        $this->MultiCell(120, 30, $remarkPriceText, 0, 'L', 0, 0, '', '', true);
        $this->MultiCell(38, 30, '', 0, 'L', 0, 0, '', '', true);
        $this->MultiCell(38, 30, $sumPrice, 0, 'R', 0, 1, '', '', true);

        // ดึงตำแหน่ง Y ปัจจุบัน
        $currentY = $this->getY();
        // วาดเส้นใต้ข้อความให้ยาวทั้งหน้า
        // $pageWidth = $this->getPageWidth() - $this->getMargins()['left'] - $this->getMargins()['right'];
        // $this->Line($this->getMargins()['left'], $currentY + 1, $pageWidth + $this->getMargins()['left'], $currentY + 1);
        // กำหนดระยะห่างระหว่างข้อความและเส้น (Ln)
        $this->Ln(4);
        //section Footer 2
    }

}

/* End of file Controllername.php */





?>