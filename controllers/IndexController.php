<?php

namespace rest\controllers;

use rest\classes\API;
use rest\classes\HttpException;
use rest\classes\LocalSecretChecker;
use rest\classes\RestException;
use rest\classes\RestFulClient;
use rest\classes\UnauthorizedException;
use wulaphp\app\App;
use wulaphp\conf\ConfigurationLoader;
use wulaphp\io\Request;
use wulaphp\io\Session;
use wulaphp\mvc\controller\Controller;
use wulaphp\mvc\view\JsonView;
use wulaphp\mvc\view\SimpleView;
use wulaphp\mvc\view\XmlView;
use wulaphp\router\Router;

/**
 * 默认控制器.
 */
class IndexController extends Controller {
    private $api;
    private $format;
    private $rqMehtod;
    private $clz;
    /**
     * @var \wulaphp\conf\Configuration
     */
    private $cfg;

    public function beforeRun($action, $refMethod) {
        $this->cfg = ConfigurationLoader::loadFromFile('rest');
        $domain    = $this->cfg->get('domain');
        $this->api = Router::getRouter()->getParam(0);
        try {
            fire('rest\startCall', $this->api, microtime(true));
        } catch (\Exception $e) {
        }
        if ($domain && $_SERVER['HTTP_HOST'] != $domain) {
            $this->httpout(403);
        }

        $rqMethod       = strtolower($_SERVER ['REQUEST_METHOD']);
        $this->rqMehtod = ucfirst($rqMethod);
        if ($rqMethod == 'post') {
            // supports content-type = 'application/json'
            Request::getInstance()->addJsonPostBody();
        }
        $this->format = rqst('format', 'json');
        if (!$this->format) {
            $this->format = 'json';
        }
        $cors = (array)$this->cfg->get('cors');
        if ($cors) {
            if ($rqMethod == 'options') {
                @header('Access-Control-Allow-Origin:' . $cors['Access-Control-Allow-Origin']);
                @header('Access-Control-Allow-Methods: ' . $cors['Access-Control-Allow-Methods'] . ', OPTIONS');
                @header('Access-Control-Allow-Headers: ' . $cors['Access-Control-Allow-Headers']);
                @header('Access-Control-Max-Age: ' . $cors['Access-Control-Max-Age'] ?? 604800);
            } else {
                foreach ($cors as $cor => $v) {
                    @header($cor . ': ' . $v);
                }
            }
        }

        if ($rqMethod == 'options') {
            return new SimpleView('');
        }

        return parent::beforeRun($action, $refMethod);
    }

