<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Cache;

use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Robin Chalas <robin.chalas@gmail.com>
 *
 * @internal
 */
final class CachePoolRegistry
{
    private $pools = array();

    public function add($id, CacheItemPoolInterface $pool)
    {
        $this->pools[$id] = $pool;
    }

    public function has($id)
    {
        return isset($this->pools[$id]);
    }

    public function get($id)
    {
        if (!$this->has($id)) {
            throw new \InvalidArgumentException(sprintf('Cache pool "%s" is not referenced.', $id));
        }

        return $this->pools[$id];
    }
}
