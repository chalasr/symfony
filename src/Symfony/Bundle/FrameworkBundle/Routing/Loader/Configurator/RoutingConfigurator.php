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

use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Bundle\FrameworkBundle\Controller\TemplateController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator as BaseRoutingConfigurator;
use Symfony\Component\Routing\Loader\PhpFileLoader;
use Symfony\Component\Routing\RouteCollection;

/**
 * @author Jules Pietri <jules@heahprod.com>
 */
class RoutingConfigurator extends BaseRoutingConfigurator
{
    private $collection;

    public function __construct(RouteCollection $collection, PhpFileLoader $loader, string $path, string $file)
    {
        parent::__construct($collection, $loader, $path, $file);

        $this->collection = $collection;
    }

    /**
     * @param string|array $path     The path, or the localized paths of the route
     * @param string       $template The template name
     */
    final public function template(string $name, $path, string $template): TemplateRouteConfigurator
    {
        return new TemplateRouteConfigurator(...$this->getRouteConfiguratorArguments($name, $path, [
            'template' => $template,
            '_controller' => TemplateController::class,
        ]));
    }

    /**
     * @param string|array $path  The path, or the localized paths of the route
     * @param string       $route The route name to redirect to
     */
    final public function redirectTo(string $name, $path, string $route): RedirectRouteConfigurator
    {
        return new RedirectRouteConfigurator(...$this->getRouteConfiguratorArguments($name, $path, [
            'route' => $route,
            '_controller' => RedirectController::class.'::redirectAction',
        ]));
    }

    /**
     * @param string|array $path The path, or the localized paths of the route
     * @param string       $url  The absolute path or URL to redirect to
     */
    final public function redirectToUrl(string $name, $path, string $url): UrlRedirectRouteConfigurator
    {
        return new UrlRedirectRouteConfigurator(...$this->getRouteConfiguratorArguments($name, $path, [
            'path' => $url,
            '_controller' => RedirectController::class.'::urlRedirectAction',
        ]));
    }

    private function getRouteConfiguratorArguments(string $name, $path, array $defaults): array
    {
        $route = $this->createLocalizedRoute($this->collection, $name, $path);
        $route->addDefaults($defaults);

        return [$this->collection, $route];
    }
}
