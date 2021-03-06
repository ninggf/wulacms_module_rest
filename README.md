此模块提供RESTFul风格的接口，客户端可以通过HTTP协议进行访问，详见[接口文档](#rest/doc)。


## 模块提供的触发器
触发器的处理器匀应快速返回，不适合做耗时处理。

### 1. rest\startCall[int $time, string $format]
开始请求时触发.

__参数:__

* $time 时间戳 
* $format 请求格式

> 如果要终止后续执行，可以在处理器中直接终止。

### 2. rest\callApi[string $api, int $time, array $args]
开始调用api时触发.

__参数:__

* $api 要调用的api
* $time 时间戳
* $args 调用api的参数

> 如果要终止后续执行，可以在处理器中抛出`rest\classes\RestException`。

### 3. rest\endApi[string $api, int $time, array $args]
api调用结束时触发.

__参数:__

* $api 要调用的api
* $time 时间戳
* $args 调用api的参数

### 4. rest\errApi[string $api, int $time, array $result]
api调用出错时触发.

__参数:__

* $api 要调用的api
* $time 时间戳
* $result 返回值

### 5. rest\callError[int $time, array $result]
调用出错时触发

__参数:__

* $time 时间戳
* $result 返回值

### 6. rest\endCall[int $time, array $result]
调用结束时触发

__参数:__

* $time 时间戳
* $result 返回值

> 以上触发器的处理器匀应快速返回，不适合做耗时处理。

## 配置

`conf/rest_config.php`是其配置文件:

```php
<?php
return [
	'dev'=>env('rest.dev',false),
	'domain'=>env('rest.domain',''),
	'expire'=>env('rest.expire',300),
	'store'=>env('rest.store'),
	'zipalign'=>env('rest.zipalign'),
	'download'=>env('rest.download')
];
```
**说明如下:**

1. `dev` 开发模式，不验签.
2. `domain`限定访问域名,接口调用只能通过此域名.
3. `expire`会话过期时间.
4. `store` 软件包存储路径
5. `zipalign` **zipalign**命令路径(用于压缩apk文件)
6. `download` 下载主机的URL.

## 云端配置

通过勾子`rest\classes\form\AppCfgForm::onParseFields`向系统注册云控项，具体可以参考`FormTable::addField()`。
