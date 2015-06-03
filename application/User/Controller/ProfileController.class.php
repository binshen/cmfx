<?php

/**
 * 会员中心
 */
namespace User\Controller;
use Common\Controller\MemberbaseController;
class ProfileController extends MemberbaseController {
	
	protected $users_model;
	function _initialize(){
		parent::_initialize();
		$this->users_model=D("Home/Broker");
		$this->Dao = D("Home/Perf");
		$this->ProjectDao = D("Home/Project");
		$this->BrokerDao = D("Home/Broker");
		$this->TypeDao = D("Home/Type");
		$this->PayMngDao = D("Home/PayMng");
		$this->QuarterPerfDao =D("Home/QuarterPerf");
	}
	
    
    public function edit_post() {
    	if(IS_POST){
    		$userid=sp_get_current_userid();
    		$_POST['id']=$userid;
    		if ($this->users_model->create()) {
				if ($this->users_model->save()!==false) {
					$user=$this->users_model->find($userid);
					sp_update_current_user($user);
					$this->success("保存成功！",U("user/profile/edit"));
				} else {
					$this->error("保存失败！");
				}
			} else {
				$this->error($this->users_model->getError());
			}
    	}
    	
    }
    
    public function password() {
    	$userid=sp_get_current_userid();
    	$user=$this->users_model->where(array("id"=>$userid))->find();
    	$this->assign($user);
    	$this->display();
    }
    
    public function password_post() {
    	if (IS_POST) {
    		if(empty($_POST['old_password'])){
    			$this->error("原始密码不能为空！");
    		}
    		if(empty($_POST['password'])){
    			$this->error("新密码不能为空！");
    		}
    		
    		$user = $_SESSION["user"];
    		$uid = $user['id'];
    		
    		$uid=sp_get_current_userid();
    		$admin=$this->users_model->where("id=$uid")->find();
    		$old_password=$_POST['old_password'];
    		$password=$_POST['password'];
    		
    		if(sha1($old_password)==$admin['password']){
    			if($_POST['password']==$_POST['repassword']){
    				if($admin['password']==sha1($password)){
    					$this->error("新密码不能和原始密码相同！");
    				}else{
    					$data['password']=sha1($password);
    					$data['id']=$uid;
    					$r=$this->users_model->save($data);
    					if ($r!==false) {
    						$this->success("修改成功！");
    					} else {
    						$this->error("修改失败！");
    					}
    				}
    			}else{
    				$this->error("密码输入不一致！");
    			}
    	
    		}else{
    			$this->error("原始密码不正确！");
    		}
    	}
    	 
    }
    
    
    function bang(){
    	$oauth_user_model=M("OauthUser");
    	$uid=sp_get_current_userid();
    	$oauths=$oauth_user_model->where(array("uid"=>$uid))->select();
    	$new_oauths=array();
    	foreach ($oauths as $oa){
    		$new_oauths[strtolower($oa['from'])]=$oa;
    	}
    	$this->assign("oauths",$new_oauths);
    	$this->display();
    }
    
    function avatar(){
    	$userid=sp_get_current_userid();
		$user=$this->users_model->where(array("id"=>$userid))->find();
		$this->assign($user);
    	$this->display();
    }
    
    function avatar_upload(){
    	$config=array(
    			'FILE_UPLOAD_TYPE' => sp_is_sae()?"Sae":'Local',//TODO 其它存储类型暂不考虑
    			'rootPath' => './'.C("UPLOADPATH"),
    			'savePath' => './avatar/',
    			'maxSize' => 512000,//500K
    			'saveName'   =>    array('uniqid',''),
    			'exts'       =>    array('jpg', 'png', 'jpeg'),
    			'autoSub'    =>    false,
    	);
    	$upload = new \Think\Upload($config);//
    	$info=$upload->upload();
    	//开始上传
    	if ($info) {
    	//上传成功
    	//写入附件数据库信息
    		$first=array_shift($info);
    		$file=$first['savename'];
    		$_SESSION['avatar']=$file;
    		$this->ajaxReturn(sp_ajax_return(array("file"=>$file),"上传成功！",1),"AJAX_UPLOAD");
    	} else {
    		//上传失败，返回错误
    		$this->ajaxReturn(sp_ajax_return(array(),$upload->getError(),0),"AJAX_UPLOAD");
    	}
    }
    
