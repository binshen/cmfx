<?php
namespace Home\Controller;
use Common\Controller\AdminbaseController;

class AdminBrokerController extends AdminbaseController {
    
    protected $Dao;
    protected $RankDao;
    
    function _initialize() {
        
        parent::_initialize();
        $this->Dao = D("Home/Broker");
        $this->RankDao = D("Home/Rank");
    }
    
    function index(){
    	
    	$map = array();
    	if(IS_POST) {
    		$broker = I('post.broker');
    		if(!empty($broker)) {
    			$map['sd_broker.name'] = array('like','%' . $broker . '%');
    		}
    		$this->assign('broker', $broker);
    	}
    	
    	$count=$this->Dao->where($map)->count();
    	$page = $this->page($count, 20);
    	
        $brokerList = $this->Dao
            ->join('sd_rank ON sd_broker.rank_id = sd_rank.id', 'left')
            ->field('sd_broker.*, sd_rank.name AS rank')
            ->where($map)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('sd_broker.date DESC')
            ->select();
        
        $this->assign('managerList', $this->getManagerList());
        $this->assign('brokerList', $brokerList);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }
    
    function add() {
        
        $rankList = $this->RankDao->select();
        $this->assign('rankList', $rankList);
        $this->assign('managerList', $this->getManagerList());
        
        $this->display('AdminBroker:edit');
    }
    
    function edit() {
        
        $id = I('get.id', 0, 'intval');
        $broker = $this->Dao->getById($id);
        $this->assign($broker);
        
        $rankList = $this->RankDao->select();
        $this->assign('rankList', $rankList);
        
        $this->assign('managerList', $this->getManagerList());
        
        $this->display();
    }
    
    function edit_post() {
    	
    	if(empty($_POST['name'])) {
    		$this->error("请输入姓名");
    		return;
    	}
    	if(empty($_POST['tel'])) {
    		$this->error("请输入联系方式");
    		return;
    	}
    	if(empty($_POST['date'])) {
    		$this->error("请输入入职日期");
    		return;
    	}
    	
    	if(!isset($_POST['flagBox'])) {
    		$_POST['flag'] = 0;
    		unset($_POST['flagBox']);
    	}
        if(empty($_POST['id'])) {
            if ($this->Dao->create()) {
                if ($this->Dao->add()!==false) {
                    $this->success("添加成功！",U("AdminBroker/index"));
                } else {
                    $this->error("添加失败！");
                }
            } else {
                $this->error($this->Dao->getError());
            }
        } else {
            if ($this->Dao->create()) {
                if ($this->Dao->save()!==false) {
                    $this->success("修改成功！",U("AdminBroker/index"));
                } else {
                    $this->error("修改失败！");
                }
            } else {
                $this->error($this->Dao->getError());
            }
        }
    }
    
    function delete() {
        
        if(isset($_POST['ids'])){
            $ids = implode(",", $_POST['ids']);
            if ($this->Dao->where("id in ($ids)")->delete()) {
                $this->success("删除成功！");
            } else {
                $this->error("删除失败！");
            }
        }else{
            if(isset($_GET['id'])){
                $id = intval(I("get.id"));
                if ($this->Dao->delete($id)) {
                    $this->success("删除成功！");
                } else {
                    $this->error("删除失败！");
                }
            }
        }
    }
}