<?php
require_once('tcpdf/tcpdf.php');

class MYPDF extends TCPDF {

    public function Footer() {
        // HTML content for footer
        $html = '
        <table style="width: 100%; text-align: center;">
            <tr>
                <td>Left Content</td>
                <td>Middle Content</td>
                <td>Right Content</td>
            </tr>
        </table>';

        // Output the HTML content
        $this->writeHTML($html, true, false, true, false, '');
    }
}

// Create new PDF document
$pdf = new MYPDF();

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Name');
$pdf->SetTitle('Document Title');
$pdf->SetSubject('Subject');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

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
// Add a page
$pdf->AddPage();

// Your main content here

// Close and output PDF document
$pdf->Output('example.pdf', 'I');
?>
