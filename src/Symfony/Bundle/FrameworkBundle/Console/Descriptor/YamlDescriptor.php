<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Console\Descriptor;

use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Dumper;

/**
 * @author Robin Chalas <robin.chalas@gmail.com>
 *
 * @internal
 */
class YamlDescriptor extends Descriptor
{
    /**
     * {@inheritdoc}
     */
    protected function describeRouteCollection(RouteCollection $routes, array $options = array())
    {
        $this->write($this->getRouteCollectionData($routes));
    }

    /**
     * {@inheritdoc}
     */
    protected function describeRoute(Route $route, array $options = array())
    {
        $this->write($this->getRouteData($route, isset($options['name']) ? $options['name'] : null));
    }

    /**
     * {@inheritdoc}
     */
    protected function describeContainerParameters(ParameterBag $parameters, array $options = array())
    {
        $this->write($this->getContainerParametersData($parameters));
    }

    /**
     * {@inheritdoc}
     */
    protected function describeContainerTags(ContainerBuilder $builder, array $options = array())
    {
        $this->write($this->getContainerTagsData($builder, isset($options['show_private']) && $options['show_private']));
    }

    /**
     * {@inheritdoc}
     */
    protected function describeContainerService($service, array $options = array(), ContainerBuilder $builder = null)
    {
        if (!isset($options['id'])) {
            throw new \InvalidArgumentException('An "id" option must be provided.');
        }

        $this->write($this->getContainerServiceData($service, $options['id'], $builder));
    }

    /**
     * {@inheritdoc}
     */
    protected function describeContainerServices(ContainerBuilder $builder, array $options = array())
    {
        $this->write($this->getContainerServicesData($builder, isset($options['tag']) ? $options['tag'] : null, isset($options['show_private']) && $options['show_private']));
    }

    /**
     * {@inheritdoc}
     */
    protected function describeContainerDefinition(Definition $definition, array $options = array())
    {
        $this->write($this->getContainerDefinitionData($definition, isset($options['id']) ? $options['id'] : null, isset($options['omit_tags']) && $options['omit_tags']));
    }

    /**
     * {@inheritdoc}
     */
    protected function describeContainerAlias(Alias $alias, array $options = array(), ContainerBuilder $builder = null)
    {
        $dom = new \DOMData('1.0', 'UTF-8');
        $dom->appendChild($dom->importNode($this->getContainerAliasData($alias, isset($options['id']) ? $options['id'] : null)->childNodes->item(0), true));

        if (!$builder) {
            return $this->write($dom);
        }

        $dom->appendChild($dom->importNode($this->getContainerDefinitionData($builder->getDefinition((string) $alias), (string) $alias)->childNodes->item(0), true));

        $this->write($dom);
    }

    /**
     * {@inheritdoc}
     */
    protected function describeEventDispatcherListeners(EventDispatcherInterface $eventDispatcher, array $options = array())
    {
        $this->write($this->getEventDispatcherListenersData($eventDispatcher, array_key_exists('event', $options) ? $options['event'] : null));
    }

    /**
     * {@inheritdoc}
     */
    protected function describeCallable($callable, array $options = array())
    {
        $this->write($this->getCallableData($callable));
    }

    /**
     * {@inheritdoc}
     */
    protected function describeContainerParameter($parameter, array $options = array())
    {
        $this->write($this->getContainerParameterData($parameter, $options));
    }

    /**
     * {@inheritdoc}
     */
    protected function write($content, $decorated = false)
    {
        parent::write((new Dumper())->dump($content, 10), $decorated);
    }

    /**
     * @param RouteCollection $routes
     *
     * @return \DOMDocument
     */
    private function getRouteCollectionData(RouteCollection $routes)
    {
        $data = array();

        foreach ($routes->all() as $name => $route) {
            $data[] = $this->getRouteData($route, $name);
        }

        return array('routes' => $data);
    }

    /**
     * @param Route       $route
     * @param string|null $name
     *
     * @return \DOMDocument
     */
    private function getRouteData(Route $route, $name = null)
    {
        return array(
            'name' => $name,
            'class' => get_class($route),
            'path' => array('raw' => $route->getPath(), 'regex' => $route->compile()->getRegex()),
            'host' => array('raw' => $route->getHost() ?: null, 'regex' => $route->getHost() ? $route->compile()->getHostRegex() : null),
            'schemes' => $route->getSchemes(),
            'methods' => $route->getMethods(),
            'defaults' => $route->getDefaults(),
            'requirements' => $route->getRequirements(),
            'options' => $route->getOptions(),
        );
    }

    /**
     * @param ParameterBag $parameters
     *
     * @return \DOMDocument
     */
    private function getContainerParametersData(ParameterBag $parameters)
    {
        return array('parameters' => $this->sortParameters($parameters));
    }

