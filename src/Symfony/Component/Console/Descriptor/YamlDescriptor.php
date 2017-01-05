<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Descriptor;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Dumper;

/**
 * @author Robin Chalas <robin.chalas@gmail.com>
 *
 * @internal
 */
class YamlDescriptor extends Descriptor
{
    /**
     * {@inheritdoc}
     */
    protected function describeInputArgument(InputArgument $argument, array $options = array())
    {
        $this->write($this->getInputArgumentData($argument));
    }

    /**
     * {@inheritdoc}
     */
    protected function describeInputOption(InputOption $option, array $options = array())
    {
        $this->write($this->getInputOptionData($option));
    }

    /**
     * {@inheritdoc}
     */
    protected function describeInputDefinition(InputDefinition $definition, array $options = array())
    {
        $this->write($this->getInputDefinitionData($definition));
    }

    /**
     * {@inheritdoc}
     */
    protected function describeCommand(Command $command, array $options = array())
    {
        $this->write($this->getCommandData($command));
    }

    /**
     * {@inheritdoc}
     */
    protected function describeApplication(Application $application, array $options = array())
    {
        $this->write($this->getApplicationData($application, isset($options['namespace']) ? $options['namespace'] : null));
    }

    protected function write($content, $decorated = false)
    {
        parent::write((new Dumper())->dump($content, 10), $decorated);
    }

    /**
     * @param InputDefinition $definition
     *
     * @return array
     */
    private function getInputDefinitionData(InputDefinition $definition)
    {
        $arguments = array();
        foreach ($definition->getArguments() as $name => $argument) {
            $arguments[] = $this->getInputArgumentData($argument);
        }

        $options = array();
        foreach ($definition->getOptions() as $name => $option) {
            $options[] = $this->getInputOptionData($option);
        }

        return array('arguments' => $arguments, 'options' => $options);
    }

    /**
     * @param Command $command
     *
     * @return array
     */
    public function getCommandData(Command $command)
    {
        $command->getSynopsis();
        $command->mergeApplicationDefinition(false);

        return array(
            'name' => $command->getName(),
            'hidden' => $command->isHidden(),
            'usages' => array_merge(array($command->getSynopsis()), $command->getAliases(), $command->getUsages()),
            'description' => $command->getDescription(),
            'help' => $command->getProcessedHelp(),
            'definition' => $this->getInputDefinitionData($command->getNativeDefinition()),
        );
    }

    private function getApplicationData(Application $application, $namespace = null)
    {
        $description = new ApplicationDescription($application, $namespace);

        return array(
            'name' => $application->getName(),
            'version' => $application->getVersion(),
            'commands' => array_map(function (Command $command) { return $this->getCommandData($command); }, $description->getCommands()),
        );
    }

    private function getInputArgumentData(InputArgument $argument)
    {
        return array(
            'name' => $argument->getName(),
            'is_required' => $argument->isRequired(),
            'is_array' => $argument->isArray(),
            'description' => $argument->getDescription(),
            'default' => $argument->getDefault(),
        );
    }

    private function getInputOptionData(InputOption $option)
    {
        $shortcuts = $option->getShortcut();

        if (false !== strpos($shortcuts, '|')) {
            $shortcuts = array_map(function ($shortcut) { return "-$shortcut"; }, explode('|', $shortcuts));
        } elseif ($shortcuts) {
            $shortcuts = array("-$shortcuts");
        } else {
            $shortcuts = array();
        }

        return array(
            'name' => sprintf('--%s', $option->getName()),
            'accept_value' => $option->acceptValue(),
            'shortcuts' => $shortcuts,
            'is_value_required' => $option->isValueRequired(),
            'is_array' => $option->isArray(),
            'description' => $option->getDescription(),
            'default' => $option->getDefault(),
        );
    }
}
