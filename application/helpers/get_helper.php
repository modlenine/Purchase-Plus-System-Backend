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
    m_prno FROM main WHERE m_prcode = '$prcode' AND m_dataareaid = '$areaid' ORDER BY m_prno DESC LIMIT 1
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

function getCompanynameEN($dataareaid)
{
    $companyName = "";
    if(!empty($dataareaid)){
        switch($dataareaid){
            case "sln":
                $companyName = "Salee Colour Public Company Limited.";
                break;
            case "ca":
                $companyName = "Composite Asia Co.,Ltd.";
                break;
            case "tbb":
                $companyName = "The bubbles Co.,Ltd.";
                break;
            case "st":
                $companyName = "Subterra Co.,Ltd.";
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

function conDateTimeFromDb($datetime)
{
    if($datetime != ""){
        $datetimeIn = date_create($datetime);
        return date_format($datetimeIn,"d/m/Y H:i:s");
    }else{
        return $datetime;
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

function getVendtable($vendid , $areaid)
{
    if(!empty($vendid) && !empty($areaid)){
        getfn()->db_mssql = getfn()->load->database("mssql" , true);
        $sql = getfn()->db_mssql->query("SELECT
                a.accountnum AS accountnum,
                a.name AS name,
                a.address AS address,
                a.paymtermid AS paymtermid,
                a.currency AS currency,
                a.email AS email,
                b.txt AS currencytxt,
                b.currencycodeiso AS currencycodeiso,
                c.exchrate,
                c.fromdate
            FROM 
                vendtable a
            INNER JOIN 
                currency b ON a.currency = b.currencycode AND a.dataareaid = b.dataareaid
            CROSS APPLY (
                SELECT TOP 1 
                    c.exchrate,
                    c.fromdate
                FROM 
                    exchrates c 
                WHERE 
                    c.currencycode = b.currencycode 
                    AND c.dataareaid = b.dataareaid
                ORDER BY 
                    c.fromdate DESC
            ) c
            WHERE 
                a.accountnum = '$vendid' 
                AND a.dataareaid = '$areaid';
            ");

        return $sql->row();
    }
}

function getCompareFormno()
{
    // check formno ซ้ำในระบบ
    getfn()->db_compare = getfn()->load->database('compare_vendor', TRUE);
    $sql = getfn()->db_compare->query("SELECT
    formno FROM compare_formno ORDER BY id DESC LIMIT 1
    ");

    $cutYear = substr(date("Y"), 2, 2);
    $getMonth = substr(date("m"), 0, 2);
    $formno = "";
    if ($sql->num_rows() == 0) {
        $formno = "CO" . $cutYear.$getMonth. "000001";
    } else {
        $getFormno = $sql->row()->formno;
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
            $formno = "CO" . $cutYear.$getMonth."000001";
        } else {
            $formno = "CO" . $cutGetYear.$getMonth. $cutNo;
        }
    }
    getfn()->db_compare->insert("compare_formno" , [
        "formno" => $formno
    ]);
    return $formno;
}

function getdataforemail_compare($compare_id){
    if(!empty($compare_id)){
        getfn()->db_compare = getfn()->load->database('compare_vendor', TRUE);
        $sql = getfn()->db_compare->query("SELECT
        compare_master.id,
        compare_master.formno,
        compare_master.dataareaid,
        compare_master.accountnum,
        compare_vendors.vendor_name,
        compare_master.vendor_index,
        compare_master.reason,
        compare_master.user_create,
        compare_master.ecode_create,
        compare_master.dept_create,
        compare_master.deptcode_create,
        DATE_FORMAT(compare_master.datetime_create, '%d/%m/%Y %H:%i:%s') AS datetime_create,
        compare_master.datetime_modify,
        compare_master.compare_status,
        compare_master.last_updated,
        compare_master.memo_approval,
        compare_master.user_approval,
        compare_master.ecode_approval,
        compare_master.deptcode_approval,
        DATE_FORMAT(compare_master.datetime_approval, '%d/%m/%Y %H:%i:%s') AS datetime_approval,
        compare_master.status_approval,
        GROUP_CONCAT(compare_items.itemdetail ORDER BY compare_items.id SEPARATOR ' , ') AS itemdetails
        FROM
        compare_master
        INNER JOIN compare_vendors ON compare_vendors.compare_id = compare_master.id AND compare_vendors.vendor_index = compare_master.vendor_index
        INNER JOIN compare_items ON compare_items.compare_id = compare_vendors.compare_id AND compare_items.vendor_index = compare_vendors.vendor_index
        WHERE
        compare_master.id = ? " , array($compare_id));
        return $sql->row();
    }
}




?>