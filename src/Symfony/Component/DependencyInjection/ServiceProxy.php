<?php

namespace Symfony\Component\DependencyInjection;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
abstract class ServiceProxy
{
    private $factory;
    private $class;
    private $method;

    public function __construct(\Closure $factory, $class, $method)
    {
        $this->factory = $factory;
        $this->class = $class;
        $this->method = $method;
    }

    public function getService()
    {
        $factory = $this->factory;

        return $factory;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getServiceInfo()
    {
        return array($this->class, $this->method);
    }
}
