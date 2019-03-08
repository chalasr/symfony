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
use Symfony\Component\Routing\Loader\Configurator\RouteConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator as BaseRoutingConfigurator;

/**
 * @author Jules Pietri <jules@heahprod.com>
 */
class RoutingConfigurator extends BaseRoutingConfigurator
{
    /**
     * @param string|array $path     The path, or the localized paths of the route
     * @param string       $template The template name
     */
    final public function template(string $name, $path, string $template): TemplateRouteConfigurator
    {
        return new TemplateRouteConfigurator($this->createRouteConfigurator($name, $path, [
            'template' => $template,
            '_controller' => TemplateController::class,
        ]));
    }

    /**
     * @param string|array $path  The path, or the localized paths of the route
     * @param string       $route The route name to redirect to
     */
    final public function redirect(string $name, $path, string $route): RedirectRouteConfigurator
    {
        return new RedirectRouteConfigurator($this->createRouteConfigurator($name, $path, [
            'route' => $route,
            '_controller' => RedirectController::class.'::redirectAction',
        ]));
    }

    /**
     * @param string|array $path The path, or the localized paths of the route
     * @param string       $url  The absolute path or URL to redirect to
     */
    final public function urlRedirect(string $name, string $path, string $url): UrlRedirectRouteConfigurator
    {
        return new UrlRedirectRouteConfigurator($this->createRouteConfigurator($name, $path, [
            'path' => $url,
            '_controller' => RedirectController::class.'::urlRedirectAction',
        ]));
    }

    private function createRouteConfigurator(string $name, $path, array $defaults): RouteConfigurator
    {
        return $this->add($name,$path)->defaults($defaults);
    }
}
