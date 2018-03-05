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

use rest\classes\API;
use wulaphp\app\App;
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
	 * @apiName 获取编号
	 *
	 * @param string $device  (required,sample=ios,android,h5,wxapp,wxgame,pc,web) 设备类型
	 * @param string $ver     (required,sample=1.0.0) 软件版本
	 * @param string $channel (sample=guanfang) 渠道
	 * @param int    $uid     用户ID（如果用户登录）
	 *
	 * @error   403=>错误的设备类型
	 * @error   404=>版本号为空
	 * @error   500=>内部错误
	 *
	 * @return array {
	 *  "id":"string|客户端编号"
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

			unset($data['id']);
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
	 * @apiName 日活统计
	 *
	 * @param string $id     (required) 客户端ID（通过rest.client.get接口获取的）
	 * @param string $device (required,sample=ios,android,h5,wxapp,wxgame,pc,web) 设备
	 * @param string $ver    (required,sample=1.0.0) 版本
	 * @param int    $uid    用户ID（如果用户登录）
	 *
	 * @return array {
	 *  "code":"int|0 or 1"
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
}