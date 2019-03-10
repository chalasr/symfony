<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Routing\Loader;

use Symfony\Bundle\FrameworkBundle\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator as BaseRoutingConfigurator;
use Symfony\Component\Routing\Loader\PhpFileLoader as BasePhpFileLoader;
use Symfony\Component\Routing\RouteCollection;

/**
 * @author Jules Pietri <jules@heahprod.com>
 */
class PhpFileLoader extends BasePhpFileLoader
{
    protected function getConfigurator(RouteCollection $collection, string $path, string $file): BaseRoutingConfigurator
    {
        return new RoutingConfigurator($collection, $this, $path, $file);
    }
}
