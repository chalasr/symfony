<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Fixtures\Console;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:method-invoke')]
class MethodBasedCommand
{
    public function __invoke(
        OutputInterface $output,
        TestService $service,
        #[Argument] string $name = 'default'
    ): int {
        $output->writeln("Invoke: {$service->getMessage()} - {$name}");

        return Command::SUCCESS;
    }

    #[AsCommand('app:method-cmd1')]
    public function cmd1(
        OutputInterface $output,
        TestService $service,
        #[Argument] string $value = 'cmd1-default'
    ): int {
        $output->writeln("Cmd1: {$service->getMessage()} - {$value}");

        return Command::SUCCESS;
    }

    #[AsCommand('app:method-cmd2')]
    public function cmd2(
        OutputInterface $output,
        TestService $service,
        #[Argument] string $data = 'cmd2-default'
    ): int {
        $output->writeln("Cmd2: {$service->getMessage()} - {$data}");

        return Command::SUCCESS;
    }
}
