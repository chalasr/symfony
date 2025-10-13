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
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:variadic', description: 'Tests variadic value resolver')]
class VariadicCommand
{
    public function __invoke(
        OutputInterface $output,
        #[Argument] string ...$files,
    ): int {
        $output->writeln('Files: '.implode(', ', $files));
        $output->writeln('Count: '.\count($files));

        return Command::SUCCESS;
    }
}
