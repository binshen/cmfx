<?php
namespace Home\Controller;
use Common\Controller\AdminbaseController;

class AdminPaybController extends AdminbaseController {
    
    protected $Dao;
    
    function _initialize() {
        
        parent::_initialize();
        $this->Daoperf = D("Home/Perf");
        $this->Daol = D("Home/PayLog");
        $this->Daop = D("Home/Project");
    }
    
    function index(){
/*    	$paybList = $this->Dao
    		->join("sd_payb ON sd_paya.id = sd_payb.pay_id","left")
    		->join("sd_project ON sd_paya.pid = sd_project.id","left")
    		->field("sd_paya.*, sum(pay) pay,sum(payd) payd,name")
    		->group("pay_id")
    		->select();
    	$this->assign('paybList', $paybList);
        $this->display('AdminPayb:index');*/
    	
    	$count = $this->Daoperf
	    	->join("sd_project ON sd_perf.pid = sd_project.id","left")
        	->field("1")
	        ->group("pid,date")
	        ->select();
        
    	$page = $this->page(count($count), 20);
    	
        $paybList = $this->Daoperf
        	->join("sd_project ON sd_perf.pid = sd_project.id","left")
        	->field("sd_perf.*, sum(bkg) pay,sum(payd) payd,name,DATE_FORMAT(date,'%Y-%m') date")
        	->order("DATE_FORMAT(date,'%Y-%m') desc")
	        ->group("pid,DATE_FORMAT(date,'%Y-%m')")
	        ->limit($page->firstRow . ',' . $page->listRows)
	        ->select();
        
        $this->assign("page", $page->show('Admin'));

        $this->assign('paybList', $paybList);
        $this->display('AdminPayb:index');
        
        
        
    }
    
    function edit(){
    	$pid = I('get.pid', 0, 'intval');
    	$date = I('get.date');
    	
    	$paya = $this->Daop
	    	->where("id=".$pid)
	    	->field("name")
	    	->find();
    	$paya['date'] = $date;
    	
    	
    	$payb = $this->Daoperf
        	->join("sd_broker ON sd_perf.bid = sd_broker.id","left")
        	->join("sd_rank ON sd_broker.rank_id = sd_rank.id","left")
    		->where('pid='.$pid)
    		->where("DATE_FORMAT(sd_perf.date,'%Y-%m')='".$date."'")
    		->field("sd_perf.*,sd_broker.name bname,tel,sd_rank.name rname")
    		->select();
    	
    	
    	$this->assign('payb', $payb);
    	$this->assign($paya);
    	$this->display();
    }
    
    
    function edit_post() {
    	$id = $_POST['id'];
		$pay = $_POST['pay'];
		foreach($id as $k=>$v){
			if($pay[$k]){

				$log = array(
						'pay_id'=>$id,
						'pay'=>$pay[$k],
						'cdate'=>date('Y-m-d H:i:s',time())
				);
				
				$this->Daoperf->where('id='.$id[$k])->setInc('payd',$pay[$k]);
				$this->Daol->add($log);
				
			}
		}
		
		$this->index();

    }

    
    
}