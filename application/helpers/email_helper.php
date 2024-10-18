<?php
class emailfn{
    public $ci;
    function __construct()
    {
        $this->ci = &get_instance();
        date_default_timezone_set("Asia/Bangkok");
    }
    public function gci()
    {
        return $this->ci;
    }
}



function email()
{
    $obj = new emailfn();
    return $obj->gci();
}

function getdataforemail($formno)
{
    if(!empty($formno)){
        $sql = email()->db->query("SELECT
        main.m_autoid,
        main.m_formno,
        main.m_prcode,
        main.m_prno,
        main.m_dataareaid,
        main.m_plantype,
        main.m_itemcategory,
        main.m_costcenter,
        main.m_department,
        main.m_ecode,
        main.m_vendid,
        main.m_vendname,
        main.m_paymtermid,
        DATE_FORMAT(main.m_datetime_create , '%d-%m-%Y %H:%i:%s') AS m_datetime_create,
        DATE_FORMAT(main.m_date_req , '%d-%m-%Y') AS m_date_req,
        DATE_FORMAT(main.m_date_delivery , '%d-%m-%Y') AS m_date_delivery,
        main.m_memo,
        main.m_status,
        main.m_userpost,
        main.m_ecodepost,
        DATE_FORMAT(main.m_datetimepost , '%d-%m-%Y %H:%i:%s') AS m_datetimepost,
        main.m_userpost_modify,
        main.m_ecodepost_modify,
        DATE_FORMAT(main.m_datetimepost_modify , '%d-%m-%Y %H:%i:%s') AS m_datetimepost_modify,
        main.m_invest_ecodefix,
        main.m_approve_invest,
        main.m_memo_invest,
        main.m_userpost_invest,
        main.m_ecodepost_invest,
        DATE_FORMAT(main.m_datetimepost_invest , '%d-%m-%Y %H:%i:%s') AS m_datetimepost_invest,
        main.m_approve_mgr,
        main.m_memo_mgr,
        main.m_userpost_mgr,
        main.m_ecodepost_mgr,
        DATE_FORMAT(main.m_datetimepost_mgr , '%d-%m-%Y %H:%i:%s') AS m_datetimepost_mgr,
        main.m_approve_pur,
        main.m_memo_pur,
        main.m_userpost_pur,
        main.m_ecodepost_pur,
        DATE_FORMAT(main.m_datetimepost_pur , '%d-%m-%Y %H:%i:%s') AS m_datetimepost_pur,
        main.m_datetimeupdate,
        main.m_pono,
        DATE_FORMAT(main.m_pocon_datetime , '%d-%m-%Y %H:%i:%s') AS m_pocon_datetime,
        main.m_version_pr,
        main.m_version_status,
        main.m_formisono,
        main.m_formisono_po,
        SUM(details.d_itempricesum) as totalprice
        FROM
        main
        INNER JOIN details ON details.d_m_formno = main.m_formno AND details.d_m_prno = main.m_prno
        WHERE
        main.m_formno = '$formno'
        ");

        if($sql->num_rows() > 0){
            return $sql->row();
        }else{
            return false;
        }
    }
}

function getEmailUser()
{
    $query = email()->db->query("SELECT * FROM email_information WHERE email_id = 1");
    return $query->row();
}

function getEmailUser2()
{
    $query = email()->db->query("SELECT * FROM email_information WHERE email_id = 2");
    return $query->row();
}

function sendemail($subject , $body , $to , $cc , $pathfile)
{
    require("PHPMailer_5.2.0/class.phpmailer.php");
    require("PHPMailer_5.2.0/class.smtp.php");

    $mail = new PHPMailer();

    try {
        // Server settings
        $mail->CharSet      = "utf-8";  // ในส่วนนี้ ถ้าระบบเราใช้ tis-620 หรือ windows-874 สามารถแก้ไขเปลี่ยนได้
        $mail->SMTPDebug    = 0;                                       // Enable verbose debug output
        $mail->isSMTP();                                            // Set mailer to use SMTP
        $mail->Host         = 'mail.saleecolour.net';                     // Specify main and backup SMTP servers
        $mail->SMTPAuth     = true;                                   // Enable SMTP authentication
        $mail->Username     = getEmailUser()->email_user;               // SMTP username
        $mail->Password     = getEmailUser()->email_password;          // SMTP password
        $mail->From         = getEmailUser()->email_user;
        $mail->FromName     = "Purchase Plus System";
        $mail->Port         = 587;                                    // TCP port to connect to

        if(!empty($to)){
            foreach($to as $email){
                $mail->AddAddress($email);
            }
        }

        // foreach($to as $email){
        //     $mail->AddAddress($email);
        // }

        if(!empty($cc)){
            foreach($cc as $email){
                $mail->AddCC($email);
            }
        }

        // foreach($cc as $email){
        //     $mail->AddCC($email);
        // }

        // $mail->AddAddress("chainarong039@gmail.com");
        $mail->AddBCC("chainarong_k@saleecolour.com");

        // Attachments
        // $file_to_attach = 'uploads/example_001.pdf';
        if(!empty($pathfile)){
            $file_to_attach = $pathfile;
            $mail->addAttachment($file_to_attach);  
        }
       // Add attachments

        $mail->WordWrap = 50;                          // set word wrap to 50 characters
        $mail->IsHTML(true);                           // set email format to HTML
        $mail->Subject = $subject;
        $mail->Body = '
        <style>
            @import url("https://fonts.googleapis.com/css2?family=Sarabun&display=swap");
    
            h3{
                font-family: Tahoma, sans-serif;
                font-size:14px;
            }
    
            table {
                font-family: Tahoma, sans-serif;
                font-size:14px;
                border-collapse: collapse;
                width: 90%;
              }
              
              td, th {
                border: 1px solid #ccc;
                text-align: left;
                padding: 8px;
              }
              
              tr:nth-child(even) {
                background-color: #F5F5F5;
              }
    
              .bghead{
                  text-align:center;
                  background-color:#D3D3D3;
              }
            </style>
        '.$body;
        if($_SERVER['HTTP_HOST'] != "localhost"){
            $mail->send();
        }
        return 'Message has been sent';
    } catch (Exception $e) {
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

function getemail_byecode($ecode)
{
    if($ecode != ""){
        email()->db2 = email()->load->database('saleecolour', TRUE);
        $sql = email()->db2->query("SELECT
        memberemail , 
        ecode
        FROM member WHERE ecode = '$ecode' AND resigned = 0
        ");
        return $sql;
    }
}

function getemail_bydeptcode($deptcode)
{
    if(!empty($deptcode)){
        if($deptcode == 1004){
            email()->db2 = email()->load->database('saleecolour', TRUE);
            $sql = email()->db2->query("SELECT
            memberemail , ecode
            FROM member WHERE DeptCode = '$deptcode' AND ecode NOT IN ('M0245') AND resigned = 0
            ");
        }else{
            email()->db2 = email()->load->database('saleecolour', TRUE);
            $sql = email()->db2->query("SELECT
            memberemail , ecode
            FROM member WHERE DeptCode = '$deptcode' AND resigned = 0
            ");
        }

        return $sql;
    }else{
        return null;
    }
}

function getemail_managerbydeptcode($deptcode , $areaid)
{
    if(!empty($deptcode)){
        if($deptcode == 1007){
            if($areaid == "tb"){
                email()->db2 = email()->load->database('saleecolour', TRUE);
                $sql = email()->db2->query("SELECT
                memberemail , ecode
                FROM member WHERE areaid = 'tb' AND resigned = 0
                ");
            }else if($areaid == "st"){
                email()->db2 = email()->load->database('saleecolour', TRUE);
                $sql = email()->db2->query("SELECT
                memberemail , ecode
                FROM member WHERE areaid = 'st' AND resigned = 0
                ");
            }else{
                email()->db2 = email()->load->database('saleecolour', TRUE);
                $sql = email()->db2->query("SELECT
                memberemail , ecode
                FROM member WHERE ecode IN ('M0506' , 'M0040')
                ");
            }

        }else if($deptcode == 1001){
            email()->db2 = email()->load->database('saleecolour', TRUE);
            $sql = email()->db2->query("SELECT
            memberemail , ecode
            FROM member WHERE ecode = 'M0015'
            ");
        }else if($deptcode == 1008 || $deptcode == 1014 || $deptcode == 1015){
            email()->db2 = email()->load->database('saleecolour', TRUE);
            $sql = email()->db2->query("SELECT
            memberemail , ecode
            FROM member WHERE ecode = 'M0112'
            ");
        }else if($deptcode == 1012){
            email()->db2 = email()->load->database('saleecolour', TRUE);
            $sql = email()->db2->query("SELECT
            memberemail , ecode
            FROM member WHERE ecode = 'M0025'
            ");
        }else{
            email()->db2 = email()->load->database('saleecolour', TRUE);
            $sql = email()->db2->query("SELECT
            memberemail , ecode
            FROM member WHERE DeptCode = '$deptcode' AND posi IN (65 , 75 ) AND resigned = 0
            ");
        }

        return $sql;
    }else{
        return null;
    }
}

function conTypeofItemcat($itemcat)
{
    if(!empty($itemcat)){
        switch($itemcat){
            case "raw_materials":
                $contype = "วัตถุดิบ";
                break;
            case "expenses":
                $contype = "ค่าใช้จ่าย";
                break;
            case "assets":
                $contype = "ทรัพย์สิน";
                break;
            default:
            $contype = $itemcat;
        }

        return $contype;
    }else{
        return "";
    }
}

function conEmailString($emailString)
{
    if(!empty($emailString)){
        return explode(',' , $emailString);
    }
}

function getVendEmail($vendid , $areaid)
{
    if(!empty($vendid) && !empty($areaid)){
        email()->db_mssql = email()->load->database("mssql" , true);
        $sql = email()->db_mssql->query("SELECT email FROM vendtable WHERE accountnum = '$vendid' AND dataareaid = '$areaid'");
        // check data null
        if($sql->num_rows() == 0){
            return "";
        }else{
            return $sql->row()->email;
        }
    }
}










?>