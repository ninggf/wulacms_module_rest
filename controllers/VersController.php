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
use rest\api\v1\ClientApi;
use rest\classes\form\AppVerForm;
use rest\models\AppVersionTable;
use wulaphp\io\Ajax;
use wulaphp\io\Response;
use wulaphp\validator\JQueryValidatorController;
use wulaphp\validator\ValidateException;

/**
 * Class VersController
 * @package rest\controllers
 * @accept  rest\classes\form\AppVerForm
 * @acl     pkg:api
 */
class VersController extends IFramePageController {
	use JQueryValidatorController;

	public function index($appkey) {
		$data['appkey'] = $appkey;

		return $this->render($data);
	}

	public function edit($appkey, $id) {
		if (empty($appkey)) {
			Response::respond(404, '找不到你要编辑的APP');
		}
		$form = new AppVerForm(true);
		if (!empty($id)) {
			$form->inflateFromDB(['id' => $id]);
		} else {
			$form->inflateByData(['appkey' => $appkey]);
		}
		$data['form']  = BootstrapFormRender::v($form);
		$data['rules'] = $form->encodeValidatorRule($this);

		return view($data);
	}

	public function savePost($id) {
		$form = new AppVerForm(true);
		$data = $form->inflate();
		try {
			$form->validate();
			$table               = new AppVersionTable();
			$data['update_time'] = time();
			$data['update_uid']  = $this->passport->uid;
			if ($id) {
				$rst = $table->updateVersion($data, $id);
			} else {
				$data['create_time'] = $data['update_time'];
				$data['create_uid']  = $data['update_uid'];
				unset($data['id']);
				$rst = $table->newVersion($data);
			}
			if ($rst) {
				return Ajax::success('保存完成');
			} else {
				return Ajax::error('出错啦,无法版本数据到数据库');
			}
		} catch (ValidateException $ve) {
			return Ajax::validate('Form', $ve->getErrors());
		} catch (\Exception $e) {
			return Ajax::error($e->getMessage());
		}
	}

	public function del($ids) {
		$ids = safe_ids2($ids);
		if ($ids) {
			$table = new AppVersionTable();
			$table->deleteVers($ids);
		}

		return Ajax::reload('#table', '所选版本已删除');
	}

	public function data($appkey, $count) {
		$table = new AppVersionTable();
		$sql   = $table->alias('AV')->select('AV.*,RA.platform,AC.name AS cfgName');
		$sql->left('{rest_app} AS RA', 'AV.appkey', 'RA.appkey');
		$sql->left('{app_cfg} AS AC', 'AV.cfgid', 'AC.id');
		$sql->where(['AV.appkey' => $appkey]);
		$sql->sort()->page();

		$data = [];
		if ($count) {
			$data['total'] = $sql->total('AV.id');
		}
		$data['rows']      = $sql->toArray();
		$data['platforms'] = ClientApi::device;

		return view($data);
	}
}