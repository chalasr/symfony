<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Routing\Loader\Configurator;

use Symfony\Component\Routing\Loader\Configurator\RouteConfigurator;

/**
 * A helper to use inheritance and decoration
 *
 * @internal
 *
 * @author Jules Pietri <jules@heahprod.com>
 */
abstract class AbstractRouteConfiguratorDecorator extends RouteConfigurator
{
    public function __construct(RouteConfigurator $routeConfigurator)
    {
        $collection = new \ReflectionProperty(RouteConfigurator::class, 'collection');
        $collection->setAccessible(true);
        $route = new \ReflectionProperty(RouteConfigurator::class, 'route');
        $route->setAccessible(true);
        $name = new \ReflectionProperty(RouteConfigurator::class, 'name');
        $name->setAccessible(true);
        $parentConfigurator = new \ReflectionProperty(RouteConfigurator::class, 'parentConfigurator');
        $parentConfigurator->setAccessible(true);
        $prefixes = new \ReflectionProperty(RouteConfigurator::class, 'prefixes');
        $prefixes->setAccessible(true);

        parent::__construct($collection->getValue($routeConfigurator), $route->getValue($routeConfigurator), $name->getValue($routeConfigurator), $parentConfigurator->getValue($routeConfigurator), $prefixes->getValue($routeConfigurator));
    }
}
