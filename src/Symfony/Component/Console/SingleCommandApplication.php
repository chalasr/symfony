<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console;

use Symfony\Component\Console\Command\Command;

/**
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class SingleCommandApplication extends Command
{
    private $version = 'UNKNOWN';

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function __destruct()
    {
        // We use the command name as the application name
        $application = new Application($this->getName() ?: 'UNKNOWN', $this->version);
        // Fix the usage of the command displayed with "--help"
        $this->setName($_SERVER['argv'][0]);
        $application->add($this);
        $application->setDefaultCommand($this->getName(), true);
        $application->run();
    }
}
