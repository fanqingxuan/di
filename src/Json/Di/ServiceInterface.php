<?php

namespace Json\Di;

/**
 * Represents a service in the services container
 */
interface ServiceInterface
{
    /**
     * Returns the service definition
     */
    public function getDefinition();

    /**
     * Returns a parameter in a specific position
     *
     * @return array
     */
    public function getParameter($position);

    /**
     * Returns true if the service was resolved
     */
    public function isResolved();

    /**
     * Check whether the service is shared or not
     */
    public function isShared();

    /**
     * Resolves the service
     *
     * @param array parameters
     */
    public function resolve($parameters = null, $container = null);

    /**
     * Set the service definition
     */
    public function setDefinition($definition);

    /**
     * Changes a parameter in the definition without resolve the service
     */
    public function setParameter($position, $parameter);

    /**
     * Sets if the service is shared or not
     */
    public function setShared(bool $shared);
}
