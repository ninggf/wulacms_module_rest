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

use wulaphp\db\Table;

class AppCfgTable extends Table {
	public function addNewCfg($data) {
		try {
			return $this->insert($data);
		} catch (\Exception $e) {
			$this->errors = $e->getMessage();
		}

		return 0;
	}

	public function updateCfg($cfg, $id) {
		try {
			return $this->update($cfg, $id);
		} catch (\Exception $e) {
			$this->errors = $e->getMessage();
		}

		return false;
	}

	public function loadConfig($id) {
		$cfgs = $this->get($id)->ary();
		$rcfg = [];
		if ($cfgs) {
			$rcfg['id']      = $id;
			$rcfg['name']    = $cfgs['name'];
			$rcfg['options'] = [];
		}
		if ($cfgs && $id !== 1) {
			$cfgs = [$cfgs];
			$this->select()->recurse($cfgs, 'id', 'pid');
			foreach ($cfgs as $cfg) {
				if ($cfg['options']) {
					$opts = @json_decode($cfg['options'], true);
					if ($opts) {
						foreach ($opts as $key => $v) {
							if ($v || is_numeric($v) || is_array($v)) {
								$rcfg['options'][ $key ] = $v;
							}
						}
					}
				}
			}
		} else if ($cfgs) {
			$rcfg['options'] = @json_decode($cfgs['options'], true);
		}
		unset($cfgs);

		return $rcfg;
	}
}