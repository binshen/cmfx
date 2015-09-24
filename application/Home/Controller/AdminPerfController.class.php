<?php
namespace Home\Controller;
use Common\Controller\AdminbaseController;

class AdminPerfController extends AdminbaseController {
    
    protected $Dao;
    protected $ProjectDao;
    protected $BrokerDao;
    protected $TypeDao;
    protected $PayMngDao;
    protected $QuarterPerfDao;
    
    function _initialize() {
    
        parent::_initialize();
        $this->Dao = D("Home/Perf");
        $this->ProjectDao = D("Home/Project");
        $this->BrokerDao = D("Home/Broker");
        $this->TypeDao = D("Home/Type");
        $this->PayMngDao = D("Home/PayMng");
        $this->QuarterPerfDao =D("Home/QuarterPerf");
    }
    
    function index(){
    
        $map = array();
        if(IS_POST) {
        	$broker = I('post.broker');
        	$project = I('post.project');
        	$date = I('post.date');
        	
            session('_broker', $broker);
            session('_project', $project);
            session('_date', $date);
        } else {
        	$broker = session('_broker');
        	$project = session('_project');
        	$date = session('_date');
        }
        
        if(!empty($broker)) {
        	$map['bid'] = $broker;
        }
        
        if(!empty($project)) {
        	$map['pid'] = $project;
        }
        
        if(!empty($date)) {
        	$map["DATE_FORMAT(sd_perf.date,'%Y%m')"] = $date;
        }
        
        $this->assign('broker', $broker);
        $this->assign('project', $project);
        $this->assign('date', $date);
        
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
    
    function add() {
        
        $typeList = $this->TypeDao->select();
        $this->assign('typeList', $typeList);
        
        $projectList = $this->ProjectDao->order('name')->select();
        $this->assign('projectList', $projectList);
        
        $brokerList = $this->BrokerDao->where('status=1')->order('name')->select();
        $this->assign('brokerList', $brokerList);
        
        $this->display("AdminPerf:edit");
    }
    
    function edit() {
        
        $id = I('get.id', 0, 'intval');
        $perf = $this->Dao
            ->join('sd_rank ON sd_rank.id = sd_perf.rank_id', 'left')
            ->field('sd_perf.*, sd_rank.name AS rank_name')
            ->where('sd_perf.id=' . $id)
            ->find();
        $this->assign($perf);
        
        $this->add();
    }
    
    function edit_post() {
        
        if(IS_POST) {
            if(!$this->valid()) {
                return;
            }
            $perf = floatval($_POST['agency']) + floatval($_POST['estimate']) + floatval($_POST['service']) + floatval($_POST['others']);
            $discount = $this->TypeDao->getFieldById($_POST['tid'], 'discount');
            $_POST['perf'] = empty($discount) ? $perf : $perf * $discount;
            
            $rank_id = (int)$_POST['rank_id'];
            if($rank_id >= 3 && $rank_id <= 5) {
                $_POST['q_perf'] = $_POST['perf'] * 0.05 * ($rank_id - 2);
                $_POST['y_perf'] = $_POST['perf'] * 0.05;
            }
            if(empty($_POST['id'])) {
                if ($this->Dao->create()) {
                    if ($this->Dao->add() !== false) {
                        $this->calculateManagerPerf($this->Dao->getLastInsID());
                        
                        $this->success("添加成功！", U("AdminPerf/index"), true);
                    } else {
                        $this->error("添加失败！");
                    }
                } else {
                    $this->error($this->Dao->getError());
                }
            } else {
                if ($this->Dao->create()) {
                    if ($this->Dao->save()!==false) {
                        $this->calculateManagerPerf($_POST['id']);
                        
                        $this->success("修改成功！", U("AdminPerf/index"), true);
                    } else {
                        $this->error("修改失败！");
                    }
                } else {
                    $this->error($this->Dao->getError());
                }
            }
        }
    }
    
    private function calculateManagerPerf($perf_id) {
        
        extract($_POST);
        
        $year = date('Y', strtotime($date));
        $quarter = ceil(date('m', strtotime($date)) / 3);
        
        if($rank_id == 6) {
            $parent_id = $bid;
        }
        if($parent_id <= 0) return;
        
        $perf = floatval($perf) - floatval($bkg);
        
        $data = array();
        $data['sid'] = $parent_id;
        $data['pid'] = $perf_id;
        $payMng = $this->PayMngDao->where($data)->find();        
        if(empty($payMng)) {
            $data['bonus'] = floatval($perf) * 0.3;
            $data['type'] = 1;
            $this->PayMngDao->add($data);
        }
        
        if($parent_parent_id < 0) return;
        
        $data['sid'] = $parent_parent_id;
        $payMng = $this->PayMngDao->where($data)->find();
        if(empty($payMng)) {
            $data['type'] = 2;
            $data['bonus'] = 0;
            $this->PayMngDao->add($data);
        }
    }
    
    function delete() {
        if(isset($_GET['id'])){
            $id = intval(I("get.id"));
            $perfObj = $this->Dao->getById($id);
            if ($this->Dao->delete($id)) {
                $this->PayMngDao->where('pid=' . $id)->delete();
                $this->updateBrokerBkg($perfObj['bid'], $perfObj['date']);
                
                $this->success("删除成功！", U("AdminPerf/index"), true);
            } else {
                $this->error("删除失败！");
            }
        }
    }
    
    private function updateBrokerBkg($bid, $date) {

        $rank_id = $this->BrokerDao->getFieldById($bid, 'rank_id');

        $year = date('Y', strtotime($date));
        $month = date('n', strtotime($date));

        $perfList = $this->Dao
            ->where("bid=" . $bid . " AND YEAR(date)='" . $year . "' AND MONTH(date)='" . $month . "'")
            ->select();
        $total_perf = 0;
        $total_bkg = 0;
        foreach ($perfList as $p) {
            $total_perf += floatval($p['perf']);
            $p['bkg'] = getBrokerageByRank($rank_id, $total_perf) - $total_bkg;
            $this->Dao->save($p);
            
            $total_bkg += $p['bkg'];
        }
    }
    
    function get_calculated_perf() {
        
        $total = I('post.total', 0, 'floatval');
        $bid = I('post.id');
        $date = I('post.date');
        
        $tid = I('post.tid', 0, 'intval');
        $discount = $this->TypeDao->getFieldById($tid, 'discount');
        
        if(!empty($discount)) {
            $total = $total * $discount;
        }
        
        $year = date('Y', strtotime($date));
        $month = date('n', strtotime($date));

        $perfObj = $this->Dao
            ->field('SUM(perf) AS total_perf, SUM(bkg) AS total_bkg')
            ->where("bid=" . $bid . " AND YEAR(date)='" . $year . "' AND MONTH(date)='" . $month . "'")
            ->find();
        
        $perfTotal = $perfObj['total_perf'];
        $bkgTotal = $perfObj['total_bkg'];
        
        if($perfTotal == null) $perfTotal = 0;
        if($bkgTotal == null) $bkgTotal = 0;
        
        $broker = $this->BrokerDao
            ->join('sd_rank ON sd_rank.id = sd_broker.rank_id', 'left')
            ->join('sd_rank AS sd_rank2 ON sd_rank2.id = sd_broker.flag', 'left')
            ->field('sd_broker.rank_id, sd_rank.name AS rank_name, sd_broker.parent_id, sd_broker.flag AS rank_id2, sd_rank2.name AS rank_name2 ')
            ->where('sd_broker.id=' . $bid)
            ->find();
        if(!empty($broker['rank_id2'])) {
        	$rank_id = $broker['rank_id2'];
        	$rank_name = $broker['rank_name2'] . "(储备)";
        } else {
        	$rank_id = $broker['rank_id'];
        	$rank_name = $broker['rank_name'];
        }
        $parent_id = $broker['parent_id'];
        if($rank_id == 6) {
        	$parent_parent_id = -1;
        } else {
        	$parent_parent_id = $this->BrokerDao->getFieldById($parent_id, 'parent_id');
        }
        
        $result = array();
        $result['bkg'] = getBrokerageByRank($rank_id, $total+$perfTotal) - $bkgTotal;
        $result['rank'] = $rank_name;
        $result['rank_id'] = $rank_id;
        $result['parent_id'] = $parent_id;
        $result['parent_parent_id'] = $parent_parent_id;
        echo json_encode($result);
    }
    
    private function valid() {
        extract($_POST);
        if(empty($pid)) {
            $this->error("请选择楼盘");
            return false;
        }
        if(empty($date)) {
            $this->error("请输入日期");
            return false;
        }
        if(!checkDateIsValid($date)) {
            $this->error("日期格式不正确");
            return false;
        }
//         if(empty($total)) {
//             $this->error("请输入楼盘成交价");
//             return false;
//         }
        if(!is_numeric($total)) {
            $this->error("楼盘成交价格式不正确");
            return false;
        }
        if(empty($num)) {
            $this->error("请输入楼盘房号");
            return false;
        }
        if(empty($agency)) {
            $this->error("请输入中介费");
            return false;
        }
        if(!is_numeric($agency)) {
            $this->error("中介费格式不正确");
            return false;
        }
        if(!is_numeric($estimate)) {
            $this->error("评估费格式不正确");
            return false;
        }
        if(!is_numeric($service)) {
            $this->error("贷款服务费格式不正确");
            return false;
        }
        if(!is_numeric($others)) {
            $this->error("其他费用格式不正确");
            return false;
        }
        if(empty($bid)) {
            $this->error("请选择业务员");
            return false;
        }
        if(empty($bkg)) {
            $this->error("请输入业务员佣金");
            return false;
        }
        if(!is_numeric($bkg)) {
            $this->error("业务员佣金格式不正确");
            return false;
        }
        return true;
    }
}