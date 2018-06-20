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

class AppCfgForm extends FormTable {
	use JQueryValidator;
	/**
	 * @var \backend\form\HiddenField
	 * @type int
	 */
	public $id;
	/**
	 * 配置名
	 * @var \backend\form\TextField
	 * @type string
	 * @required
	 * @layout 1,col-xs-6
	 */
	public $name;
	/**
	 * @var \backend\form\Separator
	 * @type string
	 * @skip true
	 */
	public $_spec;

	protected function initialize($sfields) {
		fire('rest\initCfgForm', $this);
	}
}