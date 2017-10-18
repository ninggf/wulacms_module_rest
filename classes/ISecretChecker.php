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

interface ISecretChecker {
	/**
	 * 验证appKey与appSecret是否是合法的.
	 *
	 * @param string $appKey
	 *
	 * @return null|string
	 */
	public function check($appKey);
}