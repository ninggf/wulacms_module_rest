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

use wulaphp\conf\ConfigurationLoader;
use wulaphp\form\FormTable;
use wulaphp\validator\JQueryValidator;

class AppVerForm extends FormTable {
	use JQueryValidator;
	public $table = 'app_version';
	/**
	 * @var \backend\form\HiddenField
	 * @type int
	 */
	public $id;

	/**
	 * @var \backend\form\HiddenField
	 * @type string
	 */
	public $appkey;

	/**
	 * 版本号(<b class="text-danger">*</b>)
	 * @var \backend\form\TextField
	 * @type int
	 * @required
	 * @digits 只能是数值
	 * @layout 2,col-sm-4
	 */
	public $vercode;
	/**
	 * 版本(<b class="text-danger">*</b>)
	 * @var \backend\form\TextField
	 * @type string
	 * @required
	 * @layout 2,col-sm-4
	 */
	public $version;
	/**
	 * 配置(<b class="text-danger">*</b>)
	 * @var \backend\form\SelectField
	 * @type int
	 * @required
	 * @layout 2,col-sm-4
	 * @see    table
	 * @data {"table":"app_cfg","option":{"0":"请选择配置"},"orderBy":"id"}
	 */
	public $cfgid;
	/**
	 * 渠道包文件前缀
	 * @var \backend\form\TextField
	 * @type string
	 * @layout 3,col-sm-4
	 */
	public $prefix;
	/**
	 * 软件母包文件
	 * @var \backend\form\TextField
	 * @type string
	 * @callback (checkFile) => 软件母包文件不存在
	 * @layout 3,col-sm-6
	 */
	public $ofile;
	/**
	 * 软件包大小
	 * @var \backend\form\TextField
	 * @type int
	 * @digits
	 * @layout 3,col-sm-2
	 */
	public $size;
	/**
	 * 软件下载地址
	 * @var \backend\form\TextField
	 * @type string
	 * @url
	 * @layout 4,col-sm-12
	 */
	public $file;
	/**
	 * 发行说明
	 * @var \backend\form\TextareaField
	 * @type string
	 * @option {"row":5}
	 */
	public $desc;
	/**
	 * 是否强制升级
	 * @var \backend\form\CheckboxField
	 * @type bool
	 * @layout 10,col-sm-6
	 */
	public $update_type = 1;
	/**
	 * 预览发布
	 * @var \backend\form\CheckboxField
	 * @type bool
	 * @layout 10,col-sm-6
	 */
	public $pre_release = 0;

	public function checkFile($value, $data, $msg) {
		$cfg   = ConfigurationLoader::loadFromFile('rest');
		$store = $cfg->get('store', 'pkgs');
		if (is_file(WWWROOT . $store . DS . $value)) {
			return true;
		}

		return '请先把文件上传到' . WWWROOT_DIR . $store;
	}
}