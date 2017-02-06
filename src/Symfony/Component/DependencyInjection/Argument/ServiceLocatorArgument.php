<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Argument;

use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Represents a service locator able to lazy load a given range of services.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 *
 * @experimental in version 3.3
 */
class ServiceLocatorArgument implements ArgumentInterface
{
    private $values;

    /**
     * @param array $values An array of mixed entries indexed by identifier
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function getValues()
    {
        return $this->values;
    }

    public function setValues(array $values)
    {
        $this->values = $values;
    }
}