    function avatar_update(){
    	if(!empty($_SESSION['avatar'])){
    		$targ_w = intval($_POST['w']);
    		$targ_h = intval($_POST['h']);
    		$x = $_POST['x'];
    		$y = $_POST['y'];
    		$jpeg_quality = 90;
    		
    		$avatar=$_SESSION['avatar'];
    		$avatar_dir=C("UPLOADPATH")."avatar/";
    		if(sp_is_sae()){//TODO 其它存储类型暂不考虑
    			$src=C("TMPL_PARSE_STRING.__UPLOAD__")."avatar/$avatar";
    		}else{
    			$src = $avatar_dir.$avatar;
    		}
    		
    		$avatar_path=$avatar_dir.$avatar;
    		
    		
    		if(sp_is_sae()){//TODO 其它存储类型暂不考虑
    			$img_data = sp_file_read($avatar_path);
    			$img = new \SaeImage();
    			$size=$img->getImageAttr();
    			$lx=$x/$size[0];
            	$rx=$x/$size[0]+$targ_w/$size[0];
            	$ty=$y/$size[1];
            	$by=$y/$size[1]+$targ_h/$size[1];
    			
    			$img->crop($lx, $rx,$ty,$by);
    			$img_content=$img->exec('png');
    			sp_file_write($avatar_dir.$avatar, $img_content);
    		}else{
    			$image = new \Think\Image();
    			$image->open($src);
    			$image->crop($targ_w, $targ_h,$x,$y);
    			$image->save($src);
    		}
    		
    		$userid=sp_get_current_userid();
    		$result=$this->users_model->where(array("id"=>$userid))->save(array("avatar"=>$avatar));
    		$_SESSION['user']['avatar']=$avatar;
    		if($result){
    			$this->success("头像更新成功！");
    		}else{
    			$this->error("头像更新失败！");
    		}
    		
    	}
    }
    
    function myperf(){
    	$user = $_SESSION['user'];
    	$bid= $user['id'];
    	$map = array(
    			'bid'=>$bid
    	);
    	if(IS_POST) {
    		$project = I('post.project');
    		if(!empty($project)) {
    			$map['pid'] = $project;
    		}
    	
    		$date = I('post.date');
    		if(!empty($date)) {
    			$map["DATE_FORMAT(sd_perf.date,'%Y%m')"] = $date;
    		}
    	
    		$this->assign('broker', $broker);
    		$this->assign('project', $project);
    		$this->assign('date', $date);
    	}
    	
    	$count = $this->Dao->where($map)->count();
    	$page = $this->page($count, 20);
    	
    	$perfList = $this->Dao
    	->join("sd_type ON sd_type.id = sd_perf.tid")
    	->join("sd_project ON sd_project.id = sd_perf.pid", 'left')
    	->join("sd_broker ON sd_broker.id = sd_perf.bid", 'left')
    	->field("sd_perf.*, sd_type.name AS tname, sd_project.name AS pname, sd_broker.name AS bname, sd_type.discount, sd_perf.date")
    	->where($map)
    	->order("sd_perf.date DESC")
    	->limit($page->firstRow . ',' . $page->listRows)
    	->select();
    	
    	$this->assign("page", $page->show('Admin'));
    	
    	$projectList = $this->ProjectDao->order('name')->select();
    	$this->assign('projectList', $projectList);
    	
    	$brokerList = $this->BrokerDao->where('status=1')->order('name')->select();
    	$this->assign('brokerList', $brokerList);
    	
    	$this->assign('perfList', $perfList);
    	$this->display();
    }
    
    function edit(){
    	        $id = I('get.id', 0, 'intval');
        $perf = $this->Dao
            ->join('sd_broker ON sd_broker.id = sd_perf.bid')
            ->join('sd_rank ON sd_rank.id = sd_broker.rank_id', 'left')
            ->join('sd_project ON sd_perf.pid = sd_project.id', 'left')
            ->join('sd_type ON sd_perf.tid = sd_type.id', 'left')
            ->field('sd_perf.*, sd_rank.name AS rank_name,sd_project.name pname,sd_type.name tname')
            ->where('sd_perf.id=' . $id)
            ->find();
        $this->assign($perf);
        
        $this->display();
    }
    
    
}
    
