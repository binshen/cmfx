<?php
/**
 * 会员注册
 */
namespace User\Controller;
use Common\Controller\HomeBaseController;
class LoginController extends HomeBaseController {
	
	function index(){
		$this->display(":login");
	}
	
	function active(){
		$this->check_login();
		$this->display(":active");
	}
	
	function doactive(){
		$this->check_login();
		$this->_send_to_active();
		$this->success('激活邮件发送成功，激活请重新登录！',U("user/index/logout"));
	}
	
	function forgot_password(){
		$this->display(":forgot_password");
	}
	
	
	
	function password_reset(){
		$this->display(":password_reset");
	}
	
	function dopassword_reset(){
		if(IS_POST){
			if(!sp_check_verify_code()){
				$this->error("验证码错误！");
			}else{
				$users_model=M("Users");
				$rules = array(
						//array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
						array('password', 'require', '密码不能为空！', 1 ),
						array('repassword', 'require', '重复密码不能为空！', 1 ),
						array('repassword','password','确认密码不正确',0,'confirm'),
						array('hash', 'require', '重复密码激活码不能空！', 1 ),
				);
				if($users_model->validate($rules)->create()===false){
					$this->error($users_model->getError());
				}else{
					$password=sp_password(I("post.password"));
					$hash=I("post.hash");
					$result=$users_model->where(array("user_activation_key"=>$hash))->save(array("user_pass"=>$password,"user_activation_key"=>""));
					if($result){
						$this->success("密码重置成功，请登录！",U("user/login/index"));
					}else {
						$this->error("密码重置失败，重置码无效！");
					}
					
				}
				
			}
		}
	}
	
	
    //登录验证
    function dologin(){
    	extract($_POST);
    	if(!$username || !$password){
    		$this->error("用户名或者密码不能为空");
    	}
    	
    	$where['tel']=$username;
    	$where['password']=sha1($password);
    	$users_model=D('Home/Broker');
    	$result = $users_model->where($where)->find();
    	
    	if($result != null)
    	{
    		$_SESSION["user"]=$result;
    		$redirect=empty($_SESSION['login_http_referer'])?__ROOT__."/":$_SESSION['login_http_referer'];
    		$this->success("登录验证成功！", $redirect);
    	}else{
    		$this->error("用户名或密码错误！");
    	}
    	 
    }
	
}