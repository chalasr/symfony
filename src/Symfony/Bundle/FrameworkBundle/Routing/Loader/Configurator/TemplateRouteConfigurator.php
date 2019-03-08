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

/**
 * @author Jules Pietri <jules@heahprod.com>
 */
class TemplateRouteConfigurator extends AbstractRouteConfiguratorDecorator
{
    /**
     * @param int|null $maxAge Max age for client caching
     *
     * @return $this
     */
    final public function maxAge(?int $maxAge)
    {
        return $this->defaults(['maxAge' => $maxAge]);
    }

    /**
     * @param int|null $sharedMaxAge Max age for shared (proxy) caching
     *
     * @return $this
     */
    final public function sharedMaxAge(?int $sharedMaxAge)
    {
        // normalize "sharedMaxAge" to "sharedAge"
        return $this->defaults(['sharedAge' => $sharedMaxAge]);
    }

    /**
     * @param bool|null $private Whether or not caching should apply for client caches only
     *
     * @return $this
     */
    final public function private(?bool $private)
    {
        return $this->defaults(['private' => $private]);
    }
}
