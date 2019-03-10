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

use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Bundle\FrameworkBundle\Controller\TemplateController;
use Symfony\Component\Routing\Loader\YamlFileLoader as BaseYamlFileLoader;
use Symfony\Component\Routing\RouteCollection;

/**
 * @author Jules Pietri <jules@heahprod.com>
 */
class YamlFileLoader extends BaseYamlFileLoader
{
    protected function validate($config, $name, $path)
    {
        // remove invalid keys for values normalized below
        unset(
            $config['template'], $config['max_age'], $config['shared_max_age'], $config['private'],
            $config['redirect_to'], $config['permanent'], $config['ignore_attributes'], $config['keep_query_params'],
            $config['redirect_to_url'], $config['scheme'], $config['http_port'], $config['https_port'],
            $config['keep_request_method']
        );

        parent::validate($config, $name, $path);
    }

    protected function parseRoute(RouteCollection $collection, $name, array $config, $path)
    {
        if (isset($config['template'])) {
            $config['defaults'] = array_merge($config['defaults'] ?? [], [
                '_controller' => TemplateController::class,
                'template' => $config['template'],
                'maxAge' => $config['max_age'] ?? null,
                'sharedAge' => $config['shared_max_age'] ?? null,
                'private' => $config['private'] ?? null,
            ]);
            unset($config['template'], $config['max_age'], $config['shared_max_age'], $config['private']);
        } elseif (isset($config['redirect_to'])) {
            $config['defaults'] = array_merge($config['defaults'] ?? [], [
                '_controller' => RedirectController::class.'::redirectAction',
                'route' => $config['redirect_to'],
                'permanent' => $config['permanent'] ?? false,
                'ignoreAttributes' => $config['ignore_attributes'] ?? false,
                'keepRequestMethod' => $config['keep_request_method'] ?? false,
                'keepQueryParams' => $config['keep_query_params'] ?? false,
            ]);
            unset($config['redirect_to'], $config['permanent'], $config['ignore_attributes'], $config['keep_request_method'], $config['keep_query_params']);
        } elseif (isset($config['redirect_to_url'])) {
            $config['defaults'] = array_merge($config['defaults'] ?? [], [
                '_controller' => RedirectController::class.'::urlRedirectAction',
                'path' => $config['redirect_to_url'],
                'permanent' => $config['permanent'] ?? false,
                'scheme' => $config['scheme'] ?? null,
                'httpPort' => $config['http_port'] ?? null,
                'httpsPort' => $config['https_port'] ?? null,
                'keepRequestMethod' => $config['keep_request_method'] ?? false,
            ]);
            unset($config['redirect_to_url'], $config['permanent'], $config['scheme'], $config['http_port'], $config['https_port'], $config['keepRequestMethod']);
        }

        parent::parseRoute($collection, $name, $config, $path);
    }
}
