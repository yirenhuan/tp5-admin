<?php
namespace app\admin\logic;

class App extends BaseLogic {
	/*******************************基础配置开始*******************************/

	public $flag_opt = array();
	public $id_alias = "app_id"; //主表与其他表关联的字段
	private $flag_model = ""; //标记模型
	private $slave_model = ""; //从表模型
	private $slave_relation_field = ""; //从表和主表关联字段

	/*******************************基础配置结束*******************************/
	public $opt_status = array(
		1 => '<span style="color:green">上线</span>',
		0 => '<span style="color:#CAA">下线</span>',
	);

	function __construct() {
		$arr = explode("\\", __CLASS__);
		$model = $arr[count($arr) - 1]; //获取当前模型
		$mp["id_alias"] = $this->id_alias;
		$mp["slave_model"] = $this->slave_model;
		$mp["slave_relation_field"] = $this->slave_relation_field;
		$mp["flag_model"] = $this->flag_model;
		$mp["flag_opt"] = $this->flag_opt;
		parent::__construct($model, $mp);
	}

	//获取应用名称和id的键值对
	public function apps() {
		$apps = $this->listBy(null, 'id,title', 100, 'id asc');
		$res = array();
		foreach ($apps as $app) {
			$res[$app['id']] = $app['title'];
		}
		return $res;
	}



}