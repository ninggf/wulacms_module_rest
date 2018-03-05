<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace rest\classes\form;

use wulaphp\form\FormTable;
use wulaphp\validator\JQueryValidator;

class AppCfgNewForm extends FormTable {
	use JQueryValidator;
	public $table = null;
	/**
	 * 配置名称
	 * @var \backend\form\TextField
	 * @type string
	 * @required
	 */
	public $name;
}