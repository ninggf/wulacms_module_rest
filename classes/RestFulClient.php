<?php

namespace rest\classes;

class RestFulClient {
	private $url;
	private $ver;
	private $appSecret;
	private $appKey;
	private $curl;
	private $timeout = 30;

	/**
	 * 构建一个RESTful Client 实例.
	 *
	 * @param string $url
	 *            the entry of the RESTful Server。
	 * @param string $appKey
	 *            app key.
	 * @param string $appSecret
	 *            app secret.
	 * @param string $ver
	 *            version of the API.
	 * @param int    $timeout
	 *            timeout.
	 */
	public function __construct($url, $appKey, $appSecret, $ver = '1', $timeout = 30) {
		$this->url       = $url;
		$this->appKey    = $appKey;
		$this->appSecret = $appSecret;
		$this->ver       = intval($ver) . '.0';
		$this->timeout   = intval($timeout);
	}

	/**
	 * 析构.
	 */
	public function __destruct() {
		if ($this->curl) {
			curl_close($this->curl);
			$this->curl = null;
		}
	}

	/**
	 * 使用get方法调用接口API.
	 *
	 * @param string $api    接口.
	 * @param array  $params 参数.
	 * @param int    $timeout
	 *
	 * @return \rest\classes\RestFulClient 接口的返回值.
	 */
	public function get($api, $params = [], $timeout = null) {
		$this->prepare($params, $api);
		curl_setopt($this->curl, CURLOPT_URL, $this->url . '?' . http_build_query($params));
		curl_setopt($this->curl, CURLOPT_HTTPGET, 1);
		curl_setopt($this->curl, CURLOPT_UPLOAD, false);
		if (is_numeric($timeout)) {
			$this->timeout = $timeout;
			curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);
		}

