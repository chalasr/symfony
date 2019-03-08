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
class UrlRedirectRouteConfigurator extends AbstractRouteConfiguratorDecorator
{
    /**
     * @param bool $permanent Whether the redirection is permanent
     *
     * @return $this
     */
    final public function permanent(bool $permanent)
    {
        return $this->defaults(['permanent' => $permanent]);
    }

    /**
     * @param string|null $scheme The URL scheme (null to keep the current one)
     *
     * @return $this
     */
    final public function scheme(?string $scheme)
    {
        return $this->defaults(['scheme' => $scheme]);
    }

    /**
     * @param int|null $httpPort The HTTP port (null to keep the current one for the same scheme or the default configured port)
     *
     * @return $this
     */
    final public function httpPort(?int $httpPort)
    {
        return $this->defaults(['httpPort' => $httpPort]);
    }

    /**
     * @param int|null $httpsPort The HTTPS port (null to keep the current one for the same scheme or the default configured port)
     *
     * @return $this
     */
    final public function httpsPort(?int $httpsPort)
    {
        return $this->defaults(['httpsPort' => $httpsPort]);
    }

    /**
     * @param bool $keepRequestMethod Whether redirect action should keep HTTP request method
     *
     * @return $this
     */
    final public function keepRequestMethod(bool $keepRequestMethod)
    {
        return $this->defaults(['keepRequestMethod' => $keepRequestMethod]);
    }
}
