<?php

namespace AppBundle;


use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleGuard implements EventSubscriberInterface
{
    private $env;

    public function __construct($env)
    {
        $this->env = $env;
    }

    public static function getSubscribedEvents()
    {
        return ['console.command' => 'filterCommands'];
    }

    public function filterCommands(ConsoleCommandEvent $event)
    {
        if ('prod' !== $this->env || !$command = $event->getCommand()) {
            return;
        }

        $app = $command->getApplication();

        foreach ($app->all('doctrine') as $name => $command) {
            $app->remove($name);
        }
    }
}
