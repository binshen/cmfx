<?php
namespace Home\Controller;
use Common\Controller\AdminbaseController;

class AdminPayLogController extends AdminbaseController {
    
    protected $Dao;
    
    function _initialize() {
        
        parent::_initialize();
        $this->Dao = D("Home/PayLog");
    }
    
    function index(){
    	$count=$this->Dao->count();
    	$page = $this->page($count, 20);
        $payLogList = $this->Dao
            ->join('sd_paya ON sd_pay_log.pay_id = sd_paya.id',"left")
            ->join('sd_broker ON sd_pay_log.bid = sd_broker.id',"left")
            ->join('sd_project ON sd_paya.pid = sd_project.id',"left")
            ->field('sd_pay_log.*, sd_paya.date,sd_broker.name bname,sd_project.name pname')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        
        $this->assign('payLogList', $payLogList);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }
    

}