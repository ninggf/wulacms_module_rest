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
use rest\classes\RestAppForm;
use rest\models\AppVersionTable;
use rest\models\RestApp;
use wulaphp\app\App;
use wulaphp\db\DatabaseConnection;
use wulaphp\io\Ajax;
use wulaphp\validator\JQueryValidatorController;
use wulaphp\validator\ValidateException;

/**
 * Class AppsController
 * @package rest\controllers
 * @acl     app:api
 */
class AppsController extends IFramePageController {
    use JQueryValidatorController;

    public function index() {
        return $this->render();
    }

    public function edit($id = '') {
        $form = new RestAppForm(!0);
        if ($id) {
            $admin = $form->get($id);
            $user  = $admin->get(0);
            $form->inflateByData($user);
        } else {
            $form->inflateByData(['appkey' => uniqid(), 'appsecret' => uniqid('', true)]);
        }
        $data['form']  = BootstrapFormRender::v($form);
        $data['id']    = $id;
        $data['rules'] = $form->encodeValidatorRule($this);

        return view($data);
    }

    public function del($ids) {
        $ids = safe_ids2($ids);
        if ($ids) {
            if ($ids) {
                $error = '';
                try {
                    $rst = App::db()->trans(function (DatabaseConnection $db) use ($ids) {
                        if (!$db->delete()->from('{rest_app}')->where(['id IN' => $ids])->exec()) {
                            return false;
                        }

                        return true;
                    }, $error);
                } catch (\Exception $e) {
                    $rst = false;
                }
                if ($rst) {
                    return Ajax::reload('#rest-apps-list', '所选应用已删除');
                } else {
                    return Ajax::error($error ? $error : '删除应用出错，请找系统管理员');
                }
            }
        }

        return Ajax::error('未指定要删除的应用');
    }

    public function setStatus($status, $ids = '') {
        $ids = safe_ids2($ids);
        if ($ids) {
            $status = $status === '1' ? 1 : 0;
            try {
                App::db()->update('{rest_app}')->set(['status' => $status])->where(['id IN' => $ids])->exec();
            } catch (\Exception $e) {
                return Ajax::error($e->getMessage());
            }

            return Ajax::reload('#rest-apps-list', $status == '1' ? '所选应用已激活' : '所选应用已禁用');
        } else {
            return Ajax::error('未指定应用');
        }
    }

    public function savePost($id) {
        $form = new RestAppForm(!0);
        $app  = $form->inflate();
        try {
            $form->validate($app);
            $app['update_uid']  = $this->passport->uid;
            $app['update_time'] = time();
            if ($id) {
                $app['id'] = $id;
                $rst       = $form->updateApp($app);
            } else {
                $app['create_uid']  = $app['update_uid'];
                $app['create_time'] = $app['update_time'];
                $rst                = $form->newApp($app);
            }
            if (!$rst) {
                return Ajax::error($form->lastError());
            }
        } catch (ValidateException $ve) {
            return Ajax::validate('RestAppForm', $ve->getErrors());
        } catch (\PDOException $pe) {
            return Ajax::error($pe->getMessage());
        }

        return Ajax::reload('#rest-apps-list', $id ? '应用修改成功' : '新应用已经成功创建');
    }

    public function data($status = '', $q = '', $count = '') {
        $data  = [];
        $model = new RestApp();
        if ($status == '0') {
            $where['status'] = $status;
        } else {
            $where['status'] = 1;
        }

        if ($q) {
            $where1['name LIKE']     = '%' . $q . '%';
            $where1['||appkey LIKE'] = '%' . $q . '%';
            $where[]                 = $where1;
        }
        $ver   = new AppVersionTable();
        $apps  = $model->alias('APP')->select();
        $filed = $ver->select('version')->where(['appkey' => imv('APP.appkey'),'pre_release'=>0])->desc('vercode')
            ->limit(0, 1);

        $apps->field($filed, 'version');

        $apps->where($where)->page()->sort();

        $total = '';

        if ($count) {
            $total = $apps->total('id');
        }

        $data['items']     = $apps;
        $data['total']     = $total;
        $data['canCfg']    = $this->passport->cando('cfg:api');
        $data['pkgMng']    = $this->passport->cando('pkg:api');
        $data['platforms'] = ClientApi::device;

        return view($data);
    }
}