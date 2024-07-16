<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('TCPDF/tcpdf.php');
class MYPDF extends TCPDF {
    
    public function __construct()
    {
        parent::__construct();
        //Do your magic here
    }
    
    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        
        // Footer content
        $html = '
        <table style="text-align:center;font-size:16px;">
            <tr>
                <td>ผู้ขาย</td>
                <td>ผู้ขอซื้อ</td>
                <td>ผู้มีอำนาจลงนาม</td>
            </tr>
            <tr>
                <td>Vender Acknowledgement</td>
                <td>Verify Signature</td>
                <td>Authorized Signature</td>
            </tr>
            <tr>
                <td>วันที่ / Date :_______________</td>
                <td></td>
                <td></td>
            </tr>
        </table>
                ';

        // Output the HTML content
        $this->writeHTML($html, true, false, true, false, '');
    }
}

class Pdf_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set("Asia/Bangkok");
        $this->load->model("email_model" , "email");
    }

    public function send_po()
    {
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setFooterData(array(0,64,0), array(0,64,128));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(10, 5, 10, true);
        // set margins

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set font
        $pdf->SetFont('thsarabunb', 'BI', 16);

        // Add a page
        $pdf->AddPage();

        $main = json_decode($this->input->post("main") , true);
        $details = json_decode($this->input->post("details") , true);
        $executive = json_decode($this->input->post("executive") , true);
        $taxid = $this->input->post("taxid");
        $userrequest = $this->input->post("userrequest");
        $datetimereq = $this->input->post("datetimereq");

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

        // Set some content to print
        $html = '
        <style>
            .fontsize16{
                font-size:16px;
            }
            .trlPrintPoDetail{
            width: 100%;
            border-collapse: collapse;
            font-size:16px;
            }

            .trlPrintPoDetail th,.trlPrintPoDetail td {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
            }

            /* Style for table header */
            .trlPrintPoDetail th {
            background-color: #f2f2f2;
            }

            /* Style for table rows with alternating colors */
            .trlPrintPoDetail tr:nth-child(even) {
            background-color: #f9f9f9;
            }

            .trlPrintPoDetail tr:nth-child(odd) {
            background-color: #ffffff;
            }

            /* Optional: Add hover effect */
            .trlPrintPoDetail tr:hover {
            background-color: #e2e2e2;
            }

            .numberPrint{
                font-size:16px;
            }
            .textRight{
                text-align:right;
                width:150px;
            }

        </style>
        <table>
            <tr>
                <td>
                    <span>'.$deliveryname.'</span>
                </td>
                <td style="text-align:right;">
                    <span>PC-F-002-02-25-03-67</span>
                </td>
            </tr>
            <tr>
                <td style="text-align:left;">
                    '.$deliveryaddress.'<br>
                    <span style="font-size:16px;">Tel : (66)0-23232601-8</span>
                </td>
                <td style="text-align:right;">
                    <span>ใบสั่งซื้อ</span><br>
                    <span>Purchase Order</span>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:center;font-size:16px;">
                    <span>เลขประจำตัวผู้เสียภาษี / Tax ID. '.$taxid.'</span>
                </td>
            </tr>
        </table>
        <hr>
        <table>
            <tr>
                <td>
                    <span class="fontsize16"><b>ผู้จำหน่าย / Vender : </b>'.$vendid.'</span><br>
                    <span class="fontsize16">'.$vendname.'</span><br>
                    <span class="fontsize16">'.$vendaddress.'</span><br>
                    <span class="fontsize16">'.$vendphone.'</span><span class="fontsize16" style="margin-left:10px;">'.$vendfax.'</span>
                </td>
                <td>
                    <span class="fontsize16"><b>เลขที่ใบสั่งซื้อ : </b>'.$purchid.'</span><br>
                    <span class="fontsize16"><b>วันที่ / Data : </b>'.$bpc_documentdate.'</span><br>
                    <span class="fontsize16"><b>เครดิต / Credit : </b>'.$payment.'</span><br>
                    <span class="fontsize16"><b>วันที่รับของ / Delivery Date : </b>'.$deliverydate.'</span><br>
                    <span class="fontsize16"><b>แผนกที่สั่งซื้อ / Request Dept : </b>'.$description.'</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="fontsize16"><b>หมายเหตุ : </b>กรุณาเซ็นต์ยืนยันแล้วส่งกลับมาที่ purchase@saleecolour.com</span>
                </td>
                <td>
                    <span class="fontsize16"><b>PR No. </b>'.$bpc_purchasereqno.'</span>
                </td>
            </tr>
        </table>
        <hr>';
        if(floatval($sumtax) != 0){
            $taxText = "7.0%";
        }else{
            $taxText = "";
        }
        $html .='
        <table id="trlPrintPoDetail" class="trlPrintPoDetail">
            <thead>
                <tr>
                    <th>ลำดับ</th>
                    <th>รหัสสินค้า</th>
                    <th>รายการสินค้า</th>
                    <th>จำนวน</th>
                    <th>ราคาต่อหน่วย</th>
                    <th>ส่วนลด</th>
                    <th>จำนวนเงิน</th>
                </tr>
            </thead>
            <tbody>';
            foreach($details as $key => $rs){
                $key = $key+1;
                $html .='
                <tr>
                    <td>'.$key.'</td>
                    <td>'.$rs["itemid"].'</td>
                    <td>'.$rs["name"].'</td>
                    <td>'.number_format($rs["qty"] , 2).'</td>
                    <td>'.number_format($rs["purchprice"] , 2).'</td>
                    <td>'.number_format($rs["discamount"] , 2).'</td>
                    <td>'.number_format($rs["lineamount"] , 2).'</td>
                </tr>
                ';
            }
            $html .='
            </tbody>
        </table>
        <div>
            <span class="fontsize16">กรุราระบุเลขที่สั่งซื้อในใบกำกับภาษีและแนบ COA , สำเนาใบสั่งซื้อ พร้อมส่งสินค้า</span><br>
            <span class="fontsize16">ระเบียบการวางบิล-รับเช็ค และวันหยุดประจำปี : https://intranet.saleecolour.com/intranet/holidaylastyear.html</span>
        </div>
        <hr>
        <table>
            <tr>
                <td rowspan="4" style="width:350px">
                    <span class="fontsize16">กรุณายืนยันการจัดส่งสินค้ามายังบริษัทฯ เมื่อได้รับใบสั่งซื้อฉบับนี้ และแนบหลักฐานฉบับนี้ มาพร้อมกับใบแจ้งหนี้เพื่อเรียกเก็บเงินจากบริษัทฯ</span><br>
                    <span class="fontsize16">Please confirm your delevery schedule to us when this order and attach this P.O. with your invoice for collection.</span>
                </td>
                <td class="textRight">
                    <span class="numberPrint">รวมเงิน / Sub Total</span>
                </td>
                <td class="textRight"><span class="numberPrint">'.number_format($salesorderbalance , 2).'</span></td>
            </tr>
            <tr>
                <td class="textRight"><span class="numberPrint">ส่วนลด / Discount</span></td>
                <td class="textRight"><span class="numberPrint">'.number_format($sumlinedisc , 2).'</span></td>
            </tr>
            <tr>
                <td class="textRight">
                    <span class="numberPrint">ยอดคงเหลือ / Balance</span>
                </td>
                <td class="textRight">
                    <span class="numberPrint">'.number_format($salesorderbalance , 2).'</span>
                </td>
            </tr>
            <tr>
                <td class="textRight">
                    <span class="numberPrint">ภาษีมูลค่าเพิ่ม / VAT</span><span> '.$taxText.' </span>
                </td>
                <td class="textRight">
                    <span class="numberPrint">'.number_format($sumtax , 2).'</span>
                </td>
            </tr>
            <tr>
                <td><span>'.$slc_amtintext.'</span></td>
                <td class="textRight"><span class="numberPrint">เป็นเงินทั้งสิ้น / Grand Total</span></td>
                <td class="textRight"><span class="numberPrint">'.number_format($amount , 2).'</span></td>
            </tr>
        </table>
        <hr>
        <table style="text-align:center;font-size:16px;">
            <tr>
                <td>ผู้ขาย</td>
                <td>ผู้ขอซื้อ</td>
                <td>ผู้มีอำนาจลงนาม</td>
            </tr>
            <tr>
                <td>Vender Acknowledgement</td>
                <td>Verify Signature</td>
                <td>Authorized Signature</td>
            </tr>
            <tr>
                <td style="height:50px;"></td>
                <td style="height:50px;">'.$userrequest.'</td>
                <td style="height:50px;">
                ';
                foreach ($executive as $rs) {
                $html .='<span>'.$rs['apv_approve_user'].'</span>&nbsp;&nbsp;
                <span>'.$rs['apv_approve_datetime'].'</span><br>';
                }
            $html .='
                </td>
            </tr>
            <tr>
                <td>วันที่ / Date :_______________</td>
                <td>'.$datetimereq.'</td>
                <td></td>
            </tr>
        </table>
        <br>
        <hr>
        ';
        // Print text using writeHTMLCell()
        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);




        // Close and output PDF document
        $pdfOutput = $pdf->Output('Document-'.$purchid.'.pdf', 'S'); // Output as string S = Save PDF , I = Preview

        // Save PDF to file
        $filePath = 'uploads/Document-'.$purchid.'.pdf';
        file_put_contents($filePath, $pdfOutput);
        
        // $this->sendemail($filePath , $deliveryname , $purchid);
        
        // $this->email->sendto_vendor($formno , $filePath , $email);
        $arSaveLog = array(
            "sp_formno" => $formno,
            "sp_pono" => $purchid,
            "sp_prno" => $bpc_purchasereqno,
            "sp_vendid" => $vendid,
            "sp_vendname" => $vendname,
            "sp_userpost" => $userSend,
            "sp_ecodepost" => $ecodeSend,
            "sp_mailto" => $email,
            "sp_datetime" => date("Y-m-d H:i:s")
        );
        $this->db->insert("sendpo_log" , $arSaveLog);

        // $output = json_encode(array(
        //     "msg" => "ส่ง Email สำเร็จ",
        //     "status" => "Send Data Success"
        // ));

        // echo $output;
        
        echo base64_encode($pdfOutput);
    }

    public function printpdf()
    {
        $this->load->view("printpdf");
    }

    public function send_po2()
    {
        // Set footer data
        // create new PDF document
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Your Name');
        $pdf->SetTitle('Purchase Order');
        $pdf->SetSubject('TCPDF Tutorial');
        $pdf->SetKeywords('TCPDF, PDF, example, test, guide');

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Purchase Order', 'Generated using TCPDF');
        $pdf->setPrintHeader(false);

        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, 10 , PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set font
        $pdf->SetFont('thsarabunb', 'BI', 16);

        // HTML content
        $main = json_decode($this->input->post("main") , true);
        $details = json_decode($this->input->post("details") , true);
        $executive = json_decode($this->input->post("executive") , true);
        $taxid = $this->input->post("taxid");
        $userrequest = $this->input->post("userrequest");
        $datetimereq = $this->input->post("datetimereq");

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

        // add a page
        $pdf->AddPage();

        // Set some content to print
        $html = '
        <style>
            .fontsize16{
                font-size:16px;
            }
            .trlPrintPoDetail{
                width: 100%;
                border-collapse: collapse;
                font-size:16px;
            }

            .trlPrintPoDetail th,.trlPrintPoDetail td {
                border: 1px solid #dddddd;
                text-align: left;
                padding: 8px;
            }

            /* Style for table header */
            .trlPrintPoDetail th {
                background-color: #f2f2f2;
            }

            /* Style for table rows with alternating colors */
            .trlPrintPoDetail tr:nth-child(even) {
            background-color: #f9f9f9;
            }

            .trlPrintPoDetail tr:nth-child(odd) {
            background-color: #ffffff;
            }

            /* Optional: Add hover effect */
            .trlPrintPoDetail tr:hover {
            background-color: #e2e2e2;
            }

            .numberPrint{
                font-size:16px;
            }
            .textRight{
                text-align:right;
                width:150px;
            }

        </style>
        <table>
            <tr>
                <td style="text-align: left; word-wrap: break-word;">
                    <span>'.$deliveryname.'</span>
                </td>
                <td style="text-align:right;">
                    <span>PC-F-002-02-25-03-67</span>
                </td>
            </tr>
            <tr>
                <td style="text-align:left;">
                    '.$deliveryaddress.'<br>
                    <span style="font-size:16px;">Tel : (66)0-23232601-8</span>
                </td>
                <td style="text-align:right;">
                    <span>ใบสั่งซื้อ</span><br>
                    <span>Purchase Order</span>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:center;font-size:16px;">
                    <span>เลขประจำตัวผู้เสียภาษี / Tax ID. '.$taxid.'</span>
                </td>
            </tr>
        </table>
        <hr>
        <table>
            <tr>
                <td>
                    <span class="fontsize16"><b>ผู้จำหน่าย / Vender : </b>'.$vendid.'</span><br>
                    <span class="fontsize16">'.$vendname.'</span><br>
                    <span class="fontsize16">'.$vendaddress.'</span><br>
                    <span class="fontsize16">'.$vendphone.'</span><span class="fontsize16" style="margin-left:10px;">'.$vendfax.'</span>
                </td>
                <td>
                    <span class="fontsize16"><b>เลขที่ใบสั่งซื้อ : </b>'.$purchid.'</span><br>
                    <span class="fontsize16"><b>วันที่ / Data : </b>'.$bpc_documentdate.'</span><br>
                    <span class="fontsize16"><b>เครดิต / Credit : </b>'.$payment.'</span><br>
                    <span class="fontsize16"><b>วันที่รับของ / Delivery Date : </b>'.$deliverydate.'</span><br>
                    <span class="fontsize16"><b>แผนกที่สั่งซื้อ / Request Dept : </b>'.$description.'</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="fontsize16"><b>หมายเหตุ : </b>กรุณาเซ็นต์ยืนยันแล้วส่งกลับมาที่ purchase@saleecolour.com</span>
                </td>
                <td>
                    <span class="fontsize16"><b>PR No. </b>'.$bpc_purchasereqno.'</span>
                </td>
            </tr>
        </table>
        <hr>';
        if(floatval($sumtax) != 0){
            $taxText = "7.0%";
        }else{
            $taxText = "";
        }
        $html .='
        <table id="trlPrintPoDetail" class="trlPrintPoDetail">
            <thead>
                <tr>
                    <th>ลำดับ</th>
                    <th>รหัสสินค้า</th>
                    <th>รายการสินค้า</th>
                    <th>จำนวน</th>
                    <th>ราคาต่อหน่วย</th>
                    <th>ส่วนลด</th>
                    <th>จำนวนเงิน</th>
                </tr>
            </thead>
            <tbody>';
            foreach($details as $key => $rs){
                $key = $key+1;
                $html .='
                <tr>
                    <td>'.$key.'</td>
                    <td>'.$rs["itemid"].'</td>
                    <td>'.$rs["name"].'</td>
                    <td>'.number_format($rs["qty"] , 2).'</td>
                    <td>'.number_format($rs["purchprice"] , 2).'</td>
                    <td>'.number_format($rs["discamount"] , 2).'</td>
                    <td>'.number_format($rs["lineamount"] , 2).'</td>
                </tr>
                ';
            }
            $html .='
            </tbody>
        </table>
        <div>
            <span class="fontsize16">กรุราระบุเลขที่สั่งซื้อในใบกำกับภาษีและแนบ COA , สำเนาใบสั่งซื้อ พร้อมส่งสินค้า</span><br>
            <span class="fontsize16">ระเบียบการวางบิล-รับเช็ค และวันหยุดประจำปี : https://intranet.saleecolour.com/intranet/holidaylastyear.html</span>
        </div>
        <hr>
        <table>
            <tr>
                <td rowspan="4" style="width:350px">
                    <span class="fontsize16">กรุณายืนยันการจัดส่งสินค้ามายังบริษัทฯ เมื่อได้รับใบสั่งซื้อฉบับนี้ และแนบหลักฐานฉบับนี้ มาพร้อมกับใบแจ้งหนี้เพื่อเรียกเก็บเงินจากบริษัทฯ</span><br>
                    <span class="fontsize16">Please confirm your delevery schedule to us when this order and attach this P.O. with your invoice for collection.</span>
                </td>
                <td class="textRight">
                    <span class="numberPrint">รวมเงิน / Sub Total</span>
                </td>
                <td class="textRight"><span class="numberPrint">'.number_format($salesorderbalance , 2).'</span></td>
            </tr>
            <tr>
                <td class="textRight"><span class="numberPrint">ส่วนลด / Discount</span></td>
                <td class="textRight"><span class="numberPrint">'.number_format($sumlinedisc , 2).'</span></td>
            </tr>
            <tr>
                <td class="textRight">
                    <span class="numberPrint">ยอดคงเหลือ / Balance</span>
                </td>
                <td class="textRight">
                    <span class="numberPrint">'.number_format($salesorderbalance , 2).'</span>
                </td>
            </tr>
            <tr>
                <td class="textRight">
                    <span class="numberPrint">ภาษีมูลค่าเพิ่ม / VAT</span><span> '.$taxText.' </span>
                </td>
                <td class="textRight">
                    <span class="numberPrint">'.number_format($sumtax , 2).'</span>
                </td>
            </tr>
            <tr>
                <td><span>'.$slc_amtintext.'</span></td>
                <td class="textRight"><span class="numberPrint">เป็นเงินทั้งสิ้น / Grand Total</span></td>
                <td class="textRight"><span class="numberPrint">'.number_format($amount , 2).'</span></td>
            </tr>
        </table>
        <hr>
        <table style="text-align:center;font-size:16px;">
            <tr>
                <td>ผู้ขาย</td>
                <td>ผู้ขอซื้อ</td>
                <td>ผู้มีอำนาจลงนาม</td>
            </tr>
            <tr>
                <td>Vender Acknowledgement</td>
                <td>Verify Signature</td>
                <td>Authorized Signature</td>
            </tr>
            <tr>
                <td style="height:50px;"></td>
                <td style="height:50px;">'.$userrequest.'</td>
                <td style="height:50px;">
                ';
                foreach ($executive as $rs) {
                $html .='<span>'.$rs['apv_approve_user'].'</span>&nbsp;&nbsp;
                <span>'.$rs['apv_approve_datetime'].'</span><br>';
                }
            $html .='
                </td>
            </tr>
            <tr>
                <td>วันที่ / Date :_______________</td>
                <td>'.$datetimereq.'</td>
                <td></td>
            </tr>
        </table>
        <br>
        <hr>
        ';

        // output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');

        // Close and output PDF document
        $pdfOutput = $pdf->Output('Document-'.$purchid.'.pdf', 'S'); // Output as string S = Save PDF , I = Preview

        // Save PDF to file
        $filePath = 'uploads/Document-'.$purchid.'.pdf';
        file_put_contents($filePath, $pdfOutput);

        echo base64_encode($pdfOutput);
    }

}

/* End of file ModelName.php */

?>