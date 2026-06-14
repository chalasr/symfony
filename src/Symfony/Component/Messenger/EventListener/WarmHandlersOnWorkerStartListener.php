<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Messenger\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

/**
 * Eagerly resolves message handlers from the DI container when a worker starts,
 * so the first message doesn't pay the lazy initialization cost.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
final class WarmHandlersOnWorkerStartListener implements EventSubscriberInterface
{
    /**
     * @param array<string, list<class-string>> $messagesByTransport
     */
    public function __construct(
        private HandlersLocatorInterface $handlersLocator,
        private array $messagesByTransport,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function onWorkerStarted(WorkerStartedEvent $event): void
    {
        $seen = [];

        foreach ($event->getWorker()->getMetadata()->getTransportNames() as $transportName) {
            foreach ($this->messagesByTransport[$transportName] ?? [] as $messageClass) {
                if (isset($seen[$messageClass]) || !class_exists($messageClass)) {
                    continue;
                }

                $seen[$messageClass] = true;

                $envelope = new Envelope(
                    (new \ReflectionClass($messageClass))->newInstanceWithoutConstructor(),
                    [new ReceivedStamp($transportName)],
                );

                foreach ($this->handlersLocator->getHandlers($envelope) as $handlerDescriptor) {
                    $this->logger?->debug('Warmed handler "{name}"', [
                        'name' => $handlerDescriptor->getName(),
                    ]);
                }
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerStartedEvent::class => 'onWorkerStarted',
        ];
    }
}
