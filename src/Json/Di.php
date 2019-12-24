<?php
namespace Json;

use Json\Di\Service;
use Json\Di\DiInterface;
use \Exception;
use Json\Config;
use Json\Config\Php;
use Json\Di\InjectionBase;
use Json\Di\ServiceProviderInterface;

/**
 *```php
 * use Json\Di;
 *
 * $di = new Di();
 *
 * // Using a string definition
 * $di->set("request", Request::class, true);
 *
 * // Using an anonymous function
 * $di->setShared(
 *     "request",
 *     function () {
 *         return new Request();
 *     }
 * );
 *
 * $request = $di->getRequest();
 *```
 */
class Di implements DiInterface
{
    /**
     * List of registered services
     */
    protected $services;

    /**
     * List of shared instances
     */
    protected $sharedInstances;


    /**
     * Latest Di build
     */
    protected static $_default;

    /**
     * Json\Di constructor
     */
    public function __construct()
    {
        if (!self::$_default) {
            self::$_default = $this;
        }
    }

    /**
     * Magic method to get or set services using setters/getters
     */
    public function __call($method, $arguments = [])
    {
        $instance = $possibleService = $definition = null;

        /**
         * If the magic method starts with "get" we try to get a service with
         * that name
         */
        if(strpos($method, "get") === 0) {
            $possibleService = lcfirst(substr($method, 3));

            if(isset($this->services[$possibleService])) {
                $instance = $this->get($possibleService, $arguments);

                return $instance;
            }
        }

        /**
         * If the magic method starts with "set" we try to set a service using
         * that name
         */
        if(strpos($method, "set") === 0) {
            if (isset($arguments[0])){
                $definition = $arguments[0];
                $this->set(
                    lcfirst(
                        substr($method, 3)
                    ),
                    $definition
                );

                return null;
            }
        }

        /**
         * The method doesn't start with set/get throw an exception
         */
        throw new Exception(
            "Call to undefined method or service '" . $method . "'"
        );
    }

    /**
     * Attempts to register a service in the services container
     * Only is successful if a service hasn't been registered previously
     * with the same name
     */
    public function attempt($name, $definition, $shared = false)
    {
        if (isset($this->services[$name])) {
            return false;
        }

        $this->services[$name] = new Service($definition, $shared);

        return $this->services[$name];
    }

    /**
     * Resolves the service based on its configuration
     */
    public function get($name, $parameters = null)
    {
        $service = $isShared = $instance = null;

        /**
         * If the service is shared and it already has a cached instance then
         * immediately return it without triggering events.
         */
        if (isset($this->services[$name])) {
            $service = $this->services[$name];
            $isShared = $service->isShared();

            if ($isShared && isset($this->sharedInstances[$name])) {
                return $this->sharedInstances[$name];
            }
        }


        if ( gettype($instance) != "object") {
            if ($service !== null) {
                // The service is registered in the Di.
                try {
                    $instance = $service->resolve($parameters, $this);
                } catch (Exception $e) {
                    throw new Exception(
                        "Service '" . $name . "' cannot be resolved"
                    );
                }

                // If the service is shared then we'll cache the instance.
                if ($isShared) {
                    $this->sharedInstances[$name] = $instance;
                }
            } else {
                /**
                 * The Di also acts as builder for any class even if it isn't
                 * defined in the Di
                 */
                if (!class_exists($name)) {
                    throw new Exception(
                        "Service '" . $name . "' wasn't found in the dependency injection container"
                    );
                }

                if (gettype($parameters) == "array" && count($parameters)) {
                    if(count($parameters) == 1) {
                        $instance = new $name($parameters[0]);
                    }else if(count($parameters) == 2) {
                        $instance = new $name($parameters[0],$parameters[1]);
                    } else if(count($parameters) == 3) {
                        $instance = new $name($parameters[0],$parameters[1],$parameters[2]);
                    }else if(count($parameters) == 4) {
                        $instance = new $name($parameters[0],$parameters[1],$parameters[2],$parameters[3]);
                    }else {
                        $class = new ReflectionClass($name);// 建立类的反射
                        $instance = $class ->newInstance($parameters);
                    }
                } else {
                    $instance = new $name();
                }
            }
        }

        /**
         * Pass the Di to the instance if it implements
         * \Json\Di\InjectionBase
         */
        if (gettype($instance) == "object") {
            if ($instance instanceof InjectionBase) {
                $instance->setDi($this);
            }
        }
        return $instance;
    }

    /**
     * Return the latest Di created
     */
    public static function getDefault()
    {
        return self::$_default;
    }


    /**
     * Returns a service definition without resolving
     */
    public function getRaw($name)
    {
        if (!isset($this->services[$name]))  {
            throw new Exception(
                "Service '" . $name . "' wasn't found in the dependency injection container"
            );
        }
        $service = $this->services[$name];
        return $service->getDefinition();
    }

    /**
     * Returns a Json\Di\Service instance
     */
    public function getService($name)
    {
        if (!isset($this->services[$name]))  {
            throw new Exception(
                "Service '" . $name . "' wasn't found in the dependency injection container"
            );
        }
        $service = $this->services[$name];
        return $service;
    }

