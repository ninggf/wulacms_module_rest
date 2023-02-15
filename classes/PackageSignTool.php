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

use wulaphp\app\App;

/**
 * 重新打包工具.
 *
 * @package rest\classes
 */
class PackageSignTool {
    /**
     * 重新签名
     *
     * @param string $apk_file
     * @param array  $output
     * @param bool   $force
     *
     * @return array [file=>path of singed file,md5=>md5 of signed file]
     * @throws \Exception
     * @since  1.0.0
     * @author Leo Ning <windywany@gmail.com>
     * @date   2021-06-20 12:26:53
     */
    public static function resign(string $apk_file, array &$output, bool $force = false): array {

        if (!is_file($apk_file)) {
            throw new \Exception('文件不存在');
        }
        # 解析配置参数
        $zipCmd = App::cfg('package.zip');
        if (!$zipCmd || !is_executable($zipCmd)) {
            throw new \Exception('找不到zip命令');
        }
        $zipCmd    = escapeshellcmd($zipCmd);
        $jarsigner = App::cfg('package.jarsigner');
        $zipalign  = App::cfg('package.zipalign');
        if (!$jarsigner || !is_executable($jarsigner)) {
            throw new \Exception('找不到jarsigner命令');
        }
        $jarsigner = escapeshellcmd($jarsigner);
        $keystore  = App::cfg('package.keystore');
        if (!$keystore || !is_file($keystore)) {
            throw new \Exception('keystore文件不存在');
        }
        $keyAlias = App::cfg('package.ks_alias');
        if (!$keyAlias) {
            throw new \Exception('未指定keystore别名');
        }
        $storepass = App::cfg('package.storepass');
        if (!$storepass) {
            throw new \Exception('keystore密码不存在');
        }
        $keypass = App::cfg('package.keypass');
        # 备份原包
        $baseName    = dirname($apk_file) . DS . basename($apk_file, '.apk');
        $zipfile     = $baseName . '.zip';
        $unsignFile  = $baseName . '-unsign.apk';
        $unalignFile = $baseName . '-unalign.apk';
        $singedFile  = $baseName . '-signed.apk';
        if (is_file($singedFile)) {
            if ($force) {
                @unlink($singedFile);
            } else {
                return ['file' => $singedFile, 'md5' => md5_file($singedFile), 'output' => []];
            }
        }
        if (is_file($zipfile)) {
            if ($force) {
                @unlink($zipfile);
            } else {
                throw new \Exception('备份文件已经存在，可能正在进行签名');
            }
        }
        if (!copy($apk_file, $zipfile)) {
            throw new \Exception('不能备份原文件');
        }

        # 删除原包的签名
        @exec($zipCmd . ' -d ' . $zipfile . ' META-INF/CERT.RSA', $output, $rtn);
        @exec($zipCmd . ' -d ' . $zipfile . ' META-INF/CERT.SF', $output, $rtn);

        # 复制为未对齐apk并对其进行签名
        if (!copy($zipfile, $unsignFile)) {
            @unlink($zipfile);
            throw new \Exception('无法生成未签名包文件');
        }

        $cmd    = $jarsigner;
        $args[] = '-keystore ' . $keystore;
        $args[] = '-storepass ' . $storepass;
        if ($keypass) {
            $args[] = '-keypass ' . $keypass;
        }
        $args[]  = '-signedjar ';
        $args[]  = $unalignFile;
        $args[]  = $unsignFile;
        $args[]  = $keyAlias;
        $argsStr = implode(' ', $args);
        @exec($cmd . ' ' . $argsStr, $output, $rtn);
        if ($rtn !== 0) {
            @unlink($unsignFile);
            @unlink($zipfile);
            throw new \Exception('签名失败');
        }
        if ($zipalign && is_executable($zipalign)) {
            $zipalign = escapeshellcmd($zipalign);
            @exec($zipalign . ' -f -p 4 ' . $unalignFile . ' ' . $singedFile, $output, $rtn);
            @unlink($unalignFile);
            @unlink($unsignFile);
            @unlink($zipfile);
            if ($rtn !== 0) {
                throw new \Exception('无法对齐优化签名后的包文件');
            }
        } else {
            @unlink($unsignFile);
            @unlink($zipfile);
            if (!rename($unalignFile, $singedFile)) {
                @unlink($unalignFile);
                throw new \Exception('无法复制签名后的母包文件');
            } else {
                @unlink($unalignFile);
            }
        }
        $md5 = md5_file($singedFile);

        return ['file' => $singedFile, 'md5' => $md5, 'output' => $output];
    }

