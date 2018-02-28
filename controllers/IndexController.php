<?php

namespace rest\controllers;

use rest\classes\API;
use rest\classes\LocalSecretChecker;
use rest\classes\RestException;
use rest\classes\RestFulClient;
use wulaphp\app\App;
use wulaphp\io\Request;
use wulaphp\io\Session;
use wulaphp\mvc\controller\Controller;
use wulaphp\mvc\view\XmlView;

/**
 * 默认控制器.
 */
class IndexController extends Controller {
	private $api;

	/**
	 * 默认控制方法.
	 *
	 *
	 * @return string
	 */
	public function index() {
		$format = rqst('format', 'json');
		if (!$format) {
			$format = 'json';
		}
		//离线检测
		if (App::bcfg('offline')) {
			$ips = trim(App::cfg('allowedIp'));
			$msg = App::cfg('offlineMsg', 'Service Unavailable');
			if (empty($ips)) {
				return $this->generateResult($format, ['error' => ['code' => 503, 'msg' => $msg]], false);
			}
			$ips = explode("\n", $ips);
			if (!in_array(Request::getIp(), $ips)) {
				return $this->generateResult($format, ['error' => ['code' => 503, 'msg' => $msg]], false);
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
			return $this->generateResult($format, ['error' => ['code' => 406, 'msg' => '非法请求']]);
		}
		//时间检测结束
		$v   = irqst('v', 1);
		$api = rqst('api');//API
		if (empty($api)) {
			return $this->generateResult($format, ['error' => ['code' => 10, 'msg' => '缺少api参数']]);
		}
		$app_key = rqst('app_key');//APPKEY
		if (empty($app_key)) {
			return $this->generateResult($format, ['error' => ['code' => 19, 'msg' => '缺少app_key']]);
		}
		$apis = explode('.', $api);
		if (count($apis) != 3) {
			return $this->generateResult($format, ['error' => ['code' => 11, 'msg' => 'API不存在']]);
		}
		$namesapce = $apis[0];
		$module    = App::getModuleById($namesapce);
		if (!$module) {
			return $this->generateResult($format, ['error' => ['code' => 12, 'msg' => 'API不存在']]);
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
				return $this->generateResult($format, ['error' => ['code' => 14, 'msg' => 'API不存在']]);
			}
			$params = [];
			$ps     = $m->getParameters();
			/**@var \ReflectionParameter $p */
			foreach ($ps as $p) {
				$name = $p->getName();
				if (rqset($name)) {
					$params[ $name ] = rqst($name);
				} else if ($p->isOptional()) {
					$params[ $name ] = $p->getDefaultValue();
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
			$args = array_merge($params, compact([
				'v',
				'app_key',
				'api',
				'timestamp',
				'sign_method'
			]));
			if (rqset('format')) {
				$args['format'] = $format;
			}
			$session = rqst('session');
			if (rqset('session')) {
				$args['session'] = $session;
			}
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

			if ($session) {//启动了session
				//要指定session超时
				define('REST_SESSION_ID', $session);
				(new Session())->start($session);
			}
			try {
				$this->api = $api;
				fire('rest\callApi', $api, $ctime, $args);
				$clz->setup();
				$rtn = $m->invokeArgs($clz, $params);

				return $this->generateResult($format, $rtn);
			} catch (RestException $re) {
				return $this->generateResult($format, [
					'error' => [
						'code' => $re->getCode(),
						'msg'  => $re->getMessage()
					]
				]);
			} catch (\Exception $e) {
				log_error('[' . $api . '] failed! ' . $e->getMessage() . "\n" . var_export($params, true), 'api');

				return $this->generateResult($format, ['error' => ['code' => 500, 'msg' => '内部错误']]);
			} finally {
				$clz->tearDown();
				fire('rest\endApi', $api, time(), $args);
			}
		}

		return $this->generateResult($format, ['error' => ['code' => 13, 'msg' => 'API不存在']]);
	}

	/**
	 * 生成返回结果.
	 *
	 * @param string $format
	 * @param array  $data
	 * @param bool   $trigger
	 *
	 * @return \wulaphp\mvc\view\XmlView|array
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
			return ['response' => $data];
		} else {
			return new XmlView($data, 'response');
		}
	}
}