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
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\Routing\Loader\XmlFileLoader as BaseXmlFileLoader;
use Symfony\Component\Routing\RouteCollection;

/**
 * @author Jules Pietri <jules@heahprod.com>
 */
class XmlFileLoader extends BaseXmlFileLoader
{
    public const SCHEME_PATH = __DIR__.'/../../Resources/config/schema/routing-1.0.xsd';

    protected function parseRoute(RouteCollection $collection, \DOMElement $node, $path)
    {
        parent::parseRoute($collection, $node, $path);

        $route = $collection->get($node->getAttribute(('id')));

        if ($node->hasAttribute('template')) {
            $route
                ->setDefault('_controller', TemplateController::class)
                ->setDefault('template', $node->getAttribute('template'))
                ->setDefault('maxAge', (int) $node->getAttribute('max-age') ?: null)
                ->setDefault('sharedAge', (int) $node->getAttribute('shared-max-age') ?: null)
                ->setDefault('private', $node->hasAttribute('private') ? XmlUtils::phpize($node->getAttribute('private')) : null)
            ;
        } elseif ($node->hasAttribute('redirect-to')) {
            $route
                ->setDefault('_controller', RedirectController::class.'::redirectAction')
                ->setDefault('route', $node->getAttribute('redirect-to'))
                ->setDefault('permanent', self::getBooleanAttribute($node, 'permanent'))
                ->setDefault('keepRequestMethod', self::getBooleanAttribute($node, 'keep-request-method'))
                ->setDefault('keepQueryParams', self::getBooleanAttribute($node,'keep-query-params'))
            ;
            ;
            if (is_string($ignoreAttributes = XmlUtils::phpize($node->getAttribute('ignore-attributes')))) {
                $ignoreAttributes = array_map('trim', explode(',', $ignoreAttributes));
            }
            $route->setDefault('ignoreAttributes', $ignoreAttributes);
        } elseif ($node->hasAttribute('redirect-to-url')) {
            $route
                ->setDefault('_controller', RedirectController::class.'::urlRedirectAction')
                ->setDefault('path', $node->getAttribute('redirect-to-url'))
                ->setDefault('permanent', self::getBooleanAttribute($node, 'permanent'))
                ->setDefault('scheme', $node->getAttribute('scheme'))
                ->setDefault('httpPort', (int) $node->getAttribute('http-port') ?: null)
                ->setDefault('httpsPort', (int) $node->getAttribute('https-port') ?: null)
                ->setDefault('keepRequestMethod', self::getBooleanAttribute($node, 'keep-request-method'))
            ;
        }
    }

    private static function getBooleanAttribute(\DOMElement $node, string $attribute): bool
    {
        return $node->hasAttribute($attribute) ? XmlUtils::phpize($node->getAttribute($attribute)) : false;
    }
}
