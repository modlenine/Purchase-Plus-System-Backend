<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Purchaseplusapi{

    protected $ci;

    public function __construct(){
        $this->ci =& get_instance();
    }


    public function update_po($purchid , $prid , $areaid)
    {
        $apiData = array(
            "po_no" => $purchid, 
            "pr_no" => $prid,
            "areaid" => $areaid
        );
  
        $this->request_api($apiData);
    }

    // public function insertdataRead_template($ecodeArray , $title , $status , $link , $formno , $programname)
    // {
    //     $notifyData = [];
    //     foreach($ecodeArray as $ecodeArrays){
    //        //code
    //        $detail = array(
    //           "title" => $title,
    //           "status" => $status,
    //           "link" => $link
    //        );
  
    //        $notify_formno = $formno;
    //        $notify_programname = $programname;
    //        $notify_ecode = $ecodeArrays;
    //        $notify_details = $detail;
    //        $notify_type = "read only";
    //        $notify_status = "wait read";
    //        $notify_programstatus = $status;
  
    //        $dataarray = array(
    //           "notify_formno" => $notify_formno,
    //           "notify_programname" => $notify_programname,
    //           "notify_title" => $title ,
    //           "notify_programstatus" => $notify_programstatus,
    //           "notify_ecode" => $notify_ecode,
    //           "notify_details" => $notify_details,
    //           "notify_type" => $notify_type,
    //           "notify_status" => $notify_status
    //        );
  
    //        $notifyData[] = $dataarray;
     
    //        // $this->notifycenter->savedataNotify($notify_formno , $notify_programname , $notify_ecode , $notify_details , $notify_type , $notify_status);
    //     }
  
    //     $this->request_api($notifyData);
    // }



    public function request_api($data)
    {
        // แปลงข้อมูลเป็น JSON string
        $json_data = json_encode($data);

        // กำหนด URL ของ API
        if($_SERVER['HTTP_HOST'] == "localhost"){
            $baseurl = "http://localhost/";
        }else if($_SERVER['HTTP_HOST'] == "intracent.saleecolour.com"){
            $baseurl = "http://intracent.saleecolour.com/";
        }else{
            $baseurl = "https://intranet.saleecolour.com/";
        }
        $url = $baseurl."intsys/purchaseplus/purchaseplus_backend/purchaseapi/getdata";

        // สร้าง cURL resource
        $curl = curl_init($url);

        // ตั้งค่าสำหรับ cURL
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json_data))
        );

        // ส่ง API request และรับ response
        $response = curl_exec($curl);

        // ตรวจสอบว่ามีข้อผิดพลาดหรือไม่
        if ($response === false) {
            $error_message = curl_error($curl);
            // จัดการข้อผิดพลาด
        } else {
            // ดำเนินการต่อไปกับ response
            // echo $response;
        }

        // ปิด cURL resource
        curl_close($curl);
    }

    public function cancel_api($data)
    {
        // แปลงข้อมูลเป็น JSON string
        $json_data = json_encode($data);

        // กำหนด URL ของ API
        if($_SERVER['HTTP_HOST'] == "localhost"){
            $baseurl = "http://localhost/";
        }else if($_SERVER['HTTP_HOST'] == "intracent.saleecolour.com"){
            $baseurl = "http://intracent.saleecolour.com/";
        }else{
            $baseurl = "https://intranet.saleecolour.com/";
        }
        $url = $baseurl."intranet/api/cancel_api";

        // สร้าง cURL resource
        $curl = curl_init($url);

        // ตั้งค่าสำหรับ cURL
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json_data))
        );

        // ส่ง API request และรับ response
        $response = curl_exec($curl);

        // ตรวจสอบว่ามีข้อผิดพลาดหรือไม่
        if ($response === false) {
            $error_message = curl_error($curl);
            // จัดการข้อผิดพลาด
        } else {
            // ดำเนินการต่อไปกับ response
            // echo $response;
        }

        // ปิด cURL resource
        curl_close($curl);
    }

    public function update_api($data)
    {
        // แปลงข้อมูลเป็น JSON string
        $json_data = json_encode($data);

        // กำหนด URL ของ API
        if($_SERVER['HTTP_HOST'] == "localhost"){
            $baseurl = "http://localhost/";
        }else if($_SERVER['HTTP_HOST'] == "intracent.saleecolour.com"){
            $baseurl = "http://intracent.saleecolour.com/";
        }else{
            $baseurl = "https://intranet.saleecolour.com/";
        }
        $url = $baseurl."intranet/api/update_api";

        // สร้าง cURL resource
        $curl = curl_init($url);

        // ตั้งค่าสำหรับ cURL
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json_data))
        );

        // ส่ง API request และรับ response
        $response = curl_exec($curl);

        // ตรวจสอบว่ามีข้อผิดพลาดหรือไม่
        if ($response === false) {
            $error_message = curl_error($curl);
            // จัดการข้อผิดพลาด
        } else {
            // ดำเนินการต่อไปกับ response
            // echo $response;
        }

        // ปิด cURL resource
        curl_close($curl);
    }

    public function testcall($vendid , $areaid)
    {
        if($vendid != "" && $areaid != ""){
            $output = array(
                "vendid" => $vendid,
                "areaid" => $areaid
            );

            $this->request_api($output);
        }
    }



}

/* End of file Controllername.php */




?>