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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class AddDebugLogProcessorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('profiler')) {
            $container->log($this, 'Service "profiler" does not exist, skipping.');

            return;
        }
        if (!$container->hasDefinition('monolog.logger_prototype')) {
            $container->log($this, 'Service "monolog.logger_type" does not exist, skipping.');

            return;
        }
        if (!$container->hasDefinition('debug.log_processor')) {
            $container->log($this, 'Service "debug.log_processor" does not exist, skipping.');

            return;
        }

        $definition = $container->getDefinition('monolog.logger_prototype');
        $definition->addMethodCall('pushProcessor', array(new Reference('debug.log_processor')));
    }
}
