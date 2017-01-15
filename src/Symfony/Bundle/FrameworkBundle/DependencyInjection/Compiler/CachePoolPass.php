<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler;

@trigger_error(sprintf('The %s class is deprecated since version 3.3 and will be removed in 4.0. Use Symfony\Component\Cache\DependencyInjection\CachePoolPass instead.', CachePoolPass::class), E_USER_DEPRECATED);

use Symfony\Component\Cache\DependencyInjection\CachePoolPass as BaseCachePoolPass;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class CachePoolPass extends BaseCachePoolPass
{
}
