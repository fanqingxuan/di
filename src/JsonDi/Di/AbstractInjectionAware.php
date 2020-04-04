<?php

namespace JsonDi\Di;

use JsonDi\Di\DiInterface;

/**
 * 如果一个类要自动注入di类，则继承该类
 */
class AbstractInjectionAware
{
    protected $container;
    /**
     * Sets the dependency injector
     */
    public function setDi(DiInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns the internal dependency injector
     */
    public function getDi()
    {
        return $this->container;
    }
}
