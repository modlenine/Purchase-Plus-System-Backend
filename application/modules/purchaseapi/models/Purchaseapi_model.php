<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Purchaseapi_model extends CI_Model {
    
    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set("Asia/Bangkok");
        $this->load->model("mainapi/email_model" , "email");
    }

    public function get_pr($vendid , $areaid , $apikey)
    {
        if (!$this->isValidApiKey($apikey)) {
            $this->output->set_status_header(200);
            $data = array('error' => 'cannot be accessed.');
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($data));
            return;
        }else{
            $sql = $this->db->query("SELECT
            main.m_autoid,
            main.m_formno,
            main.m_prcode,
            main.m_prno,
            main.m_dataareaid,
            main.m_plantype,
            main.m_costcenter,
            main.m_department,
            main.m_ecode,
            main.m_vendid,
            main.m_vendname,
            main.m_datetime_create,
            main.m_date_req,
            main.m_date_delivery,
            main.m_memo,
            main.m_status,
            main.m_userpost,
            main.m_ecodepost,
            main.m_datetimepost,
            main.m_approve_mgr,
            main.m_userpost_mgr,
            main.m_ecodepost_mgr,
            main.m_datetimepost_mgr,
            main.m_approve_pur,
            main.m_memo_pur,
            main.m_userpost_pur,
            main.m_ecodepost_pur,
            main.m_datetimepost_pur,
            main.m_datetimeupdate,
            main.m_pono,
            main.m_version_pr,
            main.m_version_status,
            details.d_autoid,
            details.d_m_prno,
            details.d_itemid,
            details.d_itemname,
            details.d_itemdetail,
            details.d_itemqty,
            details.d_itemprice,
            details.d_itemdiscount,
            details.d_itempricesum,
            details.d_itemunit,
            details.d_itemmemo,
            details.d_datetime,
            details.d_version_pr
            FROM
            main
            INNER JOIN details ON details.d_m_formno = main.m_formno
            WHERE m_vendid = ? AND m_dataareaid = ? AND m_status = ?
            ORDER BY m_prno DESC
            ",array($vendid , $areaid , "Purchase Verified"));

            // check null data
            if($sql->num_rows() == 0){
                $this->output->set_status_header(200);
                $output = array('rows' => array());
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode($output));
                return;
            }else{
                // แปลงผลลัพธ์เป็นอาร์เรย์
                $result = $sql->result_array();

                $output = array('rows' => $result);

                // ส่งออกผลลัพธ์ในรูปแบบ JSON
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode($output));
            }
        }
    }

    private function isValidApiKey($apiKey) {
        $this->db->where('api_key', $apiKey);
        $query = $this->db->get('apikey');
        return $query->num_rows() > 0;
    }

    public function update_po($purchid , $prid , $areaid , $apikey)
    {
        if(!empty($purchid) && !empty($prid) && !empty($areaid) && !empty($apikey)){
            if (!$this->isValidApiKey($apikey)) {
                $this->output->set_status_header(200);
                $data = array('error' => 'cannot be accessed.');
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode($data));
                return;
            }else{

                //check pr data
                $sql = $this->db->query("SELECT
                m_prno , m_formno
                FROM main WHERE m_prno = ? AND m_dataareaid = ? AND m_status = ?
                ", array($prid , $areaid , "Purchase Verified"));

                if($sql->num_rows() == 0){
                    $this->output->set_status_header(200);
                    $output = array('rows' => array());
                    $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode($output));
                    return;
                }else{
                $formno = $sql->row()->m_formno;
                //update old status
                //Send to notifycenter
                $notifyformno = $formno;
                $notifyprogramname = "Purchase Plus";
                $notifystatus = "action done";
                $notifytype = "take action";

                $this->notifycenter->updatedataAction_template($notifyformno , $notifyprogramname , $notifystatus , $notifytype);
                //Send to notifycenter
                //update old status

                $arUpdatePO = array(
                    "m_pono" => $purchid,
                    "m_status" => "PO confirmed",
                    "m_formisono_po" => "PC-F-002-02-25-03-67",
                    "m_pocon_datetime" => date("Y-m-d H:i:s")
                );
                $this->db->where("m_prno" , $prid);
                $this->db->where("m_dataareaid" , $areaid);
                $this->db->update("main" , $arUpdatePO);

                $queryAppGroup = getExecutiveData($formno);
                $appGroup = "";
                if($queryAppGroup->num_rows() > 0){
                    $appGroup = $queryAppGroup->row()->apv_group;
                }

                if($appGroup == "5"){
                    $this->email->sendto_userRequest_G5($formno);
                }else if($appGroup == "4"){
                    $this->email->sendto_userRequest_G4($formno);
                }else if($appGroup == "3"){
                    $this->email->sendto_userRequest_G3($formno);
                }else if($appGroup == "2"){
                    $this->email->sendto_userRequest_G2($formno);
                }else if($appGroup == "1"){
                    $this->email->sendto_userRequest_G1($formno);
                }else if($appGroup == "0"){
                    $this->email->sendto_userRequest_G0($formno);
                }


                // แปลงผลลัพธ์เป็นอาร์เรย์
                $output = array('success' => 'Update successfully.');

                // ส่งออกผลลัพธ์ในรูปแบบ JSON
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode($output));
                }

            }
        }else{
            $this->output->set_status_header(200);
            $data = array('error' => 'cannot be accessed.');
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($data));
            return;
        }
    }
    
    

}

/* End of file ModelName.php */



?>