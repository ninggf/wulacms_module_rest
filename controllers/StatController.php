<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace rest\controllers;

use backend\classes\IFramePageController;

/**
 * 客户端统计
 * @package rest\controllers
 * @acl     st:api
 */
class StatController extends IFramePageController {
	public function index() {
		return $this->render();
	}
}

