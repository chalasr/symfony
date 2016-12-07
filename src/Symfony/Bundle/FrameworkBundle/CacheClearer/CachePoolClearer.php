<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\CacheClearer;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

/**
 * @author Robin Chalas <robin.chalas@gmail.com>
 *
 * @internal
 */
final class CachePoolClearer implements CacheClearerInterface
{
    private $pools = array();

    public function addPool($id, CacheItemPoolInterface $pool)
    {
        $this->pools[$id] = $pool;
    }

    public function hasPool($id)
    {
        return isset($this->pools[$id]);
    }

    public function clearPool($id)
    {
        if (!$this->hasPool($id)) {
            throw new \InvalidArgumentException(sprintf('Cache pool "%s" doesn\'t exist', $id));
        }

        $this->pools[$id]->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function clear($cacheDir)
    {
        foreach ($this->pools as $pool) {
            $pool->clear();
        }
    }
}
