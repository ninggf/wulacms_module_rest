<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace rest\api\v1;

use passport\classes\model\OauthSessionTable;
use rest\classes\API;
use rest\models\AppCfgTable;
use wulaphp\app\App;
use wulaphp\cache\Cache;
use wulaphp\io\Request;

/**
 * Class ClientApi
 * @package rest\api\v1
 * @name 客户端
 */
class ClientApi extends API {
	const device = [
		'web'     => '网站',
		'ios'     => '苹果',
		'android' => '安卓',
		'wxapp'   => '小程序',
		'wxgame'  => '小游戏',
		'h5'      => 'H5',
		'pc'      => 'PC'
	];

	/**
	 * 获取客户端惟一编号，客户端取到此编号后应妥善保管，尽量保存到安全地方以保证无论用户重新安装后此编号不变。
	 *
	 * @apiName 获取编号
	 *
	 * @param string $device  (required,sample=ios,android,h5,wxapp,wxgame,pc,web) 设备类型
	 * @param string $ver     (required,sample=1.0.0) 软件版本
	 * @param string $channel (sample=guanfang) 渠道
	 * @param int    $uid     用户ID（如果用户登录）
	 *
	 * @paramo  string id 客户端编号
	 *
	 * @error   403=>错误的设备类型
	 * @error   404=>版本号为空
	 * @error   500=>内部错误
	 *
	 * @return array {
	 *  "id":"adfasdfasdfadsfa"
	 * }
	 * @throws
	 */
	public function get($device, $ver, $channel = '', $uid = 0) {
		if (!isset(self::device[ $device ])) {
			$this->error('403', '错误的设备类型');
		}
		if (empty($ver)) {
			$this->error('404', '版本号为空');
		}
		$id = uniqid() . rand_str();
		try {
			$db   = App::db();
			$time = time();
			$data = [
				'id'          => $id,
				'device'      => $device,
				'channel'     => $channel,
				'ip'          => Request::getIp(),
				'create_time' => $time
			];
			$db->insert($data)->into('{app_client}')->exec();

			unset($data['id'], $data['channel']);
			$data['ver']       = $ver;
			$data['uid']       = intval($uid);
			$data['client_id'] = $id;
			$data['day']       = date('Y-m-d');
			$db->insert($data)->into('{app_client_log}')->exec();
		} catch (\Exception $e) {
			$this->error(500, '内部错误');
		}

		return ['id' => $id];
	}

	/**
	 * 端的活跃统计，客户端APP设计时应尽量保证每天至少要调用一次该接口。
	 *
	 * @apiName 日活统计
	 *
	 * @param string $id     (required) 客户端ID（通过rest.client.get接口获取的）
	 * @param string $device (required,sample=ios,android,h5,wxapp,wxgame,pc,web) 设备
	 * @param string $ver    (required,sample=1.0.0) 版本
	 * @param int    $uid    用户ID（如果用户登录）
	 *
	 * @paramo  int code 返回码，1成功；0失败.
	 *
	 * @return array {
	 *  "code":1
	 * }
	 * @throws
	 */
	public function log($id, $device, $ver, $uid = 0) {
		if (!isset(self::device[ $device ])) {
			$this->error('403', '错误的设备类型');
		}
		if (empty($ver)) {
			$this->error('404', '版本号为空');
		}
		try {
			$data              = [
				'device'      => $device,
				'ip'          => Request::getIp(),
				'create_time' => time()
			];
			$data['ver']       = $ver;
			$data['uid']       = intval($uid);
			$data['client_id'] = $id;
			$data['day']       = date('Y-m-d');

			$db = App::db();
			$db->insert($data)->into('{app_client_log}')->exec();
		} catch (\Exception $e) {
			return ['code' => 0];
		}

		return ['code' => 1];
	}

