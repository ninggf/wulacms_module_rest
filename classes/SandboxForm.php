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
use wulaphp\util\Annotation;
use wulaphp\validator\JQueryValidator;

class SandboxForm extends FormTable {
	use JQueryValidator;
	private $_inputs;
	public  $table = null;
	/**
	 * API URL
	 * @var \wula\ui\classes\TextField
	 * @type string
	 * @required
	 * @url
	 * @option {"placeholder":"http://"}
	 */
	public $api_host;
	/**
	 * APP KEY
	 * @var \wula\ui\classes\TextField
	 * @type string
	 * @required
	 * @option {"placeholder":"app key"}
	 */
	public $app_key;
	/**
	 * APP SESCRET
	 * @var \wula\ui\classes\TextField
	 * @type string
	 * @required
	 * @option {"placeholder":"app secret"}
	 */
	public $app_secret;
	/**
	 * session
	 * @var \wula\ui\classes\TextField
	 * @type string
	 * @note 如果接口需要会话支持请填写此值.
	 */
	public $session;

	public function setInputs($inputs) {
		$this->_inputs = $inputs;
	}

	protected function beforeCreateWidgets() {
		if (!$this->_inputs) {
			return;
		}
		$data   = [];
		$inputs = $this->_inputs;
		$ann    = new Annotation([
			'type' => 'int',
			'skip' => true,
			'var'  => 'wula\ui\classes\Separator'
		]);
		$this->addField('_sepa', $ann);
		foreach ($inputs as $f => $input) {
			list($type, , $dv, $sample, $desc) = $input;
			$data[ $f ] = $dv;
			$type       = strtolower($type);
			if (substr($type, -2) == '[]') {
				$ann = new Annotation([
					'type'   => '[]',
					'name'   => $f,
					'label'  => $f,
					'note'   => $desc,
					'option' => ['placeholder' => substr($type, 0, -2)],
					'var'    => 'wula\ui\classes\TextField'
				]);
				$this->addField($f, $ann);
			} else if ($type == 'object') {
				$sample = @json_decode($sample, true);
				if ($sample) {
					foreach ($sample as $k => $v) {
						$ann = new Annotation([
							'type'   => $v,
							'label'  => $f . '.' . $k,
							'note'   => $desc,
							'option' => ['placeholder' => $v],
							'var'    => 'wula\ui\classes\TextField'
						]);
						$this->addField($f . '[' . $k . ']', $ann);
					}
				}
			} else {
				$ann = new Annotation([
					'type'   => $type,
					'label'  => $f,
					'note'   => $desc,
					'option' => ['placeholder' => $type],
					'var'    => 'wula\ui\classes\TextField'
				]);
				$this->addField($f, $ann);
			}
		}
		$this->inflateByData($data);
	}
}