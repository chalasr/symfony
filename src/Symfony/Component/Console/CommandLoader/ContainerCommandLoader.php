<?php

namespace Symfony\Component\Console\CommandLoader;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Exception\CommandNotFoundException;

/**
 * Loads commands from a PSR-11 container.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class ContainerCommandLoader implements CommandLoaderInterface
{
    private $container;
    private $names;

    public function __construct(ContainerInterface $container, array $names)
    {
        $this->container = $container;
        $this->names = $names;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new CommandNotFoundException(sprintf('Command "%s" does not exist.', $name));
        }

        return $this->container->get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        return in_array($name, $this->names, true);
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        foreach ($this->names as $name) {
            yield $name => $this->container->get($name);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNames()
    {
        return $this->names;
    }
}
