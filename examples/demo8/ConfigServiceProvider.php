<?php
require_once '../../vendor/autoload.php';
use JsonDi\Di\DiInterface;
use JsonDi\Di\ServiceProviderInterface;

class ConfigServiceProvider implements ServiceProviderInterface
{
    public function register(DiInterface $di):void
    {
        $di->set(
            'config',
            function () {
                return array(
                     'database' =>  array(),
                     'session'  =>  array()
                 );
            }
        );
    }
}
