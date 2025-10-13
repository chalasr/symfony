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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:auto-tagged-resolver')]
class AutoTaggedResolverCommand
{
    public function __invoke(
        OutputInterface $output,
        string $autoTagged
    ): int {
        $output->writeln("Auto-tagged: {$autoTagged}");

        return Command::SUCCESS;
    }
}
