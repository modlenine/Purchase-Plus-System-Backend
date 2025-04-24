<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Compareapi_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        //Do your magic here
        $this->db_compare = $this->load->database('compare_vendor', true);
        $this->db_mssql   = $this->load->database("mssql", true);
        date_default_timezone_set("Asia/Bangkok");
    }

    public function getItemid()
    {
        header('Content-Type: application/json');

        $dataareaid = $this->input->post("dataareaid");
        $itemid     = $this->input->post("itemid");

        if (! empty($dataareaid) && ! empty($itemid)) {

            $sql = "SELECT TOP 100
                        itm.itemid,
                        itm.itemname,
                        itm.itemgroupid,
                        itmm.unitid
                    FROM inventtable itm
                    JOIN INVENTTABLEMODULE itmm
                        ON itm.itemid = itmm.itemid
                        AND itm.dataareaid = itmm.dataareaid
                    WHERE itm.itemid LIKE ?
                        AND itm.dataareaid = ?
                        AND itm.slc_itemactivestatusid = 0
                        AND itmm.moduletype = (
                            SELECT MAX(moduletype)
                            FROM INVENTTABLEMODULE
                            WHERE itemid = itm.itemid
                            AND dataareaid = itm.dataareaid
                        )";

            // ใช้ binding เพื่อความปลอดภัย
            $query = $this->db_mssql->query($sql, ["%$itemid%", $dataareaid]);

            if ($query->num_rows() > 0) {
                echo json_encode([
                    'status' => 'success',
                    'result' => $query->result(),
                ]);
            } else {
                echo json_encode([
                    'status'  => 'not_found',
                    'message' => 'ไม่พบข้อมูลสินค้า',
                ]);
            }
        } else {
            echo json_encode([
                'status'  => 'error',
                'message' => 'ข้อมูลไม่ครบถ้วน',
            ]);
        }
    }

    public function saveCompareVendor()
    {
        //checkdata
        if (! empty($this->input->post("dataareaid")) && ! empty($this->input->post("selected_vendor_name"))) {
            $this->db_compare->trans_start();

            // Master Data
            $formno              = getCompareFormno();
            $dataareaid          = $this->input->post("dataareaid");
            $accountnum          = $this->input->post("selected_vendor_accountnum");
            $vendor_name         = $this->input->post("selected_vendor_name");
            $reason              = $this->input->post("reason");
            $user_ecode          = $this->input->post("user_ecode");
            $user_dept           = $this->input->post("user_dept");
            $user_deptcode       = $this->input->post("user_deptcode");
            $user_name           = $this->input->post("user_name");
            $created_datetime    = Date("Y-m-d H:i:s");
            $selectedVendorIndex = $this->input->post("selectedVendorIndex");

            //vendor
            $vendors = json_decode($this->input->post("vendors"), true);
            // Item
            $items = json_decode($this->input->post("items"), true);

            $master_data = [
                "formno"          => $formno,
                "dataareaid"      => $dataareaid,
                "accountnum"      => $accountnum,
                "vendor_index"    => $selectedVendorIndex,
                "reason"          => $reason,
                "user_create"     => $user_name,
                "ecode_create"    => $user_ecode,
                "dept_create"     => $user_dept,
                "deptcode_create" => $user_deptcode,
                "datetime_create" => $created_datetime,
                "compare_status"  => "Pending Send",
                "last_updated"    => Date("Y-m-d H:i:s"),
            ];

            $this->db_compare->insert("compare_master", $master_data);
            $compare_id = $this->db_compare->insert_id();
            //vendor data insert
            foreach ($vendors as $index => $vendor) {
                $vendor_data = [
                    "compare_id"     => $compare_id,
                    "compare_formno" => $formno,
                    "vendor_index"   => $index,
                    "vendor_name"    => $vendor['name'],
                    "accountnum"     => $vendor['accountnum'],
                    "dataareaid"     => $vendor['dataareaid'],
                ];

                $this->db_compare->insert("compare_vendors", $vendor_data);
            }

            $item_index = 0;
            foreach ($items as $item) {
                foreach ($item['prices'] as $index => $price) {
                    $itemdetail = [
                        "compare_id"     => $compare_id,
                        "compare_formno" => $formno,
                        "itemid"         => $item['itemid'],
                        "itemname"       => $item['itemname'],
                        "itemgroupid"    => $item['itemgroupid'],
                        "itemdetail"     => $item['itemdetail'],
                        "itemunit"       => $item['itemunit'],
                        "vendor_index"   => $index,
                        "item_index"     => $item_index,
                        "price"          => $price,
                        "no_quoted"      => $item['no_quoted'][$index] ?? false,  // ✅ ไม่ต้องแปลง
                    ];

                    $this->db_compare->insert("compare_items", $itemdetail);
                }
                $item_index++;
            }

            //file
            $file = 'attachments';
            uploadFile_compare($file, $formno, $compare_id);
            //file

            $this->db_compare->trans_complete();

            if ($this->db_compare->trans_status() === false) {
                echo json_encode([
                    "msg"    => "บันทึกข้อมูลไม่สำเร็จ",
                    "status" => "not success",
                ]);
            } else {
                echo json_encode([
                    "msg"    => "บันทึกข้อมูลสำเร็จ",
                    "status" => "success",
                ]);
            }

        } else {
            echo json_encode([
                "msg" => "ไม่สามารถบันทึกข้อมูลได้",
            ]);
        }
    }

    public function saveCompareVendorEdit()
    {
        //checkdata
        if (! empty($this->input->post("formno")) && ! empty($this->input->post("compare_id"))) {
            $this->db_compare->trans_start();

            $compare_id   = $this->input->post("compare_id");
            $last_updated = $this->input->post("last_updated");

            //check Permission Update Data
            if (! $this->checkLastupdated($compare_id, $last_updated)) {
                echo json_encode([
                    "status" => "error",
                    "msg"    => "ข้อมูลมีการเปลี่ยนแปลงกรุณา รีโหลดหน้าใหม่",
                ]);
                return;
            } else {
                // Master Data
                $formno              = $this->input->post("formno");
                $dataareaid          = $this->input->post("dataareaid");
                $accountnum          = $this->input->post("selected_vendor_accountnum");
                $vendor_name         = $this->input->post("selected_vendor_name");
                $reason              = $this->input->post("reason");
                $user_ecode          = $this->input->post("user_ecode");
                $user_dept           = $this->input->post("user_dept");
                $user_deptcode       = $this->input->post("user_deptcode");
                $user_name           = $this->input->post("user_name");
                $modify_datetime     = Date("Y-m-d H:i:s");
                $selectedVendorIndex = $this->input->post("selectedVendorIndex");

                //vendor
                $vendors = json_decode($this->input->post("vendors"), true);

                // Item
                $items = json_decode($this->input->post("items"), true);

                $master_data = [
                    "dataareaid"      => $dataareaid,
                    "accountnum"      => $accountnum,
                    "vendor_index"    => $selectedVendorIndex,
                    "reason"          => $reason,
                    "user_create"     => $user_name,
                    "ecode_create"    => $user_ecode,
                    "dept_create"     => $user_dept,
                    "deptcode_create" => $user_deptcode,
                    "datetime_modify" => $modify_datetime,
                    "compare_status"  => "Pending Send (Edit)",
                    "last_updated"    => Date("Y-m-d H:i:s"),
                ];
                $this->db_compare->where("id", $compare_id);
                $this->db_compare->update("compare_master", $master_data);

                //vendor data update
                foreach ($vendors as $index => $vendor) {
                    $vendor_data = [
                        "compare_id"     => $compare_id,
                        "compare_formno" => $formno,
                        "vendor_index"   => $index,
                        "vendor_name"    => $vendor['vendor_name'],
                        "accountnum"     => $vendor['accountnum'],
                        "dataareaid"     => $vendor['dataareaid'],
                    ];
                    $this->db_compare->where("id" , $vendor['id']);
                    $this->db_compare->update("compare_vendors", $vendor_data);
                }

                //Delete old data
                $this->db_compare->where("compare_id", $compare_id);
                $this->db_compare->delete("compare_items");
                $item_index = 0;
                foreach ($items as $item) {
                    foreach ($item['prices'] as $index => $price) {
                        $itemdetail = [
                            "compare_id"     => $compare_id,
                            "compare_formno" => $formno,
                            "itemid"         => $item['itemid'],
                            "itemname"       => $item['itemname'],
                            "itemgroupid"    => $item['itemgroupid'],
                            "itemdetail"     => $item['itemdetail'],
                            "itemunit"       => $item['itemunit'],
                            "vendor_index"   => $index,
                            "item_index"     => $item_index,
                            "price"          => $price,
                            "no_quoted"       => $item['no_quoted'][$index] ?? false  // ✅ ไม่ต้องแปลง
                        ];

                        $this->db_compare->insert("compare_items", $itemdetail);
                    }
                    $item_index++;
                }

                //file
                $file = 'attachments';
                uploadFile_compare($file, $formno, $compare_id);

                //file
                $this->db_compare->trans_complete();
                if ($this->db_compare->trans_status() === false) {
                    echo json_encode([
                        "msg"    => "บันทึกข้อมูลไม่สำเร็จ",
                        "status" => "not success",
                    ]);
                } else {
                    echo json_encode([
                        "msg"    => "บันทึกข้อมูลสำเร็จ",
                        "status" => "success",
                    ]);
                }

            }

        } else {
            echo json_encode([
                "msg" => "ไม่สามารถบันทึกข้อมูลได้",
            ]);
        }
    }

    // --- Backend: Controller Method ---
    public function get_compareList_json()
    {
        $request     = $_POST;
        $search      = $this->input->post('search')['value'] ?? '';
        $orderColumn = $this->input->post('order')[0]['column'];
        $orderDir    = $this->input->post('order')[0]['dir'];
        $start       = $this->input->post('start');
        $length      = $this->input->post('length');

        $userDeptcode = $request["userData_deptcode"];
        $userposi     = $request['userData_posi'];

        $filterStartDate = $request['filter_startdate'] ?? '';
        $filterEndDate   = $request['filter_enddate'] ?? '';
        $filterItemId    = $request['filter_itemid'] ?? '';
        $filterStatus    = $request['filter_status'] ?? '';

        $columns = [
            'formno',
            'items_all',
            'vendor_name',
            'ecode_create',
            'datetime_create',
            'deptcode_create',
            'compare_status',
        ];
        $orderBy = $columns[$orderColumn];

        // Base SQL
        $baseSQL = "FROM compare_master m
                    INNER JOIN compare_vendors v ON v.compare_id = m.id AND v.vendor_index = m.vendor_index
                    INNER JOIN compare_items i ON i.compare_id = v.compare_id AND i.vendor_index = v.vendor_index
                    WHERE 1=1";

        $params = [];

        if (! empty($userposi) && $userposi <= 75) {
            // เงื่อนไขการเข้าถึงข้อมูลตามแผนก
            if (! empty($userDeptcode)) {
                $baseSQL .= " AND m.deptcode_create = ?";
                $params[] = $userDeptcode;
            }
        }

        if (!empty($filterStartDate) && !empty($filterEndDate)) {
            $baseSQL .= " AND DATE(m.datetime_create) BETWEEN ? AND ?";
            $params[] = $filterStartDate;
            $params[] = $filterEndDate;
        }
        
        if (!empty($filterItemId)) {
            $baseSQL .= " AND (
                LOWER(i.itemid) LIKE ? OR 
                LOWER(i.itemname) LIKE ? OR 
                LOWER(i.itemdetail) LIKE ?
            )";
            $filter = strtolower($filterItemId);
            $params[] = "%{$filter}%";
            $params[] = "%{$filter}%";
            $params[] = "%{$filter}%";
        }
        
        if (!empty($filterStatus)) {
            $baseSQL .= " AND m.compare_status = ?";
            $params[] = $filterStatus;
        }

        // 🔽 Column specific filtering
        if ($this->input->post('columns')) {
            foreach ($this->input->post('columns') as $index => $column) {
                $searchValue = $column['search']['value'];
                $colName     = $columns[$index]; // แมปชื่อคอลัมน์

                if (! empty($searchValue)) {
                    $baseSQL .= " AND {$colName} LIKE ?";
                    $params[] = "%{$searchValue}%";
                }
            }
        }

        // เงื่อนไขค้นหาทั่วไป
        if (! empty($search)) {
            $baseSQL .= " AND (
                m.formno LIKE ? OR
                v.vendor_name LIKE ? OR
                i.itemdetail LIKE ? OR
                m.ecode_create LIKE ? OR
                m.deptcode_create LIKE ? OR
                m.compare_status LIKE ?
            )";
            for ($i = 0; $i < 6; $i++) {
                $params[] = "%{$search}%";
            }
        }

        // ดึงจำนวน record ทั้งหมด (ก่อน limit)
        $countSQL     = "SELECT COUNT(DISTINCT m.id) as total " . $baseSQL;
        $queryCount   = $this->db_compare->query($countSQL, $params);
        $recordsTotal = $queryCount->row()->total;

        // Query data จริง
        $finalSQL = "SELECT
                        m.id,
                        m.formno,
                        m.dataareaid,
                        m.accountnum,
                        m.reason,
                        m.user_create,
                        m.ecode_create,
                        m.dept_create,
                        m.deptcode_create,
                        DATE_FORMAT(m.datetime_create , '%d/%m/%Y %H:%i:%s') AS datetime_create,
                        m.compare_status,
                        m.last_updated,
                        v.vendor_name,
                        GROUP_CONCAT(i.itemdetail ORDER BY i.id SEPARATOR ' , ') AS items_all
                    " . $baseSQL . "
                    GROUP BY m.id
                    ORDER BY {$orderBy} {$orderDir}
                    LIMIT ?, ?";
        $params[] = (int) $start;
        $params[] = (int) $length;

        $queryPage = $this->db_compare->query($finalSQL, $params);

        $data = [];
        foreach ($queryPage->result() as $row) {
            $data[] = [
                'formno'          => $row->formno,
                'items_all'       => $row->items_all,
                'vendorname'      => $row->vendor_name,
                'ecode_create'    => $row->ecode_create,
                'datetime_create' => $row->datetime_create,
                'deptcode_create' => $row->deptcode_create,
                'compare_status'  => $row->compare_status,
            ];
        }

        echo json_encode([
            "draw"            => intval($this->input->post('draw')),
            "recordsTotal"    => $recordsTotal,
            "recordsFiltered" => $recordsTotal,
            "data"            => $data,
        ]);
    }

    public function getCompareMasterByFormno($formno, $deptcode)
    {
        return $this->db_compare->where(['formno' => $formno, 'deptcode_create' => $deptcode])
            ->get('compare_master')
            ->row();
    }

    public function getVendorsByCompareId($compare_id)
    {
        return $this->db_compare->where('compare_id', $compare_id)
            ->order_by('vendor_index', 'asc')
            ->get('compare_vendors')
            ->result();
    }

    public function getItemsByCompareId($compare_id)
    {
        return $this->db_compare->where('compare_id', $compare_id)
            ->order_by('id', 'asc')
            ->order_by('vendor_index', 'asc')
            ->get('compare_items')
            ->result();
    }

    public function getFilesByCompareId($compare_id)
    {
        return $this->db_compare
            ->select("id , name , path , formno , compare_id")
            ->where("compare_id", $compare_id)
            ->order_by("datetime", "asc")
            ->get("compare_file")
            ->result();
    }

    public function cancelDocument()
    {
        if (! empty($this->input->post("formno")) && ! empty($this->input->post("last_updated"))) {
            $this->db_compare->trans_start();
            $formno       = $this->input->post("formno");
            $last_updated = $this->input->post("last_updated");

            $checkLastupdate = $this->db_compare->query("SELECT
            last_updated
            FROM compare_master
            WHERE last_updated = ? AND formno = ?
            ", [$last_updated, $formno]);

            if ($checkLastupdate->num_rows() === 0) {
                echo json_encode([
                    "status" => "error",
                    "msg"    => "ข้อมูลมีการเปลี่ยนแปลง กรุณารีโหลดหน้าใหม่",
                ]);
            } else {
                $this->db_compare->where("formno", $formno);
                $this->db_compare->where("last_updated", $last_updated);
                $this->db_compare->update("compare_master", [
                    "compare_status" => "Cancel",
                    "last_updated"   => date("Y-m-d H:i:s"),
                ]);
            }

            $this->db_compare->trans_complete();
            if ($this->db_compare->trans_status() === false) {
                echo json_encode([
                    "msg"    => "บันทึกข้อมูลไม่สำเร็จ",
                    "status" => "error",
                ]);
                return;
            } else {
                echo json_encode([
                    "msg"    => "บันทึกข้อมูลสำเร็จ",
                    "status" => "success",
                ]);
                return;
            }
        } else {
            echo json_encode([
                "msg"    => "ไม่มีการส่งค่า Formno และ Last Update มา",
                "status" => "error",
            ]);
            return;
        }
    }

    public function getFileByNameAndCompareId($filename, $compare_id)
    {
        return $this->db_compare->get_where("compare_file", [
            "name"       => $filename,
            "compare_id" => $compare_id,
        ])->row();
    }

    public function checkLastupdated($compare_id, $last_updated)
    {
        return $this->db_compare
            ->where('id', $compare_id)
            ->where('last_updated', $last_updated)
            ->count_all_results('compare_master') > 0;
    }

    // Model: Compare_model.php
    public function getCompareMasterById($compare_id)
    {
        return $this->db_compare
            ->where('id', $compare_id)
            ->get('compare_master')
            ->row();
    }

    public function createQrcode($linkQrcode, $id)
    {
        // $obj = new emailfn();
        // $obj->gci()->load->library("Ciqrcode");
        require "phpqrcode/qrlib.php";
        // $this->load->library('phpqrcode/qrlib');

        $SERVERFILEPATH = $_SERVER['DOCUMENT_ROOT'] . '/intsys/purchaseplus/purchaseplus_backend/uploads/qrcode/';
        $urlQrcode      = $linkQrcode;
        // $filename1 = 'qrcode' . rand(2, 200) . ".png";
        $uniqueFileName = bin2hex(random_bytes(16));
        $filename1      = 'qrcode' . $uniqueFileName . $id . ".png";
        $folder         = $SERVERFILEPATH;

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

    public function sendto_manager($formno, $compare_id)
    {

        if ($_SERVER['HTTP_HOST'] == "localhost") {
            $adminLink = "http://localhost:8080/compareview/$formno";
        } else if ($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com") {
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/compareview/$formno";
        } else {
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/compareview/$formno";
        }

        $subject = "มีรายการ Compare Vendor ใหม่รอตรวจสอบข้อมูล";

        $short_url = $adminLink;
        $emaildata = getdataforemail_compare($compare_id);

        $body = '
            <h2>มีรายการ Compare Vendor ใหม่รอตรวจสอบข้อมูล</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>' . $emaildata->formno . '</td>
                <td><strong>วันที่</strong></td>
                <td>' . $emaildata->datetime_create . '</td>
            </tr>

            <tr>
                <td><strong>รายการสินค้า</strong></td>
                <td colspan="3">' . $emaildata->itemdetails . '</td>
            </tr>
            <tr>
                <td><strong>แผนก</strong></td>
                <td>' . $emaildata->dept_create . '</td>
                <td><strong>ผู้ดำเนินการ</strong></td>
                <td>' . $emaildata->user_create . '</td>
            </tr>

            ';

        $body .= '
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="' . $adminLink . '">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url, $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = [];
        $cc = [];

        $ecodeAr   = [];
        $ecodeccAr = [];

                                                                                                     //  Email Zone
        $optionTo = getemail_managerbydeptcode($emaildata->deptcode_create, $emaildata->dataareaid); //ดึงเอาเฉพาะ Email ของผ฿้จัดการขึ้นมา
        foreach ($optionTo->result_array() as $rs) {
            $to[]      = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }

        $optionCc = getemail_byecode($emaildata->ecode_create); //ผู้ขอซื้อ
        foreach ($optionCc->result_array() as $rs) {
            $cc[]        = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        $to        = array_unique($to);
        $cc        = array_unique($cc);
        $ecodeAr   = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject, $body, $to, $cc, $pathfile = "");
        //  Email Zone

        // Notification center program
        $ecodeActionArr = $ecodeAr;
        $ecodeReadArr   = $ecodeccAr;

        $title       = $subject;
        $status      = $emaildata->compare_status;
        $link        = $adminLink;
        $programname = "Purchase Plus";

        $this->notifycenter->insertdataaction_template($ecodeActionArr, $title, $status, $link, $formno, $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr, $title, $status, $link, $formno, $programname);

    }

    public function testgetdata($formno, $compare_id)
    {
        $emaildata = getdataforemail_compare($compare_id);
        echo json_encode([
            "result" => $emaildata,
        ]);
    }

    public function saveManagerApprove($data)
    {
        // ตรวจสอบ approval_status เพื่อตั้งค่า compare_status
        $compare_status = ($data['approval_status'] === 'yes')
        ? 'Compare Approved'
        : 'Compare Not Approve';

        $this->db_compare->where('id', $data['compare_id']);
        return $this->db_compare->update('compare_master', [
            'status_approval'   => $data['approval_status'],
            'memo_approval'     => $data['approval_memo'],
            'user_approval'     => $data['user_approve'],
            'ecode_approval'    => $data['ecode_approve'],
            'datetime_approval' => date("Y-m-d H:i:s"),
            'last_updated'      => date("Y-m-d H:i:s"),
            'compare_status'    => $compare_status,
        ]);
    }

    public function sendto_userpost($formno, $compare_id)
    {

        if ($_SERVER['HTTP_HOST'] == "localhost") {
            $adminLink = "http://localhost:8080/compareview/$formno";
        } else if ($_SERVER['HTTP_HOST'] == "intranet.saleecolour.com") {
            $adminLink = "https://intranet.saleecolour.com/intsys/purchaseplus/compareview/$formno";
        } else {
            $adminLink = "https://intracent.saleecolour.com/intsys/purchaseplus/compareview/$formno";
        }

        $subject = "รายการ Compare Vendor ตรวจสอบข้อมูลเสร็จสิ้น";

        $short_url = $adminLink;
        $emaildata = getdataforemail_compare($compare_id);

        $body = '
            <h2>รายการ Compare Vendor ตรวจสอบข้อมูลเสร็จสิ้น</h2>
            <table>
            <tr>
                <td><strong>เลขที่เอกสาร</strong></td>
                <td>' . $emaildata->formno . '</td>
                <td><strong>วันที่</strong></td>
                <td>' . $emaildata->datetime_create . '</td>
            </tr>

            <tr>
                <td><strong>รายการสินค้า</strong></td>
                <td colspan="3">' . $emaildata->itemdetails . '</td>
            </tr>
            <tr>
                <td><strong>แผนก</strong></td>
                <td>' . $emaildata->dept_create . '</td>
                <td><strong>ผู้ดำเนินการ</strong></td>
                <td>' . $emaildata->user_create . '</td>
            </tr>

            ';

        //check ผลการตรวจสอบ
        if ($emaildata->status_approval == "yes") {
            $conStatusAppro = "อนุมัติ";
        } else {
            $conStatusAppro = "ไม่อนุมัติ";
        }

        $body .= '
            <tr>
                <td colspan="4" class="bghead"><strong>ผลการตรวจสอบ</strong></td>
            </tr>
            <tr>
                <td><strong>ผลการตรวจสอบ</strong></td>
                <td colspan="3">' . $conStatusAppro . '</td>
            </tr>
            <tr>
                <td><strong>หมายเหตุ</strong></td>
                <td colspan="3">' . $emaildata->memo_approval . '</td>
            </tr>
            <tr>
                <td><strong>ผู้อนุมัติ</strong></td>
                <td>' . $emaildata->user_approval . '</td>
                <td><strong>รหัสพนักงาน</strong></td>
                <td>' . $emaildata->ecode_approval . '</td>
            </tr>
            <tr>
                <td><strong>วันที่</strong></td>
                <td colspan="3">' . $emaildata->datetime_approval . '</td>
            </tr>
        ';

        $body .= '
            <tr>
                <td><strong>ตรวจสอบรายการ</strong></td>
                <td colspan="3"><a href="' . $adminLink . '">' . $formno . '</a></td>
            </tr>

            <tr>
                <td><strong>Scan QrCode</strong></td>
                <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($short_url, $formno) . '"></td>
            </tr>


            </table>
            ';

        $to = [];
        // $cc = [];

        // $ecodeAr   = [];
        $ecodeccAr = [];

                                                                //  Email Zone
        $optionTo = getemail_byecode($emaildata->ecode_create); //ผู้ขอซื้อ
        foreach ($optionTo->result_array() as $rs) {
            $to[]        = $rs['memberemail'];
            $ecodeccAr[] = $rs['ecode'];
        }

        // $optionCc = getemail_managerbydeptcode($emaildata->deptcode_create, $emaildata->dataareaid); //ดึงเอาเฉพาะ Email 
        // foreach ($optionCc->result_array() as $rs) {
        //     $cc[]        = $rs['memberemail'];
        //     $ecodeccAr[] = $rs['ecode'];
        // }

        $to = array_unique($to);
        // $cc        = array_unique($cc);
        // $ecodeAr   = array_unique($ecodeAr);
        $ecodeccAr = array_unique($ecodeccAr);

        sendemail($subject, $body, $to, $cc = "", $pathfile = "");
        //  Email Zone

        // Notification center program
        // $ecodeActionArr = $ecodeAr;
        $ecodeReadArr = $ecodeccAr;

        $title       = $subject;
        $status      = $emaildata->compare_status;
        $link        = $adminLink;
        $programname = "Purchase Plus";

        // $this->notifycenter->insertdataaction_template($ecodeActionArr, $title, $status, $link, $formno, $programname);
        $this->notifycenter->insertdataRead_template($ecodeReadArr, $title, $status, $link, $formno, $programname);

    }

    public function searchCompareVendor($keyword)
    {
        $deptcode_user = $this->input->post("deptcode_user");
        $sql           = $this->db_compare->query("SELECT
            m.id,
            m.formno,
            m.dataareaid,
            m.accountnum,
            m.vendor_index,
            m.last_updated,
            m.deptcode_create,
            m.ecode_create,
            v.id AS vendor_id,
            v.vendor_name,
            GROUP_CONCAT(i.itemdetail ORDER BY i.id SEPARATOR ' , ') AS itemdetails
        FROM compare_master m
        INNER JOIN compare_vendors v ON v.compare_id = m.id AND v.accountnum = m.accountnum
        INNER JOIN compare_items i ON i.compare_id = v.compare_id AND i.vendor_index = v.vendor_index
        WHERE m.compare_status IN ('Compare Approved') AND m.deptcode_create = '$deptcode_user'
        GROUP BY m.id
        HAVING
            m.formno LIKE '%$keyword%' OR
            m.accountnum LIKE '%$keyword%' OR
            v.vendor_name LIKE '%$keyword%' OR
            itemdetails LIKE '%$keyword%'
        ");

        return $sql->result();
    }

    public function getVendData_Compare()
    {
        if (! empty($this->input->post("dataareaid")) && ! empty($this->input->post("accountnum"))) {
            $dataareaid = $this->input->post("dataareaid");
            $accountnum = $this->input->post("accountnum");
            $sql        = $this->db_mssql->query("SELECT
                a.accountnum AS accountnum,
                a.name AS name,
                a.address AS address,
                a.paymtermid AS paymtermid,
                a.currency AS currency,
                a.email AS email,
                a.dataareaid AS dataareaid,
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
                a.accountnum = ?
                AND a.dataareaid = ?
            ", [$accountnum, $dataareaid]);

            $output = [
                "msg"    => "ดึงข้อมูลผู้ขาย (Compare) สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->row(),
            ];
        } else {
            $output = [
                "msg"    => "ดึงข้อมูลผู้ขาย (Compare)ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ];
        }
        echo json_encode($output);
    }

    public function getItemData_Compare()
    {
        if (! empty($this->input->post("formno"))) {
            $formno = $this->input->post("formno");
            $query  = $this->db_compare->query("SELECT
                m.vendor_index,
                m.formno,
                m.id,
                m.accountnum,
                i.item_index,
                i.itemid,
                i.itemname,
                i.itemgroupid,
                i.itemdetail,
                i.itemunit,
                i.price
            FROM compare_master m
            INNER JOIN compare_items i
                ON i.vendor_index = m.vendor_index
                AND i.compare_formno = m.formno
            WHERE m.formno = ?", [$formno]);

            echo json_encode([
                "msg"    => "ดึงข้อมูล Item สำเร็จ",
                "status" => "Select Data Success",
                "result" => $query->result(),
            ]);
        } else {
            echo json_encode([
                "msg"    => "ดึงข้อมูล Item ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            ]);
        }
    }

    public function getCompareStatusList()
    {
        $this->db_compare->select('status');
        $this->db_compare->from('compare_status');
        $this->db_compare->order_by('status', 'ASC');
        $query = $this->db_compare->get();
        return $query->result();
    }

}

/* End of file ModelName.php */