    /**
     * Return the services registered in the Di
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Resolves a service, the resolved service is stored in the Di, subsequent
     * requests for this service will return the same instance
     */
    public function getShared($name, $parameters = null)
    {

        // Attempt to use the instance from the shared instances cache.
        if (!isset($this->sharedInstances[$name])) {
            // Resolve the instance normally
            $instance = $this->get($name, $parameters);

            // Store the instance in the shared instances cache.
            $this->sharedInstances[$name] = $instance;
        } else {
            $instance = $this->sharedInstances[$name];
        }

        return $instance;
    }

    /**
     * Loads services from a Config object.
     */
    protected function loadFromConfig(Config $config)
    {
        $services = $config->toArray();

        foreach($services as $name => $service) {
            $this->set(
                $name,
                $service,
                isset($service["shared"]) && $service["shared"]
            );
        }
    }

    /**
     * Loads services from a php config file.
     *
     * ```php
     * $di->loadFromPhp("path/services.php");
     * ```
     *
     * And the services can be specified in the file as:
     *
     * ```php
     * return [
     *      'myComponent' => [
     *          'className' => '\Acme\Components\MyComponent',
     *          'shared' => true,
     *      ],
     *      'group' => [
     *          'className' => '\Acme\Group',
     *          'arguments' => [
     *              [
     *                  'type' => 'service',
     *                  'service' => 'myComponent',
     *              ],
     *          ],
     *      ],
     *      'user' => [
     *          'className' => '\Acme\User',
     *      ],
     * ];
     * ```
     *
     */
    public function loadFromPhp($filePath)
    {

        $config = new Php($filePath);

        $services = $config->toArray();

        foreach( $services as $name => $service) {
            $this->set(
                $name,
                $service,
                isset($service["shared"]) && $service["shared"]
            );
        }
    }


    /**
     * Check whether the Di contains a service by a name
     */
    public function has($name)
    {
        return isset($this->services[$name]);
    }

    /**
     * Allows to obtain a shared service using the array syntax
     *
     *```php
     * var_dump($di["request"]);
     *```
     */
    public function offsetGet($name)
    {
        return $this->getShared($name);
    }

    /**
     * Check if a service is registered using the array syntax
     */
    public function offsetExists($name)
    {
        return $this->has($name);
    }

    /**
     * Allows to register a shared service using the array syntax
     *
     *```php
     * $di["request"] = new Request();
     *```
     */
    public function offsetSet($name, $definition)
    {
        $this->setShared($name, $definition);
    }

    /**
     * Removes a service from the services container using the array syntax
     */
    public function offsetUnset($name)
    {
        $this->remove($name);
    }

    /**
     * Registers a service provider.
     *
     * ```php
     * use Json\Di\DiInterface;
     * use Json\Di\ServiceProviderInterface;
     *
     * class SomeServiceProvider implements ServiceProviderInterface
     * {
     *     public function register(DiInterface $di)
     *     {
     *         $di->setShared(
     *             'service',
     *             function () {
     *                 // ...
     *             }
     *         );
     *     }
     * }
     * ```
     */
    public function register(ServiceProviderInterface $provider)
    {
        $provider->register($this);
    }

    /**
     * Removes a service in the services container
     * It also removes any shared instance created for the service
     */
    public function remove($name)
    {
        $services = $this->services;
        unset($services[$name]);
        $this->services = $services;

        $sharedInstances = $this->sharedInstances;
        unset($sharedInstances[$name]);
        $this->sharedInstances = $sharedInstances;
    }

    /**
     * Resets the internal default Di
     */
    public static function reset()
    {
        self::$_default = null;
    }

    /**
     * Registers a service in the services container
     */
    public function set($name, $definition, $shared = false)
    {
        $this->services[$name] = new Service($definition, $shared);

        return $this->services[$name];
    }

    /**
     * Set a default dependency injection container to be obtained into static
     * methods
     */
    public static function setDefault($container)
    {
        self::$_default = $container;
    }


    /**
     * Sets a service using a raw Json\Di\Service definition
     */
    public function setRaw($name, $rawDefinition)
    {
        $this->services[$name] = $rawDefinition;

        return $rawDefinition;
    }

    /**
     * Registers an "always shared" service in the services container
     */
    public function setShared($name, $definition)
    {
        return $this->set($name, $definition, true);
    }
    
    
    /**
     * Allows to obtain a shared service using the instance's property syntax
     *
     *```php
     * var_dump($di->request);
     *```
     */
    public function __get($name)
    {
        return $this->getShared($name);
    }


    /**
     * Allows to register a shared service using the instance's property syntax
     *
     *```php
     * $di->request = new Request();
     *```
     */
    public function __set($name, $definition)
    {
        $this->setShared($name, $definition);
    }

    /**
     * Removes a service from the services container using the instance's property syntax
     */
    public function __unset($name)
    {
        $this->remove($name);
    }
    
    /**
     * Check if a service is registered using the instance's property syntax
     */
    public function __isset($name)
    {
        return $this->has($name);
    }
}
