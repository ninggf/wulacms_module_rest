<?php

namespace rest;

use backend\classes\DashboardUI;
use wula\cms\CmfModule;
use wulaphp\app\App;
use wulaphp\auth\AclResourceManager;

/**
 * Class RestModule
 * @package rest
 * @group   core
 */
class RestModule extends CmfModule {
	public function getName() {
		return '应用接口';
	}

	public function getDescription() {
		return 'RESTFul风格的接口模块。';
	}

	public function getHomePageURL() {
		return 'https://www.wulacms.com/modules/rest';
	}

	public function getVersionList() {
		$v['1.0.0'] = '初始化RESTFul.';

		return $v;
	}

	/**
	 * @param \wulaphp\auth\AclResourceManager $manager
	 *
	 * @bind rbac\initAdminManager
	 */
	public static function aclcfg(AclResourceManager $manager) {
		$acl = $manager->getResource('api', '接口', 'm');
		$acl->addOperate('app', '配置应用');
	}

	/**
	 * @param \backend\classes\DashboardUI $ui
	 *
	 * @bind dashboard\initUI
	 */
	public static function initMenu(DashboardUI $ui) {
		$passport = whoami('admin');
		if ($passport->cando('m:api') && $passport->cando('m:system')) {
			$menu       = $ui->getMenu('system');
			$navi       = $menu->getMenu('api', '接口');
			$navi->icon = '&#xe62c;';
			$navi->pos  = 900;

			$doc              = $navi->getMenu('doc', '接口文档');
			$doc->pos         = 1;
			$doc->icon        = '&#xe6bc;';
			$doc->data['url'] = App::url('rest/doc');
			if ($passport->cando('app:api')) {
				$app              = $navi->getMenu('app', '接口应用');
				$app->pos         = 2;
				$app->icon        = '&#xe682;';
				$app->iconStyle   = 'color:orange';
				$app->data['url'] = App::url('rest/apps');
			}
		}
	}
}

App::register(new RestModule());