<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Purchaseapi extends MX_Controller {
    
    public function __construct()
    {
        parent::__construct();
        $this->load->model("purchaseapi_model" , "purapi");
    }
    

    public function index()
    {
        return false;
    }

    public function get_pr($vendid , $areaid , $apikey)
    {
        $this->purapi->get_pr($vendid , $areaid , $apikey);
    }

    public function getapikey()
    {
        echo generateApiKey();
    }

    public function update_po($purchid , $prid , $areaid , $apikey)
    {
        $this->purapi->update_po($purchid , $prid , $areaid , $apikey);
    }

}

/* End of file Controllername.php */



?>