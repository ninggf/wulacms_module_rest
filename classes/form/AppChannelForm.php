<?php

namespace rest\classes\form;

use wulaphp\form\FormTable;
use wulaphp\validator\JQueryValidator;

class AppChannelForm extends FormTable {
	use JQueryValidator;

	/**
	 * 启用
	 * @var \backend\form\CheckboxField
	 * @type bool
	 * @layout 1,col-xs-12
	 */
	public $status=1;

	/**
	 * 渠道名
	 * @var \backend\form\TextField
	 * @type string
	 * @required
	 * @layout 2,col-xs-6
	 */
	public $channel_name;

	/**
	 * 渠道ID
	 * @var \backend\form\TextField
	 * @type string
	 * @required
	 * @layout 2,col-xs-6
	 */
	public $channel;

	/**
	 * 说明
	 * @var \backend\form\TextareaField
	 * @type string
     * @layout 4,col-xs-12
	 */
	public $desc;

	/**
	 * 添加记录
	 *
	 * @param array $data
	 *
	 * @return bool|int
	 */
	public function addData(array $data = []) {
		return $this->insert($data);
	}

	/**
	 * 更新记录
	 *
	 * @param array $data
	 * @param array $where
	 *
	 * @return bool|\wulaphp\db\sql\UpdateSQL
	 */
	public function updateData(array $data = [], array $where = []) {
		return $this->update($data, $where);
	}
}