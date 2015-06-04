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
        $this->Daob = D("Home/Broker");
        $this->Daopay = D("Home/PayMng");
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
    			$map["DATE_FORMAT(date,'%Y%m')"] = $date;
    		}
    	
    		$this->assign('project', $project);
    		$this->assign('date', $date);
    	}
    	
    	$count = $this->Daoperf
	    	->join("sd_project ON sd_perf.pid = sd_project.id","left")
        	->field("1")
	        ->group("pid,date")
	        ->where($map)
	        ->select();
        
    	$page = $this->page(count($count), 20);
    	
        $paybList = $this->Daoperf
        	->join("sd_project ON sd_perf.pid = sd_project.id","left")
        	->field("sd_perf.*, sum(bkg) pay,sum(payd) payd,name,DATE_FORMAT(date,'%Y-%m') date")
        	->where($map)
        	->order("DATE_FORMAT(date,'%Y-%m') desc")
	        ->group("pid,DATE_FORMAT(date,'%Y-%m')")
	        ->limit($page->firstRow . ',' . $page->listRows)
	        ->select();
        
        $this->assign("page", $page->show('Admin'));

        $projectList = $this->Daop->order('name')->select();
        $this->assign('projectList', $projectList);
        
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
    	
    	$Model = new \Think\Model();
    	 
/*    	$sql = "select 
				b.`name` bname, b.rank_id, a.bkg, a.payd, a.agency, a.id,sd_rank.name rname,a.num,a.perf,a.bid,b.parent_id,
				(
				select 
				sum(sd_perf.perf)
				from sd_perf 
				join sd_broker on sd_perf.bid = sd_broker.id
				where DATE_FORMAT(sd_perf.date,'%Y-%m') = '".$date."' AND sd_broker.parent_id = b.parent_id and sd_perf.pid = ".$pid."
				) as mn,
				(
					select sum(sd_pay_mng.bonus) from sd_pay_mng 
					join sd_perf on sd_pay_mng.pid = sd_perf.id
					where DATE_FORMAT(sd_perf.date,'%Y-%m') = '".$date."' and (sd_pay_mng.sid = b.parent_id or sd_pay_mng.sid=b.id) and sd_perf.pid = ".$pid."
				) as t
				from sd_perf a
				join sd_broker b on a.bid = b.id
				join sd_rank on b.rank_id = sd_rank.id
				where DATE_FORMAT(a.date,'%Y-%m') = '".$date."' and a.pid = ".$pid;*/
    	
    	$sql = "select c.`name` bname, b.bkg, b.payd, b.agency, b.id,d.name rname,b.num,b.perf,b.bid,c.parent_id,a.bonus,b.q_perf,b.y_perf
    			from sd_pay_mng a 
    			left join sd_perf b on a.pid=b.id 
    			left join sd_broker c on b.bid = c.id
    			left join sd_rank d on c.rank_id = d.id
    			where DATE_FORMAT(b.date,'%Y-%m')='".$date."' and a.type=1 and b.pid = ".$pid;
    	
    	$payb = $Model->query($sql);
    	$this->assign('payb', $payb);
    	$this->assign($paya);
    	$this->display();
    }
    
    function edit_post() {
    	$id = $_POST['id'];
		$pay_arr = $_POST['pay'];
		$pay_all_arr = $_POST['pay_all'];
		$bid_arr = $_POST['bid'];
		$date = $_POST['date_a'];
		$payd_arr = $_POST['payd'];
		$bonus_arr = $_POST['bonus'];
		$q_perf_arr = $_POST['q_perf'];
		$y_perf_arr = $_POST['y_perf'];
		
		
		foreach($id as $k=>$v){
			if($pay_arr[$k] > 0){
				$pay = $pay_arr[$k];
				$pay_all = $pay_all_arr[$k];
				$payd = $payd_arr[$k];
				$bonus = $bonus_arr[$k];
				$q_perf = $q_perf_arr[$k];
				$y_perf = $y_perf_arr[$k];
				
				$this->Daopay->where('pid='.$v)->save(array('pay'=>($payd+$pay)/$pay_all*$bonus));
				$this->Daoperf->where('id='.$v)->save(array('q_payd'=>($payd+$pay)/$pay_all*$q_perf,'y_payd'=>($payd+$pay)/$pay_all*$y_perf));
				$this->Daoperf->where('id='.$v)->setInc('payd',$pay);
				$log = array(
						'pid'=>$v,
						'bid'=>$bid_arr[$k],
						'date'=>substr($date,0,4).substr($date,5,2),	
						'pay'=>$pay,
						'created'=>date('Y-m-d H:i:s',time())
				);
				$this->Daol->add($log);
				
			}
		}
		
		$this->index();

    }

    
    
}