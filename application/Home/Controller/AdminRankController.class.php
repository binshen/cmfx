<?php
namespace Home\Controller;
use Common\Controller\AdminbaseController;

class AdminRankController extends AdminbaseController {
    
    protected $Dao;
    
    function _initialize() {
        
        parent::_initialize();
        $this->Dao = D("Home/Rank");
    }
    
    function index(){
        
        $rankList = $this->Dao->select();
        $this->assign('rankList', $rankList);
        
        $this->display();
    }
    
    function add() {
        
        $this->display('AdminRank:edit');
    }
    
    function edit() {
        
        $id = I('get.id', 0, 'intval');
        $rank = $this->Dao->getById($id);
        $this->assign($rank);
        
        $this->display();
    }
    
    function edit_post() {
        
        if(empty($_POST['id'])) {
            if ($this->Dao->create()) {
                if ($this->Dao->add()!==false) {
                    $this->success("添加成功！",U("AdminRank/index"));
                } else {
                    $this->error("添加失败！");
                }
            } else {
                $this->error($this->Dao->getError());
            }
        } else {
            if ($this->Dao->create()) {
                if ($this->Dao->save()!==false) {
                    $this->success("修改成功！",U("AdminRank/index"));
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