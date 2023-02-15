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

use passport\classes\model\PassportTable;
use rest\classes\form\AppChannelForm;
use rest\classes\PackageSignTool;
use ucenter\Provider;
use wulaphp\app\App;
use wulaphp\io\Response;
use wulaphp\mvc\controller\Controller;
use wulaphp\util\RedisLock;

class DownloadController extends Controller {
    /**
     * 下载APP.
     *
     * @param string $appkey APP key.
     * @param string $cid    channel.
     * @param string $uid    user.
     *
     * @throws
     */
    public function index(string $appkey = '', string $cid = 'guanfang', string $uid = '') {

        if (empty ($appkey)) {
            $appkey = '5f61abe868926';
        }

        try {
            $db  = App::db();
            $uid = intval($uid);
            //是否被阻止不能下载.
            $blocked = apply_filter('rest\beforeDownload', false, $appkey, $cid, $uid);
            if ($blocked) {
                Response::respond();
            }

            $rs = $db->select('AV.*,RA.platform')->from('{app_version} AS AV')->join('{rest_app} AS RA', 'AV.appkey = RA.appkey')->where([
                'RA.appkey'      => $appkey,
                'AV.pre_release' => 0
            ])->desc('vercode')->limit(0, 1)->get();

            if (!$rs) {
                Response::respond();
            }

            # 查看是否已经有生成好的包
            $rs['uid'] = $uid;
            $rs['cid'] = $cid;
            $pkgFile   = apply_filter('rest\getPackagedFile', '', $rs);

            if ($pkgFile) {
                # 已经打包
                Response::redirect($rs['file']);
            } else {
                if ($rs['platform'] == 'ios') {
                    $rs['down_url'] = Provider::getShareUrl();

                    return template('ios_down', $rs);
                }
                //直接跳转去下载
                if (preg_match('#^(ht|f)tps?://.+$#i', $rs['file'])) {
                    Response::redirect($rs['file']);
                } else {
                    # android11打包会失败，暂时不打包，报错吧
                    Response::respond();
                }

                if (!$rs['ofile']) {
                    Response::respond();
                }

                $ofile = APPROOT . $rs['ofile'];
                if (!is_file($ofile)) {
                    Response::respond();
                }
                #打包
                if (!AppChannelForm::sexist(['channel' => $cid, 'status' => 1, 'deleted' => 0])) {
                    $cid = 'guanfang';
                }
                if (!PassportTable::sexist(['id' => $uid])) {
                    $uid = 0;
                }

                $rs['cid'] = $cid;
                $rs['uid'] = $uid;

                $key = 'pkging.' . $cid . '.' . $uid;

                if (RedisLock::ulock($key)) {
                    try {
                        $fname = TMP_PATH . $cid . '_' . $uid . '_' . $rs['vercode'] . '.apk';
                        $rst   = PackageSignTool::repackApk($ofile, $fname, [
                            'userid'  => $uid,
                            'channel' => $cid
                        ], $error);

                        if ($rst) {
                            $destDir = 'pkgs/' . $cid . '/';
                            if (!is_dir(WWWROOT . $destDir) && !mkdir(WWWROOT . $destDir, 0755, true)) {
                                RedisLock::release($key);
                                Response::respond(500, '无法创建目录');
                            }
                            $dfile = 'qww_' . base_convert($uid, 10, 36) . '_' . $rs['vercode'] . '.apk';
                            if (rename($fname, WWWROOT . $destDir . $dfile)) {
                                $url       = trailingslashit(Provider::getShareUrl()) . $destDir . $dfile;
                                $rs['url'] = $url;
                                //fire('rest\onAppPackaged', $rs);
                                RedisLock::release($key);
                                Response::redirect($url); # 跳转到下载页
                            } else {
                                RedisLock::release($key);
                                Response::respond(500, '无法复制文件');
                            }
                        } else {
                            log_error($error, 'pkg');
                            RedisLock::release($key);
                            Response::respond(500, '无法打包');
                        }
                    } finally {
                        RedisLock::release($key);
                    }
                }
            }

            Response::respond();
        } catch (\Exception $e) {
            Response::respond();
        }
    }

    public function plist($id, $file) {
        if ($file != 'manifest.plist') {
            Response::respond();
        }

        try {
            $db = App::db();

            $rs = $db->select('AV.file,RA.platform')->from('{app_version} AS AV')->join('{rest_app} AS RA', 'AV.appkey = RA.appkey')->where([
                'AV.id'          => $id,
                'AV.pre_release' => 0
            ])->desc('vercode')->limit(0, 1)->get();

            if (!$rs || $rs['platform'] != 'ios') {
                Response::respond();
            }

            $rs['bundle'] = App::cfg('@bundle', []);

            return template('ios_manifest', $rs);
        } catch (\Exception $e) {
            Response::respond();
        }
    }
}