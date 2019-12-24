<?php

namespace Json\Di;

use Json\Di\DiInterface;
use \Exception;

/**
 * Json\Di\ServiceBuilder
 *
 * This class builds instances based on complex definitions
 */
class ServiceBuilder
{
    /**
     * Builds a service using a complex service definition
     *
     * @param array parameters
     * @return mixed
     */
    public function build($container, $definition, $parameters = null)
    {
        /**
         * The class name is required
         */
        if (!isset($definition["className"])) {
            throw new Exception(
                "Invalid service definition. Missing 'className' parameter"
            );
        }
        $className = $definition["className"];
        if (gettype($parameters) == "array") {

            /**
             * Build the instance overriding the definition constructor
             * parameters
             */
            if (count($parameters)) {
                if(count($parameters) == 1) {
                    $instance = new $className($parameters[0]);
                }else if(count($parameters) == 2) {
                    $instance = new $className($parameters[0],$parameters[1]);
                } else if(count($parameters) == 3) {
                    $instance = new $className($parameters[0],$parameters[1],$parameters[2]);
                }else if(count($parameters) == 4) {
                    $instance = new $className($parameters[0],$parameters[1],$parameters[2],$parameters[3]);
                }else {
                    $class = new ReflectionClass($className);// 建立类的反射
                    $instance = $class ->newInstance($parameters);
                }
            } else {
                $instance = new $className;
            }

        } else {

            /**
             * Check if the argument has constructor arguments
             */
            if (isset($definition["arguments"])) {
                $arguments = $definition["arguments"];
                /**
                 * Create the instance based on the parameters
                 */
                $parameters = $this->buildParameters($container, $arguments);
                if(count($parameters) == 1) {
                    $instance = new $className($parameters[0]);
                }else if(count($parameters) == 2) {
                    $instance = new $className($parameters[0],$parameters[1]);
                } else if(count($parameters) == 3) {
                    $instance = new $className($parameters[0],$parameters[1],$parameters[2]);
                }else if(count($parameters) == 4) {
                    $instance = new $className($parameters[0],$parameters[1],$parameters[2],$parameters[3]);
                }else {
                    $class = new ReflectionClass($className);// 建立类的反射
                    $instance = $class ->newInstance($parameters);
                }
            } else {
                $instance = new $className;
            }
        }

        /**
         * The definition has calls?
         */
        if (isset($definition["calls"])) {
            $paramCalls = $definition["calls"];
            if (gettype($instance) != "object") {
                throw new Exception(
                    "The definition has setter injection parameters but the constructor didn't return an instance"
                );
            }

            if(gettype($paramCalls) != "array") {
                throw new Exception(
                    "Setter injection parameters must be an array"
                );
            }

            /**
             * The method call has parameters
             */
            foreach($paramCalls as $methodPosition => $method) {

                /**
                 * The call parameter must be an array of arrays
                 */
                if (gettype($method) != "array") {
                    throw new Exception(
                        "Method call must be an array on position " . $methodPosition
                    );
                }

                /**
                 * A param 'method' is required
                 */
                if (!isset($method["method"])) {
                    
                    throw new Exception(
                        "The method name is required on position " . $methodPosition
                    );
                }
                $methodName = $method["method"];
                /**
                 * Create the method call
                 */
                $methodCall = [$instance, $methodName];

                if (isset($method["arguments"])){ 
                    $arguments = $method["arguments"];
                    if (gettype($arguments) != "array") {
                        throw new Exception(
                            "Call arguments must be an array " . $methodPosition
                        );
                    }

                    if (count($arguments)) {
                        /**
                         * Call the method on the instance
                         */
                        call_user_func_array(
                            $methodCall,
                            $this->buildParameters($container, $arguments)
                        );

                        /**
                         * Go to next method call
                         */
                        continue;
                    }
                }

                /**
                 * Call the method on the instance without arguments
                 */
                call_user_func($methodCall);
            }
        }

        /**
         * The definition has properties?
         */
        if (isset($definition["properties"])) {
            $paramCalls = $definition["properties"];
            if (gettype($instance) != "object") {
                throw new Exception(
                    "The definition has properties injection parameters but the constructor didn't return an instance"
                );
            }

            if (gettype($paramCalls) != "array") {
                throw new Exception(
                    "Setter injection parameters must be an array"
                );
            }

            /**
             * The method call has parameters
             */
            foreach($paramCalls as $propertyPosition => $property) {

                /**
                 * The call parameter must be an array of arrays
                 */
                if (gettype($property) != "array") {
                    throw new Exception(
                        "Property must be an array on position " . $propertyPosition
                    );
                }

                /**
                 * A param 'name' is required
                 */
                if (!isset($property["name"]))  {
                    throw new Exception(
                        "The property name is required on position " . $propertyPosition
                    );
                }
                $propertyName = $property["name"];
                /**
                 * A param 'value' is required
                 */
                if (!isset($property["value"]))  {
                    throw new Exception(
                        "The property value is required on position " . $propertyPosition
                    );
                }
                $propertyValue = $property["value"];
                /**
                 * Update the public property
                 */
                $instance->{$propertyName} = $this->buildParameter(
                    $container,
                    $propertyPosition,
                    $propertyValue
                );
            }
        }

        return $instance;
    }

    /**
     * Resolves a constructor/call parameter
     *
     * @return mixed
     */
    private function buildParameter($container, $position, $argument)
    {
        /**
         * All the arguments must have a type
         */
        if (!isset($argument["type"]))  {
            throw new Exception(
                "Argument at position " . $position . " must have a type"
            );
        }
        $type = $argument["type"];
        switch ($type) {

            /**
             * If the argument type is 'service', we obtain the service from the
             * Di
             */
            case "service":
                if (!isset($argument["name"])) {
                    throw new Exception(
                        "Service 'name' is required in parameter on position " . $position
                    );
                }
                $name = $argument["name"];
                if (gettype($container) != "object") {
                    throw new Exception(
                        "The dependency injector container is not valid"
                    );
                }

                return $container->get($name);

            /**
             * If the argument type is 'parameter', we assign the value as it is
             */
            case "parameter":
                if (!isset($argument["value"]))  {
                    throw new Exception(
                        "Service 'value' is required in parameter on position " . $position
                    );
                }
                $value = $argument["value"];
                return $value;

            /**
             * If the argument type is 'instance', we assign the value as it is
             */
            case "instance":

                if (!isset($argument["className"]))  {
                    throw new Exception(
                        "Service 'className' is required in parameter on position " . $position
                    );
                }
                
                $name = $argument["className"];
                if (gettype($container) != "object") {
                    throw new Exception(
                        "The dependency injector container is not valid"
                    );
                }

                if (isset($argument["arguments"])) {
                    $instanceArguments = $argument["arguments"];
                    /**
                     * Build the instance with arguments
                     */
                    return $container->get($name, $instanceArguments);
                }

                /**
                 * The instance parameter does not have arguments for its
                 * constructor
                 */
                return $container->get($name);

            default:
                /**
                 * Unknown parameter type
                 */
                throw new Exception(
                    "Unknown service type in parameter on position " . $position
                );
        }
    }

    /**
     * Resolves an array of parameters
     */
    private function buildParameters($container, $arguments)
    {
        $buildArguments = [];

        foreach($arguments as $position => $argument) {
            $buildArguments[] = $this->buildParameter(
                $container,
                $position,
                $argument
            );
        }

        return $buildArguments;
    }
}
