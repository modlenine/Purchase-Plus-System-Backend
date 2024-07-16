<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends MX_Controller {
    
    public function __construct()
    {
        parent::__construct();
        $this->load->model("login_model" , "login");
    }
    

    public function index()
    {
        show_404();
    }

    public function checklogin()
    {
        $this->login->checklogin();
    }

}

/* End of file Controllername.php */




?>