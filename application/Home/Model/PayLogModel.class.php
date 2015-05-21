<?php
namespace Home\Model;
use Common\Model\CommonModel;

class PayLogModel extends CommonModel{
    
    protected $tablePrefix = 'sd_';
    
	protected function _before_write(&$data) {
		parent::_before_write($data);
	}
}