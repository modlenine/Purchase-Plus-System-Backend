<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Email_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set("Asia/Bangkok");
    }

    function createQrcode($linkQrcode, $id)
    {
        // $obj = new emailfn();
        // $obj->gci()->load->library("Ciqrcode");
        require("phpqrcode/qrlib.php");
        // $this->load->library('phpqrcode/qrlib');

        $SERVERFILEPATH = $_SERVER['DOCUMENT_ROOT'] . '/intsys/purchaseplus/purchaseplus_backend/uploads/qrcode/';
        $urlQrcode = $linkQrcode;
        // $filename1 = 'qrcode' . rand(2, 200) . ".png";
        $filename1 = 'qrcode' . $id . ".png";
        $folder = $SERVERFILEPATH;

        $filename = $folder . $filename1;

        QRcode::png(
            $urlQrcode,
            $filename,
            // $outfile = false,
            $level = QR_ECLEVEL_H,
            $size = 4,
            $margin = 2
        );

        // echo "<img src='http://192.190.10.27/crf/upload/qrcode/".$filename1."'>";
        return $filename1;
    }

    public function sendto_investigator($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "New PR Awaiting Investigation. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PR ใหม่ รอตรวจสอบ</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';
            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_byecode($emaildata->m_invest_ecodefix);//ดึงเอาเฉพาะ Email ของผู้ตรวจสอบขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }
        if($emaildata->m_invest_ecodefix == "M0140"){//เช็คผู้ตรวจสอบฝ่าย Engineer
            array_push($to , "engineer@saleecolour.com");
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        // $optionCC3 = getemail_bydeptcode("1004");//ผู้ร้องขอ
        // foreach($optionCC3->result_array() as $rs){
        //     array_push($cc , $rs['memberemail']);
        //     array_push($ecodeccAr , $rs['ecode']);
        // }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }


    public function sendto_manager($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "New PR Awaiting Manager Approval. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PR ใหม่ รอผู้จัดการอนุมัติ</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';
            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//ดึงเอาเฉพาะ Email ของผ฿้จัดการขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        // $optionCC3 = getemail_bydeptcode("1004");//ผู้ร้องขอ
        // foreach($optionCC3->result_array() as $rs){
        //     array_push($cc , $rs['memberemail']);
        //     array_push($ecodeccAr , $rs['ecode']);
        // }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        if($status == "Investigator Approved"){
            $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
            $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
        }
    }


    public function sendto_purchase_G5($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "New PR Awaiting Procurement Investigation. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PR ใหม่ รอจัดซื้อตรวจสอบข้อมูล</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }

    public function sendto_purchase_G4($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "New PR Awaiting Procurement Investigation. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PR ใหม่ รอจัดซื้อตรวจสอบข้อมูล</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';


        
        if(getExecutiveData($formno)->num_rows() > 0){
            $emailExe4data = getExecutiveData($formno)->row();
            $exe4App = $emailExe4data->apv_approve;
            $exe4user = $emailExe4data->apv_approve_user;
            $exe4datetime = $emailExe4data->apv_approve_datetime;
            $exe4memo = $emailExe4data->apv_approve_memo;
        }else{
            $emailExe4data = "";
            $exe4App = "";
            $exe4user = "";
            $exe4datetime = "";
            $exe4memo = "";
        }
            //check ผลการตรวจสอบ
            if($exe4App == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการท่านที่สอง</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$exe4memo.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$exe4user.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$exe4datetime.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }


    public function sendto_purchase_G3($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "New PR Awaiting Procurement Investigation. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PR ใหม่ รอจัดซื้อตรวจสอบข้อมูล</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';


        
        if(getExecutiveData($formno)->num_rows() > 0){
            $emailExe3data = getExecutiveData($formno)->row();
            $exe3App = $emailExe3data->apv_approve;
            $exe3user = $emailExe3data->apv_approve_user;
            $exe3datetime = $emailExe3data->apv_approve_datetime;
            $exe3memo = $emailExe3data->apv_approve_memo;
        }else{
            $emailExe3data = "";
            $exe3App = "";
            $exe3user = "";
            $exe3datetime = "";
            $exe3memo = "";
        }
            //check ผลการตรวจสอบ
            if($exe3App == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากรองกรรมการผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$exe3memo.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$exe3user.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$exe3datetime.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }


    public function sendto_purchase_G2($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "New PR Awaiting Procurement Investigation. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PR ใหม่ รอจัดซื้อตรวจสอบข้อมูล</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check ผู้จัดการอนุมัติ
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

        $queryExe = getExecutiveData($formno);
        foreach($queryExe->result() as $rs){
            //check ผลการตรวจสอบ
            if($rs->apv_approve == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }
            $body .='
                <tr>
                    <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากรองกรรมการผู้จัดการ</strong></td>
                </tr>
                <tr>
                    <td><strong>ผลการอนุมัติ</strong></td>
                    <td colspan="3">'.$Context.'</td>
                </tr>
                <tr>
                    <td><strong>หมายเหตุ</strong></td>
                    <td colspan="3">'.$rs->apv_approve_memo.'</td>
                </tr>
                <tr>
                    <td><strong>ผู้อนุมัติ</strong></td>
                    <td>'.$rs->apv_approve_user.'</td>
                    <td><strong>วันที่</strong></td>
                    <td>'.$rs->apv_approve_datetime.'</td>
                </tr>
            ';
        }
        

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }


    public function sendto_purchase_G1($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "New PR Awaiting Procurement Investigation. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PR ใหม่ รอจัดซื้อตรวจสอบข้อมูล</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check ผู้จัดการอนุมัติ
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

        $queryExe = getExecutiveData($formno);
        foreach($queryExe->result() as $rs){
            //check ผลการตรวจสอบ
            if($rs->apv_approve == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }
            $body .='
                <tr>
                    <td colspan="4" class="bghead"><strong>ผลการอนุมัติจาก '.$rs->apv_posiname.'</strong></td>
                </tr>
                <tr>
                    <td><strong>ผลการอนุมัติ</strong></td>
                    <td colspan="3">'.$Context.'</td>
                </tr>
                <tr>
                    <td><strong>หมายเหตุ</strong></td>
                    <td colspan="3">'.$rs->apv_approve_memo.'</td>
                </tr>
                <tr>
                    <td><strong>ผู้อนุมัติ</strong></td>
                    <td>'.$rs->apv_approve_user.'</td>
                    <td><strong>วันที่</strong></td>
                    <td>'.$rs->apv_approve_datetime.'</td>
                </tr>
            ';
        }
        

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }


    public function sendto_purchase_G0($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "New PR Awaiting Procurement Investigation. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PR ใหม่ รอจัดซื้อตรวจสอบข้อมูล</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check ผู้จัดการอนุมัติ
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

        $queryExe = getExecutiveData($formno);
        foreach($queryExe->result() as $rs){
            //check ผลการตรวจสอบ
            if($rs->apv_approve == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }
            $body .='
                <tr>
                    <td colspan="4" class="bghead"><strong>ผลการอนุมัติจาก '.$rs->apv_posiname.'</strong></td>
                </tr>
                <tr>
                    <td><strong>ผลการอนุมัติ</strong></td>
                    <td colspan="3">'.$Context.'</td>
                </tr>
                <tr>
                    <td><strong>หมายเหตุ</strong></td>
                    <td colspan="3">'.$rs->apv_approve_memo.'</td>
                </tr>
                <tr>
                    <td><strong>ผู้อนุมัติ</strong></td>
                    <td>'.$rs->apv_approve_user.'</td>
                    <td><strong>วันที่</strong></td>
                    <td>'.$rs->apv_approve_datetime.'</td>
                </tr>
            ';
        }
        

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }


    public function sendto_otherMgr_G4($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "New PR Awaiting Second Manager Approval. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PR ใหม่ รอผู้จัดการท่านที่สองอนุมัติ</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getExecutiveData($formno);//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['apv_email'];
            $ecodeAr[] = $rs['apv_ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }


    public function sendto_Exe_G3($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "New PR Awaiting Executive Group 3 Approval. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PR ใหม่ รอรองกรรมการผู้จัดการ อนุมัติ</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getExecutiveData($formno);//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['apv_email'];
            $ecodeAr[] = $rs['apv_ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }

    public function sendto_Exe_G2($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "New PR Awaiting Executive Group 2 Approval. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PR ใหม่ รอรองกรรมการผู้จัดการ อนุมัติ</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check ผู้จัดการอนุมัติ
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getExecutiveData($formno);//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['apv_email'];
            $ecodeAr[] = $rs['apv_ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }


    public function sendto_Exe_G1($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "New PR Awaiting Executive Group 1 Approval. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PR ใหม่ รอประธานบริหาร หรือ กรรมการผู้จัดการ หรือ รองกรรมการผู้จัดการ อนุมัติ</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check ผู้จัดการอนุมัติ
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getExecutiveData($formno);//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['apv_email'];
            $ecodeAr[] = $rs['apv_ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }


    public function sendto_Exe_G0($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "New PR Awaiting Executive Group 0 Approval. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PR ใหม่ รอกรรมการบริหาร อนุมัติ</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check ผู้จัดการอนุมัติ
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getExecutiveData($formno);//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['apv_email'];
            $ecodeAr[] = $rs['apv_ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }


    public function sendto_createPO_G5($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "Awaiting New PO Creation. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PR ใหม่ รอสร้าง PO</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_pur == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากจัดซื้อ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_pur.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_pur.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_pur.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }

    public function sendto_createPO_G4($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "Awaiting New PO Creation. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PR ใหม่ รอสร้าง PO</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

                
        if(getExecutiveData($formno)->num_rows() > 0){
            $emailExe4data = getExecutiveData($formno)->row();
            $exe4App = $emailExe4data->apv_approve;
            $exe4user = $emailExe4data->apv_approve_user;
            $exe4datetime = $emailExe4data->apv_approve_datetime;
            $exe4memo = $emailExe4data->apv_approve_memo;
        }else{
            $emailExe4data = "";
            $exe4App = "";
            $exe4user = "";
            $exe4datetime = "";
            $exe4memo = "";
        }
            //check ผลการตรวจสอบ
            if($exe4App == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการท่านที่สอง</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$exe4memo.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$exe4user.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$exe4datetime.'</td>
            </tr>
        ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_pur == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากจัดซื้อ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_pur.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_pur.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_pur.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }


    public function sendto_createPO_G3($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "Awaiting New PO Creation. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PR ใหม่ รอสร้าง PO</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check Manager Approve
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

                
        //รองกรรมการผู้จัดการ
        if(getExecutiveData($formno)->num_rows() > 0){
            $emailExe3data = getExecutiveData($formno)->row();
            $exe3App = $emailExe3data->apv_approve;
            $exe3user = $emailExe3data->apv_approve_user;
            $exe3datetime = $emailExe3data->apv_approve_datetime;
            $exe3memo = $emailExe3data->apv_approve_memo;
        }else{
            $emailExe3data = "";
            $exe3App = "";
            $exe3user = "";
            $exe3datetime = "";
            $exe3memo = "";
        }
            //check ผลการตรวจสอบ
            if($exe3App == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากรองกรรมการผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$exe3memo.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$exe3user.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$exe3datetime.'</td>
            </tr>
        ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_pur == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากจัดซื้อ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_pur.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_pur.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_pur.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }


    public function sendto_createPO_G2($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "Awaiting New PO Creation. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PR ใหม่ รอสร้าง PO</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check Manager Approve
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';


            $queryExe = getExecutiveData($formno);
            foreach($queryExe->result() as $rs){
                //check ผลการตรวจสอบ
                if($rs->apv_approve == "yes"){
                    $Context = "อนุมัติ";
                }else{
                    $Context = "ไม่อนุมัติ";
                }
                $body .='
                    <tr>
                        <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากรองกรรมการผู้จัดการ</strong></td>
                    </tr>
                    <tr>
                        <td><strong>ผลการอนุมัติ</strong></td>
                        <td colspan="3">'.$Context.'</td>
                    </tr>
                    <tr>
                        <td><strong>หมายเหตุ</strong></td>
                        <td colspan="3">'.$rs->apv_approve_memo.'</td>
                    </tr>
                    <tr>
                        <td><strong>ผู้อนุมัติ</strong></td>
                        <td>'.$rs->apv_approve_user.'</td>
                        <td><strong>วันที่</strong></td>
                        <td>'.$rs->apv_approve_datetime.'</td>
                    </tr>
                ';
            }

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_pur == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากจัดซื้อ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_pur.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_pur.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_pur.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }


    public function sendto_createPO_G1($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "Awaiting New PO Creation. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PR ใหม่ รอสร้าง PO</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check Manager Approve
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

            $queryExe = getExecutiveData($formno);
            foreach($queryExe->result() as $rs){
                //check ผลการตรวจสอบ
                if($rs->apv_approve == "yes"){
                    $Context = "อนุมัติ";
                }else{
                    $Context = "ไม่อนุมัติ";
                }
                $body .='
                    <tr>
                        <td colspan="4" class="bghead"><strong>ผลการอนุมัติจาก '.$rs->apv_posiname.'</strong></td>
                    </tr>
                    <tr>
                        <td><strong>ผลการอนุมัติ</strong></td>
                        <td colspan="3">'.$Context.'</td>
                    </tr>
                    <tr>
                        <td><strong>หมายเหตุ</strong></td>
                        <td colspan="3">'.$rs->apv_approve_memo.'</td>
                    </tr>
                    <tr>
                        <td><strong>ผู้อนุมัติ</strong></td>
                        <td>'.$rs->apv_approve_user.'</td>
                        <td><strong>วันที่</strong></td>
                        <td>'.$rs->apv_approve_datetime.'</td>
                    </tr>
                ';
            }

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_pur == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากจัดซื้อ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_pur.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_pur.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_pur.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }


    public function sendto_createPO_G0($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "Awaiting New PO Creation. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PR ใหม่ รอสร้าง PO</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check Manager Approve
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

            $queryExe = getExecutiveData($formno);
            foreach($queryExe->result() as $rs){
                //check ผลการตรวจสอบ
                if($rs->apv_approve == "yes"){
                    $Context = "อนุมัติ";
                }else{
                    $Context = "ไม่อนุมัติ";
                }
                $body .='
                    <tr>
                        <td colspan="4" class="bghead"><strong>ผลการอนุมัติจาก '.$rs->apv_posiname.'</strong></td>
                    </tr>
                    <tr>
                        <td><strong>ผลการอนุมัติ</strong></td>
                        <td colspan="3">'.$Context.'</td>
                    </tr>
                    <tr>
                        <td><strong>หมายเหตุ</strong></td>
                        <td colspan="3">'.$rs->apv_approve_memo.'</td>
                    </tr>
                    <tr>
                        <td><strong>ผู้อนุมัติ</strong></td>
                        <td>'.$rs->apv_approve_user.'</td>
                        <td><strong>วันที่</strong></td>
                        <td>'.$rs->apv_approve_datetime.'</td>
                    </tr>
                ';
            }

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_pur == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากจัดซื้อ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_pur.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_pur.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_pur.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }


    public function sendto_userRequest_G5($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "Awaiting Sending PO to Vendor. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PO ใหม่ รอส่งให้ผู้ขาย (Vendor)</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_pur == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากจัดซื้อ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_pur.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_pur.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_pur.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';


        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการสร้าง PO</strong></td>
            </tr>
            <tr>
                <td><strong>สถานะ</strong></td>
                <td>'.$emaildata->m_status.'</td>
                <td><strong>เลขที่ PO</strong></td>
                <td>'.$emaildata->m_pono.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_pocon_datetime.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        // $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }

    public function sendto_userRequest_G4($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "Awaiting Sending PO to Vendor. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PO ใหม่ รอส่งให้ผู้ขาย (Vendor)</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';


        if(getExecutiveData($formno)->num_rows() > 0){
            $emailExe4data = getExecutiveData($formno)->row();
            $exe4App = $emailExe4data->apv_approve;
            $exe4user = $emailExe4data->apv_approve_user;
            $exe4datetime = $emailExe4data->apv_approve_datetime;
            $exe4memo = $emailExe4data->apv_approve_memo;
        }else{
            $emailExe4data = "";
            $exe4App = "";
            $exe4user = "";
            $exe4datetime = "";
            $exe4memo = "";
        }
            //check ผลการตรวจสอบ
            if($exe4App == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการท่านที่สอง</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$exe4memo.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$exe4user.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$exe4datetime.'</td>
            </tr>
        ';


            //check ผลการตรวจสอบ
            if($emaildata->m_approve_pur == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากจัดซื้อ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_pur.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_pur.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_pur.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';


        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการสร้าง PO</strong></td>
            </tr>
            <tr>
                <td><strong>สถานะ</strong></td>
                <td>'.$emaildata->m_status.'</td>
                <td><strong>เลขที่ PO</strong></td>
                <td>'.$emaildata->m_pono.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_pocon_datetime.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        // $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }

    public function sendto_userRequest_G3($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "Awaiting Sending PO to Vendor. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PO ใหม่ รอส่งให้ผู้ขาย (Vendor)</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check ผู้จัดการอนุมัติ
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';


        //รองกรรมการผู้จัดการ
        if(getExecutiveData($formno)->num_rows() > 0){
            $emailExe3data = getExecutiveData($formno)->row();
            $exe3App = $emailExe3data->apv_approve;
            $exe3user = $emailExe3data->apv_approve_user;
            $exe3datetime = $emailExe3data->apv_approve_datetime;
            $exe3memo = $emailExe3data->apv_approve_memo;
        }else{
            $emailExe3data = "";
            $exe3App = "";
            $exe3user = "";
            $exe3datetime = "";
            $exe3memo = "";
        }
            //check ผลการตรวจสอบ
            if($exe3App == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากรองกรรมการผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$exe3memo.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$exe3user.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$exe3datetime.'</td>
            </tr>
        ';


            //check ผลการตรวจสอบ
            if($emaildata->m_approve_pur == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากจัดซื้อ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_pur.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_pur.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_pur.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';


        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการสร้าง PO</strong></td>
            </tr>
            <tr>
                <td><strong>สถานะ</strong></td>
                <td>'.$emaildata->m_status.'</td>
                <td><strong>เลขที่ PO</strong></td>
                <td>'.$emaildata->m_pono.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_pocon_datetime.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        // $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }

    public function sendto_userRequest_G2($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "Awaiting Sending PO to Vendor. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PO ใหม่ รอส่งให้ผู้ขาย (Vendor)</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check ผู้จัดการอนุมัติ
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';



            $queryExe = getExecutiveData($formno);
            foreach($queryExe->result() as $rs){
                //check ผลการตรวจสอบ
                if($rs->apv_approve == "yes"){
                    $Context = "อนุมัติ";
                }else{
                    $Context = "ไม่อนุมัติ";
                }
                $body .='
                    <tr>
                        <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากรองกรรมการผู้จัดการ</strong></td>
                    </tr>
                    <tr>
                        <td><strong>ผลการอนุมัติ</strong></td>
                        <td colspan="3">'.$Context.'</td>
                    </tr>
                    <tr>
                        <td><strong>หมายเหตุ</strong></td>
                        <td colspan="3">'.$rs->apv_approve_memo.'</td>
                    </tr>
                    <tr>
                        <td><strong>ผู้อนุมัติ</strong></td>
                        <td>'.$rs->apv_approve_user.'</td>
                        <td><strong>วันที่</strong></td>
                        <td>'.$rs->apv_approve_datetime.'</td>
                    </tr>
                ';
            }


            //check ผลการตรวจสอบ
            if($emaildata->m_approve_pur == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }
        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากจัดซื้อ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_pur.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_pur.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_pur.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';


        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการสร้าง PO</strong></td>
            </tr>
            <tr>
                <td><strong>สถานะ</strong></td>
                <td>'.$emaildata->m_status.'</td>
                <td><strong>เลขที่ PO</strong></td>
                <td>'.$emaildata->m_pono.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_pocon_datetime.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        // $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }

    public function sendto_userRequest_G1($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "Awaiting Sending PO to Vendor. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PO ใหม่ รอส่งให้ผู้ขาย (Vendor)</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check ผู้จัดการอนุมัติ
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';



            $queryExe = getExecutiveData($formno);
            foreach($queryExe->result() as $rs){
                //check ผลการตรวจสอบ
                if($rs->apv_approve == "yes"){
                    $Context = "อนุมัติ";
                }else{
                    $Context = "ไม่อนุมัติ";
                }
                $body .='
                    <tr>
                        <td colspan="4" class="bghead"><strong>ผลการอนุมัติจาก '.$rs->apv_posiname.'</strong></td>
                    </tr>
                    <tr>
                        <td><strong>ผลการอนุมัติ</strong></td>
                        <td colspan="3">'.$Context.'</td>
                    </tr>
                    <tr>
                        <td><strong>หมายเหตุ</strong></td>
                        <td colspan="3">'.$rs->apv_approve_memo.'</td>
                    </tr>
                    <tr>
                        <td><strong>ผู้อนุมัติ</strong></td>
                        <td>'.$rs->apv_approve_user.'</td>
                        <td><strong>วันที่</strong></td>
                        <td>'.$rs->apv_approve_datetime.'</td>
                    </tr>
                ';
            }


            //check ผลการตรวจสอบ
            if($emaildata->m_approve_pur == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }
        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากจัดซื้อ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_pur.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_pur.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_pur.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';


        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการสร้าง PO</strong></td>
            </tr>
            <tr>
                <td><strong>สถานะ</strong></td>
                <td>'.$emaildata->m_status.'</td>
                <td><strong>เลขที่ PO</strong></td>
                <td>'.$emaildata->m_pono.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_pocon_datetime.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        // $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }

    public function sendto_userRequest_G0($formno)
    {

        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $subject = "Awaiting Sending PO to Vendor. [ $formno ]";

        $short_url = $adminLink;
        $emaildata = getdataforemail($formno);

        $deptname = getDepartmentName($emaildata->m_dataareaid , $emaildata->m_department)->description;
        $userRequest = getuserdata($emaildata->m_ecode)->Fname." ".getuserdata($emaildata->m_ecode)->Lname;

        $body = '
            <h2>มีรายการ PO ใหม่ รอส่งให้ผู้ขาย (Vendor)</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>'.$emaildata->m_formno.'</td>
                <td><strong>วันที่</strong></td>
                <td>'.$emaildata->m_datetime_create.'</td>
            </tr>


            <tr>
                <td><strong>หมวดหมู่สินค้า</strong></td>
                <td>'.conTypeofItemcat($emaildata->m_itemcategory).'</td>
                <td><strong>เลขที่ PR</strong></td>
                <td>'.$emaildata->m_prno.'</td>
            </tr>

            <tr>
                <td><strong>แผนกที่ขอซื้อ</strong></td>
                <td>'.$deptname.'</td>
                <td><strong>ผู้ขอซื้อ</strong></td>
                <td>'.$userRequest.'</td>
            </tr>

            <tr>
                <td><strong>รหัสผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendid.'</td>
                <td><strong>ชื่อผู้ขาย</strong></td>
                <td>'.$emaildata->m_vendname.'</td>
            </tr>

            <tr>
                <td><strong>วันที่ขอซื้อ</strong></td>
                <td>'.$emaildata->m_date_req.'</td>
                <td><strong>วันที่จัดส่ง</strong></td>
                <td>'.$emaildata->m_date_delivery.'</td>
            </tr>

            <tr>
                <td><strong>ยอดเงินรวม</strong></td>
                <td colspan="3">'.number_format($emaildata->totalprice , 2).'</td>
            </tr>

            ';

            //check ผลการตรวจสอบ
            if($emaildata->m_approve_invest == "yes"){
                $investContext = "อนุมัติ";
            }else{
                $investContext = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">'.$investContext.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_invest.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_invest.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_invest.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_invest.'</td>
            </tr>
        ';

            //check ผู้จัดการอนุมัติ
            if($emaildata->m_approve_mgr == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }

        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากผู้จัดการ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_mgr.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_mgr.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_mgr.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';



            $queryExe = getExecutiveData($formno);
            foreach($queryExe->result() as $rs){
                //check ผลการตรวจสอบ
                if($rs->apv_approve == "yes"){
                    $Context = "อนุมัติ";
                }else{
                    $Context = "ไม่อนุมัติ";
                }
                $body .='
                    <tr>
                        <td colspan="4" class="bghead"><strong>ผลการอนุมัติจาก '.$rs->apv_posiname.'</strong></td>
                    </tr>
                    <tr>
                        <td><strong>ผลการอนุมัติ</strong></td>
                        <td colspan="3">'.$Context.'</td>
                    </tr>
                    <tr>
                        <td><strong>หมายเหตุ</strong></td>
                        <td colspan="3">'.$rs->apv_approve_memo.'</td>
                    </tr>
                    <tr>
                        <td><strong>ผู้อนุมัติ</strong></td>
                        <td>'.$rs->apv_approve_user.'</td>
                        <td><strong>วันที่</strong></td>
                        <td>'.$rs->apv_approve_datetime.'</td>
                    </tr>
                ';
            }


            //check ผลการตรวจสอบ
            if($emaildata->m_approve_pur == "yes"){
                $Context = "อนุมัติ";
            }else{
                $Context = "ไม่อนุมัติ";
            }
        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการอนุมัติจากจัดซื้อ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการอนุมัติ</strong></td>
                <td colspan="3">'.$Context.'</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">'.$emaildata->m_memo_pur.'</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>'.$emaildata->m_userpost_pur.'</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>'.$emaildata->m_ecodepost_pur.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_datetimepost_mgr.'</td>
            </tr>
        ';


        $body .='
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการสร้าง PO</strong></td>
            </tr>
            <tr>
                <td><strong>สถานะ</strong></td>
                <td>'.$emaildata->m_status.'</td>
                <td><strong>เลขที่ PO</strong></td>
                <td>'.$emaildata->m_pono.'</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">'.$emaildata->m_pocon_datetime.'</td>
            </tr>
        ';

            
        $body .='
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="'.$adminLink.'">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url , $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = array();
        $cc = array();

        $ecodeAr = array();
        $ecodeccAr = array();

        //  Email Zone
        $optionTo = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }


        $optionCc = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        foreach($optionCc->result_array() as $rs){
            $cc[] = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        foreach($optionCC2->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $optionCC3 = getemail_managerbydeptcode($emaildata->m_department , $emaildata->m_dataareaid);//cc ผู้จัดการ
        foreach($optionCC3->result_array() as $rs){
            array_push($cc , $rs['memberemail']);
            array_push($ecodeccAr , $rs['ecode']);
        }

        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeAr = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        // $this->notifycenter->insertdataaction_template($ecodeActionArr , $title , $status , $link , $formno , $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }

    public function sendto_vendor($formno , $filePath , $poemail)
    {
        if($_SERVER['HTTP_HOST'] == "localhost"){
            $adminLink = "http://localhost:8080/viewdata/$formno";
        }else if($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com"){
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }else{
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/viewdata/$formno";
        }

        $emaildata = getdataforemail($formno);
        $subject = "แจ้งส่งเอกสารใบสั่งซื้อเลขที่ ".$emaildata->m_pono;
        $body = '
        <h2>เรียน '.$emaildata->m_vendname.'</h2>
        <div>
            <span style="font-size:16px;">ขอนำส่งใบสั่งซื้อจาก '.getCompanynameTH($emaildata->m_dataareaid).'</span>
        </div>
        <div>
            <p><b>หมายเหตุ : </b> รบกวนตรวจสอบรายละเอียดในใบสั่งซื้อ (ชื่อ-ที่อยู่, รายการสินค้า, จำนวน, ราคา,เครดิต และวันส่งของ ) เซ็นต์รับทราบในใบสั่งซื้อและส่งกลับมาที่ email  purchase@saleecolour.com </p>
            <p><a href="https://intranet.saleecolour.com/intsys/ebilling/" target="_blank">วางบิลออนไลน์ ระบบ e-Billing ( กำหนดวางบิลตามตารางวางบิลรอบบัญชี )</a></p>
        </div>
        <p style="color:#CC0000;">**อีเมลฉบับนี้เป็นระบบอัตโนมัติ โปรดอย่าตอบกลับ**</p>

        ';

        $to = array();
        $cc = array();

        $ecodeccAr = array();

        //  Email Zone
        // $optionTo = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        // foreach($optionTo->result_array() as $rs){
        //     $t
        // $vendEmail = getVendEmail($emaildata->m_vendid , $emaildata->m_dataareaid);
        $vendEmail = $poemail;
        // $venderEmail = "chainarong039@gmail.com,chainarong_kid@hotmail.com";
        if($vendEmail != ""){
            $to = conEmailString($vendEmail);
        }else{
            $to = [];
        }


        $optionCC = getemail_byecode($emaildata->m_ecode);//ผู้ขอซื้อ
        if($optionCC->num_rows() > 0){
            foreach($optionCC->result_array() as $rs){
                $cc[] = $rs['memberemail'];
                $ecodeccAr[] = $rs['ecode'];
            }
        }


        $optionCC2 = getemail_byecode($emaildata->m_ecodepost);//ผู้ร้องขอ
        if($optionCC2->num_rows() > 0){
            foreach($optionCC2->result_array() as $rs){
                array_push($cc , $rs['memberemail']);
                array_push($ecodeccAr , $rs['ecode']);
            }
        }


        $optionCC3 = getemail_bydeptcode("1004");//ดึงเอาเฉพาะ Email ของจัดซื้อขึ้นมา
        if($optionCC3->num_rows() > 0){
            foreach($optionCC3->result_array() as $rs){
                array_push($cc , $rs['memberemail']);
                array_push($ecodeccAr , $rs['ecode']);
            }
        }


        $to = array_unique($to);
        $cc = array_unique($cc);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject , $body , $to , $cc , $filePath);
        //  Email Zone

        // Notification center program
        $ecodeReadArr = $ecodeccAr;

        $title = $subject;
        $status = $emaildata->m_status;
        $link = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataRead_template($ecodeReadArr , $title , $status , $link , $formno , $programname);
    }




}

/* End of file ModelName.php */

?>