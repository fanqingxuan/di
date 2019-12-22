

## a IOC container implement with php

This is a component which implements Dependency Injection, it's itself a container and it  implements the 

Inversion of Control pattern.

### Install The Package

```php+HTML
composer install fanqingxuan/di
```

### Basic Usage 

```php
require_once 'vendor/autoload.php';

use Json\Di;

$container = new Container;

class Test {

}

//注入的方式
$container->set('test','Test');

$container->set("test2",function() {
	return new Test;
});

$container->set("test3",Test::class);

$container->set('test4',new Test);

```

like you can see,there are serveral ways to register services as the follow list.

- string 

  ```php
  $container->set('test','Test');
  $container->set("test3",Test::class);
  ```

- object instance

  ```php
  $container->set('test5',new Test);
  ```

- Closures/Anonymous functions

  ```php
  $container->set("test2",function() {
  	return new Test;
  });
  ```

You can pass additonal parameters to closure function.

```php
require_once '../vendor/autoload.php';

use Json\Di;
use Json\Config;

$container = new Di;

$container->set('config',new Config(
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

$container->set('db',function () {
    return new MysqlDb($this->get('config')->database);//get the database config from container
});
```

also,you can pass parameter by using the key word of use.

```php
$config = [
    'host'      =>  'localhost',
    'username'  =>  'root',
    'password'  =>  '111111'
];
$container->set('db',function () use ($config) {
    return new MysqlDb($config);
});
```

### Advanced Usage

- #### Constructor Injection

  This is injection type can pass arguments to the class constructor.

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

  We can register the service this way.

  ```php
  $container->set(
      'userDao',
      [
  		'className'	=>	UserDao::class
      ]
  );
  $container->set(
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

- #### Setter Injection

Some class have setters for injection with their specail demand. We modify the above service as the class with setters.

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

A service with setter injection can be registered as follows:

```php
$container->set(
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

- #### Properties Injection

 You can inject parameters directly into **public attributes** of the class:

```php
class UserService 
{
    public $userDao;
    public $userType;
    public $tempObj;
}
```

A service with properties injection can be registered as follows

```php
$container->set(
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

### More Advanced Usage

The next we will give a way that inject service from php file.

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

We can inject it to container as follows:

```php
use Json/Config;

$di->loadFromPhp('service.php');
```



### Array Syntax Usage

We introduce the usage with the set function above. In fact,the array syntax is also allowed to register as services.

```php
$container['db'] = new StdClass;
$constainer['db'] = function() {
    return new StdClass;
}
$container['db'] = 'StdClass';
$container['db'] = [
    'className'	=>	'StdClass'
]
```

### Property Syntax Usage

We can inject the object with the property usage. Actually,it use the magic methd to realize it.

```php
$container->db = new StdClass;
$container->db = function() {
    return new StdClass;
}
$container->db = StdClass::class;
$container->db = [
    'className'	=>	'stdClass'
];
```



### Get Services

- get method

  ```php
  $container->get('db');
  ```

- magic method

  ```php
  $container->getDb();
  ```

- array-access syntax

  ```php
  $container['db'];
  ```

- property syntax

  ```
  $container->db;
  ```

  

### Shared Services

Services can be registered as ‘shared’ services this means that they always will act as singletons. Once the service is resolved for the first time the same instance of it is returned every time.

```php
$container->setShared(
	'db',
    function() {
        return new MysqlDb();
    }
);
```

or use the set method with its third parameter as 'true'.

```php
$container->set(
	'db',
    function() {
        return new MysqlDb();
    },
    true
);
```

### Modify the Services

When the service is registered in the container,you can get it and modify it.

```php
class Test 
{
    
}
//register service
$container->set('test','StdClass');

//get service
$test = $container->getService('test');

//change the definition
$test->setDefinition(function() {
    return new Test;
});
//resolve the service
$test->resolve();
```

### Automatic Inject the DI Container into the service

DI Container is used for inject other service into it. but sometimes the service itself need the the other instance from the container. If a class or component requires the DI itself to locate services, the DI can automatically inject itself to the instances it creates, to do this, you need to extends the Json\Di\InjectionBase class in your classes: 

```php
class HomeController extends InjectionBase
{
    public function say()
    {
        $this->container->get('db')->select();
    }
}

$conatainer->set('home',HomeController::class);
```

### Service Providers

Using the Json\Di\ServiceProviderInterface  you now register services by context. You can move all your $di->set()` calls to classes as follows.

```php
use Json\Di\ServiceProviderInterface;
use Json\Di\DiInterface;

class SessionServiceProvider implements ServiceProviderInterface 
{
    public function register(DiInterface $container) 
    {
        $container->set(
        	'session',
            'SessionClass'
        );
    }
}

$container->register(new SessionServiceProvider());
$container->get('session');
```