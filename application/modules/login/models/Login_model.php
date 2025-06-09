<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Login_model extends CI_Model {
    
    public function __construct()
    {
        parent::__construct();
        //Do your magic here
        date_default_timezone_set("Asia/Bangkok");
    }

    public function escape_string()
    {
        if($_SERVER['HTTP_HOST'] == "localhost"){
            return mysqli_connect("192.168.20.22", "ant", "Ant1234", "saleecolour");
        }else{
            return mysqli_connect("localhost", "ant", "Ant1234", "saleecolour");
        }

    }

    public function checklogin()
    {
        $this->db2 = $this->load->database('saleecolour', TRUE);
        if ($this->input->post("username") != "" && $this->input->post("password") != "") {
            $username = $this->input->post("username");
            $password = $this->input->post("password");

            $user = mysqli_real_escape_string($this->escape_string(), $username);
            $pass = mysqli_real_escape_string($this->escape_string(), md5($password));

            // Check ว่าเป็นการ Login ของ Vender หรือว่า พนักงาน
            $sql = $this->db2->query(sprintf("SELECT
                member.mid,
                member.username,
                member.Fname,
                member.Lname,
                member.Tname,
                member.TLname,
                member.ecode,
                member.Dept,
                member.DeptCode,
                member.SubDeptCode,
                member.memberemail,
                member.adding_by,
                member.subdate,
                member.edit_by,
                member.lastedit,
                member.posi,
                member.ipphoneNumber,
                member.spacial,
                member.resigned,
                member.resignedDate,
                member.file_img,
                member.areaid
                FROM
                member
                WHERE username='%s' AND password='%s' ", $user, $pass));
            if ($sql->num_rows() == 0) {
                $output = array(
                    "msg" => "ไม่พบข้อมูลผู้ใช้งานในระบบ",
                    "status" => "Login failed"
                );
            } else {
                foreach ($sql->result_array() as $r) {
                    $_SESSION['username'] = $r['username'];
                    $_SESSION['Fname'] = $r['Fname'];
                    $_SESSION['Lname'] = $r['Lname'];
                    $_SESSION['Dept'] = $r['Dept'];
                    $_SESSION['ecode'] = $r['ecode'];
                    $_SESSION['DeptCode'] = $r['DeptCode'];
                    $_SESSION['memberemail'] = $r['memberemail'];
                    $_SESSION['file_img'] = $r['file_img'];
                    $_SESSION['posi'] = $r['posi'];
                    $_SESSION['mid'] = $r['mid'];

                    // insert login log
                    session_write_close();
                }

                $uri = isset($_SESSION['RedirectKe']) ? $_SESSION['RedirectKe'] : '/intsys/purchaseplus';
                // header('location:' . $uri);
                // Check IT
                $output = array(
                    "msg" => "ลงชื่อเข้าใช้สำเร็จ",
                    "status" => "Login Successfully",
                    "uri" => $uri,
                    "session_data" => $sql->row_array(),
                    "loginexpire" => strtotime('+4 hour'),
                    "loginexpire_con" => date("Y-m-d H:i:s" , strtotime('+4 hour')),
                    // "timeExpire" => strtotime('+120 seconds'),
                    "timeNow" => strtotime('now'),
                    "timeNow_con" => date("Y-m-d H:i:s" , strtotime('now'))
                );
            }


        }else{
            $output = array(
                "msg" => "กรุณากรอก Username & Password",
                "status" => "Login failed please fill username and password"
            );
        }
      
        echo json_encode($output);
    }

    public function checksession() {
        header('Content-Type: application/json');
        if ($this->session->userdata('ecode')) {
            $ecode = $this->session->userdata('ecode');
            $this->db2 = $this->load->database('saleecolour', TRUE);

            $sql = $this->db2->query("SELECT
                member.mid,
                member.username,
                member.Fname,
                member.Lname,
                member.Tname,
                member.TLname,
                member.ecode,
                member.Dept,
                member.DeptCode,
                member.SubDeptCode,
                member.memberemail,
                member.adding_by,
                member.subdate,
                member.edit_by,
                member.lastedit,
                member.posi,
                member.ipphoneNumber,
                member.spacial,
                member.resigned,
                member.resignedDate,
                member.file_img,
                member.areaid
                FROM
                member
                WHERE member.ecode = ?" , [$ecode]);

            $uri = isset($_SESSION['RedirectKe']) ? $_SESSION['RedirectKe'] : '/intsys/purchaseplus';

            echo json_encode([
                "sessionActive" => true,
                "uri" => $uri,
                "session_data" => $sql->row_array(),
                "loginexpire" => strtotime('+4 hour'),
                "loginexpire_con" => date("Y-m-d H:i:s" , strtotime('+4 hour')),
                // "timeExpire" => strtotime('+120 seconds'),
                "timeNow" => strtotime('now'),
                "timeNow_con" => date("Y-m-d H:i:s" , strtotime('now'))
            ]);
        } else {
            echo json_encode(['sessionActive' => false]);
        }
    }
}

/* End of file ModelName.php */


?>