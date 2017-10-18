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

abstract class API {
	protected $appKey;
	protected $ver;

	public function __construct($appKey, $ver) {
		$this->appKey = $appKey;
		$this->ver    = $ver;
	}

	public function setup() {

	}

	public function tearDown() {

	}

}