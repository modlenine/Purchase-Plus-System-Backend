<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Mainapi extends MX_Controller {
    
    public function __construct()
    {
        parent::__construct();
        $this->load->model("mainapi_model" , "mainapi");
        $this->load->model("pdf_model" , "pdf");
    }
    

    public function index()
    {
        show_404();
    }

    public function getReqplan()
    {
        $this->mainapi->getReqplan();
    }

    public function getVendID()
    {
        $this->mainapi->getVendID();
    }

    public function getCostcenter()
    {
        $this->mainapi->getCostcenter();
    }

    public function getDepartment()
    {
        $this->mainapi->getDepartment();
    }

    public function getUserEcode()
    {
        $this->mainapi->getUserEcode();
    }

    public function getItemid()
    {
        $this->mainapi->getItemid();
    }

    public function getInvestigator()
    {
        $this->mainapi->getInvestigator();
    }

    public function getNowdate()
    {
        echo date("d-m-Y H:i:s");
    }

    public function saveInsertItemdata()
    {
        $this->mainapi->saveInsertItemdata();
    }

    public function saveDataAll()
    {
        $this->mainapi->saveDataAll();
    }

    public function saveDataAll_edit()
    {
        $this->mainapi->saveDataAll_edit();
    }

    public function saveDataAll_edit_purchase()
    {
        $this->mainapi->saveDataAll_edit_purchase();
    }

    public function testcut()
    {
        testcut();
    }

    public function getdata_viewfull()
    {
        $this->mainapi->getdata_viewfull();
    }

    public function loadprlist()
    {
        $this->mainapi->loadprlist();
    }

    public function saveCancel()
    {
        $this->mainapi->saveCancel();
    }

    public function sendData()
    {
        $this->mainapi->sendData();
    }

    public function saveMgrApprove()
    {
        $this->mainapi->saveMgrApprove();
    }

    public function getdataG4()
    {
        $this->mainapi->getdataG4();
    }

    public function getdataG3()
    {
        $this->mainapi->getdataG3();
    }

    public function getdataG2()
    {
        $this->mainapi->getdataG2();
    }

    public function getdataG1()
    {
        $this->mainapi->getdataG1();
    }

    public function getdataG0()
    {
        $this->mainapi->getdataG0();
    }

    public function getExecutiveData()
    {
        $this->mainapi->getExecutiveData();
    }

    public function saveExecutiveG4()
    {
        $this->mainapi->saveExecutiveG4();
    }

    public function saveExecutiveG3()
    {
        $this->mainapi->saveExecutiveG3();
    }

    public function saveExecutiveG2()
    {
        $this->mainapi->saveExecutiveG2();
    }

    public function saveExecutiveG1()
    {
        $this->mainapi->saveExecutiveG1();
    }

    public function saveExecutiveG0()
    {
        $this->mainapi->saveExecutiveG0();
    }

    public function savePurchase()
    {
        $this->mainapi->savePurchase();
    }

    public function getPayGroupMaxMoney()
    {
        $this->mainapi->getPayGroupMaxMoney();
    }

    public function getDataforprint()
    {
        $this->mainapi->getDataforprint();
    }

    public function getdata_po()
    {
        if($this->input->post("areaid") == "sln" || $this->input->post("areaid") == "ca"){
            $this->mainapi->getData_po_sln();
        }
    }

    public function getUserRequest()
    {
        $this->mainapi->getUserRequest();
    }

    public function send_po()
    {
        $this->pdf->send_po2();
    }

    public function printpdf()
    {
        $this->pdf->printpdf();
    }

    public function testcode()
    {
        $optionTo = getemail_byecode("M0140");//ดึงเอาเฉพาะ Email ของผู้ตรวจสอบขึ้นมา
        foreach($optionTo->result_array() as $rs){
            $to[] = $rs['memberemail'];
            $ecodeAr[] = $rs['ecode'];
        }
        array_push($to , 'test@saleecolour.com');
        print_r($to);
    }

    public function getdataDetail()
    {
        $this->mainapi->getdataDetail();
    }

    public function saveInvesApprove()
    {
        $this->mainapi->saveInvesApprove();
    }

    public function delFile()
    {
        $this->mainapi->delFile();
    }



}

/* End of file Controllername.php */




?>