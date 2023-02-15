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

use v1\classes\ApiResult;

/**
 * 接口基类.
 *
 * @package rest\classes
 */
abstract class API {
    public $sessionId = '';

    protected $appKey;
    protected $ver;
    //以下为头信息
    protected $device;
    protected $model;
    protected $platform;
    protected $macaddr;
    protected $version;
    protected $channel;
    protected $system;
    protected $screen;
    protected $androidid;
    protected $oaid;
    protected $os;
    protected $imei;
    protected $imeiType;

    /**
     * API constructor.
     *
     * @param string $appKey appkey
     * @param string $ver    版本.
     */
    public function __construct($appKey, $ver = '') {
        $this->appKey = $appKey;
        $this->ver    = $ver;

        $this->os        = intval($_SERVER['HTTP_X_OS'] ?? '3');
        $this->version   = intval($_SERVER['HTTP_X_VERSION'] ?? 0);
        $this->channel   = $_SERVER['HTTP_X_CHANNEL'] ?? '';
        $this->model     = $_SERVER['HTTP_X_MODEL'] ?? '';
        $this->system    = $_SERVER['HTTP_X_SYSTEM'] ?? '';
        $this->screen    = $_SERVER['HTTP_X_SCREEN'] ?? '';
        $this->device    = $_SERVER['HTTP_X_DEVICE'] ?? '';
        $this->androidid = $_SERVER['HTTP_X_ANDROIDID'] ?? '';
        $this->oaid      = $_SERVER['HTTP_X_OAID'] ?? '';
        $this->macaddr   = $_SERVER['HTTP_X_MAC'] ?? '';

        if ($this->device) {
            $this->imei     = $this->device;
            $this->imeiType = 0; // IMEI
        } else if ($this->oaid) {
            $this->imei     = $this->oaid;
            $this->imeiType = 1; // OAID
        } else if ($this->androidid) {
            $this->imei     = $this->androidid;
            $this->imeiType = 2; // ANDROIDID
        }
        # 去除0000-0000-000000-0000的OAID
        if (0 == strlen(trim($this->imei, '0-')) && $this->androidid) {
            $this->imei     = $this->androidid;
            $this->imeiType = 2;
        }
    }

    /**
     * 启动设置.
     *
     * @throws \rest\classes\RestException
     * @throws \rest\classes\UnauthorizedException
     * @throws \rest\classes\HttpException
     */
    public function setup() {
    }

    /**
     * 销毁.
     */
    public function tearDown() {
    }

    /**
     * 返回错误信息.
     *
     * @param int|string  $code
     * @param string|null $message
     *
     * @throws \rest\classes\RestException
     */
    protected final function error($code, $message = null) {
        if (empty($message) && is_string($code)) {
            $msg = explode('@', $code);
            if (count($msg) >= 2) {
                $message = $msg[1];
                $code    = intval($msg[0]);
            } else {
                $message = $code;
                $code    = 500;
            }
        } else if (empty($message)) {
            $message = '内部错误';
        }
        throw new RestException($message, $code);
    }

    /**
     * 未登录异常.
     *
     * @throws \rest\classes\UnauthorizedException
     */
    protected final function unauthorized($message = 'login') {
        throw new UnauthorizedException($message, 401);
    }
}