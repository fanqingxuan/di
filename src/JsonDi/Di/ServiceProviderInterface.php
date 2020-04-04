<?php

namespace JsonDi\Di;

use JsonDi\Di\DiInterface;

/**
 * Should be implemented by service providers, or such components, which
 * register a service in the service container.
 *
 * ```php
 * namespace Acme;
 *
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
interface ServiceProviderInterface
{
    /**
     * Registers a service provider.
     */
    public function register(DiInterface $di);
}
