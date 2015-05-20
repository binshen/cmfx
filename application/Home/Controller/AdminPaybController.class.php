<?php
namespace Home\Controller;
use Common\Controller\AdminbaseController;

class AdminPaybController extends AdminbaseController {
    
    protected $Dao;
    
    function _initialize() {
        
        parent::_initialize();
        $this->Dao = D("Home/Paya");
        $this->Daob = D("Home/Payb");
        $this->Daol = D("Home/PayLog");
    }
    
    function index(){
    	$paybList = $this->Dao
    		->join("sd_payb ON sd_paya.id = sd_payb.pay_id","left")
    		->join("sd_project ON sd_paya.pid = sd_project.id","left")
    		->field("sd_paya.*, sum(pay) pay,sum(payd) payd,name")
    		->group("pay_id")
    		->select();
    	$this->assign('paybList', $paybList);
        $this->display('AdminPayb:index');
    }
    
    function edit(){
    	$id = I('get.id', 0, 'intval');
    	
    	$paya = $this->Dao
	    	->join("sd_project ON sd_paya.pid = sd_project.id","left")
	    	->where("sd_paya.id=".$id)
	    	->field("sd_paya.*,name")
	    	->find();
    	
    	$payb = $this->Daob
    		->join("sd_broker ON sd_payb.bid = sd_broker.id","left")
    		->join("sd_rank ON sd_broker.rank_id = sd_rank.id","left")
    		->where('pay_id='.$id)
    		->field("sd_payb.*,sd_broker.name bname,tel,sd_rank.name rname")
    		->select();
    	$this->assign('payb', $payb);
    	$this->assign($paya);
    	$this->display();
    }
    
    
    function edit_post() {
    	$id = $_POST['id'];
		$bid = $_POST['bid'];
		$pay = $_POST['pay'];
		
		foreach($bid as $k=>$v){
			if($pay[$k]){
				$map =array(
						'pay_id'=>$id,
						'bid'=>$v
				
				);
				$log = array(
						'bid'=>$v,
						'pay_id'=>$id,
						'pay'=>$pay[$k],
						'cdate'=>date('Y-m-d H:i:s',time())
				);
				
				$this->Daob->where($map)->setInc('payd',$pay[$k]);
				$this->Daol->add($log);
				
			}
		}
		
		$res = $this->Daob
		->where('pay_id='.$id)
		->field(" sum(pay) pay,sum(payd) payd")
		->group("pay_id")
		->find();
		
		if($res['payd'] >= $res['pay']){
			$this->Daob->where('id='.$id)->data(array('status'=>2))->save();
		}
		
		
		//$this->success("保存成功！",U("AdminPayb/index"));
		$this->index();

    }
    
    
    
}