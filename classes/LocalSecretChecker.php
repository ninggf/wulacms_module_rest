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

use rest\models\RestApp;
use wulaphp\cache\RtCache;

/**
 * 本地检测.
 *
 * @package rest\classes
 */
class LocalSecretChecker implements ISecretChecker {
	public function check($appKey) {
		$key = 'rest@' . $appKey;
		$sec = RtCache::get($key);
		if ($sec) {
			return $sec == '404' ? false : $sec;
		}
		$table = new RestApp();
		$app   = $table->get(['appkey' => $appKey], 'appsecret');
		if ($app['appsecret']) {
			RtCache::add($key, $app['appsecret']);

			return $app['appsecret'];
		} else {
			RtCache::add($key, '404');

			return false;
		}
	}
}