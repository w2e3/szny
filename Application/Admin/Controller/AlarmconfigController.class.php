<?php
namespace Admin\Controller;
use Think\Controller;
use Admin\BaseController;
class AlarmconfigController extends BaseAuthController
{
    public function index()
    {
        $this->logSys(session('emp_atpid'),"访问日志","访问页面：【预警中心】 / 【报警配置】");
        $Model = M();
        $sql_select ="
         select 
        * 
        from szny_region t
        left join szny_energytyperegion t1 on t.rgn_atpid = t1.etr_regionid
        left join szny_energytype t2 on t2.et_atpid = t1.etr_energytypeid
        where t.rgn_atpstatus is null and t1.etr_atpstatus is null and t2.et_atpstatus is null
        group by t.rgn_atpid
         order by t.rgn_name asc
        ";
        $data_org = $Model->query($sql_select);
        $treedatas = array();
        foreach ($data_org as $key_org => $value_org) {
            $tdata = array();
            $tdata['id'] = $value_org['rgn_atpid'];
            $tdata['pid'] = $value_org['rgn_pregionid'];
            $tdata['name'] = $value_org['rgn_name'];
            $tdata['open'] = true;
            if('园区' == $value_org['rgn_category']){
                $tdata['icon'] = $this->makeICONPath()."/Public/vendor/zTree_v3/css/zTreeStyle/img/diy/park.png";
            }elseif ('楼' == $value_org['rgn_category']){
                $tdata['icon'] = $this->makeICONPath()."/Public/vendor/zTree_v3/css/zTreeStyle/img/diy/build.png";
            }elseif ('座' == $value_org['rgn_category']){
                $tdata['icon'] = $this->makeICONPath()."/Public/vendor/zTree_v3/css/zTreeStyle/img/diy/floor.png";
            }elseif ('单元' == $value_org['rgn_category']){
                $tdata['icon'] = $this->makeICONPath()."/Public/vendor/zTree_v3/css/zTreeStyle/img/diy/unit.png";
            }elseif ('层' == $value_org['rgn_category']){
                $tdata['icon'] = $this->makeICONPath()."/Public/vendor/zTree_v3/css/zTreeStyle/img/diy/storey.png";
            }elseif ('设备点' == $value_org['rgn_category']){
                if ('电能' == $value_org['et_name']){
                    $tdata['icon'] = $this->makeICONPath()."/Public/vendor/zTree_v3/css/zTreeStyle/img/diy/ele_meter.png";
                }elseif ('水能' == $value_org['et_name']){
                    $tdata['icon'] = $this->makeICONPath()."/Public/vendor/zTree_v3/css/zTreeStyle/img/diy/water_meter.png";
                }elseif ('冷能' == $value_org['et_name']){
                    $tdata['icon'] = $this->makeICONPath()."/Public/vendor/zTree_v3/css/zTreeStyle/img/diy/coldhotmeter.png";
                }elseif ('暖能' == $value_org['et_name']){
                    $tdata['icon'] = $this->makeICONPath()."/Public/vendor/zTree_v3/css/zTreeStyle/img/diy/coldhotmeter.png";
                }else{
                    $tdata['icon'] = $this->makeICONPath()."/Public/vendor/zTree_v3/css/zTreeStyle/img/diy/energywater.png";
                }
            }else{
                $tdata['icon'] = $this->makeICONPath()."/Public/vendor/zTree_v3/css/zTreeStyle/img/diy/unit.png";
            }

            $tdata['type'] = '园区';
            array_push($treedatas, $tdata);
        }
        $this->assign('treedatas',json_encode($treedatas));
        $this->display();
    }

    public function add()
    {
        $this->getRegion();
        $this->getLevel();
        $this->display();
        $this->logSys(session('emp_atpid'),"访问日志","访问页面：【预警中心】 / 【报警配置】 / 【添加】");
    }

