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
use wulaphp\conf\ConfigurationLoader;
use wulaphp\io\Session;

/**
 * Class SessionApi
 * @package rest\api\v1
 * @name 会话管理
 */
class SessionApi extends API {
	/**
	 * 启动一个新的会话并返回会话ID。
	 *
	 * @apiName 启动会话
	 *
	 * @paramo  string session 会话ID
	 * @paramo  int expire 过期时间，单位秒
	 *
	 * @return array {
	 *      "session":"phadfadkewqea12ad",
	 *      "expire":120
	 * }
	 */
	public function start() {
		$session          = new Session();
		$sid              = $session->start();
		$_SESSION['pang'] = 1;
		$cfg              = ConfigurationLoader::loadFromFile('rest');
		$expire           = $cfg->geti('expire', 300);

		return ['session' => $sid, 'expire' => $expire];
	}

	/**
	 * 检测会话是否过期.
	 *
	 * @apiName 过期检测
	 *
	 * @session
	 *
	 * @paramo  int pang 是否过期，1未过期；0过期
	 *
	 * @return array {
	 *      "pang":1
	 * }
	 */
	public function ping() {
		$pang = sess_get('pang', 0);

		return ['pang' => $pang];
	}

	/**
	 * 销毁通过`启动会话`接口开启的会话(__此接口总是能销毁会话__).
	 *
	 * @apiName 销毁会话
	 * @session
	 *
	 * @paramo  int status 始终为1
	 *
	 * @return array {
	 *      "status":1
	 * }
	 */
	public function destroy() {
		@session_destroy();

		return ['status' => 1];
	}
}