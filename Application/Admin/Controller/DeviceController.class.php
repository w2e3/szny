<?php
namespace Admin\Controller;
use Think\Controller;
use Admin\BaseController;
class DeviceController extends BaseAuthController
{
    public function index()
    {
        $this->logSys(session('emp_atpid'),"访问日志","访问页面：【设备管理】 / 【设备卡片管理】");
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
//        dump($data_org);die();
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
                    if (null == $value_org['rgn_deviceid']){
                        $tdata['icon'] = $this->makeICONPath()."/Public/vendor/zTree_v3/css/zTreeStyle/img/diy/ele_meter_red.png";
                    }else{
                        $tdata['icon'] = $this->makeICONPath()."/Public/vendor/zTree_v3/css/zTreeStyle/img/diy/ele_meter.png";
                    }
                }elseif ('水能' == $value_org['et_name']){
                    if (null == $value_org['rgn_deviceid']){
                        $tdata['icon'] = $this->makeICONPath()."/Public/vendor/zTree_v3/css/zTreeStyle/img/diy/water_meter_red.png";
                    }else {
                        $tdata['icon'] = $this->makeICONPath()."/Public/vendor/zTree_v3/css/zTreeStyle/img/diy/water_meter.png";
                    }
                }elseif ('冷能' == $value_org['et_name']){
                    if (null == $value_org['rgn_deviceid']){
                        $tdata['icon'] = $this->makeICONPath()."/Public/vendor/zTree_v3/css/zTreeStyle/img/diy/coldhotmeter_red.png";
                    }else {
                        $tdata['icon'] = $this->makeICONPath()."/Public/vendor/zTree_v3/css/zTreeStyle/img/diy/coldhotmeter.png";
                    }
                }elseif ('暖能' == $value_org['et_name']){
                    if (null == $value_org['rgn_deviceid']){
                        $tdata['icon'] = $this->makeICONPath()."/Public/vendor/zTree_v3/css/zTreeStyle/img/diy/coldhotmeter_red.png";
                    }else {
                        $tdata['icon'] = $this->makeICONPath()."/Public/vendor/zTree_v3/css/zTreeStyle/img/diy/coldhotmeter.png";
                    }
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
        $rgn_atpid = I('get.rgn_atpid','');
        $Model = M();
        $sql_select = "
        select 
          * 
          from szny_region t
          where t.rgn_atpstatus is null and t.rgn_atpid = '$rgn_atpid'
          ";
        //guid5986FFDB-2A2B-48CA-8704-77447DE379D2
        $Result = $Model->query($sql_select);
        $et_name = $Result[0]['et_name'];
        //dump($et_name);
        $this->getDevicemodel($et_name);
        $this->getDepartment();
        $this->getStatus();
        $this->getRegion();
        $this->getEmp();
        $this->display();
        $this->logSys(session('emp_atpid'),"访问日志","访问页面：【设备管理】 / 【设备卡片管理】 / 【添加】");
    }

   public function edit()
    {
        $id = I("get.id",'');//dev_atpid
        if ($id) {
            $Model = M('device');
            $data = $Model->where("dev_atpid='%s'", array($id))->find();
            //dump($data);
            if ($data) {
                $this->assign('data', $data);
            }
        }

        $Model_energytype = M('device');
        $sql_et = "
        select
        *
        from szny_device t
        left join szny_region t1 on t.dev_regionid = t1.rgn_atpid
        where t.dev_atpstatus is null and t1.rgn_atpstatus is null and t.dev_atpid = '$id'
        ";
        $Result = $Model_energytype->query($sql_et);
        //dump($Result);
        $et_name = $Result[0]['et_name'];
        $this->getDevicemodel($et_name);
        $this->getDepartment();
        $this->getStatus();
        $this->getRegion();
        $this->getEmp();
        $this->display("add");
        $this->logSys(session('emp_atpid'),"访问日志","访问页面：【设备管理】 / 【设备卡片管理】 / 【编辑】");
    }