    public function edit()
    {
        $id = $_GET['id'];
        if ($id) {
            $Model = M('alarmconfig');
            $data = $Model->where("almc_atpid='%s'", array($id))->find();
            if ($data) {
                $this->assign('data', $data);
            }
        }
        $this->getLevel();
        $this->getRegion();
        $this->display("add");
        $this->logSys(session('emp_atpid'),"访问日志","访问页面：【预警中心】 / 【报警配置】 / 【编辑】");
    }

    public function del()
    {
        try {
            $ids = $_POST['ids'];
            $array = explode(',', $ids);
            if ($array && count($array) > 0) {
                $Model = M("alarmconfig");
                foreach ($array as $id) {
                    $data = $Model->where("almc_atpid='%s'", array($id))->find();
                    $data['almc_atpstatus'] = 'DEL';
                    $data['almc_atplastmodifydatetime'] = date('Y-m-d H:i:s', time());
                    $data['almc_atplastmodifyuser'] = session('emp_account');
                    $Model->where("almc_atpid='%s'", $id)->save($data);
                }
            }
        } catch (\Exception $e) {
            echo "fail" . $e;
        }
    }


    public function submit(){
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = './Public/uploads/';
        $upload->savePath = '';
        $info = $upload->upload();
    	$Model = M('alarmconfig');
    	$data = $Model->create();
    	//dump($data);
        if (null == $data['almc_atpid'])
        {
		   //添加
            $data['almc_atpid'] = $this->makeGuid();
            $data['almc_atpcreatedatetime'] = date("Y-m-d H:i:s",time());
            $data['almc_atpcreateuser'] = session('emp_account');
            $data['almc_atplastmodifydatetime'] = date("Y-m-d H:i:s",time());
            $data['almc_atplastmodifyuser'] = session('emp_account');
            $data['almc_atpsort'] = time();

            //图片地址
            if ($info["u_photo"]) {
                $data['u_photo'] = $info["u_photo"]["savepath"] . $info["u_photo"]["savename"];
            }
            //外键
            if (I('post.almc_pregionid', '') == '') {
                $data['almc_pregionid'] = null;
            }
            $Model->add($data);
        } else
            {
            $data['almc_atplastmodifydatetime'] = date('Y-m-d H:i:s', time());
            $data['almc_atplastmodifyuser'] = session('emp_account');
            if ($info["u_photo"]) {
                $data['u_photo'] = $info["u_photo"]["savepath"] . $info["u_photo"]["savename"];
            }
            //外键
            if (I('post.almc_pregionid', '') == '') {
                $data['almc_pregionid'] = null;
            }

        	//修改
            $Model->where("almc_atpid='%s'", array($data['almc_atpid']))->save($data);
        }
    }

