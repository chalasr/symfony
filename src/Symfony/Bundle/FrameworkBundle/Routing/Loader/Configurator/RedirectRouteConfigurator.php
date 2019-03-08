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
class RedirectRouteConfigurator extends AbstractRouteConfiguratorDecorator
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
     * @param bool|array $ignoreAttributes Whether to ignore attributes or an array of attributes to ignore
     *
     * @return $this
     */
    final public function ignoreAttributes($ignoreAttributes)
    {
        return $this->defaults(['ignoreAttributes' => $ignoreAttributes]);
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

    /**
     * @param bool $keepQueryParams Whether redirect action should keep query parameters
     *
     * @return $this
     */
    final public function keepQueryParams(bool $keepQueryParams)
    {
        return $this->defaults(['keepQueryParams' => $keepQueryParams]);
    }
}
