<?php

namespace rest\controllers;

use backend\classes\IFramePageController;
use backend\form\BootstrapFormRender;
use rest\classes\form\AppChannelForm;
use wulaphp\app\App;
use wulaphp\io\Ajax;

/**
 * Class ChannelController
 * @package rest\controllers
 * @acl     channel:api
 */
class ChannelController extends IFramePageController {

	public function index() {

		return $this->render();
	}

	public function data($q = '', $count = '') {
		$model = new AppChannelForm();

		$where['deleted'] = 0;
		if ($q) {
			$where['channel Like'] = '%' . $q . '%';
		}
		$query = $model->select('*')->where($where)->page()->sort();
		$rows  = $query->toArray();
		$total = '';
		if ($count) {
			$total = $query->total('id');
		}
		$data['rows']  = $rows;
		$data['total'] = $total;

		return view($data);
	}

	public function edit($id = '') {
		$form = new AppChannelForm(true);
		if ($id) {
			$info = $form->get($id)->ary();
			$form->inflateByData($info);
		}
		$data['form']  = BootstrapFormRender::v($form);
		$data['rules'] = $form->encodeValidatorRule($this);
		$data['id']    = $id;

		return view($data);
	}

	public function setStatus($status, $ids = '') {
		$ids = safe_ids2($ids);
		if ($ids) {
			$status = $status === '1' ? 1 : 0;
			if ($ids) {
				try {
					App::db()->update('{app_channel}')->set(['status' => $status])->where(['id IN' => $ids])->exec();
				} catch (\Exception $e) {
					return Ajax::error($e->getMessage());
				}
			}

			return Ajax::reload('#table', $status == '1' ? '所选渠道已激活' : '所选渠道已禁用');
		} else {
			return Ajax::error('未指定渠道');
		}
	}

	public function del($id) {
		if (!$id) {
			return Ajax::error('参数错误啦!哥!');
		}
		$form = new AppChannelForm();
		$res  = $form->updateData(['deleted' => 1], ['id' => $id]);

		return Ajax::reload('#table', $res ? '删除成功' : '删除失败');
	}

	public function savePost($id = '') {
		$form                = new AppChannelForm(true);
		$data                = $form->inflate();
		$data['update_time'] = time();
		if ($id) {
			$res = $form->updateData($data, ['id' => $id]);
		} else {
			$data['create_time'] = time();

			$res = $form->addData($data);
		}
		if ($res) {
			return Ajax::reload('#table', $id ? '修改成功' : '新渠道已经成功创建');
		} else {
			return Ajax::error('操作失败了');
		}

	}
}