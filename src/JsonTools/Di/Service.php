<?php

namespace JsonTools\Di;

use \Closure;
use JsonTools\Di\ServiceBuilder;
use JsonTools\Di\ServiceInterface;

/**
 * Represents individually a service in the services container
 *
 *```php
 * $service = new \JsonTools\Di\Service(
 *     "request",
 *     \JsonTools\Request::class
 * );
 *
 * $request = service->resolve();
 *```
 */
class Service implements ServiceInterface
{
    protected $definition;

    /**
     * @var bool
     */
    protected $resolved = false;

    /**
     * @var bool
     */
    protected $shared = false;

    protected $sharedInstance;

    /**
     * JsonTools\Di\Service
     */
    final public function __construct($definition, $shared = false)
    {
        $this->definition = $definition;
        $this->shared = $shared;
    }

    /**
     * Returns the service definition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * Returns a parameter in a specific position
     *
     * @return array
     */
    public function getParameter($position)
    {
        $definition = $this->definition;

        if (gettype($definition) != "array") {
            throw new Exception(
                "Definition must be an array to obtain its parameters"
            );
        }

        /**
         * Update the parameter
         */
        if (isset($definition["arguments"])) {
            $arguments = $definition["arguments"];
            if (isset($arguments[$position])) {
                $parameter = $arguments[$position];
                return $parameter;
            }
        }

        return null;
    }

    /**
     * Returns true if the service was resolved
     */
    public function isResolved()
    {
        return $this->resolved;
    }

    /**
     * Check whether the service is shared or not
     */
    public function isShared()
    {
        return $this->shared;
    }

    /**
     * Resolves the service
     *
     * @param array parameters
     */
    public function resolve($parameters = null, $container = null)
    {
        $shared = $this->shared;

        /**
         * Check if the service is shared
         */
        if ($shared) {
            $sharedInstance = $this->sharedInstance;
            if ($sharedInstance !== null) {
                return $sharedInstance;
            }
        }

        $found = true;
        $instance = null;

        $definition = $this->definition;
        if (gettype($definition) == "string") {
            /**
             * String definitions can be class names without implicit parameters
             */
            if ($container !== null) {
                $instance = $container->get($definition, $parameters);
            } elseif (class_exists($definition)) {
                if (gettype($parameters) == "array" && count($parameters)) {
                    if (count($parameters) == 1) {
                        $instance = new $definition($parameters[0]);
                    } elseif (count($parameters) == 2) {
                        $instance = new $definition($parameters[0], $parameters[1]);
                    } elseif (count($parameters) == 3) {
                        $instance = new $definition($parameters[0], $parameters[1], $parameters[2]);
                    } elseif (count($parameters) == 4) {
                        $instance = new $definition($parameters[0], $parameters[1], $parameters[2], $parameters[3]);
                    } else {
                        $class = new ReflectionClass($definition);// 建立类的反射
                        $instance = $class ->newInstance($parameters);
                    }
                } else {
                    $instance = new $definition;
                }
            } else {
                $found = false;
            }
        } else {

            /**
             * Object definitions can be a Closure or an already resolved
             * instance
             */
            if (gettype($definition) == "object") {
                if ($definition instanceof Closure) {

                    /**
                     * Bounds the closure to the current Di
                     */
                    if (gettype($container) == "object") {
                        $definition = Closure::bind($definition, $container);
                    }

                    if (gettype($parameters) == "array") {
                        $instance = call_user_func_array(
                            $definition,
                            $parameters
                        );
                    } else {
                        $instance = call_user_func($definition);
                    }
                } else {
                    $instance = $definition;
                }
            } else {
                /**
                 * Array definitions require a 'className' parameter
                 */
                if (gettype($definition) == "array") {
                    $builder = new ServiceBuilder();
                    $instance = $builder->build(
                        $container,
                        $definition,
                        $parameters
                    );
                } else {
                    $found = false;
                }
            }
        }

        /**
         * If the service can't be built, we must throw an exception
         */
        if ($found === false) {
            throw new Exception();
        }

        /**
         * Update the shared instance if the service is shared
         */
        if ($shared) {
            $this->sharedInstance = $instance;
        }

        $this->resolved = true;

        return $instance;
    }

    /**
     * Set the service definition
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;
    }

    /**
     * Changes a parameter in the definition without resolve the service
     */
    public function setParameter($position, $parameter)
    {
        $definition = $this->definition;

        if (gettype($definition) != "array") {
            throw new Exception(
                "Definition must be an array to update its parameters"
            );
        }

        /**
         * Update the parameter
         */
        if (isset($definition["arguments"])) {
            $arguments = $definition["arguments"];
            $arguments[$position] = $parameter;
        } else {
            $arguments = [$position=>$parameter];
        }

        /**
         * Re-update the arguments
         */
        $definition["arguments"] = $arguments;

        /**
         * Re-update the definition
         */
        $this->definition = $definition;

        return $this;
    }

    /**
     * Sets if the service is shared or not
     */
    public function setShared($shared)
    {
        $this->shared = $shared;
    }

    /**
     * Sets/Resets the shared instance related to the service
     */
    public function setSharedInstance($sharedInstance)
    {
        $this->sharedInstance = $sharedInstance;
    }
}
