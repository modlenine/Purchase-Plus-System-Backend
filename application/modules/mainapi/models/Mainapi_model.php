<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Mainapi_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set("Asia/Bangkok");
        $this->db_mssql   = $this->load->database("mssql", true);
        $this->db_mssql2  = $this->load->database("mssql2", true);
        $this->db_compare = $this->load->database('compare_vendor', true);
        $this->load->model("email_model", "email");
    }

    public function getReqplan()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if ($received_data->action == "getReqplan") {
            $areaid = $received_data->areaid;

            $sql = $this->db_mssql->query("SELECT
            plantype,
            name,
            reqplanid,
            dataareaid,
            bpc_numbersequence
            FROM reqplan WHERE dataareaid = '$areaid' AND bpc_numbersequence != ''
            ");

            $output = [
                "msg"    => "ดึงข้อมูล reqplan สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->result(),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูล reqplan สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }

        echo json_encode($output);
    }

    public function getVendID()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if ($received_data->action == "getVendID") {
            $areaid = $received_data->areaid;
            $vendid = $received_data->vendid;
            $sql    = $this->db_mssql->query("SELECT
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
                a.accountnum LIKE '%$vendid%'
                AND a.dataareaid = '$areaid';
            ");

            $output = [
                "msg"    => "ดึงข้อมูลผู้ขายสำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->result(),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูลไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }
        echo json_encode($output);
    }

    // public function getVendData()
    // {
    //     if (! empty($this->input->post("dataareaid")) && ! empty($this->input->post("vendorname"))) {
    //         $dataareaid = $this->input->post("dataareaid");
    //         $vendorname = $this->input->post("vendorname");
    //         $sql        = $this->db_mssql->query("SELECT
    //             a.accountnum AS accountnum,
    //             a.name AS name,
    //             a.address AS address,
    //             a.paymtermid AS paymtermid,
    //             a.currency AS currency,
    //             a.email AS email,
    //             a.dataareaid AS dataareaid,
    //             b.txt AS currencytxt,
    //             b.currencycodeiso AS currencycodeiso,
    //             c.exchrate,
    //             c.fromdate
    //         FROM
    //             vendtable a
    //         INNER JOIN
    //             currency b ON a.currency = b.currencycode AND a.dataareaid = b.dataareaid
    //         CROSS APPLY (
    //             SELECT TOP 1
    //                 c.exchrate,
    //                 c.fromdate
    //             FROM
    //                 exchrates c
    //             WHERE
    //                 c.currencycode = b.currencycode
    //                 AND c.dataareaid = b.dataareaid
    //             ORDER BY
    //                 c.fromdate DESC
    //         ) c
    //         WHERE
    //             a.name LIKE '%$vendorname%'
    //             AND a.dataareaid = '$dataareaid';
    //         ");

    //         $output = [
    //             "msg"    => "ดึงข้อมูลผู้ขายสำเร็จ",
    //             "status" => "Select Data Success",
    //             "result" => $sql->result(),
    //         ];
    //     } else {
    //         $output = [
    //             "msg"    => "ดึงข้อมูลไม่สำเร็จ",
    //             "status" => "Select Data Not Success",
    //         ];
    //     }
    //     echo json_encode($output);
    // }
private function cleanVendorName($name)
{
    // คำที่ไม่จำเป็นในการค้นหา
    $remove = ['บริษัท', 'จำกัด', 'บจก.', 'บมจ.', '.', ',', '(', ')'];
    $name = str_ireplace($remove, '', $name);
    return trim(preg_replace('/\s+/', ' ', $name)); // ตัด space ซ้อน
}

public function getVendData()
{
    $dataareaid = $this->input->post("dataareaid");
    $vendorname = $this->input->post("vendorname");

    if (!empty($dataareaid) && !empty($vendorname)) {

        $cleanedName = $this->cleanVendorName($vendorname);
        $keywords = explode(" ", $cleanedName);

        $whereSQL = "";
        $params = [$dataareaid];

        foreach ($keywords as $kw) {
            if (!empty($kw)) {
                $whereSQL .= " AND REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(a.name, 'บริษัท', ''), 'จำกัด', ''), 'บจก.', ''), 'บมจ.', ''), '.', ''), ',', ''), '(', ''), ')', '') LIKE ?";
                $params[] = "%$kw%";
            }
        }

        $sql = $this->db_mssql->query("
            SELECT
                a.accountnum,
                a.name,
                a.address,
                a.paymtermid,
                a.currency,
                a.email,
                a.dataareaid,
                b.txt AS currencytxt,
                b.currencycodeiso,
                c.exchrate,
                c.fromdate
            FROM vendtable a
            INNER JOIN currency b ON a.currency = b.currencycode AND a.dataareaid = b.dataareaid
            CROSS APPLY (
                SELECT TOP 1 c.exchrate, c.fromdate
                FROM exchrates c
                WHERE c.currencycode = b.currencycode AND c.dataareaid = b.dataareaid
                ORDER BY c.fromdate DESC
            ) c
            WHERE a.dataareaid = ? $whereSQL
        ", $params);

        echo json_encode([
            "msg" => "ดึงข้อมูลผู้ขายสำเร็จ",
            "status" => "Select Data Success",
            "result" => $sql->result()
        ]);
    } else {
        echo json_encode([
            "msg" => "ดึงข้อมูลไม่สำเร็จ",
            "status" => "Select Data Not Success"
        ]);
    }
}


// 🔧 ฟังก์ชันช่วยล้างคำนำหน้าบริษัท
private function removeCompanyPrefix($name)
{
    $unwanted = ['บริษัท', 'จำกัด', 'บจก.', 'บมจ.', '.', ','];
    return str_ireplace($unwanted, '', $name);
}


    public function getCostcenter()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if ($received_data->action == "getCostcenter") {
            $areaid = $received_data->areaid;
            $sql    = $this->db_mssql->query("SELECT
             num ,
             description
             FROM DIMENSIONS WHERE dataareaid = '$areaid' AND dimensioncode = '1';");

            $output = [
                "msg"    => "ดึงข้อมูล Cost center สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->result(),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูล Cost center ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }
        echo json_encode($output);
    }

    public function getDepartment()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if ($received_data->action == "getDepartment") {
            $areaid = $received_data->areaid;
            $sql    = $this->db_mssql->query("SELECT
             num ,
             description
             FROM DIMENSIONS WHERE dataareaid = '$areaid' AND num between '1000' and '1020'");

            $output = [
                "msg"    => "ดึงข้อมูล Department สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->result(),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูล Department ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }
        echo json_encode($output);
    }

    public function getUserEcode()
    {
        $this->db_member = $this->load->database('saleecolour', true);
        $received_data   = json_decode(file_get_contents("php://input"));
        if ($received_data->action == "getUserEcode") {
            $department = $received_data->department;

            $condition = "";
            if ($department == "1001") {
                $condition = "AND DeptCode IN ('1001' , '1004')";
            } else {
                $condition = "AND DeptCode = '$department'";
            }

            $sql = $this->db_member->query("SELECT
            Fname ,
            Lname ,
            ecode
            FROM member
            WHERE resigned = 0
            $condition ORDER BY Fname ASC");

            $output = [
                "msg"    => "ดึงข้อมูลผู้ขอซื้อสำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->result(),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูลผู้ขอซื้อไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }

        echo json_encode($output);
    }

    public function getItemid()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if ($received_data->action == "getItemid") {
            $areaid = $received_data->areaid;
            $itemid = $received_data->itemid;
            $sql    = $this->db_mssql->query("SELECT TOP 100
                itm.itemid,
                itm.itemname,
                itm.itemgroupid,
                itmm.unitid
            FROM
                inventtable itm
            JOIN
                INVENTTABLEMODULE itmm ON itm.itemid = itmm.itemid AND itm.dataareaid = itmm.dataareaid
            WHERE
                itm.itemid LIKE '%$itemid%'
                AND itm.dataareaid = '$areaid'
                AND itm.slc_itemactivestatusid = 0
                AND itmm.moduletype = (
                    SELECT MAX(moduletype)
                    FROM INVENTTABLEMODULE
                    WHERE itemid = itm.itemid AND dataareaid = itm.dataareaid
                )"
            );

            $output = [
                "msg"    => "ดึงข้อมูล ItemID สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->result(),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูล ItemID ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }
        echo json_encode($output);
    }

    public function getInvestigator()
    {
        $sql = $this->db->query("SELECT
        inve_ecode , inve_fullname
        FROM investigator WHERE inve_status = 'active' ORDER BY inve_fullname ASC
        ");

        $output = json_encode([
            "msg"    => "ดึงข้อมูลผู้ตรวจสอบสำเร็จ",
            "status" => "Select Data Success",
            "result" => $sql->result(),
        ]);

        echo $output;
    }

    public function saveInsertItemdata()
    {
        if ($this->input->post("action") == "saveInsertItemdata") {
            $arSaveData = [
                //code
            ];
        }
    }

    public function saveDataAll()
    {
        if ($this->input->post("action") == "saveDataAll") {
            //Head
            $dataareaid        = $this->input->post("dataareaid");
            $plantype          = $this->input->post("plantype");
            $itemcategory      = $this->input->post("itemcategory");
            $costcenter        = $this->input->post("costcenter");
            $department        = $this->input->post("department");
            $ecode             = $this->input->post("ecode");
            $vendid            = $this->input->post("vendid");
            $vendname          = $this->input->post("vendname");
            $vendemail         = $this->input->post("vendemail");
            $paymtermid        = $this->input->post("paymtermid");
            $currency          = $this->input->post("currency");
            $currencyrate      = $this->input->post("currencyrate");
            $datetimesystem    = date("Y-m-d H:i:s");
            $datetimereq       = $this->input->post("datetimereq");
            $datetimedelivery  = $this->input->post("datetimedelivery");
            $memo              = $this->input->post("memo");
            $prno              = getPrno(concode($plantype), $dataareaid);
            $prcode            = concode($plantype);
            $ecodepost         = $this->input->post("ecodepost");
            $userpost          = $this->input->post("userpost");
            $formno            = getFormno();
            $m_invest_ecodefix = $this->input->post("m_invest_ecodefix");
            $compare_formno    = $this->input->post("compare_formno");

            $arsaveHead = [
                "m_formno"          => $formno,
                "m_prcode"          => $prcode,
                "m_prno"            => $prno,
                "m_dataareaid"      => $dataareaid,
                "m_plantype"        => $plantype,
                "m_itemcategory"    => $itemcategory,
                "m_costcenter"      => $costcenter,
                "m_department"      => $department,
                "m_ecode"           => $ecode,
                "m_vendid"          => $vendid,
                "m_vendname"        => $vendname,
                "m_vendemail"       => $vendemail,
                "m_paymtermid"      => $paymtermid,
                "m_currency"        => $currency,
                "m_currencyrate"    => $currencyrate,
                "m_datetime_create" => date("Y-m-d H:i:s"),
                "m_date_req"        => condate_todb($datetimereq),
                "m_date_delivery"   => condate_todb($datetimedelivery),
                "m_memo"            => $memo,
                "m_status"          => "Wait Send Data",
                "m_userpost"        => $userpost,
                "m_ecodepost"       => $ecodepost,
                "m_datetimepost"    => date("Y-m-d H:i:s"),
                "m_version_pr"      => 1,
                "m_version_status"  => "active",
                "m_formisono"       => "PC-F-001-00-14-07-60",
                "m_invest_ecodefix" => $m_invest_ecodefix,
                "m_compare_formno"  => $compare_formno,
            ];
            $this->db->insert("main", $arsaveHead);
            //Head

            //update Compare Status
            $this->db_compare->where("formno", $compare_formno);
            $this->db_compare->update("compare_master", [
                "compare_status" => "Compare Used",
                "pu_formno"      => $formno,
                "pr_number"      => $prno,
                "last_updated"   => date("Y-m-d H:i:s"),
            ]);

            // Detail
            $itemdata = json_decode($this->input->post("itemdata"), true);

            foreach ($itemdata as $item) {
                //code
                $arsaveDetail = [
                    "d_m_formno"     => $formno,
                    "d_m_prno"       => $prno,
                    "d_itemid"       => $item['itemid'],
                    "d_itemname"     => $item['itemname'],
                    "d_itemgroupid"  => $item['itemgroupid'],
                    "d_itemdetail"   => $item['itemdetail'],
                    "d_itemqty"      => $item['itemqty'],
                    "d_itemprice"    => $item['itemprice'],
                    "d_itemdiscount" => $item['itemdiscount'],
                    "d_itempricesum" => $item['itempricesum'],
                    "d_itemunit"     => $item['itemunit'],
                    "d_datetime"     => date("Y-m-d H:i:s"),
                    "d_version_pr"   => 1,
                ];
                $this->db->insert("details", $arsaveDetail);
            }

            if (! empty($_FILES['ip-cpr-file']['name'][0])) {
                $fileInput = "ip-cpr-file";
                uploadFile($fileInput, $formno);
            }

            $output = [
                "msg"    => "บันทึกข้อมูลสำเร็จ",
                "status" => "Insert Data Success",
                "formno" => $formno,
            ];

        } else {
            $output = [
                "msg"    => "บันทึกข้อมูลไม่สำเร็จ",
                "status" => "Insert Data Not Success",
            ];
        }

        echo json_encode($output);

    }

    public function saveDataAll_edit()
    {
        if ($this->input->post("action") == "saveDataAll_edit") {
            //Head
            $dataareaid        = $this->input->post("dataareaid");
            $plantype          = $this->input->post("plantype");
            $itemcategory      = $this->input->post("itemcategory");
            $costcenter        = $this->input->post("costcenter");
            $department        = $this->input->post("department");
            $ecode             = $this->input->post("ecode");
            $vendid            = $this->input->post("vendid");
            $vendname          = $this->input->post("vendname");
            $vendemail         = $this->input->post("vendemail");
            $paymtermid        = $this->input->post("paymtermid");
            $currency          = $this->input->post("currency");
            $currencyrate      = $this->input->post("currencyrate");
            $datetimesystem    = date("Y-m-d H:i:s");
            $datetimereq       = $this->input->post("datetimereq");
            $datetimedelivery  = $this->input->post("datetimedelivery");
            $memo              = $this->input->post("memo");
            $prno              = getPrno(concode($plantype), $dataareaid);
            $prcode            = concode($plantype);
            $ecodepost         = $this->input->post("ecodepost");
            $userpost          = $this->input->post("userpost");
            $m_invest_ecodefix = $this->input->post("m_invest_ecodefix");

            $formno  = $this->input->post("formno");
            $oldprno = $this->input->post("prno");

            $compare_formno = $this->input->post("compare_formno");
            //check compare formno
            $oldCompareFormno = $this->checkCompareFormno($compare_formno, $formno);
            if ($oldCompareFormno !== null && $oldCompareFormno !== "") {
                // ทำการอัพเดตสถานะของ Compare เดิมเป็น "Compare Approved"
                $this->db_compare->where('formno', $oldCompareFormno);
                $this->db_compare->update('compare_master', [
                    'compare_status' => 'Compare Approved',
                    'pu_formno'      => null,
                    'pr_number'      => null,
                ]);
            }

            // check formcode
            $sqlcheckformcode = $this->db->query("SELECT m_prcode , m_prno , m_dataareaid FROM main WHERE m_formno = '$formno'");
            // check Data areaid
            if ($sqlcheckformcode->row()->m_dataareaid == $dataareaid) {
                if ($sqlcheckformcode->row()->m_prcode == $prcode) {
                    $arsaveHead = [
                        "m_dataareaid"          => $dataareaid,
                        "m_costcenter"          => $costcenter,
                        "m_department"          => $department,
                        "m_itemcategory"        => $itemcategory,
                        "m_ecode"               => $ecode,
                        "m_vendid"              => $vendid,
                        "m_vendname"            => $vendname,
                        "m_paymtermid"          => $paymtermid,
                        "m_currency"            => $currency,
                        "m_currencyrate"        => $currencyrate,
                        "m_datetime_create"     => date("Y-m-d H:i:s"),
                        "m_date_req"            => condate_todb($datetimereq),
                        "m_date_delivery"       => condate_todb($datetimedelivery),
                        "m_memo"                => $memo,
                        "m_status"              => "Wait Send Data",
                        "m_userpost_modify"     => $userpost,
                        "m_ecodepost_modify"    => $ecodepost,
                        "m_datetimepost_modify" => date("Y-m-d H:i:s"),
                        "m_version_pr"          => 1,
                        "m_version_status"      => "active",
                        "m_invest_ecodefix"     => $m_invest_ecodefix,
                        "m_compare_formno"      => $compare_formno,
                    ];
                } else {
                    $arsaveHead = [
                        "m_prcode"              => $prcode,
                        "m_prno"                => $prno,
                        "m_dataareaid"          => $dataareaid,
                        "m_plantype"            => $plantype,
                        "m_itemcategory"        => $itemcategory,
                        "m_costcenter"          => $costcenter,
                        "m_department"          => $department,
                        "m_ecode"               => $ecode,
                        "m_vendid"              => $vendid,
                        "m_vendname"            => $vendname,
                        "m_paymtermid"          => $paymtermid,
                        "m_currency"            => $currency,
                        "m_currencyrate"        => $currencyrate,
                        "m_datetime_create"     => date("Y-m-d H:i:s"),
                        "m_date_req"            => condate_todb($datetimereq),
                        "m_date_delivery"       => condate_todb($datetimedelivery),
                        "m_memo"                => $memo,
                        "m_status"              => "Wait Send Data",
                        "m_userpost_modify"     => $userpost,
                        "m_ecodepost_modify"    => $ecodepost,
                        "m_datetimepost_modify" => date("Y-m-d H:i:s"),
                        "m_version_pr"          => 1,
                        "m_version_status"      => "active",
                        "m_invest_ecodefix"     => $m_invest_ecodefix,
                        "m_compare_formno"      => $compare_formno,
                    ];
                }
            } else if ($sqlcheckformcode->row()->m_dataareaid != $dataareaid) {
                $arsaveHead = [
                    "m_prcode"              => $prcode,
                    "m_prno"                => $prno,
                    "m_dataareaid"          => $dataareaid,
                    "m_plantype"            => $plantype,
                    "m_itemcategory"        => $itemcategory,
                    "m_costcenter"          => $costcenter,
                    "m_department"          => $department,
                    "m_ecode"               => $ecode,
                    "m_vendid"              => $vendid,
                    "m_vendname"            => $vendname,
                    "m_vendemail"           => $vendemail,
                    "m_paymtermid"          => $paymtermid,
                    "m_currency"            => $currency,
                    "m_currencyrate"        => $currencyrate,
                    "m_datetime_create"     => date("Y-m-d H:i:s"),
                    "m_date_req"            => condate_todb($datetimereq),
                    "m_date_delivery"       => condate_todb($datetimedelivery),
                    "m_memo"                => $memo,
                    "m_status"              => "Wait Send Data",
                    "m_userpost_modify"     => $userpost,
                    "m_ecodepost_modify"    => $ecodepost,
                    "m_datetimepost_modify" => date("Y-m-d H:i:s"),
                    "m_version_pr"          => 1,
                    "m_version_status"      => "active",
                    "m_invest_ecodefix"     => $m_invest_ecodefix,
                    "m_compare_formno"      => $compare_formno,
                ];
            }

            $this->db->where("m_formno", $formno);
            $this->db->update("main", $arsaveHead);
            //Head

            //update compare status new
            $this->db_compare->where('formno', $compare_formno);
            $this->db_compare->update('compare_master', [
                "compare_status" => "Compare Used",
                "pu_formno"      => $formno,
                "pr_number"      => $prno,
                "last_updated"   => date("Y-m-d H:i:s"),
            ]);

            // Detail
            // Delete data
            $this->db->where("d_m_formno", $formno);
            $this->db->delete("details");
            $itemdata = json_decode($this->input->post("itemdata"), true);

            if ($sqlcheckformcode->row()->m_prcode == $prcode) {
                $dpr = $sqlcheckformcode->row()->m_prno;
            } else {
                $dpr = $prno;
            }
            foreach ($itemdata as $item) {
                //code
                $arsaveDetail = [
                    "d_m_formno"     => $formno,
                    "d_m_prno"       => $dpr,
                    "d_itemid"       => $item['itemid'],
                    "d_itemname"     => $item['itemname'],
                    "d_itemdetail"   => $item['itemdetail'],
                    "d_itemgroupid"  => $item['itemgroupid'],
                    "d_itemqty"      => $item['itemqty'],
                    "d_itemprice"    => $item['itemprice'],
                    "d_itemdiscount" => $item['itemdiscount'],
                    "d_itempricesum" => $item['itempricesum'],
                    "d_itemunit"     => $item['itemunit'],
                    "d_datetime"     => date("Y-m-d H:i:s"),
                    "d_version_pr"   => 1,
                ];
                $this->db->insert("details", $arsaveDetail);
            }

            if (! empty($_FILES['ip-cpre-file']['name'][0])) {
                $fileInput = "ip-cpre-file";
                uploadFile($fileInput, $formno);
            }

            $output = [
                "msg"    => "บันทึกข้อมูลสำเร็จ",
                "status" => "Insert Data Success",
                "formno" => $formno,
            ];

        } else {
            $output = [
                "msg"    => "บันทึกข้อมูลไม่สำเร็จ",
                "status" => "Insert Data Not Success",
            ];
        }
        echo json_encode($output);

    }

    private function checkCompareFormnoNull($formno)
    {
        if ($formno != "") {
            $sql = $this->db->query("SELECT
            m_compare_formno
            FROM main
            WHERE m_formno = ? AND m_compare_formno IS NOT NULL AND m_compare_formno != ''
            ", [$formno]);

            if ($sql->num_rows() > 0) {
                return $sql->row()->m_compare_formno;
            } else {
                return "";
            }
        }
    }

    private function checkCompareFormno($compare_formno, $formno)
    {
        if (! empty($compare_formno) && ! empty($formno)) {
            $sqlCheck = $this->db->query("SELECT
            m_compare_formno
            FROM main
            WHERE m_formno = ?
            ", [$formno]);

            if ($sqlCheck->num_rows() > 0) {
                $row         = $sqlCheck->row();
                $compareInDB = $row->m_compare_formno;

                // ถ้า compare_formno ที่ส่งมา ไม่ตรงกับของเดิม
                if ($compareInDB !== $compare_formno) {
                    // 🔁 คืนค่า compare_formno เดิมที่เคยบันทึกไว้ (สำหรับอัพเดต status)
                    return $compareInDB;
                }
            }
        }

        return null; // ไม่มีข้อมูล หรือ compare_formno เหมือนเดิม
    }

    public function saveDataAll_edit_purchase()
    {
        if ($this->input->post("action") == "saveDataAll_edit_purchase") {
            //Head
            $dataareaid       = $this->input->post("dataareaid");
            $plantype         = $this->input->post("plantype");
            $itemcategory     = $this->input->post("itemcategory");
            $costcenter       = $this->input->post("costcenter");
            $department       = $this->input->post("department");
            $ecode            = $this->input->post("ecode");
            $vendid           = $this->input->post("vendid");
            $vendname         = $this->input->post("vendname");
            $vendemail        = $this->input->post("vendemail");
            $paymtermid       = $this->input->post("paymtermid");
            $currency         = $this->input->post("currency");
            $currencyrate     = $this->input->post("currencyrate");
            $datetimesystem   = date("Y-m-d H:i:s");
            $datetimereq      = $this->input->post("datetimereq");
            $datetimedelivery = $this->input->post("datetimedelivery");
            $memo             = $this->input->post("memo");
            $prno             = getPrno(concode($plantype), $dataareaid);
            $prcode           = concode($plantype);
            $ecodepost        = $this->input->post("ecodepost");
            $userpost         = $this->input->post("userpost");

            $formno  = $this->input->post("formno");
            $oldprno = $this->input->post("prno");

            $compare_formno = $this->input->post("compare_formno");
            //check compare formno
            $oldCompareFormno = $this->checkCompareFormno($compare_formno, $formno);
            if ($oldCompareFormno !== null) {
                // ทำการอัพเดตสถานะของ Compare เดิมเป็น "Compare Approved"
                $this->db_compare->where('formno', $oldCompareFormno);
                $this->db_compare->update('compare_master', [
                    'compare_status' => 'Compare Approved',
                    'pu_formno'      => null,
                    'pr_number'      => null,
                    'last_updated'   => date("Y-m-d H:i:s"),
                ]);
            }

            // check formcode
            $sqlcheckformcode = $this->db->query("SELECT m_prcode , m_prno , m_dataareaid FROM main WHERE m_formno = '$formno'");
            // check Data areaid
            if ($sqlcheckformcode->row()->m_dataareaid == $dataareaid) {
                if ($sqlcheckformcode->row()->m_prcode == $prcode) {
                    $arsaveHead = [
                        "m_dataareaid"          => $dataareaid,
                        "m_costcenter"          => $costcenter,
                        "m_department"          => $department,
                        "m_itemcategory"        => $itemcategory,
                        "m_ecode"               => $ecode,
                        "m_vendid"              => $vendid,
                        "m_vendname"            => $vendname,
                        "m_vendemail"           => $vendemail,
                        "m_paymtermid"          => $paymtermid,
                        // "m_currency" => $currency,
                        // "m_currencyrate" => $currencyrate,
                        "m_date_req"            => condate_todb($datetimereq),
                        "m_date_delivery"       => condate_todb($datetimedelivery),
                        "m_memo"                => $memo,
                        "m_userpost_modify"     => $userpost,
                        "m_ecodepost_modify"    => $ecodepost,
                        "m_datetimepost_modify" => date("Y-m-d H:i:s"),
                        "m_version_pr"          => 1,
                        "m_version_status"      => "active",
                        "m_compare_formno"      => $compare_formno,
                    ];
                } else {
                    $arsaveHead = [
                        "m_prcode"              => $prcode,
                        "m_prno"                => $prno,
                        "m_dataareaid"          => $dataareaid,
                        "m_plantype"            => $plantype,
                        "m_itemcategory"        => $itemcategory,
                        "m_costcenter"          => $costcenter,
                        "m_department"          => $department,
                        "m_ecode"               => $ecode,
                        "m_vendid"              => $vendid,
                        "m_vendname"            => $vendname,
                        "m_vendemail"           => $vendemail,
                        "m_paymtermid"          => $paymtermid,
                        // "m_currency" => $currency,
                        // "m_currencyrate" => $currencyrate,
                        "m_date_req"            => condate_todb($datetimereq),
                        "m_date_delivery"       => condate_todb($datetimedelivery),
                        "m_memo"                => $memo,
                        "m_userpost_modify"     => $userpost,
                        "m_ecodepost_modify"    => $ecodepost,
                        "m_datetimepost_modify" => date("Y-m-d H:i:s"),
                        "m_version_pr"          => 1,
                        "m_version_status"      => "active",
                        "m_compare_formno"      => $compare_formno,
                    ];
                }
            } else if ($sqlcheckformcode->row()->m_dataareaid != $dataareaid) {
                $arsaveHead = [
                    "m_prcode"              => $prcode,
                    "m_prno"                => $prno,
                    "m_dataareaid"          => $dataareaid,
                    "m_plantype"            => $plantype,
                    "m_itemcategory"        => $itemcategory,
                    "m_costcenter"          => $costcenter,
                    "m_department"          => $department,
                    "m_ecode"               => $ecode,
                    "m_vendid"              => $vendid,
                    "m_vendname"            => $vendname,
                    "m_vendemail"           => $vendemail,
                    "m_paymtermid"          => $paymtermid,
                    // "m_currency" => $currency,
                    // "m_currencyrate" => $currencyrate,
                    "m_date_req"            => condate_todb($datetimereq),
                    "m_date_delivery"       => condate_todb($datetimedelivery),
                    "m_memo"                => $memo,
                    "m_userpost_modify"     => $userpost,
                    "m_ecodepost_modify"    => $ecodepost,
                    "m_datetimepost_modify" => date("Y-m-d H:i:s"),
                    "m_version_pr"          => 1,
                    "m_version_status"      => "active",
                    "m_compare_formno"      => $compare_formno,
                ];
            }

            $this->db->where("m_formno", $formno);
            $this->db->update("main", $arsaveHead);
            //Head

            //update compare status new
            $this->db_compare->where('formno', $compare_formno);
            $this->db_compare->update('compare_master', [
                'compare_status' => 'Compare Used',
                'pu_formno'      => $formno,
                'pr_number'      => $prno,
                'last_updated'   => date("Y-m-d H:i:s"),
            ]);

            // Detail
            // Delete data
            $this->db->where("d_m_formno", $formno);
            $this->db->delete("details");
            $itemdata = json_decode($this->input->post("itemdata"), true);

            if ($sqlcheckformcode->row()->m_prcode == $prcode) {
                $dpr = $sqlcheckformcode->row()->m_prno;
            } else {
                $dpr = $prno;
            }
            foreach ($itemdata as $item) {
                //code
                $arsaveDetail = [
                    "d_m_formno"     => $formno,
                    "d_m_prno"       => $dpr,
                    "d_itemid"       => $item['itemid'],
                    "d_itemname"     => $item['itemname'],
                    "d_itemdetail"   => $item['itemdetail'],
                    "d_itemgroupid"  => $item['itemgroupid'],
                    "d_itemqty"      => $item['itemqty'],
                    "d_itemprice"    => $item['itemprice'],
                    "d_itemdiscount" => $item['itemdiscount'],
                    "d_itempricesum" => $item['itempricesum'],
                    "d_itemunit"     => $item['itemunit'],
                    "d_datetime"     => date("Y-m-d H:i:s"),
                    "d_version_pr"   => 1,
                ];
                $this->db->insert("details", $arsaveDetail);
            }

            if (! empty($_FILES['ip-cpre-file']['name'][0])) {
                $fileInput = "ip-cpre-file";
                uploadFile($fileInput, $formno);
            }

            $output = [
                "msg"    => "บันทึกข้อมูลสำเร็จ",
                "status" => "Insert Data Success",
                "formno" => $formno,
            ];

        } else {
            $output = [
                "msg"    => "บันทึกข้อมูลไม่สำเร็จ",
                "status" => "Insert Data Not Success",
            ];
        }
        echo json_encode($output);

    }

    public function getdata_viewfull()
    {
        if ($this->input->post("action") == "getdata_viewfull") {
            $formno = $this->input->post("formno");

            $sqlmain = $this->db->query("SELECT
                main.m_autoid,
                main.m_compare_formno,
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
                main.m_vendemail,
                main.m_paymtermid,
                -- main.m_datetime_create,
                DATE_FORMAT(m_datetime_create, '%d-%m-%Y %H:%i:%s') AS m_datetime_create,
                -- main.m_date_req,
                DATE_FORMAT(m_date_req, '%d-%m-%Y') AS m_date_req,
                -- main.m_date_delivery,
                DATE_FORMAT(m_date_delivery, '%d-%m-%Y') AS m_date_delivery,
                main.m_memo,
                main.m_status,
                main.m_userpost,
                main.m_ecodepost,
                -- main.m_datetimepost,
                DATE_FORMAT(m_datetimepost, '%d/%m/%Y %H:%i:%s') AS m_datetimepost,
                main.m_version_pr,
                main.m_version_status,
                main.m_invest_ecodefix,
                main.m_approve_invest,
                main.m_memo_invest,
                main.m_userpost_invest,
                main.m_ecodepost_invest,
                DATE_FORMAT(m_datetimepost_invest , '%d/%m/%Y %H:%i:%s') AS m_datetimepost_invest,
                main.m_userpost_mgr,
                main.m_ecodepost_mgr,
                main.m_memo_mgr,
                DATE_FORMAT(main.m_datetimepost_mgr , '%d/%m/%Y %H:%i:%s') AS m_datetimepost_mgr,
                main.m_approve_mgr,
                main.m_approve_pur,
                main.m_userpost_pur,
                main.m_ecodepost_pur,
                DATE_FORMAT(m_datetimepost_pur, '%d/%m/%Y %H:%i:%s') AS m_datetimepost_pur,
                main.m_memo_pur,
                main.m_pono,
                main.m_formisono_po,
                main.m_currency,
                main.m_currencyrate
                FROM
                main
                WHERE m_formno = '$formno' ORDER BY m_version_pr DESC
            ");

            if ($sqlmain->num_rows() != 0) {
                $resultVendtable = getVendtable($sqlmain->row()->m_vendid, $sqlmain->row()->m_dataareaid);

                $version_pr = $sqlmain->row()->m_version_pr;
                $sqldetails = $this->db->query("SELECT
                details.d_autoid,
                details.d_m_formno,
                details.d_itemid as itemid,
                details.d_itemname as itemname,
                details.d_itemgroupid as itemgroupid,
                details.d_itemdetail as itemdetail,
                details.d_itemqty as itemqty,
                details.d_itemprice as itemprice,
                details.d_itemdiscount as itemdiscount,
                details.d_itempricesum as itempricesum,
                SUM(details.d_itempricesum) OVER () as itemcalcprice,  -- ใช้ window function เพื่อให้ได้ผลรวม
                details.d_itemunit as itemunit,
                details.d_itemmemo,
                details.d_datetime
                FROM
                details
                WHERE d_m_formno = '$formno' AND d_version_pr = '$version_pr' ORDER BY d_autoid ASC
                ");

                $resultDetail   = $sqldetails->result();
                $resultPriceSum = $sqldetails->row()->itemcalcprice;

                // check currency type
                if ($sqlmain->row()->m_currency != "THB" && $sqlmain->row()->m_currency !== null) {
                    $resultPriceSum = (floatval($resultPriceSum) * floatval($sqlmain->row()->m_currencyrate)) / 100;
                }
                // check currency type
            } else {
                $resultDetail    = '';
                $resultPriceSum  = '';
                $resultVendtable = '';
            }

            $dataareaid    = $sqlmain->row()->m_dataareaid;
            $plan          = $sqlmain->row()->m_itemcategory;
            $queryPaygroup = $this->getPaygroupNo($dataareaid, $plan, $resultPriceSum);
            $queryAppUser  = $this->getUserApprove($formno, $queryPaygroup);
            $queryFile     = $this->getFiles($formno);

            $output = [
                "msg"         => "ดึงข้อมูล PR สำเร็จ",
                "status"      => "Select Data Success",
                "maindata"    => $sqlmain->row(),
                "details"     => $resultDetail,
                "pricesum"    => $resultPriceSum,
                "paygroup"    => $queryPaygroup,
                "datetimenow" => date("d-m-Y H:i:s"),
                "userApp"     => $queryAppUser,
                "files"       => $queryFile->result(),
                "vendtable"   => $resultVendtable,
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูล PR ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }

        echo json_encode($output);
    }

    private function getPaygroupNo($dataareaid, $plan, $sumprice)
    {
        if ($dataareaid != "" && $plan != "" && $sumprice != "") {
            if ($dataareaid == "sln" || $dataareaid == "ca" || $dataareaid == "poly" || $dataareaid == "st") {
                $dataareaid = "sc,pa,ca,st";
            }
            $conPrice = floatval($sumprice);
            $sql      = $this->db->query("SELECT
            pay_scope_start,
            pay_scope_end,
            approve_group
            FROM pay_group
            WHERE areaid = ? AND pay_doctype = ? AND pay_scope_start <= ? AND pay_scope_end >= ?",
                [$dataareaid, $plan, $sumprice, $conPrice]);

            return $sql->row()->approve_group;
        }
    }
    private function getUserApprove($formno, $appGroup)
    {
        if ($formno != "" && $appGroup != "") {
            $sql = $this->db->query("SELECT
            apv_ecode
            FROM approve_user WHERE apv_formno = '$formno' AND apv_group = '$appGroup'
            ");
            $query  = $sql->result_array();
            $result = array_column($query, 'apv_ecode');
            return $result;
        }
    }
    private function getFiles($formno)
    {
        if ($formno != "") {
            $sql = $this->db->query("SELECT
            f_autoid,
            f_formno,
            f_path,
            f_name
            FROM files WHERE f_formno = '$formno'
            ");

            return $sql;
        }
    }

    public function loadprlist()
    {
        // DB table to use
        $table = 'pr_view';

        // Table's primary key
        $primaryKey = 'm_autoid';

        $columns = [
            ['db'       => 'm_formno', 'dt' => 0,
                'formatter' => function ($d, $row) {
                    $output = '
                    <a href="' . getViewurl() . 'viewdata/' . $d . '" class="select_formno"
                        data_formno="' . $d . '"
                    ><b>' . $d . '</b></a>
                    ';
                    return $output;
                },
            ],
            ['db'       => 'm_compare_formno', 'dt' => 1,
                'formatter' => function ($d, $row) {
                    $deptcode = isset($row['m_department']) ? $row['m_department'] : '';
                    $output   = '
                <a href="javascript:void(0)" class="select_compareV_formno"
                    data-compareformno="' . $d . '"
                    data-deptcode="' . $deptcode . '"
                ><b>' . $d . '</b></a>
                ';
                    return $output;
                },
            ],
            ['db'       => 'm_dataareaid', 'dt' => 2,
                'formatter' => function ($d, $row) {
                    return $d;
                },
            ],
            ['db'       => 'm_prno', 'dt' => 3,
                'formatter' => function ($d, $row) {
                    return $d;
                },
            ],
            ['db'       => 'm_pono', 'dt' => 4,
                'formatter' => function ($d, $row) {
                    return $d;
                },
            ],
            ['db'       => 'item_details', 'dt' => 5,
                'formatter' => function ($d, $row) {
                    return strlen($d) > 40 ? substr($d, 0, 40) . ".." : $d;
                },
            ],
            ['db'       => 'm_formno', 'dt' => 6,
                'formatter' => function ($d, $row) {
                    return number_format(sumPriceByFormno($d), 3);
                },
            ],
            ['db'       => 'm_department', 'dt' => 7,
                'formatter' => function ($d, $row) {
                    return $d;
                },
            ],
            ['db'       => 'm_ecode', 'dt' => 8,
                'formatter' => function ($d, $row) {
                    return $d;
                },
            ],
            ['db'       => 'm_date_req', 'dt' => 9,
                'formatter' => function ($d, $row) {
                    return condate_fromdb($d);
                },
            ],
            ['db' => 'm_vendid', 'dt' => 10],
            ['db' => 'm_vendname', 'dt' => 11],
            ['db'       => 'm_date_delivery', 'dt' => 12,
                'formatter' => function ($d, $row) {
                    return condate_fromdb($d);
                },
            ],
            ['db'       => 'm_status', 'dt' => 13,
                'formatter' => function ($d, $row) {
                    $color = "";
                    if ($d == "Wait Send Data") { //สีเหลือง
                        $color = "color:#FF9933;";
                    } else if ($d == "New PR") { //สีฟ้า
                        $color = "color:#0066FF;";
                    } else if ($d == "PO confirmed") {
                        $color = "color:#00CC00;";
                    } else if ($d == "User Cancel") {
                        $color = "color:#CC0000;";
                    } else {
                        $color = "color:#2F4F4F;";
                    }

                    $html = '<span style="' . $color . '"><b>' . $d . '</b></span>';
                    return $html;
                },
            ],
        ];

        // SQL server connection information
        $sql_details = [
            'user' => getDb()->db_username,
            'pass' => getDb()->db_password,
            'db'   => getDb()->db_databasename,
            'host' => getDb()->db_host,
        ];

        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
        * If you just want to use the basic configuration for DataTables with PHP
        * server-side, there is no need to edit below this line.
        */
        require 'server-side/scripts/ssp.class.php';

        $sql_searchBydate = "";
        $startDate        = $_POST['startdate_filter'];
        $endDate          = $_POST['enddate_filter'];
        $dateType         = $_POST['datetype_filter'];
        $itemid           = $_POST['itemid_filter'];
        $status           = $_POST['status_filter'];

        $sql_searchBydate = "";
        if ($dateType == "deliveryDate") {
            if (empty($startDate) && empty($endDate)) {
                $sql_searchBydate = "m_date_delivery LIKE '%%' ";
            } else if (empty($startDate) && ! empty($endDate)) {
                $sql_searchBydate = "m_date_delivery BETWEEN '$endDate' AND '$endDate' ";
            } else if (! empty($startDate) && ! empty($endDate)) {
                $sql_searchBydate = "m_date_delivery BETWEEN '$startDate' AND '$endDate' ";
            } else if (! empty($startDate) && empty($endDate)) {
                $sql_searchBydate = "m_date_delivery BETWEEN '$startDate' AND '$startDate' ";
            }
        } else {
            if (empty($startDate) && empty($endDate)) {
                $sql_searchBydate = "m_date_req LIKE '%%' ";
            } else if (empty($startDate) && ! empty($endDate)) {
                $sql_searchBydate = "m_date_req BETWEEN '$endDate' AND '$endDate' ";
            } else if (! empty($startDate) && ! empty($endDate)) {
                $sql_searchBydate = "m_date_req BETWEEN '$startDate' AND '$endDate' ";
            } else if (! empty($startDate) && empty($endDate)) {
                $sql_searchBydate = "m_date_req BETWEEN '$startDate' AND '$startDate' ";
            }
        }

        if (empty($status)) {
            $sql_searchByStatus = "m_status LIKE '%%'";
        } else {
            $sql_searchByStatus = "m_status = '$status'";
        }

        $sql_searchByStatus = "m_status LIKE '%$status%'";

        // Item ID filtering
        if (empty($itemid)) {
            $sql_searchByItemid = "1=1"; // No filtering on itemid
        } else {
            $sql_searchByItemid = "EXISTS (
                SELECT 1
                FROM details d
                WHERE d.d_m_formno = m_formno
                AND d.d_itemid LIKE '%$itemid%'
            )";
        }

        $sql_searchByAll = "$sql_searchBydate AND $sql_searchByStatus AND $sql_searchByItemid";

        echo json_encode(
            SSP::complex($_POST, $sql_details, $table, $primaryKey, $columns, null, "$sql_searchByAll")
        );

    }

    public function saveCancel()
    {
        if ($this->input->post("action") == "saveCancel") {
            $formno = $this->input->post("formno");

            $arsaveCancel = [
                "m_status"         => "User Cancel",
                "m_datetimeupdate" => date("Y-m-d H:i:s"),
                "m_compare_formno" => null,
            ];
            $this->db->where("m_formno", $formno);
            $this->db->update("main", $arsaveCancel);

            if (! empty($this->input->post("compare_formno"))) {
                $compare_formno = $this->input->post("compare_formno");
                $this->db_compare->where("formno", $compare_formno);
                $this->db_compare->update("compare_master", [
                    "compare_status" => "Compare Approved",
                    "pu_formno"      => null,
                    "pr_number"      => null,
                    "last_updated"   => date("Y-m-d H:i:s"),
                ]);
            }

            $output = [
                "msg"    => "ยกเลิกเอกสารสำเร็จ",
                "status" => "Update Data Success",
            ];
        } else {
            $output = [
                "msg"    => "ยกเลิกเอกสารไม่สำเร็จ",
                "status" => "Update Data Not Success",
            ];
        }
        echo json_encode($output);
    }

    public function sendData()
    {
        if ($this->input->post("action") == "sendData") {

            $formno = $this->input->post("formno");

            $arsaveSendData = [
                "m_status" => "New PR",
            ];
            $this->db->where("m_formno", $formno);
            $this->db->update("main", $arsaveSendData);

            $this->email->sendto_investigator($formno);

            $output = [
                "msg"    => "ส่งข้อมูลสำเร็จ",
                "status" => "Update Data Success",
            ];
        } else {
            $output = [
                "msg"    => "ส่งข้อมูลไม่สำเร็จ",
                "status" => "Update Data Not Success",
            ];
        }
        echo json_encode($output);
    }

    public function saveMgrApprove()
    {
        if (! empty($this->input->post("formno"))) {
            // $this->load->model("email_model" , "email");
            if ($this->input->post("approveType") == "yes") {
                $status = "Manager Approved";
            } else {
                $status = "Manager Not Approve";
            }

            $formno = $this->input->post("formno");

            //update old status
            //Send to notifycenter
            $notifyformno      = $formno;
            $notifyprogramname = "Purchase Plus";
            $notifystatus      = "action done";
            $notifytype        = "take action";

            $this->notifycenter->updatedataAction_template($notifyformno, $notifyprogramname, $notifystatus, $notifytype);
            //Send to notifycenter
            //update old status

            $arsaveMgr = [
                "m_approve_mgr"      => $this->input->post("approveType"),
                "m_memo_mgr"         => $this->input->post("memoMgr"),
                "m_userpost_mgr"     => $this->input->post("userpostMgr"),
                "m_ecodepost_mgr"    => $this->input->post("ecodepostMgr"),
                "m_datetimepost_mgr" => date("Y-m-d H:i:s"),
                "m_status"           => $status,
            ];

            $this->db->where("m_formno", $formno);
            $this->db->update("main", $arsaveMgr);

            $paygroup   = $this->input->post("paygroup");
            $ecodeArray = [];
            $group      = "";

            if ($paygroup != "5" && $this->input->post("approveType") != "no") {

                if ($paygroup == "4") {
                    $ecodeArray = $this->input->post("g4Check");
                    $group      = "4";
                } else if ($paygroup == "3") {
                    $ecodeArray = $this->input->post("g3Check");
                    $group      = "3";
                } else if ($paygroup == "2") {
                    $ecodeArray = $this->input->post("g2Check");
                    $group      = "2";
                } else if ($paygroup == "1") {
                    $ecodeArray = $this->input->post("g1Check");
                    $group      = "1";
                } else if ($paygroup == "0") {
                    $ecodeArray = $this->input->post("g0Check");
                    $group      = "0";
                }

                if (is_array($ecodeArray) == true) {
                    foreach ($ecodeArray as $ecodes) {
                        $sqlGetAppGroup = $this->getApproveGroup($group, $ecodes)->row();
                        $sqlGetEmail    = $this->getemailpaygroup($ecodes)->row();
                        $arsave_appuser = [
                            "apv_group"       => $group,
                            "apv_user"        => $sqlGetAppGroup->app_user,
                            "apv_ecode"       => $ecodes,
                            "apv_posiname"    => $sqlGetAppGroup->app_posiname,
                            "apv_formno"      => $this->input->post("formno"),
                            "apv_areaidgroup" => $sqlGetAppGroup->app_areaid,
                            "apv_areaid"      => $this->input->post("dataareaid"),
                            "apv_email"       => $sqlGetEmail->memberemail,
                            "apv_datetime"    => date("Y-m_d H:i:s"),
                        ];
                        $this->db->insert("approve_user", $arsave_appuser);
                    }
                } else {
                    $sqlGetAppGroup = $this->getApproveGroup($group, $ecodeArray)->row();
                    $sqlGetEmail    = $this->getemailpaygroup($ecodeArray)->row();

                    $arsave_appuser = [
                        "apv_group"       => $group,
                        "apv_user"        => $sqlGetAppGroup->app_user,
                        "apv_ecode"       => $ecodeArray,
                        "apv_posiname"    => $sqlGetAppGroup->app_posiname,
                        "apv_formno"      => $this->input->post("formno"),
                        "apv_areaidgroup" => $sqlGetAppGroup->app_areaid,
                        "apv_areaid"      => $this->input->post("dataareaid"),
                        "apv_email"       => $sqlGetEmail->memberemail,
                        "apv_datetime"    => date("Y-m_d H:i:s"),
                    ];
                    $this->db->insert("approve_user", $arsave_appuser);
                }

                // savedata to approve_user
            }

            if ($paygroup == "5" && $this->input->post("approveType") == "yes") {
                $this->email->sendto_purchase_G5($formno);
            } else if ($paygroup == "4" && $this->input->post("approveType") == "yes") {
                $this->email->sendto_otherMgr_G4($formno);
            } else if ($paygroup == "3" && $this->input->post("approveType") == "yes") {
                $this->email->sendto_Exe_G3($formno);
            } else if ($paygroup == "2" && $this->input->post("approveType") == "yes") {
                $this->email->sendto_Exe_G2($formno);
            } else if ($paygroup == "1" && $this->input->post("approveType") == "yes") {
                $this->email->sendto_Exe_G1($formno);
            } else if ($paygroup == "0" && $this->input->post("approveType") == "yes") {
                $this->email->sendto_Exe_G0($formno);
            }

            $output = [
                "msg"    => "บันทึกข้อมูลสำเร็จ",
                "status" => "Update Data Success",
                "test"   => is_array($ecodeArray),
            ];
        } else {
            $output = [
                "msg"    => "บันทึกข้อมูลไม่สำเร็จ",
                "status" => "Update Data Not Success",
            ];
        }
        echo json_encode($output);
    }
    private function getApproveGroup($group, $ecode)
    {
        if ($group != "" && $ecode != "") {
            $sql = $this->db->query("SELECT
            app_user,
            app_posiname,
            app_areaid
            FROM approve_group WHERE app_group = ? AND app_ecode = ?
            ", [$group, $ecode]);

            return $sql;
        }
    }
    private function getemailpaygroup($ecode)
    {
        if ($ecode != "") {
            $this->db2 = $this->load->database('saleecolour', true);
            $sql       = $this->db2->query("SELECT
                memberemail
            FROM
                member
            WHERE
                resigned != ?
                AND ecode = ?", [1, $ecode]);
            return $sql;
        }
    }

    public function getdataG4()
    {
        if ($this->input->post("action") == "getdataG4") {
            $sql = $this->db->query("SELECT
            approve_group.app_autoid,
            approve_group.app_group,
            approve_group.app_username,
            approve_group.app_user,
            approve_group.app_ecode,
            approve_group.app_deptcode,
            approve_group.app_posiname,
            approve_group.app_areaid,
            approve_group.app_status
            FROM
            approve_group
            WHERE
            approve_group.app_group = ? AND
            approve_group.app_areaid != ? AND app_status = ?",
                [4, 'tb', 'Active']);

            $output = [
                "msg"    => "ดึงข้อมูลกลุ่ม 4 สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->result(),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูลกลุ่ม 4 ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }
        echo json_encode($output);

    }

    public function getdataG3()
    {
        if ($this->input->post("action") == "getdataG3") {
            $sql = $this->db->query("SELECT
            approve_group.app_autoid,
            approve_group.app_group,
            approve_group.app_username,
            approve_group.app_user,
            approve_group.app_ecode,
            approve_group.app_deptcode,
            approve_group.app_posiname,
            approve_group.app_areaid,
            approve_group.app_status
            FROM
            approve_group
            WHERE
            approve_group.app_group = ? AND
            approve_group.app_areaid != ? AND app_status = ?",
                [3, 'tb', 'Active']);

            $output = [
                "msg"    => "ดึงข้อมูลกลุ่ม 3 สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->result(),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูลกลุ่ม 3 ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }
        echo json_encode($output);

    }

    public function getdataG2()
    {
        if ($this->input->post("action") == "getdataG2") {
            $sql = $this->db->query("SELECT
            approve_group.app_autoid,
            approve_group.app_group,
            approve_group.app_username,
            approve_group.app_user,
            approve_group.app_ecode,
            approve_group.app_deptcode,
            approve_group.app_posiname,
            approve_group.app_areaid,
            approve_group.app_status
            FROM
            approve_group
            WHERE
            approve_group.app_group = ? AND
            approve_group.app_areaid != ? AND app_status = ?",
                [2, 'tb', 'Active']);

            $output = [
                "msg"    => "ดึงข้อมูลกลุ่ม 2 สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->result(),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูลกลุ่ม 2 ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }
        echo json_encode($output);

    }

    public function getdataG1()
    {
        if ($this->input->post("action") == "getdataG1") {
            $sql = $this->db->query("SELECT
            approve_group.app_autoid,
            approve_group.app_group,
            approve_group.app_username,
            approve_group.app_user,
            approve_group.app_ecode,
            approve_group.app_deptcode,
            approve_group.app_posiname,
            approve_group.app_areaid,
            approve_group.app_status
            FROM
            approve_group
            WHERE
            approve_group.app_group = ? AND
            approve_group.app_areaid != ? AND app_status = ?",
                [1, 'tb', 'Active']);

            $output = [
                "msg"    => "ดึงข้อมูลกลุ่ม 1 สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->result(),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูลกลุ่ม 1 ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }
        echo json_encode($output);

    }

    public function getdataG0()
    {
        if ($this->input->post("action") == "getdataG0") {
            $sql = $this->db->query("SELECT
            approve_group.app_autoid,
            approve_group.app_group,
            approve_group.app_username,
            approve_group.app_user,
            approve_group.app_ecode,
            approve_group.app_deptcode,
            approve_group.app_posiname,
            approve_group.app_areaid,
            approve_group.app_status
            FROM
            approve_group
            WHERE
            approve_group.app_group = ? AND
            approve_group.app_areaid != ? AND app_status = ?",
                [0, 'tb', 'Active']);

            $output = [
                "msg"    => "ดึงข้อมูลกลุ่ม 0 สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->result(),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูลกลุ่ม 0 ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }
        echo json_encode($output);

    }

    public function getExecutiveData()
    {
        if ($this->input->post("action") == "getExecutiveData") {
            $formno = $this->input->post('formno');
            $sql    = $this->db->query("SELECT
            apv_ecode ,
            apv_posiname,
            apv_user,
            apv_group,
            apv_areaid,
            apv_approve,
            apv_approve_memo,
            apv_approve_user,
            DATE_FORMAT(apv_approve_datetime, '%d/%m/%Y %H:%i:%s') AS apv_approve_datetime
            FROM approve_user WHERE apv_formno = '$formno'
            ");

            $output = [
                "msg"    => "ดึงข้อมูล Executive สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->result(),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูล Excetive ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }
        echo json_encode($output);
    }

    public function saveExecutiveG4()
    {
        if ($this->input->post("action") == "saveExecutiveG4") {
            $appType  = $this->input->post("appType");
            $appUser  = $this->input->post("appUser");
            $appEcode = $this->input->post("appEcode");
            $appMemo  = $this->input->post("appMemo");
            $formno   = $this->input->post("formno");

            if ($appMemo == null || $appMemo == "" || $appMemo == "null") {
                $appMemo = "";
            }

            //check Approve Type
            $mainStatus = "";
            if ($appType == "yes") {
                $mainStatus = "Executive Group 4 Approved";
            } else {
                $mainStatus = "Executive Group 4 Not Approve";
            }

            //update old status
            //Send to notifycenter
            $notifyformno      = $formno;
            $notifyprogramname = "Purchase Plus";
            $notifystatus      = "action done";
            $notifytype        = "take action";

            $this->notifycenter->updatedataAction_template($notifyformno, $notifyprogramname, $notifystatus, $notifytype);
            //Send to notifycenter
            //update old status

            $arsaveExeG4 = [
                "apv_approve"          => $appType,
                "apv_approve_memo"     => $appMemo,
                "apv_approve_user"     => $appUser,
                "apv_approve_datetime" => date("Y-m-d H:i:s"),
            ];
            $this->db->where("apv_ecode", $appEcode);
            $this->db->where("apv_formno", $formno);
            $this->db->update("approve_user", $arsaveExeG4);

            $arSaveStatus = [
                "m_status"         => $mainStatus,
                "m_datetimeupdate" => date("Y-m-d H:i:s"),
            ];
            $this->db->where("m_formno", $formno);
            $this->db->update("main", $arSaveStatus);

            if ($appType == "yes") {
                $this->email->sendto_purchase_G4($formno);
            }

            $output = [
                "msg"    => "บันทึกข้อมูล G4 สำเร็จ",
                "status" => "Update Data Success",
            ];
        } else {
            $output = [
                "msg"    => "บันทึกข้อมูล G4 ไม่สำเร็จ",
                "status" => "Update Data Not Success",
            ];
        }

        echo json_encode($output);
    }

    public function saveExecutiveG3()
    {
        if ($this->input->post("action") == "saveExecutiveG3") {
            $appType  = $this->input->post("appType");
            $appUser  = $this->input->post("appUser");
            $appEcode = $this->input->post("appEcode");
            $appMemo  = $this->input->post("appMemo");
            $formno   = $this->input->post("formno");

            if ($appMemo == null || $appMemo == "" || $appMemo == "null") {
                $appMemo = "";
            }

            //check Approve Type
            $mainStatus = "";
            if ($appType == "yes") {
                $mainStatus = "Executive Group 3 Approved";
            } else {
                $mainStatus = "Executive Group 3 Not Approve";
            }

            //update old status
            //Send to notifycenter
            $notifyformno      = $formno;
            $notifyprogramname = "Purchase Plus";
            $notifystatus      = "action done";
            $notifytype        = "take action";

            $this->notifycenter->updatedataAction_template($notifyformno, $notifyprogramname, $notifystatus, $notifytype);
            //Send to notifycenter
            //update old status

            $arsaveExeG3 = [
                "apv_approve"          => $appType,
                "apv_approve_memo"     => $appMemo,
                "apv_approve_user"     => $appUser,
                "apv_approve_datetime" => date("Y-m-d H:i:s"),
            ];
            $this->db->where("apv_ecode", $appEcode);
            $this->db->where("apv_formno", $formno);
            $this->db->update("approve_user", $arsaveExeG3);

            $arSaveStatus = [
                "m_status"         => $mainStatus,
                "m_datetimeupdate" => date("Y-m-d H:i:s"),
            ];
            $this->db->where("m_formno", $formno);
            $this->db->update("main", $arSaveStatus);

            if ($appType == "yes") {
                $this->email->sendto_purchase_G3($formno);
            }

            $output = [
                "msg"    => "บันทึกข้อมูล G3 สำเร็จ",
                "status" => "Update Data Success",
            ];
        } else {
            $output = [
                "msg"    => "บันทึกข้อมูล G3 ไม่สำเร็จ",
                "status" => "Update Data Not Success",
            ];
        }

        echo json_encode($output);
    }

    public function saveExecutiveG2()
    {
        if ($this->input->post("action") == "saveExecutiveG2") {
            $appType  = $this->input->post("approval");
            $appUser  = $this->input->post("userpost");
            $appEcode = $this->input->post("ecodepost");
            $appMemo  = $this->input->post("memo");
            $formno   = $this->input->post("formno");

            if ($appMemo == null || $appMemo == "" || $appMemo == "null") {
                $appMemo = "";
            }

            //check Approve Type
            $mainStatus = "";
            if ($appType == "yes") {
                $mainStatus = "Executive Group 2 Approved";
            } else {
                $mainStatus = "Executive Group 2 Not Approve";
            }

            $arsaveExeG2 = [
                "apv_approve"          => $appType,
                "apv_approve_memo"     => $appMemo,
                "apv_approve_user"     => $appUser,
                "apv_approve_datetime" => date("Y-m-d H:i:s"),
            ];
            $this->db->where("apv_ecode", $appEcode);
            $this->db->where("apv_formno", $formno);
            $this->db->update("approve_user", $arsaveExeG2);

            $sqlCheckTypeYes = $this->db->query("SELECT apv_approve FROM approve_user WHERE apv_formno = '$formno' AND apv_approve IS NOT NULL");
            $sqlTotalApp     = $this->db->query("SELECT apv_ecode FROM approve_user WHERE apv_formno = '$formno'");

            if ($appType == "yes") {
                if ($sqlCheckTypeYes->num_rows() == $sqlTotalApp->num_rows()) {
                    //update old status
                    //Send to notifycenter
                    $notifyformno      = $formno;
                    $notifyprogramname = "Purchase Plus";
                    $notifystatus      = "action done";
                    $notifytype        = "take action";

                    $this->notifycenter->updatedataAction_template($notifyformno, $notifyprogramname, $notifystatus, $notifytype);
                    //Send to notifycenter
                    //update old status

                    $arSaveStatus = [
                        "m_status"         => $mainStatus,
                        "m_datetimeupdate" => date("Y-m-d H:i:s"),
                    ];
                    $this->db->where("m_formno", $formno);
                    $this->db->update("main", $arSaveStatus);
                    $this->email->sendto_purchase_G2($formno);
                }
            } else {
                //update old status
                //Send to notifycenter
                $notifyformno      = $formno;
                $notifyprogramname = "Purchase Plus";
                $notifystatus      = "action done";
                $notifytype        = "take action";

                $this->notifycenter->updatedataAction_template($notifyformno, $notifyprogramname, $notifystatus, $notifytype);
                //Send to notifycenter
                //update old status

                $arSaveStatus = [
                    "m_status"         => $mainStatus,
                    "m_datetimeupdate" => date("Y-m-d H:i:s"),
                ];
                $this->db->where("m_formno", $formno);
                $this->db->update("main", $arSaveStatus);
                // $this->email->sendto_purchase_G2($formno);
            }

            $output = [
                "msg"    => "บันทึกข้อมูล G2 สำเร็จ",
                "status" => "Update Data Success",
            ];
        } else {
            $output = [
                "msg"    => "บันทึกข้อมูล G2 ไม่สำเร็จ",
                "status" => "Update Data Not Success",
            ];
        }

        echo json_encode($output);
    }

    public function saveExecutiveG1()
    {
        if ($this->input->post("action") == "saveExecutiveG1") {
            $appType  = $this->input->post("approval");
            $appUser  = $this->input->post("userpost");
            $appEcode = $this->input->post("ecodepost");
            $appMemo  = $this->input->post("memo");
            $formno   = $this->input->post("formno");

            if ($appMemo == null || $appMemo == "" || $appMemo == "null") {
                $appMemo = "";
            }

            //check Approve Type
            $mainStatus = "";
            if ($appType == "yes") {
                $mainStatus = "Executive Group 1 Approved";
            } else {
                $mainStatus = "Executive Group 1 Not Approve";
            }

            $arsaveExeG1 = [
                "apv_approve"          => $appType,
                "apv_approve_memo"     => $appMemo,
                "apv_approve_user"     => $appUser,
                "apv_approve_datetime" => date("Y-m-d H:i:s"),
            ];
            $this->db->where("apv_ecode", $appEcode);
            $this->db->where("apv_formno", $formno);
            $this->db->update("approve_user", $arsaveExeG1);

            $sqlCheckTypeYes = $this->db->query("SELECT apv_approve FROM approve_user WHERE apv_formno = '$formno' AND apv_approve IS NOT NULL");
            $sqlTotalApp     = $this->db->query("SELECT apv_ecode FROM approve_user WHERE apv_formno = '$formno'");

            if ($appType == "yes") {
                if ($sqlCheckTypeYes->num_rows() == $sqlTotalApp->num_rows()) {

                    //update old status
                    //Send to notifycenter
                    $notifyformno      = $formno;
                    $notifyprogramname = "Purchase Plus";
                    $notifystatus      = "action done";
                    $notifytype        = "take action";

                    $this->notifycenter->updatedataAction_template($notifyformno, $notifyprogramname, $notifystatus, $notifytype);
                    //Send to notifycenter
                    //update old status

                    $arSaveStatus = [
                        "m_status"         => $mainStatus,
                        "m_datetimeupdate" => date("Y-m-d H:i:s"),
                    ];
                    $this->db->where("m_formno", $formno);
                    $this->db->update("main", $arSaveStatus);
                    $this->email->sendto_purchase_G1($formno);
                }
            } else {
                //update old status
                //Send to notifycenter
                $notifyformno      = $formno;
                $notifyprogramname = "Purchase Plus";
                $notifystatus      = "action done";
                $notifytype        = "take action";

                $this->notifycenter->updatedataAction_template($notifyformno, $notifyprogramname, $notifystatus, $notifytype);
                //Send to notifycenter
                //update old status

                $arSaveStatus = [
                    "m_status"         => $mainStatus,
                    "m_datetimeupdate" => date("Y-m-d H:i:s"),
                ];
                $this->db->where("m_formno", $formno);
                $this->db->update("main", $arSaveStatus);
                // $this->email->sendto_purchase_G1($formno);
            }

            $output = [
                "msg"    => "บันทึกข้อมูล G1 สำเร็จ",
                "status" => "Update Data Success",
            ];
        } else {
            $output = [
                "msg"    => "บันทึกข้อมูล G1 ไม่สำเร็จ",
                "status" => "Update Data Not Success",
            ];
        }

        echo json_encode($output);
    }

    public function saveExecutiveG0()
    {
        if ($this->input->post("action") == "saveExecutiveG0") {
            $appType  = $this->input->post("approval");
            $appUser  = $this->input->post("userpost");
            $appEcode = $this->input->post("ecodepost");
            $appMemo  = $this->input->post("memo");
            $formno   = $this->input->post("formno");

            if ($appMemo == null || $appMemo == "" || $appMemo == "null") {
                $appMemo = "";
            }

            //check Approve Type
            $mainStatus = "";
            if ($appType == "yes") {
                $mainStatus = "Executive Group 0 Approved";
            } else {
                $mainStatus = "Executive Group 0 Not Approve";
            }

            $arsaveExeG0 = [
                "apv_approve"          => $appType,
                "apv_approve_memo"     => $appMemo,
                "apv_approve_user"     => $appUser,
                "apv_approve_datetime" => date("Y-m-d H:i:s"),
            ];
            $this->db->where("apv_ecode", $appEcode);
            $this->db->where("apv_formno", $formno);
            $this->db->update("approve_user", $arsaveExeG0);

            $sqlCheckTypeYes = $this->db->query("SELECT apv_approve FROM approve_user WHERE apv_formno = '$formno' AND apv_approve IS NOT NULL");
            $sqlTotalApp     = $this->db->query("SELECT apv_ecode FROM approve_user WHERE apv_formno = '$formno'");

            if ($appType == "yes") {
                if ($sqlCheckTypeYes->num_rows() == $sqlTotalApp->num_rows()) {

                    //update old status
                    //Send to notifycenter
                    $notifyformno      = $formno;
                    $notifyprogramname = "Purchase Plus";
                    $notifystatus      = "action done";
                    $notifytype        = "take action";

                    $this->notifycenter->updatedataAction_template($notifyformno, $notifyprogramname, $notifystatus, $notifytype);
                    //Send to notifycenter
                    //update old status

                    $arSaveStatus = [
                        "m_status"         => $mainStatus,
                        "m_datetimeupdate" => date("Y-m-d H:i:s"),
                    ];
                    $this->db->where("m_formno", $formno);
                    $this->db->update("main", $arSaveStatus);
                    $this->email->sendto_purchase_G0($formno);
                }
            } else {
                //update old status
                //Send to notifycenter
                $notifyformno      = $formno;
                $notifyprogramname = "Purchase Plus";
                $notifystatus      = "action done";
                $notifytype        = "take action";

                $this->notifycenter->updatedataAction_template($notifyformno, $notifyprogramname, $notifystatus, $notifytype);
                //Send to notifycenter
                //update old status

                $arSaveStatus = [
                    "m_status"         => $mainStatus,
                    "m_datetimeupdate" => date("Y-m-d H:i:s"),
                ];
                $this->db->where("m_formno", $formno);
                $this->db->update("main", $arSaveStatus);
                // $this->email->sendto_purchase_G0($formno);
            }

            $output = [
                "msg"    => "บันทึกข้อมูล G0 สำเร็จ",
                "status" => "Update Data Success",
            ];
        } else {
            $output = [
                "msg"    => "บันทึกข้อมูล G0 ไม่สำเร็จ",
                "status" => "Update Data Not Success",
            ];
        }

        echo json_encode($output);
    }

    public function savePurchase()
    {
        if ($this->input->post("action") == "savePurchase") {
            // $this->load->model("email_model" , "email");
            $m_approve_pur      = $this->input->post("m_approve_pur");
            $m_userpost_pur     = $this->input->post("m_userpost_pur");
            $m_ecodepost_pur    = $this->input->post("m_ecodepost_pur");
            $m_memo_pur         = $this->input->post("m_memo_pur");
            $m_datetimepost_pur = date("Y-m-d H:i:s");
            $formno             = $this->input->post("formno");
            $paygroup           = $this->input->post("paygroup");

            if ($m_approve_pur == "yes") {
                $status = "Purchase Verified";
            } else {
                $status = "Purchase Not Approve";
            }

            //update old status
            //Send to notifycenter
            $notifyformno      = $formno;
            $notifyprogramname = "Purchase Plus";
            $notifystatus      = "action done";
            $notifytype        = "take action";

            $this->notifycenter->updatedataAction_template($notifyformno, $notifyprogramname, $notifystatus, $notifytype);
            //Send to notifycenter
            //update old status

            $arsavePur = [
                "m_approve_pur"      => $m_approve_pur,
                "m_memo_pur"         => $m_memo_pur,
                "m_userpost_pur"     => $m_userpost_pur,
                "m_ecodepost_pur"    => $m_ecodepost_pur,
                "m_datetimepost_pur" => $m_datetimepost_pur,
                "m_status"           => $status,
            ];

            $this->db->where("m_formno", $formno);
            $this->db->update("main", $arsavePur);

            if ($paygroup == "5" && $m_approve_pur == "yes") {
                $this->email->sendto_createPO_G5($formno);
            } else if ($paygroup == "4" && $m_approve_pur == "yes") {
                $this->email->sendto_createPO_G4($formno);
            } else if ($paygroup == "3" && $m_approve_pur == "yes") {
                $this->email->sendto_createPO_G3($formno);
            } else if ($paygroup == "2" && $m_approve_pur == "yes") {
                $this->email->sendto_createPO_G2($formno);
            } else if ($paygroup == "1" && $m_approve_pur == "yes") {
                $this->email->sendto_createPO_G1($formno);
            } else if ($paygroup == "0" && $m_approve_pur == "yes") {
                $this->email->sendto_createPO_G0($formno);
            }

            $output = [
                "msg"    => "บันทึกข้อมูลส่วนของจัดซื้อสำเร็จ",
                "status" => "Update Data Success",
            ];
        } else {
            $output = [
                "msg"    => "บันทึกข้อมูลส่วนของจัดซื้อไม่สำเร็จ",
                "status" => "Update Data Not Success",
            ];
        }
        echo json_encode($output);
    }

    public function getPayGroupMaxMoney()
    {
        if ($this->input->post("pay_doctype") != "") {
            $pay_doctype = $this->input->post("pay_doctype");
            $sql         = $this->db->query("SELECT max(pay_scope_end)as maxprice FROM pay_group WHERE pay_doctype = '$pay_doctype';");

            $output = [
                "msg"    => "ดึงข้อมูลวงเงินสูงสุดสำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->row(),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูลวงเงินสูงสุดไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }
        echo json_encode($output);
    }

    public function getDataforprint()
    {
        if ($this->input->post("formno") != "") {
            $formno = $this->input->post("formno");
            $sql    = $this->db->query("SELECT
            m_dataareaid,
            CASE
                WHEN m_dataareaid = 'sln' THEN 'Salee Colour Public Co.,Ltd.'
                WHEN m_dataareaid = 'poly' THEN 'Poly Meritasia Co.,Ltd.'
                WHEN m_dataareaid = 'ca' THEN 'Composite Asia Co.,Ltd.'
                WHEN m_dataareaid = 'st' THEN 'Subterra Co.,Ltd.'
                WHEN m_dataareaid = 'tbb' THEN 'The bubbles Co.,Ltd.'
            END AS companyfullname,
            m_vendid,
            m_vendname,
            m_prno,
            m_paymtermid,
            DATE_FORMAT(m_datetime_create, '%d/%m/%Y') AS m_datetime_create,
            m_department,
            m_ecode,
            DATE_FORMAT(m_datetimepost, '%d/%m/%Y') AS m_datetimepost,
            DATE_FORMAT(m_date_req, '%d/%m/%Y') AS m_date_req,
            m_memo,
            m_userpost_mgr,
            DATE_FORMAT(m_datetimepost_mgr, '%d/%m/%Y') AS m_datetimepost_mgr,
            m_formisono,
            DATE_FORMAT(m_date_delivery, '%d/%m/%Y') AS m_date_delivery,
            m_userpost_invest,
            DATE_FORMAT(m_datetimepost_invest , '%d/%m/%Y') AS m_datetimepost_invest
            FROM main WHERE m_formno = ?
            ", [$formno]);

            if ($sql->num_rows() != 0) {
                $ecode = $sql->row()->m_ecode;
            } else {
                $ecode = "";
            }

            $sqlDetail = $this->db->query("SELECT
                d_itemid,
                d_itemname,
                d_itemgroupid,
                d_itemdetail,
                d_itemqty,
                d_itemunit,
                d_itemprice,
                d_itempricesum,
                d_itemdiscount
                FROM details WHERE d_m_formno = ?
                ORDER BY d_autoid DESC
            ", [$formno]);

            $sqlExecutive = $this->db->query("SELECT
            apv_approve_user,
            DATE_FORMAT(apv_approve_datetime, '%d/%m/%Y') AS apv_approve_datetime
            FROM approve_user WHERE apv_formno = ?
            ORDER BY apv_autoid DESC
            ", [$formno]);

            $output = [
                "msg"         => "ดึงข้อมูลสำหรับปริ้น PR สำเร็จ",
                "status"      => "Select Data Success",
                "maindata"    => $sql->row(),
                "detailsdata" => $sqlDetail->result(),
                "approveuser" => $sqlExecutive->result(),
                "userRequest" => $this->getUsername($ecode),
                "datetimenow" => date("d-m-Y H:i:s"),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูลสำหรับปริ้นไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }
        echo json_encode($output);
    }
    private function getUsername($ecode)
    {
        if (! empty($ecode)) {
            $this->dbsalee = $this->load->database("saleecolour", true);
            $sql           = $this->dbsalee->query("SELECT
            Fname,
            Lname
            FROM member WHERE ecode = ?
            ", [$ecode]);
            if ($sql->num_rows() != 0) {
                return $sql->row()->Fname . " " . $sql->row()->Lname;
            }
        }
    }

    public function getData_po_sln()
    {
        if ($this->input->post("areaid") != "" && $this->input->post("pono") != "" && $this->input->post("department") != "") {

            $areaid     = $this->input->post("areaid");
            $pono       = $this->input->post("pono");
            $department = $this->input->post("department");

            $sql = $this->db_mssql->query("SELECT
            a.purchid as purchid ,
            a.purchorderdocnum as purchorderdocnum ,
            FORMAT(a.bpc_documentdate, 'dd-MM-yyyy') AS bpc_documentdate,
            a.purchaseorderid as purchaseorderid,
            FORMAT(a.purchorderdate, 'dd-MM-yyyy') AS purchorderdate,
            a.currencycode as currencycode ,
            a.bpc_purchasereqno as bpc_purchasereqno ,
            a.slc_amtintext as slc_amtintext ,
            a.deliveryname as deliveryname ,
            a.deliveryaddress as deliveryaddress,
            FORMAT(a.deliverydate, 'dd-MM-yyyy') AS deliverydate,
            a.payment as payment,
            a.sumtax as sumtax ,
            a.salesorderbalance as salesorderbalance,
            a.sumlinedisc as sumlinedisc,
            a.amount as amount,
            a.invoiceaccount as vendid,
			e.street+' '+e.zipcode as vendaddress,
			d.purchname as vendname,
            b.phone as vendphone,
            b.telefax as vendfax,
            c.num as num,
            c.description as description,
            d.email as email,
            d.bpc_remark as bpc_remark,
            d.purchplacer as ecodepostpo
            FROM vendpurchorderjour a
            INNER JOIN vendtable b ON a.dataareaid = b.dataareaid AND a.invoiceaccount = b.accountnum
            INNER JOIN dimensions c ON a.dataareaid = c.dataareaid
            INNER JOIN purchtable d ON a.dataareaid = d.dataareaid AND a.purchid = d.purchid
			INNER JOIN slc_vendaddress e ON a.dataareaid = e.dataareaid AND a.invoiceaccount = e.accountnum
            WHERE a.dataareaid = ? AND a.purchid = ? AND c.num = ?
            ORDER BY purchaseorderid DESC
            ", [$areaid, $pono, $department]);

            $output = [
                "msg"          => "ดึงข้อมูลรายการ PO สำเร็จ",
                "status"       => "Select Data Success",
                "resultPoMain" => $sql->result(),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูลรายการ PO ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }
        echo json_encode($output);
    }

    public function getUserRequest()
    {
        if ($this->input->post("ecode") != "") {
            $ecode  = $this->input->post("ecode");
            $formno = $this->input->post("formno");

            $sqlExecutive = $this->db->query("SELECT
            apv_approve_user,
            DATE_FORMAT(apv_approve_datetime, '%d/%m/%Y') AS apv_approve_datetime
            FROM approve_user WHERE apv_formno = ?
            ORDER BY apv_approve_datetime DESC
            ", [$formno]);

            $getInvest = $this->db->query("SELECT
            m_userpost_invest,
            DATE_FORMAT(m_datetimepost_invest , '%d/%m/%Y') AS m_datetimepost_invest
            FROM main WHERE m_formno = ?
            ", [$formno]);

            $output = [
                "msg"         => "ดึงข้อมูล User Request สำเร็จ",
                "status"      => "Select Data Success",
                "result"      => $this->getUsername($ecode),
                "approveUser" => $sqlExecutive->result(),
                "invest"      => $getInvest->row(),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูล User Request wม่สำเร็จสำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }
        echo json_encode($output);
    }

    public function getdataDetail()
    {
        if ($this->input->post("areaid") != "" && $this->input->post("pono") != "" && $this->input->post("purchaseorderid") != "") {
            $areaid          = $this->input->post("areaid");
            $pono            = $this->input->post("pono");
            $purchaseorderid = $this->input->post("purchaseorderid");

            $sql = $this->db_mssql->query("SELECT
            a.origpurchid ,
            a.itemid ,
            a.qty ,
            a.purchunit ,
            a.purchprice ,
            a.lineamount ,
            a.discamount ,
            a.lineamounttax ,
            a.name,
            a.dataareaid ,
            a.bpc_discount,
            b.inventbatchid , *
            FROM VendPurchOrderTrans a
            LEFT JOIN inventdim b ON a.inventdimid = b.inventdimid AND a.dataareaid = b.dataareaid
            WHERE a.dataareaid = ? AND a.OrigPurchId = ? AND a.purchaseorderid = ?
            ORDER BY a.linenum ASC
            ", [$areaid, $pono, $purchaseorderid]);

            $output = [
                "msg"    => "ดึงข้อมูลรายละเอียดของ Po สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->result(),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูลไม่สำเร็จ",
                "status" => "Select Data Succes",
            ];
        }
        echo json_encode($output);
    }

    public function saveInvesApprove()
    {
        if ($this->input->post("invesApproveType") != "") {

            $invesApproveType = $this->input->post("invesApproveType");
            $invesMemo        = $this->input->post("invesMemo");
            $invesUserpost    = $this->input->post("invesUserpost");
            $invesEcodepost   = $this->input->post("invesEcodepost");
            $formno           = $this->input->post("formno");

            //update old status
            //Send to notifycenter
            $notifyformno      = $formno;
            $notifyprogramname = "Purchase Plus";
            $notifystatus      = "action done";
            $notifytype        = "take action";

            $this->notifycenter->updatedataAction_template($notifyformno, $notifyprogramname, $notifystatus, $notifytype);
            //Send to notifycenter
            //update old status

            if ($invesApproveType == "yes") {
                $formStatus = "Investigator Approved";
            } else {
                $formStatus = "Investigator Not Approve";
            }

            $saveInvestor = [
                "m_approve_invest"      => $invesApproveType,
                "m_memo_invest"         => $invesMemo,
                "m_userpost_invest"     => $invesUserpost,
                "m_ecodepost_invest"    => $invesEcodepost,
                "m_datetimepost_invest" => date("Y-m-d H:i:s"),
                "m_status"              => $formStatus,
            ];

            $this->db->where("m_formno", $formno);
            $this->db->update("main", $saveInvestor);

            $this->email->sendto_manager($formno);

            $output = json_encode([
                "msg"    => "บันทึกข้อมูลผู้ตรวจสอบสำเร็จ",
                "status" => "Update Data Success",
            ]);
        } else {
            $output = json_encode([
                "msg"    => "บันทึกข้อมูลผู้ตรวจสอบไม่สำเร็จ",
                "status" => "Update Data Not Success",
            ]);
        }
        echo $output;
    }

    public function delFile()
    {
        if ($this->input->post("data_autoid") != "") {
            $data_autoid = $this->input->post("data_autoid");
            $data_path   = $this->input->post("data_path");
            $data_name   = $this->input->post("data_name");

            // remove file
            $filepath = $data_path . $data_name;
            if (file_exists($filepath)) {
                if (unlink($filepath)) {
                    $this->db->where("f_autoid", $data_autoid);
                    $this->db->delete("files");

                    $output = [
                        "msg"    => "ลบไฟล์สำเร็จ",
                        "status" => "Delete Data Success",
                    ];
                } else {
                    $output = [
                        "msg"    => "ลบไฟล์ไม่สำเร็จ",
                        "status" => "Delete Data Not Success",
                    ];
                }
            } else {
                $output = [
                    "msg"    => "ไม่พบไฟล์ดังกล่าว",
                    "status" => "Not Found File!",
                ];
            }

        } else {
            $output = [
                "msg"    => "พบข้อผิดพลาด",
                "status" => "Delete Data Not Success",
            ];
        }

        echo json_encode($output);
    }

    public function getStatus()
    {
        $sql = $this->db->query("SELECT
        m_status
        FROM main GROUP BY m_status ORDER BY m_status ASC
        ");

        $output = [
            "msg"    => "ดึงข้อมูล Status สำเร็จ",
            "status" => "Select Data Success",
            "result" => $sql->result(),
        ];

        echo json_encode($output);
    }

    //2025-02-28
    public function getSendToVendorHistory()
    {
        if (! empty($this->input->post("pono")) && ! empty($this->input->post("ponoDocnum")) && ! empty($this->input->post("formno"))) {
            $pono       = $this->input->post("pono");
            $ponoDocnum = $this->input->post("ponoDocnum");
            $formno     = $this->input->post("formno");

            $sql = $this->db->query("SELECT
            sp_prno,
            sp_pono,
            sp_vendid,
            sp_vendname,
            sp_userpost,
            sp_mailto,
            DATE_FORMAT(sp_datetime , '%d-%m-%Y %H:%i:%s') AS sp_datetime
            FROM sendpo_log WHERE sp_formno = ? AND sp_pono = ? AND sp_pono_docnum = ? ORDER BY sp_datetime DESC
            ", [$formno, $pono, $ponoDocnum]);

            $output = [
                "msg"    => "ดึงข้อมูลประวัติการส่ง Email สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->result(),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูลประวัติการส่ง Email ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }
        echo json_encode($output);
    }

    public function getLastVendorEmail()
    {
        if (! empty($this->input->post("accountnum")) && ! empty($this->input->post("dataareaid"))) {
            $accountnum = $this->input->post("accountnum");
            $dataareaid = $this->input->post("dataareaid");

            $sql = $this->db->query("SELECT
            sp_mailto
            FROM sendpo_log WHERE sp_vendid = ? AND sp_dataareaid = ? ORDER BY sp_datetime DESC LIMIT 1
            ", [$accountnum, $dataareaid]);

            $output = [
                "msg"    => "ดึงข้อมูลรายชื่อ Email ล่าสุดสำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->row(),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูลรายชื่อ Email ล่าสุดไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }
        echo json_encode($output);
    }

    public function checkSendEmailHistory()
    {
        if (! empty($this->input->post("pono")) && ! empty($this->input->post("ponoDocnum")) && ! empty($this->input->post("formno"))) {
            $pono       = $this->input->post("pono");
            $ponoDocnum = $this->input->post("ponoDocnum");
            $formno     = $this->input->post("formno");

            $sql = $this->db->query("SELECT
            sp_mailto
            FROM sendpo_log WHERE sp_pono = ? AND sp_pono_docnum = ? AND sp_formno = ?
            ", [$pono, $ponoDocnum, $formno]);

            $output = [
                "msg"    => "เช็กข้อมูลการส่ง Email สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->num_rows(),
            ];
        } else {
            $output = [
                "msg"    => "เช็กข้อมูลการส่ง Email ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }
        echo json_encode($output);
    }

}
/* End of file ModelName.php */
