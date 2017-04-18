<?php
namespace rest;

use wula\cms\CmfModule;
use wulaphp\app\App;

class RestModule extends CmfModule {
	public function getName() {
		return 'RESTFul';
	}

	public function getDescription() {
		return '实现';
	}

	public function getHomePageURL() {
		return 'https://www.wulacms.com/modules/core';
	}

	protected function getVersionList() {
		$v['1.0.0'] = '';

		return $v;
	}
}
App::register(new RestModule());