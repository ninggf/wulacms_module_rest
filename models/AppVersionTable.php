<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace rest\models;

use wulaphp\conf\ConfigurationLoader;
use wulaphp\db\Table;

class AppVersionTable extends Table {

	public function updateVersion($data, $id) {
		if (isset($data['file']) && $data['file']) {
			$data['size'] = $this->getPkgSize($data['file']);
		} else {
			$data['size'] = 0;
		}

		return $this->update($data, $id);
	}

	public function newVersion($data) {
		if (isset($data['file']) && $data['file']) {
			$data['size'] = $this->getPkgSize($data['file']);
		} else {
			$data['size'] = 0;
		}

		return $this->insert($data);
	}

	private function getPkgSize($file) {
		$cfg   = ConfigurationLoader::loadFromFile('rest');
		$store = $cfg->get('store', 'pkgs');
		if (is_file(WWWROOT . $store . DS . $file)) {
			return intval(@filesize(WWWROOT . $store . DS . $file));
		}

		return 0;
	}
}