<?php
namespace Home\Controller;
use Common\Controller\AdminbaseController;

class AdminPerfController extends AdminbaseController {
    
    protected $Dao;
    protected $PerfBrokerDao;
    protected $ProjectDao;
    protected $BrokerDao;
    
    function _initialize() {
    
        parent::_initialize();
        $this->Dao = D("Home/Perf");
        $this->PerfBrokerDao = D("Home/PerfBroker");
        $this->ProjectDao = D("Home/Project");
        $this->BrokerDao = D("Home/Broker");
    }
    
    function index(){
    
        $map = array();
        if(IS_POST) {
            $project = I('post.project');
            if(!empty($project)) {
                $map['pid'] = $project;
            }
            
            $date = I('post.date');
            if(!empty($date)) {
                $map['date'] = $date;
            }
            
            $this->assign('project', $project);
            $this->assign('date', $date);
        }
        
        $count = $this->Dao->where($map)->count();
        $page = $this->page($count, 20);
        
        $perfList = $this->Dao
            ->join("sd_project ON sd_project.id = sd_perf.pid")
            ->join("sd_broker ON sd_broker.id = sd_perf.bid")
            ->field("sd_perf.*, sd_project.name AS pname, sd_broker.name AS bname")
            ->where($map)
            ->order("sd_perf.date DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        
        $this->assign("page", $page->show('Admin'));
        
        $projectList = $this->ProjectDao->order('name')->select();
        $this->assign('projectList', $projectList);
        
        $this->assign('perfList', $perfList);
    
        $this->display();
    }
    
    function add() {
        
        $projectList = $this->ProjectDao->order('name')->select();
        $this->assign('projectList', $projectList);
        
        $brokerList = $this->BrokerDao->where('status=1')->order('name')->select();
        $this->assign('brokerList', $brokerList);
        
        $this->display("AdminPerf:edit");
    }
    
    function edit_post() {
        
        if(IS_POST) {
            if ($this->Dao->create()) {
                if ($this->Dao->add() !== false) {
                    $this->success("添加成功！", U("AdminPerf/index"), true);
                } else {
                    $this->error("添加失败！");
                }
            }
        }
    }
    
    function add_broker() {
        
        $brokerList = $this->BrokerDao->order('name')->select();
        $this->assign('brokerList', $brokerList);
        
        $this->display();
    }
    
    function getBroker() {
        
        $id = I('post.id', 0 , 'intval');
        $pbList = $this->PerfBrokerDao
            ->join("sd_broker ON sd_broker.id = sd_perf_broker.bid")
            ->field("sd_perf_broker.*, sd_broker.name AS bname")
            ->where('sd_perf_broker.pid=' . $id)
            ->select();
        
        echo json_encode($pbList);
    }
    
    function get_calculated_perf() {
        
        $total = I('post.total');
        $bid = I('post.id');
        $broker = $this->BrokerDao
            ->join('sd_rank ON sd_rank.id = sd_broker.rank_id')
            ->field('sd_broker.rank_id, sd_rank.name AS rank_name')
            ->where('sd_broker.id=' . $bid)
            ->find();
        
        $rank_id = $broker['rank_id'];
        $rank_name = $broker['rank_name'];
        $result = array();
        $result['bkg'] = getBrokerageByRank($rank_id, $total);
        $result['rank'] = $rank_name;
        echo json_encode($result);
    }
}