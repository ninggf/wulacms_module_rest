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

use wulaphp\form\FormTable;
use wulaphp\validator\JQueryValidator;

class RestAppForm extends FormTable {
	use JQueryValidator;

	/**
	 * 启用
	 * @var \wula\ui\classes\CheckboxField
	 * @type bool
	 * @layout 1,col-xs-12
	 */
	public $status = 1;
	/**
	 * 应用名称
	 * @var \wula\ui\classes\TextField
	 * @type string
	 * @required
	 * @layout 2,col-xs-12 col-sm-4
	 */
	public $name;
	/**
	 * 回调URL
	 * @var \wula\ui\classes\TextField
	 * @type string
	 * @url
	 * @layout 2,col-xs-12 col-sm-8
	 */
	public $callback_url;
	/**
	 * APPKEY
	 * @var \wula\ui\classes\TextField
	 * @type string
	 * @option {"readonly":true}
	 * @layout 3,col-xs-12 col-sm-4
	 */
	public $appkey;
	/**
	 * APPSECRET
	 * @var \wula\ui\classes\TextField
	 * @type string
	 * @required
	 * @layout 3,col-xs-12 col-sm-8
	 */
	public $appsecret;
	/**
	 * 说明
	 * @var \wula\ui\classes\TextareaField
	 * @type string
	 */
	public $note;

	public function newApp($app) {
		return $this->insert($app);
	}

	public function updateApp($app) {
		$id = $app['id'];

		return $this->update($app, ['id' => $id]);
	}
}