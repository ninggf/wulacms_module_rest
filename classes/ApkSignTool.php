<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace rest\classes;

use wulaphp\conf\ConfigurationLoader;

/**
 * 重新打包工具.
 *
 * @package rest\classes
 */
class ApkSignTool {

	/**
	 * 将渠道号加入META-INF目录生成一个新的渠道包.
	 *
	 * @param string $origional_apk_file
	 *            原始APK文件.
	 * @param string $apk_file
	 *            新渠道APK文件.
	 * @param array  $channels
	 *            渠道列表.
	 *
	 * @return bool 成功true,失败false.
	 */
	public static function repack($origional_apk_file, $apk_file, $channels = []) {
		if (is_file($apk_file)) {
			@unlink($apk_file);
		}
		$dest_apk_file = false;
		if (is_file($origional_apk_file)) {
			$ddir = dirname($apk_file);
			if (!file_exists($ddir)) {
				@mkdir($ddir, 0755, true);
			}
			if (empty ($channels)) {
				if (@copy($origional_apk_file, $apk_file)) {
					return true;
				}

				return false;
			}
			$dir = TMP_PATH . md5($apk_file);
			if (is_dir($dir)) {
				rmdirs($dir, false);
			}
			@mkdir($dir);
			$tmpApk = $dir . DS . 'tmp.apk';
			if (is_dir($dir) && @copy($origional_apk_file, $tmpApk)) {
				@mkdir($dir . DS . 'META-INF');
				foreach ($channels as $name => $val) {
					if (!@touch($dir . DS . 'META-INF' . DS . $name . '_' . $val)) {
						log_error('无法添加渠道：' . $name . ' = ' . $val . ' 到文件：' . $apk_file);

						return false;
					}
				}
				@chdir($dir);
				$output = [];
				@exec('cd ' . $dir);
				$rtn = 0;
				foreach ($channels as $name => $val) {
					@exec('zip tmp.apk' . ' META-INF' . DS . $name . '_' . $val, $output, $rtn);
				}
				if ($rtn === 0) {
					$cfg      = ConfigurationLoader::loadFromFile('rest');
					$zipalign = $cfg->get('zipalign');
					if ($zipalign && is_executable($zipalign)) {
						@exec($zipalign . ' -v 4 ' . $tmpApk . ' ' . $tmpApk . '.tmp');
						if (is_file($tmpApk . '.tmp')) {
							$tmpApk = $tmpApk . '.tmp';
						}
					}
					if (@rename($tmpApk, $apk_file)) {
						$dest_apk_file = true;
					} else {
						log_error('无法重命名渠道包为：' . $apk_file);
					}
				} else {
					log_error('无法将渠道文件加入APK：' . $tmpApk . "\n" . implode("\n", $output));
				}
			} else {
				log_error('无法复制母包到文件' . $tmpApk);
			}
			if (is_dir($dir)) {
				rmdirs($dir, false);
			}
		} else {
			log_error('母包文件不存在:' . $origional_apk_file);
		}

		return $dest_apk_file;
	}

	/**
	 * 将渠道号加入META-INF目录生成一个新的渠道包.
	 *
	 * @param string $origional_apk_file
	 *            原始APK文件.
	 * @param string $apk_file
	 *            新渠道APK文件.
	 * @param array  $channels
	 *            渠道.
	 * @param string $appName
	 *
	 * @return bool 成功true,失败false.
	 */
	public static function repackIOS($origional_apk_file, $apk_file, $channels, $appName) {
		if (file_exists($apk_file)) {
			@unlink($apk_file);
		}
		$dest_apk_file = false;
		if (is_file($origional_apk_file)) {
			$ddir = dirname($apk_file);
			if (!file_exists($ddir)) {
				@mkdir($ddir, 0755, true);
			}
			if (empty ($channels)) {
				if (@copy($origional_apk_file, $apk_file)) {
					return true;
				}

				return false;
			}
			$dir = TMP_PATH . md5($apk_file);
			if (is_dir($dir)) {
				rmdirs($dir, false);
			}
			@mkdir($dir);
			$tmpApk = $dir . DS . 'tmp.ipa';
			if (is_dir($dir) && @copy($origional_apk_file, $tmpApk)) {
				foreach ($channels as $channel => $val) {
					$rst = @mkdir($dir . DS . "Payload/{$appName}.app/extra/{$channel}_{$val}", 0755, true);
					if (!$rst) {
						log_error('无法生成渠道目录：' . $dir . DS . "Payload/{$appName}.app/extra/{$channel}_{$val}");

						return false;
					}
				}
				@chdir($dir);
				$output = [];
				@exec('cd ' . $dir);
				@exec('zip -r tmp.ipa' . ' Payload', $output, $rtn);
				if ($rtn === 0) {
					if (@rename($tmpApk, $apk_file)) {
						$dest_apk_file = true;
					} else {
						log_error('无法重命名渠道包为：' . $apk_file);
					}
				} else {
					log_error('无法将渠道文件加入IPA：' . $tmpApk . "\n" . implode("\n", $output));
				}
			} else {
				log_error('无法复制母包到文件' . $tmpApk);
			}
			if (is_dir($dir)) {
				rmdirs($dir, false);
			}
		} else {
			log_error('母包文件不存在:' . $origional_apk_file);
		}

		return $dest_apk_file;
	}

	/**
	 * 生成渠道包.
	 *
	 * @param string|int $id
	 * @param string     $channel
	 *
	 * @return array
	 */
	public static function generate($id, $channel = '') {
		$apk = dbselect('AMK.market,AV.version,AV.apk_file,prefix,AV.os')->from('{app_version_market} AS AMK')->join('{app_version} AS AV', 'AMK.version_id = AV.id')->where(['AMK.id' => $id])->get(0);
		if (empty ($apk)) {
			return ['status' => false, 'msg' => '不存在该记录'];
		}
		$apk_file = WEB_ROOT . $apk ['apk_file'];
		if (!file_exists($apk_file)) {
			return ['status' => false, 'msg' => '包不存在！'];
		}
		$channel = $channel ? $channel : $apk ['market'];

		$url      = cfg('apk_home@mobiapp', 'uploads') . DS . $apk ['prefix'] . '_' . $channel . '_' . $apk ['version'] . ($apk ['os'] == '2' ? '.ipa' : '.apk');
		$file     = WEB_ROOT . $url;
		$channels = ['channel' => $channel];
		if ($apk ['os'] == '2') {
			// IOS
			$rst = ApkSignTool::repackIOS($apk_file, $file, $channels, $apk ['prefix']);
		} else {
			// 安卓
			$rst = ApkSignTool::repack($apk_file, $file, $channels);
		}
		$host = cfg('host@mobiapp');
		if (!$host) {
			$host = untrailingslashit(cfg('site_url'));
		}

		$url = $host . '/' . str_replace('\\', '/', $url);
		if ($rst) {
			dbupdate('{app_version_market}')->set(['url' => $url])->where(['id' => $id])->exec();

			return ['status' => true, 'msg' => '成功！', 'data' => $url];
		} else {
			return ['status' => false, 'msg' => '生成失败，查看日志！'];
		}
	}
}