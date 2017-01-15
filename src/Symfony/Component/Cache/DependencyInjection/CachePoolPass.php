<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache\DependencyInjection;

use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class CachePoolPass implements CompilerPassInterface
{
    private $cachePoolTag;
    private $seed;

    public function __construct($cachePoolTag = 'cache.pool', $seed = '')
    {
        $this->cachePoolTag = $cachePoolTag;
        $this->seed = $seed;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$seed = $this->seed) {
            if ($container->hasParameter('cache.prefix.seed')) {
                $seed = '.'.$container->getParameterBag()->resolveValue($container->getParameter('cache.prefix.seed'));
            } elseif ($container->hasParameter('kernel.root_dir')) {
                $seed = '_'.$container->getParameter('kernel.root_dir');
            }

            if ($container->hasParameter('kernel.name') && $container->hasParameter('kernel.environment')) {
                $seed .= '.'.$container->getParameter('kernel.name').'.'.$container->getParameter('kernel.environment');
            }
        }

        $attributes = array(
            'provider',
            'namespace',
            'default_lifetime',
        );
        foreach ($container->findTaggedServiceIds($this->cachePoolTag) as $id => $tags) {
            $adapter = $pool = $container->getDefinition($id);
            if ($pool->isAbstract()) {
                continue;
            }
            $isLazy = $pool->isLazy();
            while ($adapter instanceof ChildDefinition) {
                $adapter = $container->findDefinition($adapter->getParent());
                $isLazy = $isLazy || $adapter->isLazy();
                if ($t = $adapter->getTag($this->cachePoolTag)) {
                    $tags[0] += $t[0];
                }
            }
            if (!isset($tags[0]['namespace'])) {
                $tags[0]['namespace'] = $this->getNamespace($seed, $id);
            }
            if (isset($tags[0]['clearer'])) {
                $clearer = $tags[0]['clearer'];
                while ($container->hasAlias($clearer)) {
                    $clearer = (string) $container->getAlias($clearer);
                }
            } else {
                $clearer = null;
            }
            unset($tags[0]['clearer']);

            if (isset($tags[0]['provider'])) {
                $tags[0]['provider'] = new Reference(static::getServiceProvider($container, $tags[0]['provider']));
            }
            $i = 0;
            foreach ($attributes as $attr) {
                if (isset($tags[0][$attr])) {
                    $pool->replaceArgument($i++, $tags[0][$attr]);
                }
                unset($tags[0][$attr]);
            }
            if (!empty($tags[0])) {
                throw new InvalidArgumentException(sprintf('Invalid "%s" tag for service "%s": accepted attributes are "clearer", "provider", "namespace" and "default_lifetime", found "%s".', $this->cachePoolTag, $id, implode('", "', array_keys($tags[0]))));
            }

            $attr = array();
            if (null !== $clearer) {
                $attr['clearer'] = $clearer;
            }
            if (!$isLazy) {
                $pool->setLazy(true);
                $attr['unlazy'] = true;
            }
            if ($attr) {
                $pool->addTag($this->cachePoolTag, $attr);
            }
        }
    }

    private function getNamespace($seed, $id)
    {
        return substr(str_replace('/', '-', base64_encode(hash('sha256', $id.$seed, true))), 0, 10);
    }

    /**
     * @internal
     */
    public static function getServiceProvider(ContainerBuilder $container, $name)
    {
        $container->resolveEnvPlaceholders($name, null, $usedEnvs);

        if ($usedEnvs || preg_match('#^[a-z]++://#', $name)) {
            $dsn = $name;

            if (!$container->hasDefinition($name = md5($dsn))) {
                $definition = new Definition(AbstractAdapter::class);
                $definition->setPublic(false);
                $definition->setFactory(array(AbstractAdapter::class, 'createConnection'));
                $definition->setArguments(array($dsn));
                $container->setDefinition($name, $definition);
            }
        }

        return $name;
    }
}
