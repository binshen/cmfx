<?php
namespace Home\Controller;
use Common\Controller\AdminbaseController;

class AdminRankController extends AdminbaseController {
    
    protected $rank_model;
    
    function _initialize() {
        
        parent::_initialize();
        $this->rank_model = D("Home/Rank");
    }
    
    function index(){
        
        $rank = $this->rank_model->select();
        dump($rank);
        
        $this->display();
    }
}