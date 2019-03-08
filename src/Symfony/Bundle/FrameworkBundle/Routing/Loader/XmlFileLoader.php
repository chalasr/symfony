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
        if ($template = $node->getAttribute('template')) {
            $node->removeAttribute('template');
            $node->setAttribute('controller', TemplateController::class);

            $templateNode = new \DOMElement('default', $template);
            $templateNode->setAttribute('key', 'template');
            $node->appendChild($templateNode);

            // normalize "sharedMaxAge" to "sharedAge"
            foreach ($node->getElementsByTagNameNS(self::NAMESPACE_URI, 'default') as $n) {
                /** @var \DOMElement $n */
                if ($node !== $n->parentNode || 'sharedMaxAge' !== $n->getAttribute('key')) {
                    continue;
                }

                $n->setAttribute('key', 'sharedAge');

                break;
            }
        } elseif ($redirect = $node->getAttribute('redirect')) {
            $node->removeAttribute('redirect');
            $node->setAttribute('controller', RedirectController::class.'::redirectAction');

            $redirectNode = new \DOMElement('default', $redirect);
            $redirectNode->setAttribute('key', 'route');
            $node->appendChild($redirectNode);
        } elseif ($urlRedirect = $node->getAttribute('url-redirect')) {
            $node->removeAttribute('url-redirect');
            $node->setAttribute('controller', RedirectController::class.'::urlRedirectAction');

            $urlRedirectNode = new \DOMElement('default', $urlRedirect);
            $urlRedirectNode->setAttribute('key', $urlRedirect);
            $node->appendChild($urlRedirectNode);
        }

        parent::parseRoute($collection, $node, $path);
    }
}
