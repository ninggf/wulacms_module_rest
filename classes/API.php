<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace rest\classes;
/**
 * 接口基类.
 *
 * @package rest\classes
 */
abstract class API {
	public    $sessionId = '';
	protected $appKey;
	protected $ver;

	public function __construct($appKey, $ver) {
		$this->appKey = $appKey;
		$this->ver    = $ver;
	}

	/**
	 * @throws \rest\classes\RestException
	 * @throws \rest\classes\UnauthorizedException
	 */
	public function setup() {

	}

	public function tearDown() {

	}

	/**
	 * 返回错误信息.
	 *
	 * @param int|string  $code
	 * @param string|null $message
	 *
	 * @throws \rest\classes\RestException
	 */
	protected final function error($code, $message = null) {
		if (empty($message) && is_string($code)) {
			$msg = explode('@', $code);
			if (count($msg) >= 2) {
				$message = $msg[1];
				$code    = intval($msg[0]);
			} else {
				$message = $code;
				$code    = 500;
			}
		} else if (empty($message)) {
			$message = '内部错误';
		}
		throw new RestException($message, $code);
	}

	/**
	 * 未登录异常.
	 *
	 * @throws \rest\classes\UnauthorizedException
	 */
	protected function unauthorized() {
		throw new UnauthorizedException();
	}
}