    //获取所有数据
    public function getData(){
    	$queryparam = json_decode(file_get_contents("php://input"), true);
        $Model = M();
        $sql_select = "
				select
					*
				from szny_alarmconfig t
				left join szny_region t1 on t.almc_regionid = t1.rgn_atpid
				";
		$sql_count = "
				select
					count(1) c
				from szny_alarmconfig t
				left join szny_region t1 on t.almc_regionid = t1.rgn_atpid
				";
        $sql_select = $this->buildSql($sql_select, "t.almc_atpstatus is null");
        $sql_count = $this->buildSql($sql_count, "t.almc_atpstatus is null");
        $sql_select = $this->buildSql($sql_select, "t1.rgn_atpstatus is null");
        $sql_count = $this->buildSql($sql_count, "t1.rgn_atpstatus is null");
        //快捷搜索
        if (null != $queryparam['search']) {
            $searchcontent = trim($queryparam['search']);
            $sql_select = $this->buildSql($sql_select, "t.almc_name like '%" . $searchcontent . "%'");
            $sql_count = $this->buildSql($sql_count, "t.almc_name like '%" . $searchcontent . "%'");
        }

        if (null != $queryparam['rgn_atpid']) {
            $searchcontent = trim($queryparam['rgn_atpid']);
            $Result_tree = $this->regionrecursive($searchcontent);
            $endrgn_atpidsstrings = array();
            foreach ($Result_tree as $k => $v){
                array_push($endrgn_atpidsstrings,$v['rgn_atpid']);
            }
            $endrgn_atpidsstrings = "'".implode("','",$endrgn_atpidsstrings)."'";
            $sql_select = $this->buildSql($sql_select, "t1.rgn_atpid in (".$endrgn_atpidsstrings.")");
            $sql_count = $this->buildSql($sql_count, "t1.rgn_atpid in (".$endrgn_atpidsstrings.")");
        }
        //排序
        if (null != $queryparam['sort']) {
            $sql_select = $sql_select . " order by " . $queryparam['sort'] . ' ' . $queryparam['sortOrder'] . ' ';
        } else {
            $sql_select = $sql_select . " order by t.almc_name desc";
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

        $alarmconfig_atpid = [];
        foreach ($Result as $k => $v)
        {
            array_push($alarmconfig_atpid, $v['almc_atpid']);
            $v['value_param'] = '';
        }
        $ModelParam = M();
        $sql_selectparam= "
				select
					*
				from szny_alarmparam t
				left join szny_devicemodelparam t1 on t.almp_paramid = t1.dmp_atpid
				where t.almp_atpstatus is null and t1.dmp_atpstatus is null
				and t.almp_alarmid in ('".implode("','",$alarmconfig_atpid)."')
				order by t1.dmp_atpcreatedatetime asc  ";
        $Result_rel = $ModelParam->query($sql_selectparam);
        foreach ($Result as $k => &$v) {
            foreach ($Result_rel as $rmk => $rmv) {
                if ($v['almc_atpid'] == $rmv['almp_alarmid']) {
                    if ($v['value_param'] != '') {
                        $v['value_param'] = $v['value_param'] . "<br/>" . $rmv['dmp_name'].":下限".$rmv['almp_floor']."---上限".$rmv['almp_upper'];
                    } else {
                        $v['value_param'] = $rmv['dmp_name'].":下限".$rmv['almp_floor']."---上限".$rmv['almp_upper'];
                    }
                }
            }
        }
        echo json_encode(array('total' => $Count[0]['c'], 'rows' => $Result));
    }
/******************************************************************************************/
    public function addwarn(){
        $almc_atpid = I("get.almc_atpid","");//dump($almp_alarmid);
        $this->getParam($almc_atpid);
        $this->display();
        $this->logSys(session('emp_atpid'),"访问日志","访问页面：【预警中心】 / 【报警配置】 / 【配制报警条件】");
    }
    public function editwarn(){
        $id = I('get.id','');
        if ($id) {
            $Model = M('alarmparam');
            $data = $Model->where("almp_atpid='%s'", array($id))->find();//dump($data);
            if ($data) {
                $this->assign('data', $data);
            }
        }
        $this->getParam($data['almp_alarmid']);
        $this->display();
        $this->logSys(session('emp_atpid'),"访问日志","访问页面：【预警中心】 / 【报警配置】 / 【编辑报警条件】");
    }

    public function delwarn()
    {
        try {
            $ids = $_POST['ids'];
            $array = explode(',', $ids);
            if ($array && count($array) > 0) {
                $Model = M("alarmparam");
                foreach ($array as $id) {
                    $data = $Model->where("almp_atpid='%s'", array($id))->find();
                    $data['almp_atpstatus'] = 'DEL';
                    $data['almp_atplastmodifydatetime'] = date('Y-m-d H:i:s', time());
                    $data['almp_atplastmodifyuser'] = session('emp_account');
                    $Model->where("almp_atpid='%s'", $id)->save($data);
                }
            }
        } catch (\Exception $e) {
            echo "fail" . $e;
        }
    }

    public function submitwarn(){
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = './Public/uploads/';
        $upload->savePath = '';
        $info = $upload->upload();
        $Model = M('alarmparam');
        $data = $Model->create();
        if (null == $data['almp_atpid'])
        {
            //添加
            $data['almp_atpid'] = $this->makeGuid();
            $data['almp_atpcreatedatetime'] = date("Y-m-d H:i:s",time());
            $data['almp_atpcreateuser'] = session('emp_account');
            $data['almp_atplastmodifydatetime'] = date("Y-m-d H:i:s",time());
            $data['almp_atplastmodifyuser'] = session('emp_account');
            $data['almp_atpsort'] = time();

            //图片地址
            if ($info["u_photo"]) {
                $data['u_photo'] = $info["u_photo"]["savepath"] . $info["u_photo"]["savename"];
            }
            $Model->add($data);
        } else
        {
            $data['almp_atplastmodifydatetime'] = date('Y-m-d H:i:s', time());
            $data['almp_atplastmodifyuser'] = session('emp_account');
            if ($info["u_photo"]) {
                $data['u_photo'] = $info["u_photo"]["savepath"] . $info["u_photo"]["savename"];
            }
            //修改
            $Model->where("almp_atpid='%s'", array($data['almp_atpid']))->save($data);
        }
    }
    public function getInfoWarnData()
    {
        $almc_atpid = I('get.id','');
        $queryparam = json_decode(file_get_contents("php://input"), true);
        $Model = M();
        $sql_select = "
				select
					*
				from szny_alarmparam t 
				left join szny_devicemodelparam t1 on t.almp_paramid = t1.dmp_atpid
				where t.almp_atpstatus is null and t1.dmp_atpstatus is null and t.almp_alarmid = '$almc_atpid'
				";
        $sql_count = "
				select
					count(1) c
				from szny_alarmparam t 
				left join szny_devicemodelparam t1 on t.almp_paramid = t1.dmp_atpid
				where t.almp_atpstatus is null and t1.dmp_atpstatus is null and t.almp_alarmid = '$almc_atpid'
				";

        //排序
        if (null != $queryparam['sort']) {
            $sql_select = $sql_select . " order by " . $queryparam['sort'] . ' ' . $queryparam['sortOrder'] . ' ';
        } else {
            $sql_select = $sql_select . " order by t.almp_atpid desc";
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

/******************************************************************************************/
    public function getLevel()
    {
        $M = M('config');
        $data = $M->where("cfg_key='报警等级'")->find();
        $array = explode(',',$data['cfg_value']);
        $this->assign('ds_level',$array);
    }

    public function isPosition(){
        $rgn_atpid = I('post.rgn_atpid');
        $Model = M('region');
        $select_is_one = "
            select 
            *
            from szny_region t 
            where t.rgn_atpstatus is null and t.rgn_atpid = '$rgn_atpid'
            ";
        $Result = $Model->query($select_is_one);
        if ('设备点' == $Result[0]['rgn_category']){echo "1";}else{echo "0";};
    }

    public function isOne(){
        $rgn_atpid = I('post.rgn_atpid');
        $Model = M('region');
        $select_is_one = "
            select 
            count(1) c 
            from szny_alarmconfig t 
            where t.almc_atpstatus is null and t.almc_regionid = '$rgn_atpid'
            ";
        $Result = $Model->query($select_is_one);
        //dump($Result);
        if($Result[0]['c'] > 0){echo "1";}else{ echo "0";}
    }

//    private function _getParNodeChilds($id, &$arr,$mod){
//        $ret = $mod->where("rgn_atpid='%s'",$id)->field('rgn_pregionid,rgn_name')->select();
//        if(!empty($ret[0])){
//            foreach ($ret as $k => $node){
//                $arr[] = $node['rgn_name'];
//                $this->_getParNodeChilds($node['rgn_pregionid'], $arr, $mod);
//            }
//        }
//        return array_reverse($arr);
//    }
    public function getRegion()
    {
        $Model = M('region');
        $sql_select="
            select
                *
            from szny_region t
            where t.rgn_atpstatus is null and t.rgn_category = '设备点'
            ";
        $Result = $Model->query($sql_select);
//        $parNodes = array();
//        foreach ($Result as $key => &$value){
//            $rgn_atpid = $value['rgn_atpid'];
//            $res =  $this->_getParNodeChilds($rgn_atpid,$parNodes[$key],$Model);
//            $value['rgn_allname'] = implode('--',$res);
//        }
        $this->assign('ds_region',$Result);
    }

    public function getParam($almc_atpid){
//        dump($almc_atpid);
        $Model_almc = M('alarmconfig');
        $data_almc = $Model_almc->where("almc_atpid='%s'",$almc_atpid)->find();
//        dump($data_almc);die();
        $rgn_atpid = $data_almc['almc_regionid'];
        $Model_region = M('region');
        $sql_select_region = "
        select 
        *
        from szny_region t 
        left join szny_energytyperegion t1 on t1.etr_regionid = t.rgn_atpid
        left join szny_energytype t2 on t1.etr_energytypeid = t2.et_atpid
        where t.rgn_atpstatus is null and t1.etr_atpstatus is null and t2.et_atpstatus is null and t.rgn_atpid = '$rgn_atpid'
        ";
        $Result_region = $Model_region->query($sql_select_region);
        $et_atpid = array();
        if (count($Result_region) > 0){
            foreach ($Result_region as $k => $v){
                array_push($et_atpid,$v['et_atpid']);
            }
        }else{
            array_push($et_atpid,$Result_region[0]['et_atpid']);
        }
        $et_atpidstrings = "'".implode("','",$et_atpid)."'";
//        dump($et_atpidstrings);die();

        $Model = M();
        $sql_select = "
        select
        distinct
        t.dmp_atpid p_atpid,
        t.dmp_name p_name
        from szny_devicemodelparam t
        left join szny_energytypemodel t1 on t.dmp_devicemodelid = t1.etm_devicemodelid
        left join szny_energytyperegion t2 on t2.etr_energytypeid = t1.etm_energytypeid
        left join szny_region t3 on t2.etr_regionid = t3.rgn_atpid
        left join szny_alarmconfig t4 on t4.almc_regionid = t3.rgn_atpid
        where t.dmp_atpstatus is null
        and t1.etm_atpstatus is null
        and t2.etr_atpstatus is null
        and t3.rgn_atpstatus is null
        and t4.almc_atpstatus is null
        and t4.almc_atpid = '$almc_atpid'
        and t2.etr_energytypeid in (".$et_atpidstrings.")
        order by t.dmp_atpcreatedatetime asc
        ";
        $Result = $Model->query($sql_select);
//        dump($Result);
        $this->assign('ds_param',$Result);
    }

    public function ishaswarn(){
        $almc_atpid = I('get.id','');
        $Model = M('alarmparam');
        $select_num = "select count(1) c from szny_alarmparam t where t.almp_atpstatus is null and t.almp_alarmid = '$almc_atpid'";
        $num = $Model->query($select_num);
//        dump($num);die();
        if($num[0]['c'] > 0){echo "1";}else{ echo "0";}
    }
/*******************************************************************************************/
    public function getInfoRegion()
    {
        $id = I("get.id","");
        $queryparam = json_decode(file_get_contents("php://input"), true);
        $Model = M();
        $sql_select = "
        select 
        * 
        from szny_region t
        ";
        $sql_count = "
        select
        count(1) c
        from szny_region t
        ";

        $sql_select = $this->buildSql($sql_select,"t.rgn_atpstatus is null");
        $sql_count = $this->buildSql($sql_count,"t.rgn_atpstatus is null");
        $sql_select = $this->buildSql($sql_select,"t.rgn_atpid = '$id'");
        $sql_count = $this->buildSql($sql_count,"t.rgn_atpid = '$id'");

        // //排序
        if (null != $queryparam['sort']) {
            $sql_select = $sql_select . " order by " . $queryparam['sort'] . ' ' . $queryparam['sortOrder'] . ' ';
        } else {
            $sql_select = $sql_select . " order by rgn_atpid asc";
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
}