    public function del()
    {
        try {
            $ids = $_POST['ids'];
            $array = explode(',', $ids);
            if ($array && count($array) > 0) {
                $Model = M("device");
                foreach ($array as $id) {
                    $data = $Model->where("dev_atpid='%s'", array($id))->find();
                    $data['dev_atpstatus'] = 'DEL';
                    $data['dev_atplastmodifydatetime'] = date('Y-m-d H:i:s', time());
                    $data['dev_atplastmodifyuser'] = session('emp_account');
                    $Mode_region = M('region');
                    $sql_region = "update szny_region t set t.rgn_deviceid = null where t.rgn_deviceid = '$id'";
                    $Mode_region->execute($sql_region);
                    /*************************************************************************************/
                    $Model->where("dev_atpid='%s'", $id)->save($data);

                }
            }
        } catch (\Exception $e) {
            echo "fail" . $e;
        }
        $this->logSys(session('emp_atpid'),"访问日志","访问页面：【设备管理】 / 【设备卡片管理】 / 【删除】");
    }

    public function submit(){
        $upload = new \Think\Upload();
        $upload->maxSize = 3145728;
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
        $upload->rootPath = './Public/uploads/';
        $upload->savePath = '';
        $info = $upload->upload();
    	$Model = M('device');
        $Model_region = M('region');
    	$data = $Model->create();


        $tcondition['dev_code'] = array('eq',$data['dev_code']);
        $tcondition['dev_atpstatus'] = array('exp', 'is null');
        $tparamnum = $Model->where($tcondition)->count();
        if($tparamnum!=0)
        {
            echo "1";
            die;
        }


//    	dump($data);die();
        $list = M('device')->count("dev_atpid");
        // $dev_code = 'SBK'.data("Ymd",time()).$list;
        if (null == $data['dev_atpid']){
		   //添加
            $data['dev_atpid'] = $this->makeGuid();
            $data['dev_atpcreatedatetime'] = date("Y-m-d H:i:s",time());
            $data['dev_atpcreateuser'] = session('emp_account');
            $data['dev_atplastmodifydatetime'] = date("Y-m-d H:i:s",time());
            $data['dev_atplastmodifyuser'] = session('emp_account');
            // $data['dev_acquisition'] = $this->makeCollection();
//            $data['dev_acquisition'] = $data['dev_acquisition'];  // 采集号
            $data['dev_code'] = $this->makeDeviceNo();  //设备卡编号
            $data['dev_regionid'] = null;
            //外键
            if (I('post.dev_pregionid', '') == '') {
                $data['dev_pregionid'] = null;
            }
            /***********************************************************************************************/
           // $Model_region->execute("update szny_region t set t.rgn_deviceid = '".$data['dev_atpid']."' where t.rgn_atpid = '".$data['dev_regionid']."'");
            /***********************************************************************************************/

            $add=$Model->add($data);

        } else{
            $data['dev_atplastmodifydatetime'] = date('Y-m-d H:i:s', time());
            $data['dev_atplastmodifyuser'] = session('emp_account');
            /*if ($info["u_photo"]) {
                $data['u_photo'] = $info["u_photo"]["savepath"] . $info["u_photo"]["savename"];
            }*/

//            dump($data);
            /***********************************************************************************************/
            //$Model_region->execute("update szny_region t set t.rgn_deviceid = null where t.rgn_atpid = '".$data['dev_regionid']."'");
            //$Model_region->execute("update szny_region t set t.rgn_deviceid = '".$data['dev_atpid']."' where t.rgn_atpid = '".$data['dev_regionid']."'");
            /***********************************************************************************************/
//            die();
        	//修改
            $Model->where("dev_atpid='%s'", array($data['dev_atpid']))->save($data);
        }
    }

