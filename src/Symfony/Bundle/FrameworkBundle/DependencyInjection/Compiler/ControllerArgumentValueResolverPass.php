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

@trigger_error(sprintf('The %s class is deprecated since version 3.3 and will be removed in 4.0. Use Symfony\Component\HttpKernel\DependencyInjection\ControllerArgumentValueResolverPass instead.', ControllerArgumentValueResolverPass::class), E_USER_DEPRECATED);

use Symfony\Component\HttpKernel\DependencyInjection\ControllerArgumentValueResolverPass as BaseControllerArgumentValueResolverPass;

/**
 * Gathers and configures the argument value resolvers.
 *
 * @author Iltar van der Berg <kjarli@gmail.com>
 *
 * @deprecated since version 3.3, to be removed in 4.0. Use {@link BaseControllerArgumentValueResolverPass}
 */
class ControllerArgumentValueResolverPass extends BaseControllerArgumentValueResolverPass
{
}
