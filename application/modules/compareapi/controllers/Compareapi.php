<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Compareapi extends MX_Controller
{

    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set('Asia/Bangkok');
        $this->load->model('compareapi_model', 'compareapi');
    }

    public function saveCompareVendor()
    {
        header('Content-Type: application/json');
        $this->compareapi->saveCompareVendor();
    }

    public function saveCompareVendorEdit()
    {
        header('Content-Type: application/json');
        $this->compareapi->saveCompareVendorEdit();
    }

    public function getItemid()
    {
        $this->compareapi->getItemid();
    }

    public function get_compareList_json()
    {
        $this->compareapi->get_compareList_json();
    }

    public function getCompareDetailByFormno()
    {
        header('Content-Type: application/json');

        $formno = $this->input->post('formno');
        $deptcode = $this->input->post("deptcode");
        if (empty($formno)) {
            echo json_encode(['status' => 'error', 'message' => 'Formno is required']);
            return;
        }

        $compare = $this->compareapi->getCompareMasterByFormno($formno , $deptcode);
        if (! $compare) {
            echo json_encode(['status' => 'error', 'message' => 'Compare not found']);
            return;
        }

        $vendors = $this->compareapi->getVendorsByCompareId($compare->id);
        $items   = $this->compareapi->getItemsByCompareId($compare->id);
        $files   = $this->compareapi->getFilesByCompareId($compare->id);

        // แปลง items ให้รวมราคาแบบแยกตาม vendor_index
        $groupedItems = [];
        foreach ($items as $item) {
            $key = $item->item_index; // กำหนด key เฉพาะสำหรับสินค้า
            if (! isset($groupedItems[$key])) {
                $groupedItems[$key] = [
                    'itemid'      => $item->itemid,
                    'itemname'    => $item->itemname,
                    'itemdetail'  => $item->itemdetail,
                    'itemunit'    => $item->itemunit,
                    'itemgroupid' => $item->itemgroupid,
                    'prices'      => [],
                ];
            }
            $groupedItems[$key]['prices'][$item->vendor_index] = $item->price;
        }

        // เรียงลำดับตาม item_index
        ksort($groupedItems);

        echo json_encode([
            'status' => 'success',
            'result' => [
                'formno'            => $compare->formno,
                'compare_id'        => $compare->id,
                'dataareaid'        => $compare->dataareaid,
                'accountnum'        => $compare->accountnum,
                'reason'            => $compare->reason,
                'user_create'       => $compare->user_create,
                'ecode_create'      => $compare->ecode_create,
                'dept_create'       => $compare->dept_create,
                'deptcode_create'   => $compare->deptcode_create,
                'datetime_create'   => conDateTimeFromDb($compare->datetime_create),
                'last_updated'      => $compare->last_updated,
                'compare_status'    => $compare->compare_status,
                'selectedIndex'     => $compare->vendor_index,
                'vendors'           => $vendors,
                'items'             => array_values($groupedItems),
                'files'             => $files,

                'memo_approval'     => $compare->memo_approval,
                'user_approval'     => $compare->user_approval,
                'ecode_approval'    => $compare->ecode_approval,
                'deptcode_approval' => $compare->deptcode_approval,
                'datetime_approval' => conDateTimeFromDb($compare->datetime_approval),
                'status_approval'   => $compare->status_approval,
            ],
        ]);
    }

    public function cancelDocument()
    {
        $this->compareapi->cancelDocument();
    }

    public function deleteFile()
    {
        $this->db_compare = $this->load->database('compare_vendor', true);
        header('Content-Type: application/json');

        $filename   = $this->input->post("filename");
        $compare_id = $this->input->post("compare_id");

        if (empty($filename) || empty($compare_id)) {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน(1)']);
            return;
        }

        $file = $this->compareapi->getFileByNameAndCompareId($filename, $compare_id);
        if (! $file) {
            echo json_encode(["status" => "error", "msg" => "ไม่พบไฟล์(2)"]);
            return;
        }

        $full_path = FCPATH . $file->path . $file->name;
        if (file_exists($full_path)) {
            unlink($full_path);
        }

        $this->db_compare->where("name", $filename);
        $this->db_compare->where("compare_id", $compare_id);
        $this->db_compare->delete("compare_file");

        echo json_encode(["status" => "success", "msg" => "ลบไฟล์สำเร็จ"]);
    }

    // Controller: Compareapi.php
    public function sendDataToManager()
    {
        header('Content-Type: application/json');

        $formno     = $this->input->post('formno');
        $compare_id = $this->input->post('compare_id');

        if (empty($formno) || empty($compare_id)) {
            echo json_encode([
                'status' => 'error',
                'msg'    => 'Missing required parameters.',
            ]);
            return;
        }

        $compareData = $this->compareapi->getCompareMasterById($compare_id);

        if (! $compareData) {
            echo json_encode([
                'status' => 'error',
                'msg'    => 'ไม่พบข้อมูล Compare',
            ]);
            return;
        }

        // อัปเดตสถานะ
        $this->db_compare = $this->load->database('compare_vendor', true);
        $this->db_compare->where('id', $compare_id);
        $this->db_compare->update('compare_master', [
            'compare_status' => 'Pending Approve',
            'last_updated'   => date('Y-m-d H:i:s'),
        ]);

        $this->compareapi->sendto_manager($formno, $compare_id);

        echo json_encode([
            'status' => 'success',
            'msg'    => 'ส่งข้อมูลไปยังผู้จัดการเพื่อยืนยันรายการเรียบร้อย',
        ]);
    }

    // Controller: compareapi.php
    public function saveManagerApprove()
    {
        header('Content-Type: application/json');

        $formno         = $this->input->post("formno");
        $compare_id     = $this->input->post("compare_id");
        $approvalStatus = $this->input->post("approvalStatus");
        $approvalMemo   = $this->input->post("approvalMemo");
        $userApprove    = $this->input->post("userApprove");
        $ecodeApprove   = $this->input->post("ecodeApprove");
        $last_updated   = $this->input->post("last_updated");

        if (! $formno || ! $compare_id || ! $approvalStatus || ! $ecodeApprove || ! $last_updated) {
            echo json_encode([
                'status' => 'error',
                'msg'    => 'Missing required data',
            ]);
            return;
        }

        // ตรวจสอบ last_updated ก่อน
        if (! $this->compareapi->checkLastupdated($compare_id, $last_updated)) {
            echo json_encode([
                "status" => "error",
                "msg"    => "ข้อมูลมีการเปลี่ยนแปลงกรุณา รีโหลดหน้าใหม่",
            ]);
            return;
        }

        // บันทึกการอนุมัติ
        $update = $this->compareapi->saveManagerApprove([
            'compare_id'      => $compare_id,
            'formno'          => $formno,
            'approval_status' => $approvalStatus,
            'approval_memo'   => $approvalMemo,
            'user_approve'    => $userApprove,
            'ecode_approve'   => $ecodeApprove,
            'last_updated'    => date("Y-m-d H:i:s"),
        ]);

        if ($update) {
            $this->compareapi->sendto_userpost($formno, $compare_id);
            echo json_encode([
                'status' => 'success',
                'msg'    => 'บันทึกสำเร็จ',
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'msg'    => 'ไม่สามารถบันทึกได้',
            ]);
        }
    }

    public function getCompareOwnerByFormno()
    {
        $this->db_compare = $this->load->database('compare_vendor', true);
        $formno           = $this->input->post("formno");
        $query            = $this->db_compare->get_where("compare_master", ['formno' => $formno]);
        $data             = $query->row();

        if ($data) {
            echo json_encode([
                'status' => 'success',
                'result' => [
                    'ecode_create' => $data->ecode_create,
                ],
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'msg'    => 'ไม่พบรายการนี้',
                'result' => $this->input->post("formno"),
            ]);
        }
    }

    public function searchCompareVendor()
    {
        $keyword = $this->input->post('keyword');

        $result = $this->compareapi->searchCompareVendor($keyword);
        if ($result) {
            echo json_encode([
                'status' => 'success',
                'result' => $result,
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'msg'    => 'ไม่พบข้อมูล',
            ]);
        }
    }

    public function getVendData_Compare()
    {
        $this->compareapi->getVendData_Compare();
    }

    public function getItemData_Compare()
    {
        $this->compareapi->getItemData_Compare();
    }

}
/* End of file Compareapi.php */
