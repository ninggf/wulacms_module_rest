<?php

namespace rest;

use dashboard\classes\DashboardUI;
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
	 * @param \dashboard\classes\DashboardUI $ui
	 *
	 * @bind dashboard\initUI
	 */
	public static function initMenu(DashboardUI $ui) {
		$passport = whoami('admin');
		if ($passport->cando('m:api')) {
			$navi          = $ui->getMenu('api', '接口');
			$navi->icon    = 'fa fa-code-fork';
			$navi->pos     = 900;
			$navi->iconCls = 'bg-success';

			$doc            = $navi->getMenu('doc', '接口文档');
			$doc->pos       = 1;
			$doc->icon      = 'fa fa-book';
			$doc->iconStyle = 'color:green';
			$doc->url       = App::hash('~rest/doc');

			$app            = $navi->getMenu('app', '应用管理');
			$app->pos       = 2;
			$app->icon      = 'fa fa-anchor';
			$app->iconStyle = 'color:orange';
			$app->url       = App::hash('~rest/apps');
		}
	}
}

App::register(new RestModule());