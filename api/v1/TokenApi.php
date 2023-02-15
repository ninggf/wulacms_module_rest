<?php
/**
 * Created by PhpStorm.
 * User: hwt
 * Date: 2020/8/27
 * Time: 10:14
 */

namespace rest\api\v1;

use rest\classes\API;
use wulaphp\app\App;
use wulaphp\util\RedisClient;

class TokenApi extends API {

    /**
     * 刷新token
     *
     * @apiName 刷新token
     *
     * @param string $refresh_token (required) refresh_token
     * @param string $token         (required) token
     * @param string $device        (required) 设备类型
     *
     * @paramo  string refresh_token 新refresh_token
     * @paramo  string token 新token
     * @paramo  int  token_expire token过期时间
     * @paramo  int  refresh_expire refresh_token过期时间
     *
     * @error   403=>错误的设备类型
     * @error   500=>内部错误
     *
     * @return array {
     *  "refresh_token":"fc32720cd6cb0f7c6776276cc7882fb2"
     *  "token":"48f7081aaa777ae9fc8732a09f568d23"
     * }
     * @noauth
     * @throws
     */
    public function refreshToken(string $refresh_token, string $token, string $device = '') {
        if (empty($token)) {
            $this->error(403, 'TOKEN为空');
        }
        $device = trim($device);
        if (!ClientApi::checkDevice($device)) {
            $this->error(403, '客户端来源异常');
        }

        $redis = RedisClient::getRedis(App::icfg('redisdb@passport', 10));

        $uid = $redis->get($token);//获取用户信息
        if (!$uid) {
            $this->unauthorized();
        }
        $info = $redis->get('u@' . $uid);
        if (!$info) {
            $this->unauthorized();
        }
        $info = @json_decode($info, true);

        if (!$info || $uid != $info['uid'] || $info['token'] != $token || $info['refresh_token'] != $refresh_token || $info['refresh_expire'] < time()) {
            $this->unauthorized();//请登录
        }

        $redis->del($token);

        //生成新的token
        $new_token         = md5(uniqid() . $device . $uid);
        $new_refresh_token = md5(uniqid() . $device . $new_token);

        $expire         = App::icfgn('expire@passport', 120) * 60;//分钟
        $refresh_expire = App::icfgn('refresh_expire@passport', 30) * 86400;//天

        //获取信息

        $time                   = time();
        $info['token']          = $new_token;
        $info['refresh_token']  = $new_refresh_token;
        $info['token_expire']   = $time + $expire;
        $info['refresh_expire'] = $time + $refresh_expire;
        $info_json              = json_encode($info);

        $re = $redis->setex($new_token, $refresh_expire ?: 2592000, $uid);
        if ($re) {
            $re = $redis->set('u@' . $uid, $info_json);
            if (!$re) {
                $this->unauthorized();
            }
        }

        $db   = App::db();
        $time = time();
        $db->update('{oauth_session}')->set([
            'expiration'    => $time + $expire,
            'token'         => $new_token,
            'refresh_token' => $new_refresh_token
        ])->where(['token' => $token])->exec(true);

        return [
            'refresh_token'  => $new_refresh_token,
            'token'          => $new_token,
            'token_expire'   => $time + $expire,
            'refresh_expire' => $time + $refresh_expire
        ];
    }
}