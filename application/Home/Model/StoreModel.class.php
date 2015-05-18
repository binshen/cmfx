<?php
namespace Home\Model;
use Common\Model\CommonModel;

class StoreModel extends CommonModel{
    
    protected $tablePrefix = 'sd_';
    
	//自动验证
	protected $_validate = array(
		//array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
		array('name', 'require', '店东名称不能为空！', 1, 'regex', 3),
		array('loss', 'require', '店损不能为空！', 1, 'regex', 3),
		array('manager_id', 'require', '经理不能为空！', 1, 'regex', 3),
	);
	
	protected function _before_write(&$data) {
		parent::_before_write($data);
	}
}