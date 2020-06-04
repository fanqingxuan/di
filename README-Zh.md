phalcon的php版本依赖注入容器

### 环境要求
**php7.0+**

### 安装

```php+HTML
composer require fanqingxuan/di 
```
如果你想安装php扩展版本的容器，可参考**[di-ext](https://github.com/fanqingxuan/di-ext)**，用法跟这个一模一样

### 基本用法

```php
require_once 'vendor/autoload.php';

use JsonDi\Di;

class Test
{
}

$di = new Di;
//注入的方式
$di->set('test', 'Test');
$di->set("test2", function () {
    return new Test;
});
$di->set("test3", Test::class);
$di->set('test4', new Test);

```
你可以看到，有以下几种方式注入容器

- 字符串 

```php
$di->set('test','Test');
$di->set("test3",Test::class);
```

- 实例对象

```php
$di->set('test5',new Test);
```

- 闭包/匿名函数

```php
$di->set("test2",function() {
return new Test;
});
```
你也可以给匿名函数传递额外的参数

```php
require_once 'vendor/autoload.php';

use JsonDi\Di;
use JsonDi\Config;
$di = new Di;
$di->set('config',new Config(
    [
        'database'  =>  [
            'host'      =>  'localhost',
            'username'  =>  'root',
            'password'  =>  '111111'
        ]
    ]
));
class MysqlDb
{
    public function __construct($config) 
    {
        print_r($config);
    }
}
$di->set('db',function () {
    return new MysqlDb($this->get('config')->database);//get the database config from container
});
```

你也可以使用use关键字实现

```php
$config = [
    'host'      =>  'localhost',
    'username'  =>  'root',
    'password'  =>  '111111'
];
$di->set('db',function () use ($config) {
    return new MysqlDb($config);
});
```

### 高级用法

- #### 构造函数注入

这种注入类型可以给构造函数注入参数
  ```php
  class UserService 
  {
      protected $userDao;
      protected $userType;
      
      public function __construct(UserDao $userDao,$userType) 
      {
          $this->userDao  = $userDao;
          $this->userType = $userType;
      }
  }
  ```

  通过下面的方式注入

  ```php
  $di->set(
      'userDao',
      [
  		'className'	=>	UserDao::class
      ]
  );
  $di->set(
  	'userService',
      [
          'className'	=>	UserService::class,
          'arguments'	=>	[
              [
                  'type'	=>	'service',
                  'name'	=>	'userDao',//another service name in the container
              ],
              [
                  'type'	=>	'parameter',
                  'value'	=>	3
              ]
          ]
      ]
  );
  ```

- #### setter方式注入

```php 
class UserService 
{
    protected $userDao;
    protected $userType;
    
    public function setUserDao(UserDao $userDao) 
    {
        $this->userDao = $userDao;
    }
    
    public function setUserType($userType) 
    {
        $this->userType = $userType;
    }
}
```

通过下面的方式给setter注入参数
```php
$di->set(
    'userService',
    [
        'className'	=>	'UserService',
        'calls'		=>	[
            [
                'method'	=>	'setUserDao',
                'arguments'	=>	[
                    [
                        'type'	=>	'service',
                    	'name'	=>	'userDao',
                    ]
                ]
            ],
            [
                'method'	=>	'setUserType',
                'arguments'	=>	[
                    [
                        'type'	=>	'parameter',
                    	'value'	=>	3
                    ]
                ]
            ]
        ]
    ]
);
```

- #### 属性注入

给public的属性注入参数

```php
class UserService 
{
    public $userDao;
    public $userType;
    public $tempObj;
}
```

```php
$di->set(
	'userService',
    [
        'className'	=>	UserService::class,
        'properties'=>[
            [
                'name'	=>	'userDao',
                'value'	=>	[
                    'type'	=>	'service',
                    'name'	=>	'userDao',//service name in the container
                ]
            ],
            [
                'name'	=>	'userType',
                'value'	=>	[
                    'type'	=>	'parameter',
                    'value'	=>	2,
                ]
            ],
            [
                'name'	=>	'tempObj',
                'value'	=>	[
                    'type'	=>	'instance',
                    'className'	=>	'StdClass',
                    'arguments'	=>	[]
                ]
            ]
        ]
    ]
);
```

### 更多高级用法

下面是通过php文件注入

```php
//service.php
<?php
return [
    'testA' => [
        'className' => Test::class,
        'shared'    => true,
    ],
];
```

通过如下的方式注入
```php
require_once 'vendor/autoload.php';

use Json/Config;

$di = new Di;
$di->loadFromPhp('service.php');
```

### 数组方式注入

上面我们介绍了使用set函数注入的方式，事实上，也可以使用数组key-value方式注入
```php
$di['db'] = new StdClass;
$di['db'] = function() {
    return new StdClass;
}
$di['db'] = 'StdClass';
$di['db'] = [
    'className'	=>	'StdClass'
]
```

### 属性方式注入

可以使用实例属性方式注入，本质上是容器实现了__set,__get魔术方法
```php
$di->db = new StdClass;
$di->db = function() {
    return new StdClass;
}
$di->db = StdClass::class;
$di->db = [
    'className'	=>	'stdClass'
];
```



### 获取服务

- get方法

```php
$di->get('db');
```

- magic方法

```php
$di->getDb();
```

- 数组key

```php
$di['db'];
```

- 实例属性

```
$di->db;
```



### 单例服务
服务可以作为单例模式注入到容器，也就是说在执行过程中同一个服务只有一个实例

```php
$di->setShared(
	'db',
    function() {
        return new MysqlDb();
    }
);
```
也可以用set方法，将第三个参数设置值为true
```php
$di->set(
	'db',
    function() {
        return new MysqlDb();
    },
    true
);
```

### 修改容器中的服务

服务注入容器后，你也可以修改它
```php
class Test 
{
    
}
//register service
$di->set('test','StdClass');
//get service
$test = $di->getService('test');
//change the definition
$test->setDefinition(function() {
    return new Test;
});
//resolve the service
$test->resolve();
```

### 将容器注入到服务中

容器是用来将其它服务注入到容器中的，但是有时候需要将容器注入到服务当中，这样在服务中就可以通过容器访问容器中所有服务，实现解耦。为实现这个目的，你需要将你的服务继承JsonDi\Di\AbstractInjectionAware类即可

```php
require_once 'vendor/autoload.php';

use JsonDi\Di;
use JsonDi\Di\AbstractInjectionAware;

class Mysql
{
    public function select()
    {   
       return "this is select";
    }   
}

class HomeController extends AbstractInjectionAware
{
    public function say()
    {   
       echo $this->container->get('db')->select();
    }   
}

$di = new Di; 
$di->set('db', Mysql::class);

$di->set('home', HomeController::class);
$di->get('home')->say();
```

### 服务提供者

使用JsonDi\Di\ServiceProviderInterface接口你可以将你的注入容器代码写入到register方法中。
```php
require_once 'vendor/autoload.php';

use JsonDi\Di\DiInterface;
use JsonDi\Di\ServiceProviderInterface;
class SessionServiceProvider implements ServiceProviderInterface 
{
    public function register(DiInterface $di):void
    {
        $di->set(
        	'session',
            'SessionClass'
        );
    }
}
$di->register(new SessionServiceProvider());
$di->get('session');
```