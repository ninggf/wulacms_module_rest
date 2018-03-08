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
use wulaphp\mvc\view\XmlView;

/**
 * 默认控制器.
 */
class IndexController extends Controller {
	private $api;
	private $format;
	/**
	 * @var \wulaphp\conf\Configuration
	 */
	private $cfg;

	public function beforeRun($action, $refMethod) {
		$this->cfg = ConfigurationLoader::loadFromFile('rest');
		$domain    = $this->cfg->get('domain');
		if ($domain && $_SERVER['HTTP_HOST'] != $domain) {
			$this->httpout(403);
		}
		$this->format = rqst('format', 'json');
		if (!$this->format) {
			$this->format = 'json';
		}

		return parent::beforeRun($action, $refMethod);
	}

	/**
	 * 默认控制方法.
	 *
	 *
	 * @return string|mixed
	 * @throws
	 */
	public function index() {
		$format = $this->format;
		//离线检测
		if (App::bcfg('offline')) {
			$ips = trim(App::cfg('allowedIp'));
			$msg = App::cfg('offlineMsg', 'Service Unavailable');
			if (empty($ips)) {
				$this->httpout(503, $msg);
			}
			$ips = explode("\n", $ips);
			if (!in_array(Request::getIp(), $ips)) {
				$this->httpout(503, $msg);
			}
		}
		fire('rest\startCall', time(), $format);
		$rtime     = 0;
		$timestamp = rqst('timestamp');
		//时间检测，时差正负5分钟
		if ($timestamp && preg_match('/^20\d\d-(1[0-2]|0[1-9])-([0-2][1-9]|3[01])\s([01][0-9]|2[0-3]):([0-5]\d):([0-5]\d)(\sGMT)?$/', $timestamp)) {
			$timestampx = @strtotime($timestamp);
			if ($timestampx !== false) {
				$rtime = $timestampx;
			}
		}
		$ctime = time();
		if (($rtime + 300) < $ctime || $rtime - 300 > $ctime) {
			$this->httpout(406);//非法请求
		}
		//时间检测结束
		$v   = irqst('v', 1);
		$api = rqst('api');//API
		if (empty($api)) {
			$this->httpout(400);
		}
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
			$this->httpout(404);
		}
		$cls = ucfirst($apis[1]) . 'Api';
		$cls = $namesapce . '\\api\\v' . $v . '\\' . $cls;
		if (class_exists($cls) && is_subclass_of($cls, API::class)) {
			/**@var API $clz */
			$clz      = new $cls($app_key, $v);
			$ann      = new \ReflectionObject($clz);
			$rqMethod = strtolower($_SERVER ['REQUEST_METHOD']);
			$rm       = ucfirst($rqMethod);
			if ($rm == 'Post') {
				$m = $ann->getMethod($apis[2] . 'Post');
			} else {
				$m = $ann->getMethod($apis[2]);
			}
			if (!$m) {
				$this->httpout(405, '不支持的请求方法');
			}
			$params  = [];//请求参数用于签名
			$dparams = [];//调用参数
			$ps      = $m->getParameters();
			/**@var \ReflectionParameter $p */
			foreach ($ps as $p) {
				$name = $p->getName();
				if (rqset($name)) {
					$dparams[ $name ] = $params[ $name ] = rqst($name);
				} else if ($p->isOptional()) {
					$dparams[ $name ] = $p->getDefaultValue();
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
			if (rqset('session') && $session) {
				$args['session'] = $session;
			}
			//开发模式
			$dev = $this->cfg->getb('dev', false);
			if (!$dev) {
				//验签
				$sign1 = RestFulClient::chucksum($args, $appSecret);
				if ($sign !== $sign1) {
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
				$this->api = $api;
				fire('rest\callApi', $api, $ctime, $args);
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
				$this->httpout(401);
			} catch (\Exception $e) {
				log_error('[' . $api . '] failed! ' . $e->getMessage() . "\n" . var_export($dparams, true), 'api');

				return $this->generateResult($format, ['error' => ['code' => 20, 'msg' => $e->getMessage()]]);
			} finally {
				$clz->tearDown();
				fire('rest\endApi', $api, time(), $args);
			}
		}

		$this->httpout(501);

		return null;
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
	private function generateResult($format, $data, $trigger = true) {
		$etime = time();
		if ($trigger) {
			if (isset($data['error'])) {
				if ($this->api) {
					fire('rest\errApi', $this->api, $etime, $data);
				}
				fire('rest\callError', $etime, $data);
			}
			fire('rest\endCall', $etime, $data);
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
		status_header($status);
		if ($message) {
			echo $message;
		}
		exit();
	}
}