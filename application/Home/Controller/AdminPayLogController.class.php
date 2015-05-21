<?php
namespace Home\Controller;
use Common\Controller\AdminbaseController;

class AdminPayLogController extends AdminbaseController {
    
    protected $Dao;
    
    function _initialize() {
        
        parent::_initialize();
        $this->Dao = D("Home/PayLog");
        $this->ProjectDao = D("Home/Project");
    }
    
    function index(){
    	$map = array();
    	if(IS_POST) {
    		$project = I('post.project');
    		if(!empty($project)) {
    			$map['sd_paya.pid'] = $project;
    		}
    	
    		$date = I('post.date');
    		if(!empty($date)) {
    			$map['sd_paya.date'] = $date;
    		}
    	
    		$this->assign('project', $project);
    		$this->assign('date', $date);
    	}
    	
    	$count = $this->Dao
	    	->join('sd_paya ON sd_pay_log.pay_id = sd_paya.id',"left")
	    	->join('sd_broker ON sd_pay_log.bid = sd_broker.id',"left")
	    	->join('sd_project ON sd_paya.pid = sd_project.id',"left")
	    	->field('count(1) num')
	    	->where($map)
	    	->find();
    	
    	$page = $this->page($count['num'], 20);
        $payLogList = $this->Dao
            ->join('sd_paya ON sd_pay_log.pay_id = sd_paya.id',"left")
            ->join('sd_broker ON sd_pay_log.bid = sd_broker.id',"left")
            ->join('sd_project ON sd_paya.pid = sd_project.id',"left")
            ->order("sd_pay_log.cdate DESC")
            ->field('sd_pay_log.*, sd_paya.date,sd_broker.name bname,sd_project.name pname')
            ->where($map)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        
        $projectList = $this->ProjectDao->order('name')->select();
        $this->assign('projectList', $projectList);
        
        $this->assign('payLogList', $payLogList);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }
    

}