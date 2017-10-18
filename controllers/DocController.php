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

use dashboard\classes\BackendController;
use Michelf\MarkdownExtra;
use rest\classes\API;
use rest\classes\RestFulClient;
use rest\classes\SandboxForm;
use wula\ui\classes\BootstrapFormRender;
use wulaphp\app\App;
use wulaphp\io\Ajax;
use wulaphp\util\Annotation;
use wulaphp\validator\JQueryValidatorController;

/**
 * Class DocController
 * @package rest\controllers
 * @acl     m:api
 */
class DocController extends BackendController {
	use JQueryValidatorController;

	public function index() {
		return view();
	}

	public function doc($type) {
		$data = [];
		$file = $this->module->getPath($type . '.md');
		if (is_file($file)) {
			$content     = file_get_contents($file);
			$content     = str_replace(['$app_url$'], [App::url('~') . App::hash('~rest/apps')], $content);
			$data['doc'] = MarkdownExtra::defaultTransform($content);
		}

		return view($data);
	}

	public function view($v, $api, $method) {
		$apis = explode('.', $api);
		if (count($apis) != 3) {
			return Ajax::fatal('API不合法');
		}
		$namesapce = $apis[0];
		$module    = App::getModuleById($namesapce);
		if (!$module) {
			return Ajax::fatal('模块不存在');
		}
		$cls  = ucfirst($apis[1]) . 'Api';
		$cls  = $namesapce . '\\api\\' . $v . '\\' . $cls;
		$data = [];
		if (class_exists($cls) && is_subclass_of($cls, API::class)) {
			/**@var API $clz */
			$clz = new $cls(null, null);
			$ref = new \ReflectionObject($clz);

			if ($method == 'post') {
				$m     = $ref->getMethod($apis[2] . 'Post');
				$label = '<span class="label bg-success">POST</span> `' . $api . '`';
			} else {
				$m     = $ref->getMethod($apis[2]);
				$label = '<span class="label bg-info">GET</span> `' . $api . '`';
			}
			if (!$m) {
				return Ajax::fatal('API不存在');
			}
			$ann  = new Annotation($m);
			$sess = $ann->has('session');

			$markdown[] = ($sess ? '<span class="label bg-warning">SESSION</span> ' : '') . $label;

			$apiName    = $ann->getString('apiName', $api);
			$markdown[] = "\n## " . $apiName;

			$markdown[] = $ann->getDoc();

			$markdown[] = "\n### 请求参数\n";
			$args       = [];
			foreach ($m->getParameters() as $p) {
				if ($p->isOptional()) {
					$args[ $p->getName() ] = $p->getDefaultValue();
				}
			}
			$params = $ann->getMultiValues('param');
			$inputs = [];
			if ($params) {
				$markdown[] = '|名称|类型|是否必须|默认值|示例值|描述|';
				$markdown[] = '|:---|:---:|:---:|:---:|:---|:---|';
				foreach ($params as $param) {
					if (preg_match('/([^\s]+)\s+\$([^\s]+)(\s+(\((?P<req>required,?)?(\s*sample=(?P<sample>.+?))?\)\s*)?(?P<desc>.*))?/', $param, $ms)) {
						$ms[1]            = ucfirst($ms[1]);
						$req              = $ms['req'] != '' ? 'Y' : 'N';
						$sample           = $ms['sample'];
						$dv               = isset($args[ $ms[2] ]) ? $args[ $ms[2] ] : '';
						$markdown[]       = "|{$ms[2]}|{$ms[1]}|{$req}|$dv|{$sample}|{$ms['desc']}|";
						$inputs[ $ms[2] ] = [$ms[1], $req, $dv, $sample, $ms['desc']];
					}
				}
			} else {
				$markdown[] = '无';
			}
			$markdown         = implode("\n", $markdown);
			$data['document'] = MarkdownExtra::defaultTransform($markdown);

			//返回数据
			$rtnData    = ['## 响应示例'];
			$rtnData [] = '```json';
			$rtn        = $ann->getString('return');
			if (preg_match('/^(.+?)\s+(.+)$/', $rtn, $ms)) {
				$rtn = $ms[2];
				$rtn = json_encode(@json_decode('{"response":' . $rtn . '}', true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			} else {
				$rtn = '{}';
			}
			$rtnData [] = $rtn;
			$rtnData [] = '```';

			//错误代码
			$errors = $ann->getMultiValues('error');
			if ($errors) {
				$rtnData [] = "\n## 异常示例\n";
				$rtnData [] = '```json';
				$rtnData [] = json_encode(json_decode('{"error":{"code":405,"msg":"非法请求"}}', true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
				$rtnData [] = '```';
				$rtnData[]  = "\n### 异常代码";
				$rtnData[]  = '|代码|描述|';
				$rtnData[]  = '|:---|:---|';
				foreach ($errors as $error) {
					$ers       = explode('=>', trim($error));
					$rtnData[] = "|{$ers[0]}|{$ers[1]}|";
				}
			}
			$data['return']  = MarkdownExtra::defaultTransform(implode("\n", $rtnData));
			$data['version'] = substr($v, 1);
			$form            = $this->createSandboxForm($inputs);

			$form->inflateByData([
				'api_host'   => sess_get('rest.host'),
				'app_key'    => sess_get('rest.appkey'),
				'app_secret' => sess_get('rest.appsecret'),
				'session'    => sess_get('rest.session')
			]);

			$data['form']   = BootstrapFormRender::h($form, [
				'label-col' => 'col-xs-12 col-md-2',
				'field-col' => 'col-xs-12 col-md-10'
			]);
			$data['rule']   = $form->encodeValidatorRule($this);
			$data['api']    = $api;
			$data['method'] = $method;
			$data['params'] = implode(',', array_keys($inputs));
		}

		return view($data);
	}

	public function test() {
		$ver                        = rqst('v');
		$api                        = rqst('api');
		$method                     = rqst('_method');
		$params                     = rqst('_params');
		$url                        = rqst('api_host');
		$app_key                    = rqst('app_key');
		$app_secret                 = rqst('app_secret');
		$session                    = rqst('session');
		$_SESSION['rest.host']      = $url;
		$_SESSION['rest.appkey']    = $app_key;
		$_SESSION['rest.appsecret'] = $app_secret;
		if ($params) {
			$params = explode(',', $params);
			$data   = rqsts($params);
		}

		$data['sign_method'] = 'hmac';
		$data['format']      = 'json';
		$data['timestamp']   = gmdate('Y-m-d H:i:s') . ' GMT';
		if ($session) {
			$data['session']          = $session;
			$_SESSION['rest.session'] = $session;
		}
		$rest = new RestFulClient($url, $app_key, $app_secret, $ver, 30);
		if ($method == 'post') {
			$rest->post($api, $data);
		} else {
			$rest->get($api, $data);
		}
		$rst = $rest->getReturn();
		$rst = '<pre><code class="json">' . json_encode($rst, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</code></pre>';

		return Ajax::update('#api-test-result', $rst, '数据已经提交');
	}

	public function dic($id = '') {
		$data = [];
		if ($id) {
			$ids = explode('/', $id);
			if (count($ids) == 1) {
				$module = App::getModuleById($id);
				if ($module) {
					$path = $module->getPath() . DS . 'api';
					$dir  = new \DirectoryIterator($path);
					foreach ($dir as $d) {
						if ($d->isDot()) continue;
						$ns     = $d->getFilename();
						$data[] = [
							'id'       => $id . '/' . $ns,
							'name'     => $ns,
							'isParent' => true
						];
					}
				}
			} else if (count($ids) == 2) {
				$idx    = $ids[0];
				$module = App::getModuleById($idx);
				if ($module) {
					$path = $module->getPath() . DS . 'api' . DS . $ids[1];
					$dir  = new \DirectoryIterator($path);
					foreach ($dir as $d) {
						if ($d->isDot()) continue;
						$ns   = strstr($d->getFilename(), '.', true);
						$cls  = $idx . '\\api\\' . $ids[1] . '\\' . $ns;
						$apic = [];
						if (class_exists($cls) && is_subclass_of($cls, API::class)) {
							$clz              = new $cls(null, null);
							$ref              = new \ReflectionObject($clz);
							$cna              = new Annotation($ref);
							$apic['id']       = $id . '/' . $ns;
							$apic['name']     = $cna->getString('name', preg_replace('/Api$/', '', $ns));
							$apic['isParent'] = true;
							$methods          = $ref->getMethods();
							$rname            = lcfirst(preg_replace('/Api$/', '', $ref->getShortName()));
							$children         = [];
							foreach ($methods as $method) {
								$name = $method->getName();
								$mna  = new Annotation($method);
								if (preg_match('/^(__.+|setup|tearDown)$/', $name)) continue;
								$api        = $ids[0] . '.' . $rname . '.' . preg_replace('/Post$/', '', $name);
								$children[] = [
									'id'   => $api,
									'name' => $mna->getString('apiName', $api),
									'v'    => $ids[1],
									'post' => preg_match('/^.+Post$/', $name) ? 'post' : 'get'
								];
							}
							$apic['children'] = $children;
						}
						$data[] = $apic;
					}
				}
			}
		} else {
			$data[]  = ['id' => '_intro', 'name' => 'API接入详解', 'type' => 'intro'];
			$data[]  = ['id' => '_errCode', 'name' => '全局错误代码', 'type' => 'errCode'];
			$data[]  = ['id' => '_impl', 'name' => 'API开发详解', 'type' => 'impl'];
			$modules = App::modules('installed');
			foreach ($modules as $mid => $module) {
				$path = $module->getPath() . DS . 'api';
				if (is_dir($path)) {
					$data[] = [
						'id'       => $mid,
						'name'     => $module->getName(),
						'isParent' => true
					];
				}
			}
		}

		return $data;
	}

	public function createSandboxForm($inputs) {
		$form = new SandboxForm();
		if ($inputs) {
			$form->setInputs($inputs);
		}

		return $form;
	}
}