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
use Symfony\Component\Routing\Loader\XmlFileLoader as BaseXmlFileLoader;
use Symfony\Component\Routing\RouteCollection;

/**
 * @author Jules Pietri <jules@heahprod.com>
 */
class XmlFileLoader extends BaseXmlFileLoader
{
    const SCHEME_PATH = __DIR__.'/../../Resources/config/schema/routing-1.0.xsd';

    protected function parseRoute(RouteCollection $collection, \DOMElement $node, $path)
    {
        if ($node->hasAttribute('template')) {
            $node->setAttribute('controller', TemplateController::class);
            $this->setDefault($node, 'template');

            if ($node->hasAttribute('max-age')) {
                $this->setDefault($node, 'max-age', 'maxAge');
            }
            if ($node->hasAttribute('shared-max-age')) {
                $this->setDefault($node, 'shared-max-age', 'sharedAge');
            }
            if ($node->hasAttribute('private')) {
                $this->setDefault($node, 'private');
            }
        } elseif ($node->hasAttribute('redirect')) {
            $node->setAttribute('controller', RedirectController::class.'::redirectAction');
            $this->setDefault($node, 'redirect', 'route');

            if ($node->hasAttribute('permanent')) {
                $this->setDefault($node, 'permanent');
            }
            if ($node->hasAttribute('ignore-attributes')) {
                $this->setDefault($node, 'ignore-attributes', 'ignoreAttributes');
            }
            if ($node->hasAttribute('keep-method-name')) {
                $this->setDefault($node, 'keep-method-name', 'keepMethodName');
            }
        } elseif ($node->hasAttribute('url-redirect')) {
            $node->setAttribute('controller', RedirectController::class.'::urlRedirectAction');
            $this->setDefault($node, 'url-redirect', 'path');

            if ($node->hasAttribute('permanent')) {
                $this->setDefault($node, 'permanent');
            }
            if ($node->hasAttribute('scheme')) {
                $this->setDefault($node, 'scheme');
            }
            if ($node->hasAttribute('http-port')) {
                $this->setDefault($node, 'http-port', 'httpPort');
            }
            if ($node->hasAttribute('https-port')) {
                $this->setDefault($node, 'https-port', 'httpsPort');
            }
            if ($node->hasAttribute('keep-method-name')) {
                $this->setDefault($node, 'keep-method-name', 'keepMethodName');
            }
        }

        parent::parseRoute($collection, $node, $path);
    }

    private function setDefault(\DOMElement $node, string $attribute, string $defaultName = null): void
    {
        $redirectNode = new \DOMElement('default', $node->getAttribute($attribute));
        $redirectNode->setAttribute('key', $defaultName ?: $attribute);
        $node->appendChild($redirectNode);
        $node->removeAttribute($attribute);
    }
}
