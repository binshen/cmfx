<?php

/**
 * 会员中心
 */
namespace User\Controller;
use Common\Controller\MemberbaseController;
class CenterController extends MemberbaseController {
	
	protected $users_model;
	function _initialize(){
		parent::_initialize();
		$this->users_model=D("Common/Users");
	}
    //会员中心
	public function index() {
		//$userid=sp_get_current_userid();
		//$user=$this->users_model->where(array("id"=>$userid))->find();
		$user_s = $_SESSION["user"];
		$users_model=D('Home/Broker');
		$result = $users_model->join("sd_rank on sd_broker.rank_id=sd_rank.id")
					->field("sd_broker.*,sd_rank.name rank_name")
					->where('sd_broker.id='.$user_s['id'])
					->find();
		
		$this->assign($result);
    	$this->display(':center');
    }
}
