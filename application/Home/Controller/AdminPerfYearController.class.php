<?phpnamespace Home\Controller;use Common\Controller\AdminbaseController;class AdminPerfYearController extends AdminbaseController {        function _initialize() {        parent::_initialize();        $this->Daol = D("Home/PayLog");    }        function index($year=null) {    	$yearList = array();    	for($i=0; $i<10; $i++) {    		$yearList[] = date("Y",strtotime("-$i year"));    	}    	    	$year = $year?$year:$_POST['year'];		    	if($year){    		$sql = "SELECT						a.bid bid,						c.name bname,						sum(y_perf) y_perf,						sum(y_payd) y_payd,    					IFNULL(b.pay, 0) pay					FROM						sd_perf a					LEFT JOIN (						SELECT							bid,sum(pay) pay						FROM							sd_pay_log						WHERE							type = 4						AND date = '".$year."12'						group by bid					) b ON a.bid = b.bid					left join sd_broker c on a.bid=c.id					WHERE						DATE_FORMAT(a.date, '%Y') =".$year."					group by a.bid";    		$Model = new \Think\Model();    		$perfYear = $Model->query($sql);    		$this->assign('perfYear', $perfYear);    	}    	$this->assign('year', $year);    	$this->assign('yearList', $yearList);        $this->display('AdminPerfYear:index');    }        function edit_post(){    	$bid_arr = $_POST['bid'];    	$pay_arr = $_POST['pay'];    	$year = $_POST['year'];    	$date = $year.'12';    	foreach($bid_arr as $k=>$v){    		if($pay_arr[$k] > 0){    			$pay = $pay_arr[$k];    			$log = array(    					'bid'=>$bid_arr[$k],    					'date'=>$date,    					'pay'=>$pay,    					'created'=>date('Y-m-d H:i:s',time()),    					'type'=>4    			);    			$this->Daol->add($log);    	    		}    	}    	redirect("/Home/AdminPerfYear/index/year/".$year);    }}