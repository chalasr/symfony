<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class AddValidatorInitializersPass implements CompilerPassInterface
{
    private $builderService;
    private $initializerTag;

    public function __construct($builderService = 'validator.builder', $initializerTag = 'validator.initializer')
    {
        $this->builderService = $builderService;
        $this->initializerTag = $initializerTag;
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition($this->builderService)) {
            return;
        }

        $validatorBuilder = $container->getDefinition($this->builderService);

        $initializers = array();
        foreach ($container->findTaggedServiceIds($this->initializerTag) as $id => $attributes) {
            $initializers[] = new Reference($id);
        }

        $validatorBuilder->addMethodCall('addObjectInitializers', array($initializers));
    }
}
