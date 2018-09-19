<?php

namespace rest;

use backend\classes\DashboardUI;
use wula\cms\CmfModule;
use wulaphp\app\App;
use wulaphp\auth\AclResourceManager;
use wulaphp\conf\ConfigurationLoader;

/**
 * Class RestModule
 * @package rest
 * @group   core
 */
class RestModule extends CmfModule {
	public function getName() {
		return '应用接入';
	}

	public function getDescription() {
		return 'RESTFul风格的接口模块。';
	}

	public function getHomePageURL() {
		return 'https://www.wulacms.com/modules/rest';
	}

	public function getVersionList() {
		$v['1.0.0'] = '初始化RESTFul.';
		$v['1.1.0'] = '添加版本配置功能';
		$v['1.1.1'] = '添加预览发布功能';
		$v['1.1.2'] = '添加母包文件支持';
		$v['1.1.3'] = '添加渠道';

		return $v;
	}

	/**
	 * @param \wulaphp\auth\AclResourceManager $manager
	 *
	 * @bind rbac\initAdminManager
	 */
	public static function aclcfg(AclResourceManager $manager) {
		$acl = $manager->getResource('api', '应用接入', 'm');
		$acl->addOperate('app', '应用管理');
		$acl->addOperate('cfg', '云端控制');
		$acl->addOperate('st', '终端统计');
		$acl->addOperate('pkg', '版本管理');
		$acl->addOperate('channel', '渠道管理');

	}

	/**
	 * @param \backend\classes\DashboardUI $ui
	 *
	 * @bind dashboard\initUI
	 */
	public static function initMenu(DashboardUI $ui) {
		$passport = whoami('admin');
		if ($passport->cando('m:api') && $passport->cando('m:system')) {
			$cfg           = ConfigurationLoader::loadFromFile('rest');
			$menu          = $ui->getMenu('system');
			$navi          = $menu->getMenu('api', '应用接入');
			$navi->iconCls = 'alicon';
			$navi->icon    = '&#xe74a;';
			$navi->pos     = 900;
			if ($cfg->getb('dev', false)) {
				$navi->iconStyle = 'color:orange';
			} else {
				$navi->iconStyle = 'color:green';
			}
			$doc              = $navi->getMenu('doc', '接口文档');
			$doc->pos         = 1;
			$doc->icon        = '&#xe6bc;';
			$doc->data['url'] = App::url('rest/doc');

			if ($passport->cando('app:api')) {
				$app              = $navi->getMenu('app', '应用管理');
				$app->pos         = 2;
				$app->icon        = '&#xe658;';
				$app->iconCls     = 'alicon';
				$app->data['url'] = App::url('rest/apps');
			}
			if ($passport->cando('channel:api')) {
				$cg              = $navi->getMenu('channel', '渠道管理', 3);
				$cg->data['url'] = App::url('rest/channel');
				$cg->icon        = '&#xe645;';
				$cg->iconCls     = 'alicon';
				$cg->iconStyle   = 'color:green';
			}
			if ($passport->cando('cfg:api')) {
				$cg              = $navi->getMenu('cfg', '云端控制', 4);
				$cg->data['url'] = App::url('rest/cfg');
				$cg->icon        = '&#xe640;';
				$cg->iconCls     = 'alicon';
				$cg->iconStyle   = 'color:red';
			}
			//			if ($passport->cando('st:api')) {
			//				$cl              = $navi->getMenu('stat', '终端统计', 4);
			//				$cl->data['url'] = App::url('rest/stat');
			//				$cl->icon        = '&#xe682;';
			//			}
		}
	}
}

App::register(new RestModule());