<?php
namespace Admin\Controller;
use Think\Controller;
class AdminloginController extends BaseController {

    public function index()
    {
        $this->display();
    }
    public function dologin()
    {
       
        $map['emp_category'] = array('EQ','管理员');
        $map['emp_atpstatus'] = array('EXP', "is null");
        $Model = M('emp');
        $Model_emprole = M('');

        $result = $Model->where($map)->select();

        $emproledata=$Model->query("select
             *
           from szny_emprole t
           left join  szny_role t1 on t1.role_atpid =t.emprole_roleid
           where t.emprole_empid='".$result[0]['emp_atpid']."' and t.emprole_atpstatus is null");

        if (count($result) == 1) {
            session(null);
            session('emp_atpid',$result[0]['emp_atpid']);
            session('emp_account',$result[0]['emp_account']);
            session('emp_name',$result[0]['emp_name']);
            session('ip',get_client_ip());
            session('emp_role',$emproledata);
            session('role_iskfs',false);
            session('role_isadmin',false);
            session('role_iswy',false);
            foreach ($emproledata as $mk => &$mv) {
                if ($mv['role_name'] == '开发商') {
                    session('role_iskfs',true);
                }
                if ($mv['role_name'] == '管理员') {
                    session('role_isadmin',true);
                }
                if ($mv['role_name'] == '物业人员') {
                    session('role_iswy',true);
                }
            }
           
            $this->redirect('/Admin/Frame');
        } else {
           $this->redirect('/Admin/login');
        }
    }
}