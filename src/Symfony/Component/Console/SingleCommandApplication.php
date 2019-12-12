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
class SingleCommandApplication
{
    private $command;
    private $version = 'UNKNOWN';
    private $running = false;

    public function __construct()
    {
        $this->command = new Command;
    }

    public function setCode(callable $code): self
    {
        $this->command->setCode($code);

        return $this;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function __call($method, $args)
    {
        if ('run' === $method && !$this->running) {
            return $this->doRun();
        }

        if ($this->command === $retval = call_user_func_array([$this->command, $method], $args)) {
            return $this;
        }

        return $retval;
    }

    private function doRun(): int
    {
        if (!$this->command) {
            throw new \LogicException('"setCode()" must be called beforehand.');
        }

        // We use the command name as the application name
        $application = new Application($this->command->getName() ?: 'UNKNOWN', $this->version);
        // Fix the usage of the command displayed with "--help"
        $this->setName($_SERVER['argv'][0]);

        $application->add($this->command);
        $application->setDefaultCommand($this->command->getName(), true);

        $this->running = true;
        $exitCode = $application->run();
        $this->running = false;

        return $exitCode;
    }
}
