<?php
namespace Admin\Controller;

use Think\Controller;

class AlarmcenterController extends BaseController
{
    public function index()
    {
        $this->logSys(session('emp_atpid'), "访问日志", "访问页面：【预警中心】 / 【报警中心】");
        $this->display();
    }

    //获取所有数据
    public function getData()
    {
        $queryparam = json_decode(file_get_contents("php://input"), true);
        $Model = M();
        $sql_select = "
				select
					*
				from szny_alarm t 
				left join szny_emp t1 on t.alm_empid = t1.emp_atpid 
				left join szny_device t2 on t2.dev_atpid = t.alm_deviceid
				left join szny_alarmconfig t3 on t3.almc_atpid = t.alm_alarmconfigid
				left join szny_region t4 on t4.rgn_atpid = t3.almc_regionid
				";
        $sql_count = "
				select
					count(1) c
				from szny_alarm t 
				left join szny_emp t1 on t.alm_empid = t1.emp_atpid 
				left join szny_device t2 on t2.dev_atpid = t.alm_deviceid
				left join szny_alarmconfig t3 on t3.almc_atpid = t.alm_alarmconfigid
				left join szny_region t4 on t4.rgn_atpid = t3.almc_regionid
				";
        $sql_select = $this->buildSql($sql_select, "t.alm_atpstatus is null");
        $sql_count = $this->buildSql($sql_count, "t.alm_atpstatus is null");
//        $sql_select = $this->buildSql($sql_select, "t1.emp_atpstatus is null");
//        $sql_count = $this->buildSql($sql_count, "t1.emp_atpstatus is null");
//        $sql_select = $this->buildSql($sql_select, "t2.dev_atpstatus is null");
//        $sql_count = $this->buildSql($sql_count, "t2.dev_atpstatus is null");
//        $sql_select = $this->buildSql($sql_select, "t.alm_confirmstatus != '已忽略'");
//        $sql_count = $this->buildSql($sql_count, "t.alm_confirmstatus != '已忽略'");

        //快捷搜索
        if (null != $queryparam['disposestatus']) {
            // $searchcontent = trim($queryparam['search']);
            $sql_select = $this->buildSql($sql_select, "t.alm_confirmstatus like '%" . $queryparam['disposestatus'] . "%'");
            $sql_count = $this->buildSql($sql_count, "t.alm_confirmstatus like '%" . $queryparam['disposestatus'] . "%'");
        }

        //排序
        if (null != $queryparam['sort']) {
            $sql_select = $sql_select . " order by " . $queryparam['sort'] . ' ' . $queryparam['sortOrder'] . ' ';
        } else {
            $sql_select = $sql_select . " order by t.alm_atpid desc";
        }
        //自定义分页
        if (null != $queryparam['limit']) {

            if ('0' == $queryparam['offset']) {
                $sql_select = $sql_select . " limit " . '0' . ',' . $queryparam['limit'] . ' ';
            } else {
                $sql_select = $sql_select . " limit " . $queryparam['offset'] . ',' . $queryparam['limit'] . ' ';
            }
        }

        $Result = $Model->query($sql_select);
        $Count = $Model->query($sql_count);
        echo json_encode(array('total' => $Count[0]['c'], 'rows' => $Result));
    }


    public function edit()
    {
        $id = $_GET['id'];
        $Model = M();

        $sql_select = "
				select
					*
				from szny_alarm t 
				left join szny_emp t1 on t.alm_empid = t1.emp_atpid 
				left join szny_device t2 on t2.dev_atpid = t.alm_deviveid where t.alm_atpid =   
				" . "'" . $id . "'";
        $data = $Model->query($sql_select);
        $this->assign('data', $data);
        $this->display();
    }


    public function handle()
    {

        $id = $_GET['id'];
        $this->getAlarm($id);
        $this->getEmp();
        $this->display();

    }


    // public function getEmp()
    // {
    //   $Model = M();
    //   $sql = "select * from szny_emp";

    //   $emp = $Model->query($sql);

    //   $this->assign('emp',$emp);
    // }