    /**
     * @param ContainerBuilder $builder
     * @param bool             $showPrivate
     *
     * @return \DOMDocument
     */
    private function getContainerTagsData(ContainerBuilder $builder, $showPrivate = false)
    {
        $data = array();

        foreach ($this->findDefinitionsByTag($builder, $showPrivate) as $tag => $definitions) {
            $definitionsData = array();
            foreach ($definitions as $serviceId => $definition) {
                $definitionsData[] = $this->getContainerDefinitionData($definition, $serviceId, true);
            }

            $data[] = array('name' => $tag, 'definitions' => $definitionsData);
        }

        return $data;
    }

    /**
     * @param mixed                 $service
     * @param string                $id
     * @param ContainerBuilder|null $builder
     *
     * @return \DOMDocument
     */
    private function getContainerServiceData($service, $id, ContainerBuilder $builder = null)
    {
        $dom = new \DOMData('1.0', 'UTF-8');

        if ($service instanceof Alias) {
            $dom->appendChild($dom->importNode($this->getContainerAliasData($service, $id)->childNodes->item(0), true));
            if ($builder) {
                $dom->appendChild($dom->importNode($this->getContainerDefinitionData($builder->getDefinition((string) $service), (string) $service)->childNodes->item(0), true));
            }
        } elseif ($service instanceof Definition) {
            $dom->appendChild($dom->importNode($this->getContainerDefinitionData($service, $id)->childNodes->item(0), true));
        } else {
            $dom->appendChild($serviceXML = $dom->createElement('service'));
            $serviceXML->setAttribute('id', $id);
            $serviceXML->setAttribute('class', get_class($service));
        }

        return $dom;
    }

    /**
     * @param ContainerBuilder $builder
     * @param string|null      $tag
     * @param bool             $showPrivate
     *
     * @return \DOMDocument
     */
    private function getContainerServicesData(ContainerBuilder $builder, $tag = null, $showPrivate = false)
    {
        $dom = new \DOMData('1.0', 'UTF-8');
        $dom->appendChild($containerXML = $dom->createElement('container'));

        $serviceIds = $tag ? array_keys($builder->findTaggedServiceIds($tag)) : $builder->getServiceIds();

        foreach ($this->sortServiceIds($serviceIds) as $serviceId) {
            $service = $this->resolveServiceDefinition($builder, $serviceId);

            if ($service instanceof Definition && !($showPrivate || $service->isPublic())) {
                continue;
            }

            $serviceXML = $this->getContainerServiceData($service, $serviceId);
            $containerXML->appendChild($containerXML->ownerDocument->importNode($serviceXML->childNodes->item(0), true));
        }

        return $dom;
    }

    /**
     * @param Definition  $definition
     * @param string|null $id
     * @param bool        $omitTags
     *
     * @return \DOMDocument
     */
    private function getContainerDefinitionData(Definition $definition, $id = null, $omitTags = false)
    {
        $dom = new \DOMData('1.0', 'UTF-8');
        $dom->appendChild($serviceXML = $dom->createElement('definition'));

        if ($id) {
            $serviceXML->setAttribute('id', $id);
        }

        $serviceXML->setAttribute('class', $definition->getClass());

        if ($factory = $definition->getFactory()) {
            $serviceXML->appendChild($factoryXML = $dom->createElement('factory'));

            if (is_array($factory)) {
                if ($factory[0] instanceof Reference) {
                    $factoryXML->setAttribute('service', (string) $factory[0]);
                } elseif ($factory[0] instanceof Definition) {
                    throw new \InvalidArgumentException('Factory is not describable.');
                } else {
                    $factoryXML->setAttribute('class', $factory[0]);
                }
                $factoryXML->setAttribute('method', $factory[1]);
            } else {
                $factoryXML->setAttribute('function', $factory);
            }
        }

        $serviceXML->setAttribute('public', $definition->isPublic() ? 'true' : 'false');
        $serviceXML->setAttribute('synthetic', $definition->isSynthetic() ? 'true' : 'false');
        $serviceXML->setAttribute('lazy', $definition->isLazy() ? 'true' : 'false');
        if (method_exists($definition, 'isShared')) {
            $serviceXML->setAttribute('shared', $definition->isShared() ? 'true' : 'false');
        }
        $serviceXML->setAttribute('abstract', $definition->isAbstract() ? 'true' : 'false');

        if (method_exists($definition, 'isAutowired')) {
            $serviceXML->setAttribute('autowired', $definition->isAutowired() ? 'true' : 'false');
        }

        $serviceXML->setAttribute('file', $definition->getFile());

        $calls = $definition->getMethodCalls();
        if (count($calls) > 0) {
            $serviceXML->appendChild($callsXML = $dom->createElement('calls'));
            foreach ($calls as $callData) {
                $callsXML->appendChild($callXML = $dom->createElement('call'));
                $callXML->setAttribute('method', $callData[0]);
            }
        }

        if (!$omitTags) {
            $tags = $definition->getTags();

            if (count($tags) > 0) {
                $serviceXML->appendChild($tagsXML = $dom->createElement('tags'));
                foreach ($tags as $tagName => $tagData) {
                    foreach ($tagData as $parameters) {
                        $tagsXML->appendChild($tagXML = $dom->createElement('tag'));
                        $tagXML->setAttribute('name', $tagName);
                        foreach ($parameters as $name => $value) {
                            $tagXML->appendChild($parameterXML = $dom->createElement('parameter'));
                            $parameterXML->setAttribute('name', $name);
                            $parameterXML->appendChild(new \DOMText($this->formatParameter($value)));
                        }
                    }
                }
            }
        }

        return $dom;
    }

