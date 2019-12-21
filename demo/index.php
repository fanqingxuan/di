<?php

require_once '../vendor/autoload.php';

use Json\Di;
use Json\Di\InjectionBase;
use Json\Config\Php;
use Json\Config\Ini;

$container = new Di;

class Test {

}

class DB{
	public $host;
	public $user;
	public $pwd;
	public function __construct($host,$user,$pwd) {
		$this->host = $host;
		$this->user = $user;
		$this->pwd = $pwd;
	}
}
//注入的方式
$container['test1'] = new StdClass;

$container->set("test2",function() {
	return new Test;
});

$container->set("test4",Test::class);

$container->setTest3(function(){
	return "test3";
});

//$config = new Php("config/database.php");
$config = new Ini("config/database.ini");

$container['config'] = $config;
$container->set('db_read',function(){
	return new DB(
		$this->get('config')->get('DB_HOST'),
		$this->get('config')->get('DB_USER'),
		$this->get('config')->get('DB_PWD')
	);
});
var_dump($container->get('db_read'));
//构造函数注入
$container->set('db',[
	'className'	=>	DB::class,
	'arguments'=>[
		[
            'type'  => 'parameter',
            'name'  => 'user',
            'value' => 'root',
        ],
		[
            'type'  => 'parameter',
            'name'  => 'host',
            'value' => '127.0.0.1',
        ],
        [
            'type'  => 'parameter',
            'name'  => 'pwd',
            'value' => 'a12345',
        ],
	]
]);


//属性注入

class DB1{
	public $host;
	public $user;
	public $pwd;
	public $db;
	public $test;
}
$container->set('db1',[
	'className'	=>	DB1::class,
	'properties' => [
	    [
	        'name'  => 'host',
	        'value' => [
	            'type'  => 'parameter',
	            'value' => 'this is host',
	        ],
	    ],
	    [
	        'name'  => 'user',
	        'value' => [
	            'type'  => 'parameter',
	            'value' => 'this is user',
	        ],
	    ],
	    [
	        'name'  => 'pwd',
	        'value' => [
	            'type'  => 'parameter',
	            'value' => 'aaaaa',
	        ],
	    ],
	    [
	    	'name'  => 'db',
	        'value' => [
	            'type'  => 'instance',//参数是实例
	            'className' => Test::class
	        ],
	    ],
	    [
	    	'name'  => 'test',
	        'value' => [
	            'type'  => 'service',//参数是容器里的一个服务
	            'name' => 'test4'
	        ],
	    ]
	]
]);

$container['db2'] = [
	'className'	=>	DB1::class
];
//访问的方式
var_dump($container['test3'],$container->get('test1'),$container->getTest2(),$container->get('db'));
var_dump("<hr/>",$container->get('db1'),$container->getDb2());

//从文件加载注入
$container->loadFromPhp('config/services.php');
var_dump("<hr/>文件加载注入",$container->get('testA'));

//一个服务只有一个单例
$container->set("hello",Test::class,true);
$d = $container->getHello();
$d->name="abcd";
$container->setShared("hello",Test::class);
$dd = $container->getHello();
$dd->name="ABCD";
var_dump("<hr/>",$d,$dd,DI::getDefault()->getHello());

//继承injectbase类的，自动给实例注入容器
class Hello extends InjectionBase {
}
$container->set("T",Hello::class);
var_dump($container->get("T")->getDi()->get('hello')->name);

//文件服务类方式注入
require_once "Providers\ConfigProvider.php";
require_once "Providers\RegistryProvider.php";
$services = include('config/providers.php');
foreach ($services as $service) {
    $container->register(new $service());
}
print_r($container['config']);
print_r($container->getRegistry());