    /**
     * 默认控制方法.
     *
     * @param string $api api
     *
     * @return string|mixed
     * @throws
     */
    public function index(string $api) {
        $format    = $this->format;
        $rtime     = 0;
        $timestamp = rqst('timestamp');
        //时间检测，时差正负5分钟
        if ($timestamp && preg_match('/^20\d\d-(1[0-2]|0[1-9])-(0[1-9]|[12]\d|3[01])\s([01][0-9]|2[0-3]):([0-5]\d):([0-5]\d)(\sGMT)?$/', $timestamp)) {
            $timestampx = @strtotime($timestamp);
            if ($timestampx !== false) {
                $rtime = $timestampx;
            }
        }
        $ctime = time();
        if (($rtime + 300) < $ctime || $rtime - 300 > $ctime) {
            log_info('incorrect time: '.$timestamp, 'api');
            $this->httpout(406);//非法请求
        }
        //时间检测结束
        $v       = irqst('v', 1);
        $app_key = rqst('app_key');//APPKEY
        if (empty($app_key)) {
            return $this->generateResult($format, ['error' => ['code' => 19, 'msg' => '缺少app_key']]);
        }
        $apis = explode('.', $api);
        if (count($apis) != 3) {
            $this->httpout(416, 'API格式不正确');
        }
        $namesapce = $apis[0];
        $module    = App::getModuleById($namesapce);
        if (!$module) {
            $this->httpout(404, 'module not found');
        }
        $cls = ucfirst($apis[1]) . 'Api';
        $cls = $namesapce . '\\api\\v' . $v . '\\' . $cls;
        if (class_exists($cls) && is_subclass_of($cls, API::class)) {
            /**@var API $clz */
            $this->clz = $clz = new $cls($app_key, $v);
            $ann       = new \ReflectionObject($clz);
            $rm        = $this->rqMehtod;
            try {
                if ($rm == 'Post') {
                    $m = $ann->getMethod($apis[2] . 'Post');
                } else {
                    $m = $ann->getMethod($apis[2]);
                }
            } catch (\Exception $mre) {
                try {
                    if ($rm == 'Post') {
                        $tmp = $ann->getMethod($apis[2]);
                        if ($tmp) {
                            $this->httpout(405, '不支持的请求方法');
                        }
                    } else {
                        $tmp = $ann->getMethod($apis[2] . 'Post');
                        if ($tmp) {
                            $this->httpout(405, '不支持的请求方法');
                        }
                    }
                } catch (\Exception $e) {

                }
                $m = false;
            }
            if (!$m) {
                $this->httpout(404, 'api not found');
            }

            $params  = [];//请求参数用于签名
            $dparams = [];//调用参数
            $ps      = $m->getParameters();
            /**@var \ReflectionParameter $p */
            foreach ($ps as $p) {
                $name = $p->getName();
                if ($name == 'token') {
                    //header中是否有token
                    $token = $_SERVER['HTTP_AUTHORIZATION'];
                    if ($token) {
                        $dparams[ $name ] = $params[ $name ] = $token;
                    } else if ($p->isOptional()) {
                        $dparams[ $name ] = $p->getDefaultValue();
                    } else {
                        return $this->generateResult($format, [
                            'error' => [
                                'code' => 15,
                                'msg'  => '缺少' . $name . '参数'
                            ]
                        ]);
                    }
                    continue;
                }
                if (rqset($name)) {
                    $dparams[ $name ] = $params[ $name ] = rqst($name);
                } else if ($p->isOptional()) {
                    $dparams[ $name ] = $p->getDefaultValue();
                } else if (isset($_FILES[ $name ])) {
                    $dparams[ $name ] = $_FILES[ $name ];
                } else {
                    return $this->generateResult($format, ['error' => ['code' => 15, 'msg' => '缺少' . $name . '参数']]);
                }
            }
            $sign_method = rqst('sign_method');
            if ($sign_method != 'md5' && $sign_method != 'sha1' && $sign_method != 'hmac') {
                return $this->generateResult($format, ['error' => ['code' => 16, 'msg' => '不支持的签名方法']]);
            }

            $appSecret = (new LocalSecretChecker())->check($app_key);
            if (!$appSecret) {
                return $this->generateResult($format, ['error' => ['code' => 17, 'msg' => '无效的app_key']]);
            }
            //签名
            $sign = rqst('sign');
            $args = array_merge($params, [
                'v'           => $v,
                'app_key'     => $app_key,
                'api'         => $api,
                'timestamp'   => $timestamp,
                'sign_method' => $sign_method
            ]);
            //响应格式
            if (rqset('format')) {
                $args['format'] = $format;
            }
            //会话
            $session = rqst('session');
            if (rqset('session')) {
                $args['session'] = $session;
            }

            //开发模式
            $dev = $this->cfg->getb('dev', false);
            if (!$dev) {
                //验签
                $sign1 = RestFulClient::chucksum($args, $appSecret, 'sha1', true);
                if ($sign !== $sign1) {
                    log_error($sign . ' != ' . $sign1 . ', args= ' . json_encode($args, JSON_UNESCAPED_UNICODE |
                            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS));

                    return $this->generateResult($format, [
                        'error' => [
                            'code' => 18,
                            'msg'  => '签名错误'
                        ]
                    ]);
                }
            }

            if ($session) {//启动了session
                define('REST_SESSION_ID', $session);
                $expire = $this->cfg->geti('expire', 300);
                (new Session($expire))->start($session);
                $clz->sessionId = $session;
            }

            try {
                $clz->setup();
                $rtn = $m->invokeArgs($clz, $dparams);

                return $this->generateResult($format, $rtn);
            } catch (RestException $re) {
                return $this->generateResult($format, [
                    'error' => [
                        'code' => $re->getCode(),
                        'msg'  => $re->getMessage()
                    ]
                ]);
            } catch (HttpException $he) {
                $this->httpout($he->getCode(), $he->getMessage());
            } catch (UnauthorizedException $un) {
                $this->httpout(401, $un->getMessage());
            } catch (\PDOException $pe) {
                return $this->generateResult($format, ['error' => ['code' => 1026, 'msg' => '内部错误(数据库)']]);
            } catch (\Exception $e) {
                log_error('[' . $api . '] failed! ' . $e->getMessage() . "\n" . var_export($dparams, true), 'api');

                return $this->generateResult($format, ['error' => ['code' => 20, 'msg' => $e->getMessage()]]);
            } finally {
                // 如果调用了 exit() 不会走这儿的
                $clz->tearDown();
            }
        }

        $this->httpout(501);

        return null;
    }

    public function indexPost($api) {
        return $this->index($api);
    }

    /**
     * 生成返回结果.
     *
     * @param string $format
     * @param array  $data
     * @param bool   $trigger
     *
     * @return \wulaphp\mvc\view\View
     */
    private function generateResult(string $format, array $data, bool $trigger = true) {
        $etime = microtime(true);
        if ($trigger) {
            if (isset($data['error'])) {
                try {
                    fire('rest\errApi', $this->api, $etime, $data);
                } catch (\Exception $e) {
                }
            }
            try {
                fire('rest\endCall', $this->api, $etime, $data);
            } catch (\Exception $e) {
            }
        }
        if ($format == 'json') {
            return new JsonView(['response' => $data]);
        } else {
            return new XmlView($data, 'response');
        }
    }

    /**
     * 输出http响应输出。
     *
     * @param string|int $status 状态
     * @param string     $message
     */
    private function httpout($status, $message = '') {
        if ($this->clz) {
            try {
                $this->clz->tearDown();
            } catch (\Exception $e) {
            }
        }
        $etime = microtime(true);
        status_header($status);
        if ($message) {
            echo $message;
        }
        $data['error']['code'] = $status;
        $data['error']['msg']  = $message;
        try {
            fire('rest\errApi', $this->api, $etime, $data);
            fire('rest\endCall', $this->api, $etime, $data);
        } catch (\Exception $e) {
        }
        exit();
    }
}