    public function submit()
    {

        $repairlog = M('repairlog');
        $repairlog->startTrans();

        $data['rl_codename'] = $_POST['codename'];
        $data['rl_name'] = $_POST['rl_name'];
        $data['rl_describe'] = $_POST['describe'];
        $data['rl_empid'] = $_POST['emp'];
        $data['rl_startdatetime'] = $_POST['startdatetime'];
        $data['rl_atpid'] = $this->makeGuid();
        $data['rl_atpcreatedatetime'] = date("Y-m-d H:i:s", time());
        $data['rl_atpcreateuser'] = session('emp_account');
        $data['rl_atplastmodifydatetime'] = date("Y-m-d H:i:s", time());
        $data['rl_atplastmodifyuser'] = session('emp_account');

        $Result = $repairlog->add($data);

        if (!$Result) {
            $repairlog->rollback();
        }


        $alm_atpid = explode(",", $_POST['alarm']);

        foreach ($alm_atpid as $value) {

            $Model = M();

            $sql = "select alm_deviveid from szny_alarm where alm_atpid = " . "'" . $value . "'";

            $res = $Model->query($sql);

            $dat['rd_deviceid'] = $res[0]['alm_deviveid'];
            $dat['rd_repairlogid'] = $data['rl_atpid'];
            $dat['rd_atpid'] = $this->makeGuid();
            $dat['rd_atpcreatedatetime'] = date("Y-m-d H:i:s", time());
            $dat['rd_atpcreateuser'] = session('emp_account');
            $dat['rd_atplastmodifydatetime'] = date("Y-m-d H:i:s", time());
            $dat['rd_atplastmodifyuser'] = session('emp_account');

            $repairdetail = M('repairdetail');

            $resu = $repairdetail->add($dat);

            if (!$resu) {
                $repairlog->rollback();
            }

            $da['almp_alarmid'] = $value;
            $da['almp_repairdetailid'] = $dat['rd_atpid'];
            $da['almp_atpid'] = $this->makeGuid();
            $da['almp_atpcreatedatetime'] = date("Y-m-d H:i:s", time());
            $da['almp_atpcreateuser'] = session('emp_account');
            $da['almp_atplastmodifydatetime'] = date("Y-m-d H:i:s", time());
            $da['almp_atplastmodifyuser'] = session('emp_account');

            $alarmrepair = M('alarmrepair');

            $re = $alarmrepair->add($da);

            if (!$re) {
                $repairlog->rollback();
            }

        }


        foreach ($alm_atpid as $key => $value) {


            $M = M();

            $alm_empid = $_POST['emp'];

            $sql_update = "update szny_alarm set alm_confirmstatus = '处理中',alm_empid =" . "'" . $alm_empid . "'" . " where alm_atpid = " . "'" . $value . "'";

            $k = $M->execute($sql_update);

            if (!$k) {
                $repairlog->rollback();
            }

        }

        $repairlog->commit();

    }

    public function ischuli()
    {
        $alm_atpid = $_GET['alm_atpid'];

        $Model = M();

        $sql = "select * from szny_alarm where alm_atpid =" . "'" . $alm_atpid . "'";

        $data = $Model->query($sql);

        if ($data[0]['alm_confirmstatus'] == '未处理') {
            echo 1;
        } else {
            echo 0;
        }
    }


    public function isignore()
    {
        $alm_atpid = $_GET['alm_atpid'];

        $Model = M();

        $sql = "select * from szny_alarm where alm_atpid =" . "'" . $alm_atpid . "'";

        $data = $Model->query($sql);

        if ($data[0]['alm_confirmstatus'] == '待确认') {
            echo 1;
        } else {
            echo 0;
        }
    }


    public function iscomfirm()
    {
        $alm_atpid = $_GET['alm_atpid'];

        $Model = M();

        $sql = "select * from szny_alarm where alm_atpid =" . "'" . $alm_atpid . "'";

        $data = $Model->query($sql);

        if ($data[0]['alm_confirmstatus'] == '待确认') {
            echo 1;
        } else {
            echo 0;
        }
    }

    public function comfirm()
    {
        $alm_atpid = $_POST['alm_atpid'];
        $Model_alarm = M('alarm');
        $data = $Model_alarm->where("alm_atpid='%s'", array($alm_atpid))->find();
        $data['alm_atplastmodifydatetime'] = date('Y-m-d H:i:s', time());
        $data['alm_atplastmodifyuser'] = session('emp_account');
        $data['alm_empid'] = session('emp_atpid');
        $data['alm_confirmdiscribe'] = $_POST['alm_discribe'];
        $data['alm_confirmstatus'] = "已确认";
        $data['alm_disposemethod'] = "手工确认";
        $data['alm_disposedate'] = date('Y-m-d H:i:s', time());
        $Model_alarm->where("alm_atpid='%s'", $alm_atpid)->save($data);
        echo 1;
    }

    public function ignore()
    {
        $alm_atpid = $_POST['alm_atpid'];
        $Model_alarm = M('alarm');
        $data = $Model_alarm->where("alm_atpid='%s'", array($alm_atpid))->find();
        $data['alm_atplastmodifydatetime'] = date('Y-m-d H:i:s', time());
        $data['alm_atplastmodifyuser'] = session('emp_account');
        $data['alm_empid'] = session('emp_atpid');
        $data['alm_unconfirmdiscribe'] = $_POST['alm_discribe'];
        $data['alm_confirmstatus'] = "已忽略";
        $data['alm_disposemethod'] = "手工确认";
        $data['alm_disposedate'] = date('Y-m-d H:i:s', time());
        $Model_alarm->where("alm_atpid='%s'", $alm_atpid)->save($data);
        echo 1;
    }


    public function getAlarm($id)
    {
        $Model = M();

        $sql = "select alm_deviveid from szny_alarm where alm_atpstatus is null and alm_atpid =" . "'" . $id . "'";

        $deviveid = $Model->query($sql);

        $sql_select = "select * from szny_alarm where alm_atpstatus is null and alm_confirmstatus = '未处理' and alm_deviveid = " . "'" . $deviveid[0]['alm_deviveid'] . "'";

        $alarm = $Model->query($sql_select);

        $this->assign('alarm', $alarm);
    }

    public function getEmp()
    {
        $Model = M();

        $sql = "select * from szny_emp where emp_atpstatus is null";

        $emp = $Model->query($sql);

        $this->assign('emp', $emp);
    }


    public function reason()
    {
        $alm_atpid = I('get.id');

        $this->assign('alm_atpid', $alm_atpid);
        $this->display();
    }


    public function submitignore()
    {

        $alm_atpid = $_POST['alm_atpid'];

        $Model = M();

        $sql_update = "update szny_alarm set alm_confirmstatus = '已忽略' where alm_atpid =" . "'" . $alm_atpid . "'";

        $Model->query($sql_update);
    }

}