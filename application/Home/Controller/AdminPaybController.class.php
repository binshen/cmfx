<?php
namespace Home\Controller;
use Common\Controller\AdminbaseController;

class AdminPaybController extends AdminbaseController {
    
//    protected $Dao;
//    protected $BrokerDao;
    
    function _initialize() {
        
        parent::_initialize();
        //$this->Dao = D("Home/Payb");
        //$this->BrokerDao = D("Home/Broker");
    }
    
    function index(){
        $this->display();
    }
    
    function edit(){
    	$this->display();
    }
}