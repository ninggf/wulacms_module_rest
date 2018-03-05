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

use rest\api\v1\ClientApi;
use wulaphp\form\FormTable;
use wulaphp\validator\JQueryValidator;

class RestAppForm extends FormTable {
	use JQueryValidator;

	/**
	 * 启用
	 * @var \backend\form\CheckboxField
	 * @type bool
	 * @layout 1,col-xs-12
	 */
	public $status = 1;
	/**
	 * 应用名称
	 * @var \backend\form\TextField
	 * @type string
	 * @required
	 * @layout 2,col-xs-12 col-sm-4
	 */
	public $name;
	/**
	 * 回调URL
	 * @var \backend\form\TextField
	 * @type string
	 * @url
	 * @layout 2,col-xs-12 col-sm-8
	 */
	public $callback_url;
	/**
	 * APPKEY
	 * @var \backend\form\TextField
	 * @type string
	 * @option {"readonly":true}
	 * @layout 3,col-xs-12 col-sm-4
	 */
	public $appkey;
	/**
	 * APPSECRET
	 * @var \backend\form\TextField
	 * @type string
	 * @required
	 * @layout 3,col-xs-12 col-sm-4
	 */
	public $appsecret;
	/**
	 * 平台
	 * @var \backend\form\SelectField
	 * @type string
	 * @required
	 * @data ::getDeviceList
	 * @layout 3,col-xs-12 col-sm-4
	 */
	public $platform;
	/**
	 * 说明
	 * @var \backend\form\TextareaField
	 * @type string
	 */
	public $note;

	/**
	 * @param $app
	 *
	 * @return bool|int
	 * @throws
	 */
	public function newApp($app) {
		return $this->insert($app);
	}

	/**
	 * @param $app
	 *
	 * @return bool|\wulaphp\db\sql\UpdateSQL
	 * @throws
	 */
	public function updateApp($app) {
		$id = $app['id'];

		return $this->update($app, ['id' => $id]);
	}

	public function getDeviceList() {
		$device = ClientApi::device;

		return $device;
	}
}