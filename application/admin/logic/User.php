<?php
namespace app\admin\logic;
use wxapp\Wxapp;

class User extends BaseLogic {
	/*******************************基础配置开始*******************************/

	public $flag_opt = array();
	public $id_alias = "user_id"; //主表与其他表关联的字段
	private $flag_model = ""; //标记模型
	private $slave_model = ""; //从表模型
	private $slave_relation_field = ""; //从表和主表关联字段

	/*******************************基础配置结束*******************************/

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

	/**
	 * 根据open_id获取用户信息
	 *
	 * @access public
	 * @param $open_id int 微信open_id
	 * @return array 用户信息
	 */
	public function infoByOpenid($open_id) {
		$where["open_id"] = $open_id;
		return parent::infoBy($where);
	}

	/**
	 * 根据用户名获取用户信息
	 *
	 * @access public
	 * @param $name string 用户名
	 * @return array 用户信息
	 */
	public function infoByName($name) {
		$where["name"] = $name;
		return parent::infoBy($where);
	}

	/**
	 * 根据用户名获取用户信息
	 *
	 * @access public
	 * @param $name string 用户名
	 * @return array 用户信息
	 */
	public function infoByUnionId($union_id) {
		$where["union_id"] = $union_id;
		return parent::infoBy($where);
	}

	/**
	 * 修改用户余额（平台币）
	 *
	 * @access public
	 * @param $id int 用户id
	 * @param $old_money decimal 用户账户上原来拥有的金额
	 * @param $now_money decimal 用户帐户要改成的金额
	 * @return array 用户信息
	 */
	public function updMoney($id, $old_money, $now_money) {
		$data["money"] = $now_money;
		$where["id"] = $id;
		$where["money"] = $old_money;
		return parent::updBy($data, $where);
	}

	/**
	 * 修改用户余额（平台币）
	 *
	 * @access public
	 * @param $id int 用户id
	 * @param $old_money decimal 用户账户上原来拥有的金额
	 * @param $now_money decimal 用户帐户要改成的金额
	 * @return array 用户信息
	 */
	public function updPoint($id, $old_point, $now_point) {
		$data["point"] = $old_point;
		$where["id"] = $id;
		$where["point"] = $now_point;
		return parent::updBy($data, $where);
	}

	/**
	 * 用户注册
	 *
	 * @access public
	 * @param $data array 注册时提交的数据
	 * @return array 用户信息
	 */
	public function reg($data) {
		$data["reg_time"] = time();
		$data["reg_date"] = date("Ymd", time());
		$data['reg_ip'] = \think\Request::instance()->ip();
		$data['nip'] = ip2long($data['reg_ip']);
		$res = parent::add($data);
		if ($res) {
			$data2 = [];
			$data2['user_id'] = $res['id'];
			$data2['app_id'] = $data['app_id'];
			$data2['open_id'] = $data['open_id'];
			$data2['add_time'] = $data2['last_login_time'] = $data["reg_time"];
			logic("user_app")->edit($data2);
		}
		return $res;
	}

	/**
	 * 用户登录
	 *
	 * @access public
	 * @param $data array 登录时提交的数据
	 * @return array 成功返回用户信息，失败返回false
	 */
	public function login($data, $isChkPwd = true) {
		$info = $this->infoBy(['union_id' => $data['union_id']]);
		if (!$info) {
			$this->error = "union_id不存在";
			return false;
		}
		if ($info["status"] != 1) {
			$this->error = "您的账号被封，请联系客服！";
			return false;
		}
		$curr_pwd = strtolower(auth_code($pwd, "ENCODE", config('USER_AUTH_KEY')));

		if (strtolower($info["pwd"]) == $curr_pwd || !$isChkPwd) {
			//添加登录日志
			$data["user_id"] = $info["id"];
			$data["agent_id"] = $info["agent_id"];
			$data["agent_pid"] = $info["agent_pid"];
			$data["ip"] = \think\Request::instance()->ip();
			$data["nip"] = ip2long($data['ip']);
			$data["add_time"] = time();
			$data["add_date"] = date("Ymd", time());
			$res = logic("user_login_log")->add($data);
			logic("user_app")->edit($data);
			return $res;
		} else {
			$this->error = "密码不正确！";
			return false;
		}
		return false;
	}

	/**
	 * 根据code获取用户微信数据
	 * @Author   slpi1
	 * @Email    365625906@gmail.com
	 * @DateTime 2018-03-06T10:37:35+0800
	 * @param    string                   $code 授权码
	 * @return   string
	 */
	public function getWeixinData($code, $encryptedData, $iv, $appid, $secret) {
		// $wxapp = new Wxapp(config("myconfig.app_id"), config("myconfig.app_secret"));
		$wxapp = new Wxapp($appid, $secret);
		$res = $wxapp->jscode2Session($code);
		if (is_array($res) && !empty($res)) 
		{
			if ($encryptedData && $iv) 
			{
				$session_key = $res['session_key'];
				$content = $wxapp->decryptData($session_key, $iv, $encryptedData);
				if ($content) 
				{
					$arr = json_decode($content, true);
					$arr['session_key'] = $session_key;
					return $arr;
				}
			} 
			else 
			{
				return ['openId' => $res['openid']];
			}
		}
		return false;
	}

	/**
	 * 初始化用户（如果用户存在，返回用户信息，如果用户不存在，则注册并返回用户信息）
	 *
	 * @access public
	 * @param $data array 用户数据
	 * @return array 用户信息
	 */
	public function wxLoginOrReg($data) {
		$info = $this->infoByUnionId($data["union_id"]);
		if (!$info) {
			$info = $this->reg($data);
			if (!$info) {
				$this->error = "注册失败";
				return false;
			}
		} 
		else {
			$this->login($data, false); //登录并且不验证密码
		}
		return $info;
	}

}