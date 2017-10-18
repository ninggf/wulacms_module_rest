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
	 * @return array {
	 *      "session":"会话ID",
	 *      "expire":"会话多久后过期，单位秒"
	 * }
	 */
	public function start() {
		$session          = new Session();
		$sid              = $session->start();
		$_SESSION['pang'] = 1;

		return ['session' => $sid, 'expire' => @ini_get('session.gc_maxlifetime')];
	}

	/**
	 * 检测会话是否过期.
	 * @apiName Ping
	 *
	 * @session
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
	 * @return array {
	 *      "status":1
	 * }
	 */
	public function destroy() {
		@session_destroy();

		return ['status' => 1];
	}
}