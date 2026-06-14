<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Messenger\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\EventListener\WarmHandlersOnWorkerStartListener;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;
use Symfony\Component\Messenger\Tests\Fixtures\DummyMessage;
use Symfony\Component\Messenger\Worker;
use Symfony\Component\Messenger\WorkerMetadata;

class WarmHandlersOnWorkerStartListenerTest extends TestCase
{
    public function testResolvesHandlersForMatchingTransport()
    {
        $handler = new HandlerDescriptor(function () {});

        $locator = $this->createMock(HandlersLocatorInterface::class);
        $locator->expects($this->once())
            ->method('getHandlers')
            ->willReturn(new \ArrayIterator([$handler]));

        $listener = new WarmHandlersOnWorkerStartListener($locator, [
            'async' => [DummyMessage::class],
        ]);

        $listener->onWorkerStarted($this->createWorkerEvent(['async']));
    }

    public function testSkipsUnknownTransport()
    {
        $locator = $this->createMock(HandlersLocatorInterface::class);
        $locator->expects($this->never())->method('getHandlers');

        $listener = new WarmHandlersOnWorkerStartListener($locator, [
            'async' => [DummyMessage::class],
        ]);

        $listener->onWorkerStarted($this->createWorkerEvent(['unknown']));
    }

    public function testDeduplicatesMessageClassesAcrossTransports()
    {
        $handler = new HandlerDescriptor(function () {});

        $locator = $this->createMock(HandlersLocatorInterface::class);
        // Should only be called once despite DummyMessage appearing in both transports
        $locator->expects($this->once())
            ->method('getHandlers')
            ->willReturn(new \ArrayIterator([$handler]));

        $listener = new WarmHandlersOnWorkerStartListener($locator, [
            'async' => [DummyMessage::class],
            'crons' => [DummyMessage::class],
        ]);

        $listener->onWorkerStarted($this->createWorkerEvent(['async', 'crons']));
    }

    public function testSkipsNonExistentMessageClasses()
    {
        $locator = $this->createMock(HandlersLocatorInterface::class);
        $locator->expects($this->never())->method('getHandlers');

        $listener = new WarmHandlersOnWorkerStartListener($locator, [
            'async' => ['App\\NonExistent\\Message'],
        ]);

        $listener->onWorkerStarted($this->createWorkerEvent(['async']));
    }

    public function testHandlesEmptyMapping()
    {
        $locator = $this->createMock(HandlersLocatorInterface::class);
        $locator->expects($this->never())->method('getHandlers');

        $listener = new WarmHandlersOnWorkerStartListener($locator, []);

        $listener->onWorkerStarted($this->createWorkerEvent(['async']));
    }

    private function createWorkerEvent(array $transportNames): WorkerStartedEvent
    {
        $worker = (new \ReflectionClass(Worker::class))->newInstanceWithoutConstructor();
        $metadata = new \ReflectionProperty(Worker::class, 'metadata');
        $metadata->setValue($worker, new WorkerMetadata(['transportNames' => $transportNames]));

        return new WorkerStartedEvent($worker);
    }
}