    /**
     * 将渠道号加入META-INF目录生成一个新的渠道包.
     *
     * @param string $origional_apk_file 原始APK文件.
     * @param string $apk_file           新渠道APK文件.
     * @param array  $channels           渠道列表.
     *
     * @return bool 成功true,失败false.
     */
    public static function repackApk(string $origional_apk_file, string $apk_file, array $channels, ?string &$error): bool {
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
                $error = '无法复制文件到:' . $apk_file;

                return false;
            }
            // 保证打包时，apk文件唯一
            $dir = TMP_PATH . md5($apk_file . rand_str(10));
            if (is_dir($dir)) {
                rmdirs($dir, false);
            }
            @mkdir($dir);
            $tmpApk = $dir . DS . 'tmp.apk';
            if (is_dir($dir) && @copy($origional_apk_file, $tmpApk)) {
                @mkdir($dir . DS . 'META-INF');
                foreach ($channels as $name => $val) {
                    if (!@touch($dir . DS . 'META-INF' . DS . $name . '_' . $val)) {
                        $error = '无法添加渠道：' . $name . ' = ' . $val . ' 到文件：' . $apk_file;

                        return false;
                    }
                }
                @chdir($dir);
                $output = [];
                @exec('cd ' . $dir);
                $rtn = 0;
                $zip = App::cfg('package.zip', 'zip');
                foreach ($channels as $name => $val) {
                    @exec($zip . ' tmp.apk' . ' META-INF' . DS . $name . '_' . $val, $output, $rtn);
                }
                if ($rtn === 0) {
                    $zipalign = App::cfg('package.zipalign');
                    if ($zipalign && is_executable($zipalign)) {
                        @exec($zipalign . ' -f -p 4 ' . $tmpApk . ' ' . $tmpApk . '.tmp');
                        if (is_file($tmpApk . '.tmp')) {
                            $tmpApk = $tmpApk . '.tmp';
                        }
                    }
                    if (@rename($tmpApk, $apk_file)) {
                        $dest_apk_file = true;
                    } else {
                        $error = '无法重命名渠道包为：' . $apk_file;
                    }
                } else {
                    $error = '无法将渠道文件加入APK：' . $tmpApk . "\n" . implode("\n", $output);
                }
            } else {
                $error = '无法复制母包到文件' . $tmpApk;
            }
            if (is_dir($dir)) {
                rmdirs($dir, false);
            }
        } else {
            $error = '母包文件不存在:' . $origional_apk_file;
        }

        return $dest_apk_file;
    }

    /**
     * 将渠道号加入META-INF目录生成一个新的渠道包.
     *
     * @param string      $origional_apk_file
     *            原始APK文件.
     * @param string      $apk_file
     *            新渠道APK文件.
     * @param array       $channels
     *            渠道.
     * @param string      $appName
     * @param string|null $error
     *
     * @return bool 成功true,失败false.
     */
    public static function repackIOS(string $origional_apk_file, string $apk_file, array $channels, string $appName, ?string &$error): bool {
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
                $error = '无法复制文件到:' . $apk_file;

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
                    $rst = @mkdir($dir . DS . "Payload/$appName.app/extra/{$channel}_{$val}", 0755, true);
                    if (!$rst) {
                        $error = '无法生成渠道目录：' . $dir . DS . "Payload/$appName.app/extra/{$channel}_$val";

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
                        $error = '无法重命名渠道包为：' . $apk_file;
                    }
                } else {
                    $error = '无法将渠道文件加入IPA：' . $tmpApk . "\n" . implode("\n", $output);
                }
            } else {
                $error = '无法复制母包到文件' . $tmpApk;
            }
            if (is_dir($dir)) {
                rmdirs($dir, false);
            }
        } else {
            $error = '母包文件不存在:' . $origional_apk_file;
        }

        return $dest_apk_file;
    }
}