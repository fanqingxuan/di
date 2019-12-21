<?php
namespace Providers;

use Json\Di\ServiceProviderInterface;
use Json\Di\DiInterface;

class ConfigProvider implements ServiceProviderInterface {
	/**
     * @param DiInterface $container
     */
    public function register(DiInterface $container)
    {
        $container->setShared(
            'config',
            function () {
                return ['config'=>[]];
            }
        );
    }
}