	/**
	 * 通过此接口可以获取以下信息:
	 *
	 * 1. 云控配置信息，如果无云控配置信息则`cfg`为`null`。
	 * 2. 新版本信息, 如果无可用新版本信息则`update`为`null`。
	 *
	 * __后端使用到的hook:__
	 *
	 * 1. `rest\onGetClientStatus($cfg)`通过此勾子添加全局配置
	 * 2. `rest\canUpdate($rst,$channel,$uid)`通过此勾子实现灰度升级
	 *
	 * @apiName 状态检测
	 *
	 * @param int    $vercode (required,sample=10) 版本号
	 * @param string $channel (sample=guanfang) 渠道
	 * @param string $token   登录TOKEN
	 *
	 * @error   500=> 无法连接数据库
	 *
	 * @paramo  object cfg 配置项，未加载到配置时为`null`
	 * @paramo  mixed .`...` 具体配置项
	 * @paramo  object update 升级信息，无升级信息时为`null`
	 * @paramo  string .version 新版本名称
	 * @paramo  string .desc 发行日志
	 * @paramo  int .force 1强制升级;0不强制
	 * @paramo  string .size 升级包大小
	 * @paramo  string .url 软件包下载地址
	 *
	 * @return array {
	 *  "cfg":{
	 *      "cfg1":true,
	 *      "cfg2":"你好啊"
	 *  },
	 *  "update":{
	 *      "version":"v1.0.0",
	 *      "desc":"修复了一些BUG",
	 *      "force":1,
	 *      "size":"2M",
	 *      "url":"/rest/downlad/adsfasdf/adsf"
	 *  }
	 * }
	 * @throws
	 */
	public function status($vercode, $channel = '', $token = '') {
		try {
			$vercode = intval($vercode);
			$table   = new AppCfgTable();
			$db      = $table->db();
			$cache   = Cache::getCache();
			//加载配置
			$where['AV.appkey'] = $this->appKey;
			$where['vercode']   = $vercode;
			$ckey               = 'client@' . md5($this->appKey . $vercode . $token . $channel);
			$cfg                = $cache->get($ckey);
			$device             = '';
			$info               = false;
			if ($cfg === null) {
				$rst = $db->select('cfgid,platform')->from('{app_version} AS AV')->join('{rest_app} AS RA', 'AV.appkey = RA.appkey')->where($where)->desc('AV.id')->limit(0, 1)->get();
				if ($rst) {
					$cfg    = $table->loadConfig($rst['cfgid'] ? $rst['cfgid'] : 1);
					$cfg    = $cfg['options'] ? $cfg['options'] : [];
					$device = $rst['platform'];
				} else {
					$cfg = [];
				}
			}
			if ($token) {
				$info = (new OauthSessionTable())->getInfo($token);
			}

			$cfg = apply_filter('rest\onGetClientStatus', $cfg, $device, $channel, $info);
			$cache->add($ckey, $cfg, 300);

			$data = [
				'cfg'    => $cfg ? $cfg : null,
				'update' => null
			];

			//升级检测
			$uw = [
				'appkey'    => $this->appKey,
				'vercode >' => $vercode
			];

			$sql = $db->select('version,desc,file,update_type,size')->from('{app_version}');
			$pkg = $sql->where($uw)->desc('vercode')->limit(0, 1)->get();

			if ($pkg && $pkg['file'] && apply_filter('rest\canUpdate', true, $pkg, $device, $channel, $info)) {
				$url            = preg_match('#^(ht|f)tps?://.+$#i', $pkg['file']) ? $pkg['file'] : App::url('rest/download/' . $this->appKey . ($channel ? '/' . $channel : ''));
				$data['update'] = [
					'version' => $pkg['version'],
					'desc'    => $pkg['desc'],
					'force'   => $pkg['update_type'],
					'size'    => readable_size($pkg['size']),
					'url'     => $url
				];
			}

			return $data;
		} catch (\Exception $e) {
			$this->error(500, '内部错误');
		}

		return ['update' => null, 'cfg' => null];
	}

	/**
	 * 检测设备
	 *
	 * @param string $device
	 *
	 * @return bool
	 */
	public static function checkDevice($device) {
		return isset(self::device[ $device ]);
	}

	/**
	 * 客户端是否有效.
	 *
	 * @param string $cid
	 *
	 * @return bool
	 */
	public static function checkClient($cid) {
		if (empty($cid)) {
			return false;
		}
		try {
			$db = App::db();

			return $db->select('id')->from('{app_client}')->where(['id' => $cid])->exist('id');
		} catch (\Exception $e) {

		}

		return false;
	}
}