    /**
     * @param Alias       $alias
     * @param string|null $id
     *
     * @return \DOMDocument
     */
    private function getContainerAliasData(Alias $alias, $id = null)
    {
        $dom = new \DOMData('1.0', 'UTF-8');
        $dom->appendChild($aliasXML = $dom->createElement('alias'));

        if ($id) {
            $aliasXML->setAttribute('id', $id);
        }

        $aliasXML->setAttribute('service', (string) $alias);
        $aliasXML->setAttribute('public', $alias->isPublic() ? 'true' : 'false');

        return $dom;
    }

    /**
     * @param string $parameter
     * @param array  $options
     *
     * @return \DOMDocument
     */
    private function getContainerParameterData($parameter, $options = array())
    {
        $dom = new \DOMData('1.0', 'UTF-8');
        $dom->appendChild($parameterXML = $dom->createElement('parameter'));

        if (isset($options['parameter'])) {
            $parameterXML->setAttribute('key', $options['parameter']);
        }

        $parameterXML->appendChild(new \DOMText($this->formatParameter($parameter)));

        return $dom;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param string|null              $event
     *
     * @return \DOMDocument
     */
    private function getEventDispatcherListenersData(EventDispatcherInterface $eventDispatcher, $event = null)
    {
        $dom = new \DOMData('1.0', 'UTF-8');
        $dom->appendChild($eventDispatcherXML = $dom->createElement('event-dispatcher'));

        $registeredListeners = $eventDispatcher->getListeners($event);
        if (null !== $event) {
            $this->appendEventListenerData($eventDispatcher, $event, $eventDispatcherXML, $registeredListeners);
        } else {
            ksort($registeredListeners);

            foreach ($registeredListeners as $eventListened => $eventListeners) {
                $eventDispatcherXML->appendChild($eventXML = $dom->createElement('event'));
                $eventXML->setAttribute('name', $eventListened);

                $this->appendEventListenerData($eventDispatcher, $eventListened, $eventXML, $eventListeners);
            }
        }

        return $dom;
    }

    /**
     * @param \DOMElement $element
     * @param array       $eventListeners
     */
    private function appendEventListenerData(EventDispatcherInterface $eventDispatcher, $event, \DOMElement $element, array $eventListeners)
    {
        foreach ($eventListeners as $listener) {
            $callableXML = $this->getCallableData($listener);
            $callableXML->childNodes->item(0)->setAttribute('priority', $eventDispatcher->getListenerPriority($event, $listener));

            $element->appendChild($element->ownerDocument->importNode($callableXML->childNodes->item(0), true));
        }
    }

    /**
     * @param callable $callable
     *
     * @return \DOMDocument
     */
    private function getCallableData($callable)
    {
        $dom = new \DOMData('1.0', 'UTF-8');
        $dom->appendChild($callableXML = $dom->createElement('callable'));

        if (is_array($callable)) {
            $callableXML->setAttribute('type', 'function');

            if (is_object($callable[0])) {
                $callableXML->setAttribute('name', $callable[1]);
                $callableXML->setAttribute('class', get_class($callable[0]));
            } else {
                if (0 !== strpos($callable[1], 'parent::')) {
                    $callableXML->setAttribute('name', $callable[1]);
                    $callableXML->setAttribute('class', $callable[0]);
                    $callableXML->setAttribute('static', 'true');
                } else {
                    $callableXML->setAttribute('name', substr($callable[1], 8));
                    $callableXML->setAttribute('class', $callable[0]);
                    $callableXML->setAttribute('static', 'true');
                    $callableXML->setAttribute('parent', 'true');
                }
            }

            return $dom;
        }

        if (is_string($callable)) {
            $callableXML->setAttribute('type', 'function');

            if (false === strpos($callable, '::')) {
                $callableXML->setAttribute('name', $callable);
            } else {
                $callableParts = explode('::', $callable);

                $callableXML->setAttribute('name', $callableParts[1]);
                $callableXML->setAttribute('class', $callableParts[0]);
                $callableXML->setAttribute('static', 'true');
            }

            return $dom;
        }

        if ($callable instanceof \Closure) {
            $callableXML->setAttribute('type', 'closure');

            return $dom;
        }

        if (method_exists($callable, '__invoke')) {
            $callableXML->setAttribute('type', 'object');
            $callableXML->setAttribute('name', get_class($callable));

            return $dom;
        }

        throw new \InvalidArgumentException('Callable is not describable.');
    }
}
