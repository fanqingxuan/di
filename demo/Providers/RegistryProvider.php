<?php
namespace Providers;

use Json\Di\ServiceProviderInterface;
use Json\Di\DiInterface;

class RegistryProvider implements ServiceProviderInterface {
	/**
     * @param DiInterface $container
     */
    public function register(DiInterface $container)
    {
        $container->setShared(
            'registry',
            function () {
                return new \StdClass;
            }
        );
    }
}