    //获取所有数据
    public function getData(){
    	$queryparam = json_decode(file_get_contents("php://input"), true);
        $Model = M();
        $WhereConditionArray = array();
        $sql_select = "
				select
					t.*,t1.*,t2.dpm_name dpm_name,t2.dpm_atpid,t3.dpm_atpid dpm_atpid1,t3.dpm_name dpm_usename,t4.*
				from szny_device t 
				left join szny_devicemodel t1 on t.dev_devicemodelid = t1.dm_atpid
				left join szny_department t2 on t.dev_departmentid = t2.dpm_atpid
				left join szny_department t3 on t.dev_usedepartmentid = t3.dpm_atpid
				left join szny_region t4 on t.dev_regionid = t4.rgn_atpid
				";
		$sql_count = "
				select
					count(1) c
				from szny_device t 
				left join szny_devicemodel t1 on t.dev_devicemodelid = t1.dm_atpid
				left join szny_department t2 on t.dev_departmentid = t2.dpm_atpid
				left join szny_department t3 on t.dev_usedepartmentid = t3.dpm_atpid
				left join szny_region t4 on t.dev_regionid = t4.rgn_atpid
				";
        $sql_select = $this->buildSql($sql_select, "t.dev_atpstatus is null");
        $sql_count = $this->buildSql($sql_count, "t.dev_atpstatus is null");
      //  $sql_select = $this->buildSql($sql_select, "t.dev_status = '启用'");
      //  $sql_count = $this->buildSql($sql_count, "t.dev_status = '启用'");

        //快捷搜索
        // if (null != $queryparam['search']) {
        //     $searchcontent = trim($queryparam['search']);
        //     $sql_select = $this->buildSql($sql_select, "t.dev_code like '%" . $searchcontent . "%'");
        //     $sql_count = $this->buildSql($sql_count, "t.dev_code like '%" . $searchcontent . "%'");
        // }
        //快捷搜索
        if (null != $queryparam['search']) {
            $searchcontent = trim($queryparam['search']);
            $sql_select = $this->buildSql($sql_select, "t.dev_code like '%s'");
            $sql_count = $this->buildSql($sql_count, "t.dev_code like '%s'");
            // $sql_select = $this->buildSql($sql_select, "t.dev_name like '%" . $searchcontent . "%'");
            // $sql_count = $this->buildSql($sql_count, "t.dev_name like '%" . $searchcontent . "%'");
            array_push($WhereConditionArray, $this->buildSqlLikeContain($searchcontent));
        }

    /*    if (null != $queryparam['rgn_atpid']) {
            $searchcontent = trim($queryparam['rgn_atpid']);
            $Result_tree = $this->regionrecursive($searchcontent);
            $endrgn_atpidsstrings = array();
            foreach ($Result_tree as $k => $v){
                array_push($endrgn_atpidsstrings,$v['rgn_atpid']);
            }
            $endrgn_atpidsstrings = "'".implode("','",$endrgn_atpidsstrings)."'";
            $sql_select = $this->buildSql($sql_select, "t.dev_regionid in (".$endrgn_atpidsstrings.")");
            $sql_count = $this->buildSql($sql_count, "t.dev_regionid in (".$endrgn_atpidsstrings.")");
        }
   */
        //排序
        if (null != $queryparam['sort']) {
            $sql_select = $sql_select . " order by " . $queryparam['sort'] . ' ' . $queryparam['sortOrder'] . ' ';
        } else {
            $sql_select = $sql_select . " order by t.dev_atpcreatedatetime desc";
        }

        //自定义分页
        if (null != $queryparam['limit']) {

            if ('0' == $queryparam['offset']) {
                $sql_select = $sql_select . " limit " . '0' . ',' . $queryparam['limit'] . ' ';
            } else {
                $sql_select = $sql_select . " limit " . $queryparam['offset'] . ',' . $queryparam['limit'] . ' ';
            }
        }

        $Result = $Model->query($sql_select, $WhereConditionArray);
        $Count = $Model->query($sql_count, $WhereConditionArray);
        // var_dump($Result);die;
        echo json_encode(array('total' => $Count[0]['c'], 'rows' => $Result));
    }
/******************************************************************************************/
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
        //dump($rgn_atpid);
        $Model = M('region');
        $select_is_one = "
            select 
            count(1) c 
            from szny_device t 
            where t.dev_atpstatus is null and t.dev_status = '启用' and t.dev_regionid = '$rgn_atpid'
            ";
        $Result = $Model->query($select_is_one);
        //dump($Result);
        if($Result[0]['c'] > 0){echo "1";}else{ echo "0";}
    }

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
        $this->assign('ds_region',$Result);
    }
    public function getEmp()
    {
        $Model = M();
        $sql = "select * from szny_emp t where t.emp_atpstatus is null";
        $emp = $Model->query($sql);
        $this->assign('emp',$emp);
    }

    public function getDevicemodel($et_name)
    {
        $Model = M();
        $sql_select="
            select
                *
            from szny_devicemodel t
            where t.dm_atpstatus is null
            ";
        if (null != $et_name) {
            $sql_select = $this->buildSql($sql_select, "t2.et_name = '$et_name'");
        }
        $Result = $Model->query($sql_select);
   //dump($Result);
        $this->assign('ds_devicemodel',$Result);
    }

    public function getDepartment()
    {
        $Model = M();
        $sql_select="
            select
                *
            from szny_department t
            where t.dpm_atpstatus is null";
        $Result = $Model->query($sql_select);//dump($Result);
        $this->assign('ds_department',$Result);
    }
    public function getStatus()
    {
        $M = M('config');
        $data = $M->where("cfg_key='设备状态'")->find();
        $array = explode(',',$data['cfg_value']);
        $this->assign('ds_devstatus',$array);
    }

    public function getInfoDevicemodel()
    {
        $id = I("get.id","");
        $queryparam = json_decode(file_get_contents("php://input"), true);
        $Model = M();
        $sql_select = "
        select 
        * 
        from szny_devicemodel t
        left join szny_company t1 on t.dm_companyid = t1.cpy_atpid
        ";
        $sql_count = "
         select
        count(1) c
         from szny_devicemodel t
        left join szny_company t1 on t.dm_companyid = t1.cpy_atpid
        ";

        $sql_select = $this->buildSql($sql_select,"t.dm_atpstatus is null");
        $sql_count = $this->buildSql($sql_count,"t.dm_atpstatus is null");
        $sql_select = $this->buildSql($sql_select,"t1.cpy_atpstatus is null");
        $sql_count = $this->buildSql($sql_count,"t1.cpy_atpstatus is null");
        $sql_select = $this->buildSql($sql_select,"t.dm_atpid = '$id'");
        $sql_count = $this->buildSql($sql_count,"t.dm_atpid = '$id'");

        // //排序
        if (null != $queryparam['sort']) {
            $sql_select = $sql_select . " order by " . $queryparam['sort'] . ' ' . $queryparam['sortOrder'] . ' ';
        } else {
            $sql_select = $sql_select . " order by dm_atpid asc";
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

    public function getInfoRegion()
    {
        $id = I("get.id","");
        $queryparam = json_decode(file_get_contents("php://input"), true);
        $Model = M();
        $sql_select = "
        select 
        * 
        from szny_region t
		left join szny_device t2 on t.rgn_deviceid = t2.dev_atpid
        ";
        $sql_count = "
        select
        count(1) c
        from szny_region t
		left join szny_device t2 on t.rgn_deviceid = t2.dev_atpid
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
    public function getInfoDepartment(){
        $id = I("get.id","");
        $queryparam = json_decode(file_get_contents("php://input"), true);
        $Model = M();
        $sql_select = "
        select 
        * 
        from szny_department t
        ";
        $sql_count = "
        select
        count(1) c
        from szny_department t
        ";

        $sql_select = $this->buildSql($sql_select,"t.dpm_atpstatus is null");
        $sql_count = $this->buildSql($sql_count,"t.dpm_atpstatus is null");
        $sql_select = $this->buildSql($sql_select,"t.dpm_atpid = '$id'");
        $sql_count = $this->buildSql($sql_count,"t.dpm_atpid = '$id'");

        // //排序
        if (null != $queryparam['sort']) {
            $sql_select = $sql_select . " order by " . $queryparam['sort'] . ' ' . $queryparam['sortOrder'] . ' ';
        } else {
            $sql_select = $sql_select . " order by dpm_atpid asc";
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
    /******************************************************************************************************************/
    public function isHasDevice(){
        $rgn_atpid = I("post.rgn_atpid","");
        $Model_region = M('region');
        $sql_select_region = "
        select 
        *
        from szny_region t 
        left join szny_energytyperegion t1 on t1.etr_regionid = t.rgn_atpid
        where t.rgn_atpstatus is null and t1.etr_atpstatus is null and t.rgn_atpid = '$rgn_atpid'
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
//        echo $et_atpidstrings;
//        dump($et_atpidstrings);
//        die();
        $Model_device = M('device');
        $sql_select_device_devnum = "
        select 
        count(*) devnum
        from szny_device t
        left join szny_devicemodel t1 on t.dev_devicemodelid = t1.dm_atpid
        left join szny_energytypemodel t2 on t2.etm_devicemodelid = t1.dm_atpid
        where t.dev_atpstatus is null and t1.dm_atpstatus is null and t2.etm_atpstatus is null and t.dev_status = '待安装' and t3.et_atpid in(".$et_atpidstrings.")
        ";
        $Result_devicenum = $Model_device->query($sql_select_device_devnum);
//        dump($Result_devicenum);
        if (0 < $Result_devicenum[0]['devnum']){echo '1';}else{echo '0';};
    }
    public function tablesubmit(){
        $dev_atpid = I('post.dev_atpid','');
        $rgn_atpid = I('post.rgn_atpid','');
        $Model_region = M('region');
        $Model_device = M('device');
        $data_region = $Model_region ->where("rgn_atpid='%s'",$rgn_atpid)->find();
        $data_device = $Model_device ->where("dev_atpid='%s'",$dev_atpid)->find();
        $Model_region->execute("update szny_region t set t.rgn_deviceid = '".$data_device['dev_atpid']."' where t.rgn_atpid = '$rgn_atpid'");
        $Model_region->execute("update szny_device t set t.dev_regionid = '".$data_region['rgn_atpid']."',t.dev_status = '启用' where t.dev_atpid = '$dev_atpid'");
    }

    public function getDeviceData()
    {
        $rgn_atpid = I("get.rgn_atpid","");
        $Model_region = M('region');
        $sql_select_region = "
        select 
        *
        from szny_region t 
        left join szny_energytyperegion t1 on t1.etr_regionid = t.rgn_atpid
        where t.rgn_atpstatus is null and t1.etr_atpstatus is null and t.rgn_atpid = '$rgn_atpid'
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
//        die();
        $queryparam = json_decode(file_get_contents("php://input"), true);
        $Model = M();
        $sql_select = "
        select 
        * 
        from szny_device t
        left join szny_devicemodel t1 on t.dev_devicemodelid = t1.dm_atpid
        left join szny_energytypemodel t2 on t2.etm_devicemodelid = t1.dm_atpid
        left join szny_energytype t3 on t2.etm_energytypeid = t3.et_atpid
        ";
        $sql_count = "
        select
        count(1) c
        from szny_device t
        left join szny_devicemodel t1 on t.dev_devicemodelid = t1.dm_atpid
        left join szny_energytypemodel t2 on t2.etm_devicemodelid = t1.dm_atpid
        left join szny_energytype t3 on t2.etm_energytypeid = t3.et_atpid
        ";

        $sql_select = $this->buildSql($sql_select,"t.dev_atpstatus is null");
        $sql_count = $this->buildSql($sql_count,"t.dev_atpstatus is null");
        $sql_select = $this->buildSql($sql_select,"t1.dm_atpstatus is null");
        $sql_count = $this->buildSql($sql_count,"t1.dm_atpstatus is null");
        $sql_select = $this->buildSql($sql_select,"t2.etm_atpstatus is null");
        $sql_count = $this->buildSql($sql_count,"t2.etm_atpstatus is null");
        $sql_select = $this->buildSql($sql_select,"t3.et_atpstatus is null");
        $sql_count = $this->buildSql($sql_count,"t3.et_atpstatus is null");
        $sql_select = $this->buildSql($sql_select,"t.dev_status = '待安装'");
        $sql_count = $this->buildSql($sql_count,"t.dev_status = '待安装'");
        $sql_select = $this->buildSql($sql_select,"t3.et_atpid in(".$et_atpidstrings.")");
        $sql_count = $this->buildSql($sql_count,"t3.et_atpid in(".$et_atpidstrings.")");
        $sql_select = $sql_select . "  group by t.dev_atpid";
        $sql_count = $sql_count . "  group by t.dev_atpid";

        // //排序
        if (null != $queryparam['sort']) {
            $sql_select = $sql_select . " order by " . $queryparam['sort'] . ' ' . $queryparam['sortOrder'] . ' ';
        } else {
            $sql_select = $sql_select . " order by t.dev_code asc";
        }

        //自定义分页
        if (null != $queryparam['limit']) {

            if ('0' == $queryparam['offset']) {
                $sql_select = $sql_select . " limit " . '0' . ',' . $queryparam['limit'] . ' ';
            } else {
                $sql_select = $sql_select . " limit " . $queryparam['offset'] . ',' . $queryparam['limit'] . ' ';
            }
        }
        $Result = $Model->query($sql_select);//echo $Model->_sql();
        $Count = $Model->query($sql_count);
        echo json_encode(array('total' => $Count[0]['c'], 'rows' => $Result));
    }
}