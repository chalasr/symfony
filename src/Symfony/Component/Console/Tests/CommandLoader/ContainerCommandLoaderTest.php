<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ContainerCommandLoaderTest extends TestCase
{
    public function testHas()
    {
        $loader = new ContainerCommandLoader(new ServiceLocator(array(
            'foo' => function () { return new Command('foo'); },
            'bar' => function () { return new Command('bar'); },
        )), array('foo', 'bar'));

        $this->assertTrue($loader->has('foo'));
        $this->assertTrue($loader->has('bar'));
        $this->assertFalse($loader->has('baz'));
    }

    public function testGet()
    {
        $loader = new ContainerCommandLoader(new ServiceLocator(array(
            'foo' => function () { return new Command('foo'); },
            'bar' => function () { return new Command('bar'); },
        )), array('foo', 'bar'));

        $this->assertInstanceOf(Command::class, $loader->get('foo'));
        $this->assertInstanceOf(Command::class, $loader->get('bar'));
    }

    /**
     * @expectedException \Symfony\Component\Console\Exception\CommandNotFoundException
     */
    public function testGetUnknownCommandThrows()
    {
        (new ContainerCommandLoader(new ServiceLocator(array()), array()))->get('unknown');
    }

    public function testAll()
    {
        $loader = new ContainerCommandLoader(new ServiceLocator(array(
            'foo' => function () { return new Command('foo'); },
            'bar' => function () { return new Command('bar'); },
        )), array('foo', 'bar'));

        $this->assertEquals(array('foo' => new Command('foo'), 'bar' => new Command('bar')), iterator_to_array($loader->all()));
    }

    public function testGetCommandNames()
    {
        $loader = new ContainerCommandLoader(new ServiceLocator(array()), array('foo', 'bar'));

        $this->assertSame(array('foo', 'bar'), $loader->getNames());
    }
}
