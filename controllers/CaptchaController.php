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

use backend\classes\CaptchaCode;
use wulaphp\app\App;
use wulaphp\io\Response;
use wulaphp\io\Session;
use wulaphp\mvc\controller\Controller;

class CaptchaController extends Controller {
	public function beforeRun($action, $refMethod) {
		$domain = App::cfg('rest_domain');
		if ($domain && $_SERVER['HTTP_HOST'] != $domain) {
			Response::respond(403);
		}
		$view = parent::beforeRun($action, $refMethod);

		return $view;
	}

	public function index($sid, $file = '60x20.15.gif') {
		if (empty($sid)) {
			Response::respond(403);
		}
		Response::nocache();
		(new Session())->start($sid);
		$args = explode('.', $file);
		$len  = count($args);
		switch ($len) {
			case 0:
				$args[0] = '90x30';
				$args[1] = '15';
				$args[2] = 'gif';
				break;
			case 1:
				$args[1] = '15';
				$args[2] = 'gif';
				break;
			case 2:
			default:
				$args[1] = intval($args[2]);
				$args[2] = 'gif';
		}
		$size = $args[0];
		$font = $args[1];
		$type = $args[2];
		$size = explode('x', $size);
		if (count($size) == 1) {
			$width  = intval($size [0]);
			$height = $width * 3 / 4;
		} else if (count($size) >= 2) {
			$width  = intval($size [0]);
			$height = intval($size [1]);
		} else {
			$width  = 60;
			$height = 20;
		}
		$font          = intval($font);
		$font          = max([18, $font]);
		$type          = in_array($type, ['gif', 'png']) ? $type : 'png';
		$auth_code_obj = new CaptchaCode ();
		// 定义验证码信息
		$arr ['code'] = ['characters' => 'A-H,J-K,M-N,P-Z,3-9', 'length' => 4, 'deflect' => true, 'multicolor' => true];
		$auth_code_obj->setCode($arr ['code']);
		// 定义干扰信息
		$arr ['molestation'] = ['type' => 'both', 'density' => 'normal'];
		$auth_code_obj->setMolestation($arr ['molestation']);
		// 定义图像信息. 设置图象类型请确认您的服务器是否支持您需要的类型
		$arr ['image'] = ['type' => $type, 'width' => $width, 'height' => $height];
		$auth_code_obj->setImage($arr ['image']);
		// 定义字体信息
		$arr ['font'] = ['space' => 5, 'size' => $font, 'left' => 5];
		$auth_code_obj->setFont($arr ['font']);
		// 定义背景色
		$arr ['bg'] = ['r' => 255, 'g' => 255, 'b' => 255];
		$auth_code_obj->setBgColor($arr ['bg']);
		$auth_code_obj->paint();
		Response::getInstance()->close(true);
	}
}