		return $this;
	}

	/**
	 * 批量获取.
	 *
	 * @param array    $apis
	 * @param array    $params
	 * @param null|int $timeout
	 *
	 * @return array
	 */
	public function gets($apis, $params = [], $timeout = null) {
		$clients = [];
		foreach ($apis as $idx => $api) {
			$param           = $params[ $idx ];
			$client          = new self($this->url, $this->appKey, $this->appSecret, $this->ver, $timeout ? $timeout : $this->timeout);
			$clients[ $idx ] = $client->get($api, $param, $timeout);
		}

		return $this->execute($clients);
	}

	/**
	 * 批量提交.
	 *
	 * @param array    $apis
	 * @param array    $params
	 * @param null|int $timeout
	 *
	 * @return array
	 */
	public function posts($apis, $params = [], $timeout = null) {
		$clients = [];
		foreach ($apis as $idx => $api) {
			$param           = $params[ $idx ];
			$client          = new self($this->url, $this->appKey, $this->appSecret, $this->ver, $timeout ? $timeout : $this->timeout);
			$clients[ $idx ] = $client->post($api, $param, $timeout);
		}

		return $this->execute($clients);
	}

	/**
	 * 使用POST方法调用接口API.
	 *
	 * @param string $api
	 *            接口.
	 * @param array  $params
	 *            参数.
	 * @param int    $timeout
	 *
	 * @return \rest\classes\RestFulClient 接口的返回值.
	 */
	public function post($api, $params = [], $timeout = null) {
		$this->prepare($params, $api);
		curl_setopt($this->curl, CURLOPT_URL, $this->url);
		curl_setopt($this->curl, CURLOPT_SAFE_UPLOAD, true);
		$this->preparePostData($params);

		curl_setopt($this->curl, CURLOPT_POST, true);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);

		if (is_numeric($timeout)) {
			$this->timeout = $timeout;
			curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);
		}

		return $this;
	}

	/**
	 * 解析JOSN格式的返回值到array格式.
	 *
	 * @param string $rst
	 *            JSON格式的返回值.
	 *
	 * @return array 结果.
	 */
	public function getReturn($rst = null) {
		if ($rst === null) {
			$rst = curl_exec($this->curl);
			if ($rst === false) {
				log_error(curl_error($this->curl), 'rest.err');
			}
			curl_close($this->curl);
			$this->curl = null;
		}
		if (empty ($rst)) {
			return ['error' => 106, 'message' => __('Internal error.')];
		} else {
			$json = @json_decode($rst, true);
			if ($json) {
				return $json;
			} else {
				return ['error' => 107, 'message' => __('Not supported response format.'), 'data' => $rst];
			}
		}
	}

	/**
	 * 计算请求的CHECKSUM值.
	 *
	 * @param array  $args
	 *            参数.
	 * @param string $appSecret
	 *            app secret.
	 * @param string $type
	 *
	 * @return string CHECKSUM值.
	 */
	public static function chucksum(array $args, $appSecret, $type = 'sha1') {
		$args = self::checkArgs($args);
		self::sortArgs($args);
		$sign = [];
		foreach ($args as $key => $v) {
			if (is_array($v)) {
				foreach ($v as $k => $v1) {
					if ($v1{0} == '@') {
						$sign [] = $key . "[{$k}]=" . self::getfileSha1($v1);
					} else {
						$sign [] = $key . "[{$k}]=" . $v1;
					}
				}
			} else if ($v{0} == '@') {
				$sign [] = $key . "=" . self::getfileSha1($v);
			} else if ($v || is_numeric($v)) {
				$sign [] = $key . "=" . $v;
			} else {
				$sign [] = $key . "=";
			}
		}
		$str = implode('&', $sign) . $appSecret;
		if ($type == 'sha1') {
			return sha1($str);
		} else {
			return md5($str);
		}
	}

	/**
	 * 递归对参数进行排序.
	 *
	 * @param array $args
	 */
	public static function sortArgs(array &$args) {
		ksort($args);
		foreach ($args as $key => $val) {
			if (is_array($val)) {
				ksort($val);
				$args [ $key ] = $val;
				self::sortArgs($val);
			}
		}
	}

	/**
	 * 处理上传的文件参数.
	 *
	 * @param array $args
	 *
	 * @return array mixed
	 */
	private static function checkArgs(array $args) {
		if ($_FILES) {
			foreach ($_FILES as $key => $f) {
				if (is_array($f['name'])) {
					foreach ($f['tmp_name'] as $tmp) {
						$args[ $key ][] = '@"' . $tmp . '"';
					}
				} else {
					$args[ $key ] = '@"' . $f['tmp_name'] . '"';
				}
			}
		}

		return $args;
	}

	/**
	 * @param $value
	 *
	 * @return string
	 */
	private static function getfileSha1($value) {
		$file = trim(substr($value, 1), '"');
		if (is_file($file)) {
			return sha1_file($file);
		} else {
			return 'fnf';
		}
	}

	/**
	 * 处理POST数据,主要处理上传的文件.
	 *
	 * @param array $data
	 */
	private function preparePostData(array &$data) {
		foreach ($data as $key => &$val) {
			if (is_string($val) && $val{0} == '@' && file_exists(trim(substr($val, 1), '"'))) {
				$data [ $key ] = new \CURLFile(realpath(trim(substr($val, 1), '"')));
			} else if (is_array($val)) {
				$this->preparePostData($val);
			}
		}
	}

	/**
	 * 准备连接请求.
	 *
	 * @param array  $params
	 * @param string $api
	 */
	private function prepare(&$params, $api) {
		$this->close();
		$params ['api']    = $api;
		$params ['appkey'] = $this->appKey;
		$params ['ver']    = $this->ver;
		$params ['crc']    = self::chucksum($params, $this->appSecret);
		$this->curl        = curl_init();
		curl_setopt($this->curl, CURLOPT_AUTOREFERER, 1);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, array());
	}

	/**
	 * 并行执行请求。
	 *
	 * @param array $clients
	 *
	 * @return array results for each request.
	 */
	private function execute(array $clients) {
		if ($clients) {
			$mh      = curl_multi_init();
			$handles = [];
			/**@var \rest\classes\RestFulClient $client */
			foreach ($clients as $i => $client) {
				$ch             = $client->curl;
				$handles [ $i ] = ['h' => $ch, 'c' => $client];
				curl_multi_add_handle($mh, $ch);
			}
			$active = null;
			do {
				curl_multi_exec($mh, $active);
				if ($active > 0) {
					usleep(50);
				}
			} while ($active > 0);
			$rsts = [];
			foreach ($handles as $i => $h) {
				/**@var \rest\classes\RestFulClient $client */
				$client      = $h ['c'];
				$rsts [ $i ] = $client->getReturn(curl_multi_getcontent($client->curl));
				curl_multi_remove_handle($mh, $h ['h']);
			}
			curl_multi_close($mh);

			return $rsts;
		}

		return [];
	}
}