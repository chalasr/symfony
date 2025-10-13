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
use Symfony\Component\Console\Attribute\ValueResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:targeted-resolver')]
class TargetedResolverCommand
{
    public function __invoke(
        OutputInterface $output,
        #[ValueResolver('targeted')]
        TargetedCustomType $targeted
    ): int {
        $output->writeln("Targeted: {$targeted->value}");

        return Command::SUCCESS;
    }
}
