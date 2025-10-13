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
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

#[AsCommand(name: 'app:uid', description: 'Tests UID value resolver')]
class UidCommand
{
    public function __invoke(
        OutputInterface $output,
        #[Argument] Uuid $id,
        #[Option] ?Ulid $reference = null,
    ): int {
        $output->writeln('UUID: '.$id);
        $output->writeln('ULID: '.($reference ?? 'none'));

        return Command::SUCCESS;
    }
}
