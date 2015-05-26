<?php
namespace Home\Controller;
use Common\Controller\AdminbaseController;

class AdminPerfController extends AdminbaseController {
    
    protected $Dao;
    protected $ProjectDao;
    protected $BrokerDao;
    protected $TypeDao;
    protected $PayMngDao;
    
    function _initialize() {
    
        parent::_initialize();
        $this->Dao = D("Home/Perf");
        $this->ProjectDao = D("Home/Project");
        $this->BrokerDao = D("Home/Broker");
        $this->TypeDao = D("Home/Type");
        $this->PayMngDao = D("Home/PayMng");
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
                $map["DATE_FORMAT(sd_perf.date,'%Y%m')"] = $date;
            }
            
            $this->assign('project', $project);
            $this->assign('date', $date);
        }
        
        $count = $this->Dao->where($map)->count();
        $page = $this->page($count, 20);
        
        $perfList = $this->Dao
            ->join("sd_type ON sd_type.id = sd_perf.tid")
            ->join("sd_project ON sd_project.id = sd_perf.pid")
            ->join("sd_broker ON sd_broker.id = sd_perf.bid")
            ->field("sd_perf.*, sd_type.name AS tname, sd_project.name AS pname, sd_broker.name AS bname, sd_type.discount, DATE_FORMAT(sd_perf.date,'%Y-%m') AS month")
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
            ->join('sd_broker ON sd_broker.id = sd_perf.bid')
            ->join('sd_rank ON sd_rank.id = sd_broker.rank_id')
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
            if(empty($_POST['id'])) {
                if ($this->Dao->create()) {
                    $id = $this->Dao->add();
                    if ($id !== false) {
                        $this->calculateManagerPerformance($id);
                        
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
                        $this->calculateManagerPerformance($_POST['id']);
                        
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
    
    private function calculateManagerPerformance($id) {
        
        extract($_POST);
        $broker = $this->BrokerDao->field('parent_id')->where('id=' . $bid)->find();
        $parent_id = $broker['parent_id'];
        if($parent_id <= 0) return;
        
        $perf = floatval($agency) + floatval($estimate) + floatval($service) + floatval($others) - floatval($bkg);
        
        $data = array();
        $data['sid'] = $parent_id;
        $data['pid'] = id;
        $payMng = $this->PayMngDao->where($data)->find();        
        if(empty($payMng)) {
            $data['bonus'] = floatval($perf) * 0.3;
            $data['type'] = 1;
            $this->PayMngDao->add($data);
        } else {
            $payMng['bonus'] = floatval($perf) * 0.3;
            $data['type'] = 1;
            $this->PayMngDao->save($payMng);
        }
        
        $broker = $this->BrokerDao->field('parent_id')->where('id=' . $parent_id)->find();
        $parent_id = $broker['parent_id'];
        if($parent_id <= 0) return;
                
        $data['sid'] = $parent_id;
        $payMng = $this->PayMngDao->where($data)->find();
        if(empty($payMng)) {
            $data['bonus'] = floatval($perf) * 0.3 * 0.15;
            $data['type'] = 2;
            $this->PayMngDao->add($data);
        } else {
            $payMng['bonus'] = floatval($perf) * 0.3 * 0.15;
            $data['type'] = 2;
            $this->PayMngDao->save($payMng);
        }
    }
    
    function delete() {
    
        if(isset($_POST['ids'])){
            $ids = implode(",", $_POST['ids']);
            if ($this->Dao->where("id in ($ids)")->delete()) {
                $this->success("删除成功！", U("AdminPerf/index"), true);
            } else {
                $this->error("删除失败！");
            }
        }else{
            if(isset($_GET['id'])){
                $id = intval(I("get.id"));
                if ($this->Dao->delete($id)) {
                    $this->success("删除成功！", U("AdminPerf/index"), true);
                } else {
                    $this->error("删除失败！");
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
        
        $total = I('post.total', 0, 'floatval');
        $bid = I('post.id');
        $date = I('post.date');

        $year = date('Y', strtotime($date));
        $month = date('n', strtotime($date));

        $perfObj = $this->Dao
            ->field('SUM(agency+estimate+service+others) AS total_perf, SUM(bkg) AS total_bkg')
            ->where("bid=" . $bid . " AND YEAR(date)='" . $year . "' AND MONTH(date)='" . $month . "'")
            ->sum('agency+estimate+service+others');
        
        $perfTotal = $perfObj['total_perf'];
        $bkgTotal = $perfObj['total_bkg'];
        
        if($perfTotal == null) $perfTotal = 0;
        if($bkgTotal == null) $bkgTotal = 0;
        
        $broker = $this->BrokerDao
            ->join('sd_rank ON sd_rank.id = sd_broker.rank_id')
            ->field('sd_broker.rank_id, sd_rank.name AS rank_name')
            ->where('sd_broker.id=' . $bid)
            ->find();
        
        $rank_id = $broker['rank_id'];
        $rank_name = $broker['rank_name'];
        $result = array();
        $result['bkg'] = getBrokerageByRank($rank_id, $total+$perfTotal) - $bkgTotal;
        $result['rank'] = $rank_name;
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
        if(empty($total)) {
            $this->error("请输入楼盘成交价");
            return false;
        }
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