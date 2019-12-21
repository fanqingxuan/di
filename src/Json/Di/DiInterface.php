<?php

namespace Json\DI;

use \ArrayAccess;

/**
 * Interface for Json\Di
 */
interface DiInterface extends ArrayAccess
{
    /**
     * Attempts to register a service in the services container
     * Only is successful if a service hasn't been registered previously
     * with the same name
     *
     * @param mixed definition
     */
    public function attempt($name, $definition, bool $shared = false);

    /**
     * Resolves the service based on its configuration
     */
    public function get($name, $parameters = null);

    /**
     * Return the last DI created
     */
    public static function getDefault();

    /**
     * Returns a service definition without resolving
     */
    public function getRaw($name);

    /**
     * Returns the corresponding Json\Di\Service instance for a service
     */
    public function getService($name);

    /**
     * Return the services registered in the DI
     */
    public function getServices();

    /**
     * Returns a shared service based on their configuration
     */
    public function getShared($name, $parameters = null);

    /**
     * Check whether the DI contains a service by a name
     */
    public function has($name);

    /**
     * Removes a service in the services container
     */
    public function remove($name);

    /**
     * Resets the internal default DI
     */
    public static function reset();

    /**
     * Registers a service in the services container
     */
    public function set($name, $definition, bool $shared = false);

    /**
     * Set a default dependency injection container to be obtained into static
     * methods
     */
    public static function setDefault($container);

    /**
     * Sets a service using a raw Json\Di\Service definition
     */
    public function setRaw($name, $rawDefinition);

    /**
     * Registers an "always shared" service in the services container
     */
    public function setShared($name, $definition);
}
