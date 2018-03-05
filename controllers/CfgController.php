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
use backend\form\BootstrapFormRender;
use rest\classes\form\AppCfgForm;
use rest\classes\form\AppCfgNewForm;
use rest\models\AppCfgTable;
use wulaphp\io\Ajax;
use wulaphp\io\Response;
use wulaphp\validator\JQueryValidatorController;
use wulaphp\validator\ValidateException;

/**
 * Class CfgController
 * @package rest\controllers
 * @acl     cfg:api
 */
class CfgController extends IFramePageController {
	use JQueryValidatorController;

	public function index() {

		return $this->render();
	}

	public function dic($id = 0) {
		$data = [];
		$id   = intval($id);
		$cfg  = new AppCfgTable();
		$sql  = $cfg->alias('CFG')->select('id,name,pid')->asc('id')->where(['pid' => $id])->toArray();

		foreach ($sql as $cg) {
			$cg['isParent'] = true;
			$data[]         = $cg;
		}

		return $data;
	}

	public function add($pid) {
		$data['pid']   = $pid;
		$form          = new AppCfgNewForm(true);
		$data['form']  = BootstrapFormRender::v($form);
		$data['rules'] = $form->encodeValidatorRule($this);

		return view($data);
	}

	public function addPost($pid, $name) {
		$pid          = intval($pid);
		$data['pid']  = $pid;
		$data['name'] = $name;
		if (empty($name)) {
			return Ajax::validate('AddForm', ['name' => '配置名不能为空']);
		}
		$table = new AppCfgTable();
		$id    = $table->addNewCfg($data);
		if ($id) {
			$data['id']       = $id;
			$data['isParent'] = true;

			return Ajax::success(['message' => '配置添加成功', 'cfg' => $data]);
		} else {
			return Ajax::error('添加配置失败:' . $table->lastError());
		}
	}

	public function del($id) {
		$id = intval($id);
		if (empty($id)) {
			return Ajax::error('未指定要删除的配置');
		}
		if ($id === 1) {
			return Ajax::error('无法删除系统默认配置');
		}
		$table = new AppCfgTable();
		if ($table->exist(['pid' => $id])) {
			return Ajax::error('请先删除它的子配置', 'alert');
		}
		$table->db()->delete()->from('{app_cfg}')->where(['id' => $id])->exec();

		return Ajax::success('配置已删除');
	}

	public function edit($id) {
		$data['id'] = $id;
		$table      = new AppCfgTable();
		$cfg        = $table->loadConfig($id);
		if (!$cfg) {
			Response::error('配置不存在');
		}
		$form = new AppCfgForm(true);
		if ($cfg['options']) {
			$form->inflateByData($cfg['options']);
		}
		$form->inflateByData($cfg);
		$data['cfgName'] = $cfg['name'];
		$data['form']    = BootstrapFormRender::h($form, [
			'label-col' => 'col-xs-12 col-md-3',
			'field-col' => 'col-xs-12 col-md-9'
		]);
		$data['rules']   = $form->encodeValidatorRule($this);

		return view($data);
	}

	public function save($id, $name) {
		$form = new AppCfgForm(true);
		$data = $form->inflate();
		try {
			$form->validate();
			$cfg['name'] = $name;
			unset($data['id'], $data['name']);
			if ($data) {
				$cfg['options'] = json_encode($data);
			} else {
				$cfg['options'] = '';
			}
			$table = new AppCfgTable();
			$rst   = $table->updateCfg($cfg, $id);
			if ($rst) {
				return Ajax::success(['message' => '保存完成', 'cfg' => ['id' => $id, 'name' => $name]]);
			} else {
				return Ajax::error('无法保存配置:' . $table->lastError());
			}
		} catch (ValidateException $e) {
			return Ajax::validate('SettingForm', $e->getErrors());
		}
	}
}