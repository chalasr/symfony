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
        unset($config['template'], $config['redirect'], $config['url_redirect']);

        parent::validate($config, $name, $path);
    }

    protected function parseRoute(RouteCollection $collection, $name, array $config, $path)
    {
        if (isset($config['template'])) {
            // normalize "sharedMaxAge" to "sharedAge"
            if (isset($config['defaults']['sharedMaxAge'])) {
                $config['defaults']['sharedAge'] = $config['defaults']['sharedMaxAge'];
                unset($config['defaults']['sharedMaxAge']);
            }

            $config['defaults'] = array_merge($config['defaults'] ?? [], [
                '_controller' => TemplateController::class,
                'template' => $config['template'],
            ]);
            unset($config['template']);
        } elseif (isset($config['redirect'])) {
            $config['defaults'] = array_merge($config['defaults'] ?? [], [
                '_controller' => RedirectController::class.'::redirectAction',
                'route' => $config['redirect'],
            ]);
            unset($config['redirect']);
        } elseif (isset($config['url_redirect'])) {
            $config['defaults'] = array_merge($config['defaults'] ?? [], [
                '_controller' => RedirectController::class.'::urlRedirectAction',
                'path' => $config['url_redirect'],
            ]);
            unset($config['url_redirect']);
        }

        parent::parseRoute($collection, $name, $config, $path);
    }
}
