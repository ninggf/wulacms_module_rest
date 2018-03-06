## API开发详解

RESTFul模块提供了一个简单的，所见即所得的API机制。开发人员只需要按约定编写代码与注释即可完成API的开发与文档的编写。

### API名称约定
以API`helloworld.greeting.sayHello`为例。API名称被`.`分成了三段：

* 第一段:`helloworld`, 模块的命名空间,表示此API属于`helloworld`模块.
* 第二段:`greeting`, API是由`GreetingApi`类实现的.
* 第三段:`sayHello`, `SessionApi`类的`sayHello`方法即API的具体实现.

### 代码实现
RESTFul中每个API类都是`rest\classes\API`类的子类且都有版本号，RESTFul约定版本号为正整数。API实现类文件存放在`api`目录下对应的版本目录中.

以API`helloworld.greeting.sayHello`开发为例:

1. 在__helloworld__模块目录(一般为`helloworld`)下新建目录`api`(与`controllers`同级):
<pre>
helloworld
|-- api
|-- controllers
</pre>

2. 根据约定在`api`目录下新建文件`v1/GreetingApi.php`:
<pre>
helloworld
|-- api
    |-- v1
        |-- GreetingApi.php 
</pre>

> * `v1` 目录即1版本API类存放目录.
> * `GreetingApi.php` 即API类.

3. 在`GreetingApi.php`定义`GreetingApi`类并实现sayHello方法:

```php
namespace helloworld\api\v1;

use rest\classes\API;

/**
 * @name 打招呼
 */
class GreetingApi extends API {
	/**
	 * 打招呼
	 *
	 * @apiName 打招呼
	 *
	 * @param string $name (required) 姓名
	 *
	 * @return array {
	 *      "hello":"姓名"
	 * }
	 */
	public function sayHello($name) {
		return ['hello' => $name];
	}
}
```

> * 如需使用`POST`方法调用`helloworld.greeting.sayHello`则将类中`sayHello`方法重命名为`sayHelloPost`即可。
> * 请注意此类的命名空间，切不可写错喽。

### 文档注释说明
RESTFul可以通过类或方法的`doc comment`中的注解自动生成API文档和测试沙盒, RESTFul会使用的以下注解和它们要遵守的格式:

|注释|位置|唯一|示例|说明|
|---|:---:|:---:|---|---|
|name|类|是|@name 会话管理|API实现类名|
|apiName|方法|是|@apiName 开启会话|API名称|
|session|方法|是|@session|API需要SESSION支持|
|param|方法|否|@param string $name 姓名|参数定义，支持多种格式，详见下文|
|paramo|方法|否|@paramo string abc 输出数据描述|输出数据定义，支持多种格式，详见下文|
|error|方法|否|@error 200=>出错啦|定义此API可能出现的错误信息|
|return|方法|是|@return array {"id":"用户ID"}|返回信息定义，必须是合法的JSON格式|

> `return` 注解格式为`@return array {合法的返回值示例JSON}`

__param输入参数注解格式：__

1. `@param string $name` 只定义参数名与类型
2. `@param string $name 姓名` 定义参数名，类型与描述
3. `@param string $name (required) 姓名` 定义参数名，类型，描述且说明此参数必须
4. `@param object $info (sample={"age":"int","name":"string"}) 信息` 定义参数名，类型，描述，且给出示例值
5. `@param object $info (required,sample={}) 信息` 参数名，类型，描述，必须且出给示例值
    * 当参数类型为`object`时，`sample`(示例值)必须提供.

__paramo输出数据注解格式：__

1. `@paramo string name` 只定义数据名与类型
2. `@paramo string .key` 表示`key`是它一条数据的子项数据，用于Object类型的输出.

