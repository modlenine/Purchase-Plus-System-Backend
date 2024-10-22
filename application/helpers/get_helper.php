<?php
class getfn{
    public $ci;
    public function __construct(){
        $this->ci =& get_instance();
        date_default_timezone_set("Asia/Bangkok");
    }
    public function gci(){
        return $this->ci;
    }

}

function getfn()
{
    $obj = new getfn();
    return $obj->gci();
}

function getDb()
{
    if($_SERVER['HTTP_HOST'] == "localhost"){
        $sql = getfn()->db->query("SELECT * FROM db WHERE db_autoid = '2' ");
    }else{
        $sql = getfn()->db->query("SELECT * FROM db WHERE db_autoid = '1' ");
    }
    
    return $sql->row();
}


//Convert Zone
function concode($oriCode)
{
    if($oriCode != ""){
        switch($oriCode){
            case "PRP53":
                return "PR";
                break;
            case "PRC53":
                return "PRC";
                break;
            case "PRI53":
                return "PRI";
                break;
            case "PRE53":
                return "PRE";
                break;
            case "FC10";
                return "FC";
                break;
        }
    }
}

function getViewurl()
{
    if($_SERVER['HTTP_HOST'] == "localhost"){
        $viewurl = "/intsys/purchaseplus/";
    }else{
        $mysqlServer = "/";
    }
}

function condate_todb($date)
{
    if($date != ""){
        $dateOri = date_create($date);
        return date_format($dateOri , "Y-m-d");
    }
}

function condate_fromdb($date)
{
    if($date != ""){
        $dateOri = date_create($date);
        return date_format($dateOri , "d/m/Y");
    }
}

//Convert Zone

function testcut()
{
    $fulltext = "PR6700001";
    $cutno = substr($fulltext , -5 , 5);
    $cutno++;
    echo $cutno;
}

function generateApiKey() {
    return bin2hex(random_bytes(16)); // สร้าง API Key ความยาว 32 ตัวอักษร
}

