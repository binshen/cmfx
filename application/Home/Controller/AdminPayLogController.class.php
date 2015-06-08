<?php
namespace Home\Controller;
use Common\Controller\AdminbaseController;

class AdminPayLogController extends AdminbaseController {
    
    protected $Dao;
    protected $BrokerDao;
    
    function _initialize() {
        
        parent::_initialize();
        $this->Dao = D("Home/PayLog");
        $this->BrokerDao = D("Home/Broker");
    }
    
    function index(){
    	$map = array();
    	if(IS_POST) {
    		$broker = I('post.broker');
    		if(!empty($broker)) {
    			$map['sd_pay_log.bid'] = $broker;
    		}
    	
    		$date = I('post.date');
    		if(!empty($date)) {
    			$map['sd_pay_log.date'] = $date;
    		}
    	
    		$this->assign('broker', $broker);
    		$this->assign('date', $date);
    	}
    	
    	$count = $this->Dao
	    	->join('sd_broker ON sd_pay_log.bid = sd_broker.id', "left")
	    	->where($map)
	    	->count();
    	
    	$page = $this->page($count, 20);
        $payLogList = $this->Dao
            ->join('sd_broker ON sd_pay_log.bid = sd_broker.id',"left")
            ->order("sd_pay_log.created DESC")
            ->field('sd_broker.name AS bname, sd_pay_log.date, sd_pay_log.pay,sd_pay_log.created')
            ->where($map)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        
        $brokerList = $this->BrokerDao->order('name')->select();
        $this->assign('brokerList', $brokerList);
        
        $this->assign('payLogList', $payLogList);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }
}