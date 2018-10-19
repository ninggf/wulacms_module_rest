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

use rest\classes\ApkSignTool;
use wulaphp\app\App;
use wulaphp\conf\ConfigurationLoader;
use wulaphp\io\Response;
use wulaphp\mvc\controller\Controller;

class DownloadController extends Controller {
	/**
	 * 下载APP.
	 *
	 * @param string $appkey APP key.
	 * @param string $cid    channel.
	 * @param int    $uid    user.
	 *
	 * @throws
	 */
	public function index($appkey, $cid = 'guanfang', $uid = 0) {
		if (empty ($appkey)) {
			Response::respond(404);
		}
		try {
			$db = App::db();

			//是否被阻止不能下载.
			$blocked = apply_filter('rest\beforeDownload', false, $appkey, $cid, $uid);
			if ($blocked) {
				Response::respond(404);
			}

			$rs = $db->select('AV.*,RA.platform')->from('{app_version} AS AV')->join('{rest_app} AS RA', 'AV.appkey = RA.appkey')->where([
				'RA.appkey'      => $appkey,
				'AV.pre_release' => 0
			])->desc('vercode')->get();

			if (!$rs || !$rs['ofile']) {
				Response::respond(404);
			}
			//直接跳转去下载
			if (preg_match('#^(ht|f)tps?://.+$#i', $rs['ofile'])) {
				Response::redirect($rs['ofile']);
			}

			$cfg                = ConfigurationLoader::loadFromFile('rest');
			$store              = $cfg->get('store', 'pkgs');
			$origional_apk_file = WWWROOT . $store . DS . $rs['ofile'];
			//母包文件未找到
			if (!is_file($origional_apk_file)) {
				Response::respond(404);
			}

			$channel = $cid;
			$userid  = $uid;

			if (!preg_match('/^[\da-z_]{1,15}$/i', $channel)) {
				$channel = 'guanfang';
			}

			if (!preg_match('/^[\d]{1,10}$/i', $userid)) {
				$userid = '0';
			}
			switch ($rs['platform']) {
				case 'ios':
					$ext = 'ipa';
					break;
				case 'android':
					$ext = 'apk';
					break;
				default:
					$ext = 'exe';
			}
			$host = $cfg->get('download');
			if (!$host) {
				$host = apply_filter('rest\get_download_url',[]);
			}
			$ourl      = $store . '/v' . $rs['vercode'] . '/' . ($rs ['prefix'] ? $rs['prefix'] : 'app') . '_' . $channel . '.' . $ext;
			$dest_file = WWWROOT . $ourl;
			if (is_file($dest_file)) {
				$downloadUrl = untrailingslashit($host) . '/' . ltrim($ourl, '/');
				Response::redirect($downloadUrl);
			}
			//下载指定的母包而不打包
			//if (preg_match('#^(ht|f)tps?://.+$#i', $rs['file'])) {
			//	Response::redirect($rs['file']);
			//}

			$ver       = '_v' . $rs['vercode'];
			$path      = $ext . 's/' . substr(md5($channel . '_' . $userid), 0, 2);
			$uc        = $userid == '0' ? '' : '_' . $userid;
			$url       = $store . '/' . $path . '/' . ($rs ['prefix'] ? $rs['prefix'] : 'app') . '_' . $channel . $uc . $ver . '.' . $ext;
			$dest_file = WWWROOT . $url;

			$downloadUrl = untrailingslashit($host) . '/' . ltrim($url, '/');
			if (is_file($dest_file)) {
				Response::redirect($downloadUrl);
			}

			$channels ['channel'] = $channel;
			$channels ['userid']  = $userid;
			$rst                  = false;
			if ($ext == 'ipa') {
				$rst = ApkSignTool::repackIOS($origional_apk_file, $dest_file, $channels, $rs ['prefix']);
			} else if ($ext == 'apk') {
				$rst = ApkSignTool::repack($origional_apk_file, $dest_file, $channels);
			} else {
				Response::redirect(untrailingslashit($host) . '/' . $store . '/' . $rs['ofile']);
			}
			if ($rst) {
				Response::redirect($downloadUrl);
			} else {
				Response::respond(404);
			}
		} catch (\Exception $e) {
			Response::respond(404);
		}
	}
}