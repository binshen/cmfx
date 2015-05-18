<?php
namespace Home\Model;
use Common\Model\CommonModel;

class ProjectModel extends CommonModel{
    
    protected $tablePrefix = 'sd_';
    
	//自动验证
	protected $_validate = array(
		//array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
		array('name', 'require', '楼盘名称不能为空！', 1, 'regex', 3),
	);
	
}