function getPrno($prcode , $areaid)
{
    // check formno ซ้ำในระบบ
    $sql = getfn()->db->query("SELECT
    m_prno FROM main WHERE m_prcode = '$prcode' AND m_dataareaid = '$areaid' ORDER BY m_datetime_create DESC LIMIT 1
    ");

    $curYear = date("Y");
    $thisYearTH = $curYear+543;
    $cutYear = substr($thisYearTH, 2, 2);
    $prno = "";
    // check pr code
    $nextRuning = "";

    if($areaid == "sln"){
        if($prcode == "PRC"){
            $nextRuning = "00949";
        }else if($prcode == "PRE"){
            $nextRuning = "01301";
        }
    }else if($areaid == "ca"){
        if($prcode == "PRC"){
            $nextRuning = "00007";
        }else if($prcode == "PRE"){
            $nextRuning = "00255";
        }
    }



    if ($sql->num_rows() == 0) {
        $prno = $prcode . $cutYear. $nextRuning;
    } else {
        $getPrno = $sql->row()->m_prno;
        $cutGetYear = substr($getPrno, -7, 2); //PR67123456 => 67
        $cutNo = substr($getPrno, -5, 5); //อันนี้ตัดเอามาแค่ตัวเลขจาก CP2003000001 ตัดเหลือ 000001
        $cutNo++;

        if ($cutNo < 10) {
            $cutNo = "0000" . $cutNo;
        } else if ($cutNo < 100) {
            $cutNo = "000" . $cutNo;
        }else if($cutNo < 1000){
            $cutNo = "00" . $cutNo;
        }else if($cutNo < 10000){
            $cutNo = "0" . $cutNo;
        }

        if ($cutGetYear != $cutYear) {
            $prno = $prcode.$cutYear."00001";
        } else {
            $prno = $prcode.$cutGetYear. $cutNo;
        }
    }
    return $prno;
}

function getFormno()
{
    // check formno ซ้ำในระบบ
    $sql = getfn()->db->query("SELECT
    m_formno FROM main ORDER BY m_autoid DESC LIMIT 1
    ");

    $cutYear = substr(date("Y"), 2, 2);
    $getMonth = substr(date("m"), 0, 2);
    $formno = "";
    if ($sql->num_rows() == 0) {
        $formno = "PU" . $cutYear.$getMonth. "000001";
    } else {
        $getFormno = $sql->row()->m_formno;
        $cutGetYear = substr($getFormno, 2, 2); //KB2003001
        $cutNo = substr($getFormno, 6, 6); //อันนี้ตัดเอามาแค่ตัวเลขจาก CP2003000001 ตัดเหลือ 000001
        $cutNo++;

        if ($cutNo < 10) {
            $cutNo = "00000" . $cutNo;
        } else if ($cutNo < 100) {
            $cutNo = "0000" . $cutNo;
        }else if($cutNo < 1000){
            $cutNo = "000" . $cutNo;
        }else if($cutNo < 10000){
            $cutNo = "00" . $cutNo;
        }else if($cutNo < 100000){
            $cutNo = "0" . $cutNo;
        }

        if ($cutGetYear != $cutYear) {
            $formno = "PU" . $cutYear.$getMonth."000001";
        } else {
            $formno = "PU" . $cutGetYear.$getMonth. $cutNo;
        }
    }
    return $formno;
}

function sumPriceByFormno($formno)
{
    if(!empty($formno)){
        $sql = getfn()->db->query("SELECT
        sum(d_itempricesum)as sumprice
        FROM details WHERE d_m_formno = '$formno'
        ");

        return $sql->row()->sumprice;
    }
}

function getRuningCode()
{
    //100 = wdf , 200 = adv , 300 = sal , 400 = po
    //stepcode fnc = 719 , apc = 729 , accc = 739 , mgrc = 749 , fnc2 = 759 , urc2 = 769 
    $date = date_create();
    $dateTimeStamp = date_timestamp_get($date);
    return $dateTimeStamp;
}

function getDepartmentName($areaid , $deptcode)
{
    if(!empty($deptcode)){
        getfn()->db_mssql = getfn()->load->database("mssql" , TRUE);
        $sql = getfn()->db_mssql->query("SELECT
        num , 
        description 
        FROM DIMENSIONS WHERE dataareaid = '$areaid' AND num = '$deptcode'");
        if($sql->num_rows() > 0){
            return $sql->row();
        }else{
            return null;
        }

    }
}

function getuserdata($ecode){
    if(!empty($ecode)){
        getfn()->db2 = getfn()->load->database("saleecolour" , true);
        $sql = getfn()->db2->query("SELECT
        Fname ,
        Lname 
        FROM member WHERE ecode = '$ecode' AND resigned = 0
        ");
        if($sql->num_rows() > 0 ){
            return $sql->row();
        }else{
            return null;
        }
    }
}

function getCompanynameTH($dataareaid)
{
    $companyName = "";
    if(!empty($dataareaid)){
        switch($dataareaid){
            case "sln":
                $companyName = "บริษัท สาลี่ คัลเล่อร์ จำกัด (มหาชน)";
                break;
            case "ca":
                $companyName = "บริษัท คอมโพสิท เอเชีย จำกัด";
                break;
            case "tbb":
                $companyName = "บริษัท เดอะ บับเบิ้ลส์ จำกัด";
                break;
            case "st":
                $companyName = "บริษัท ซับเทอร่า จำกัด";
                break;
        }
        return $companyName;
    }
}

function getExecutiveData($formno)
{
    if(!empty($formno))
    {
        $sql = getfn()->db->query("SELECT
        apv_email , 
        apv_ecode ,
        apv_approve ,
        apv_posiname,
        apv_approve_memo ,
        apv_approve_user , 
        apv_group,
        DATE_FORMAT(apv_approve_datetime , '%d-%m-%Y %H:%i:%s') AS apv_approve_datetime
        FROM approve_user WHERE apv_formno = ?
        " , array($formno));
        return $sql;
    }
}

function conPayment($payment)
{
    if(!empty($payment)){
        $pattern = "/D/";
        $replacement = " วัน";
        return preg_replace($pattern , $replacement , $payment);
    }else{
        return "";
    }
}

function conDateFromDb($date)
{
    if($date != ""){
        $datetimeIn = date_create($date);
        return date_format($datetimeIn,"d/m/Y");
    }else{
        return $date;
    }
    
}

function getItemDetail($formno)
{
    if(!empty($formno)){
        $sql = getfn()->db->query("SELECT
        d_itemid
        FROM details
        WHERE d_m_formno = ? ORDER BY d_autoid ASC
        " , array($formno));
        $itemid = [];
        foreach($sql->result() as $rs){
            $itemid[] = $rs->d_itemid;
        }
        $itemidString = implode(' , ', $itemid);
        return $itemidString;
    }
}




?>