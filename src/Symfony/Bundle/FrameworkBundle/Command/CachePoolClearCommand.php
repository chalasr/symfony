<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\CacheClearer\Psr6CacheClearer;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Clear cache pools.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
final class CachePoolClearCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cache:pool:clear')
            ->setDefinition(array(
                new InputArgument('pools', InputArgument::IS_ARRAY, 'A list of cache pools or cache pool clearers'),
            ))
            ->setDescription('Clears cache pools')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command clears the given cache pools or cache pool clearers.

    %command.full_name% <cache pool or clearer 1> [...<cache pool or clearer N>]
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $pools = array();
        $clearers = array();
        $container = $this->getContainer();
        $cacheDir = $container->getParameter('kernel.cache_dir');
        $defaultClearer = $container->get('cache.default_clearer');

        foreach ($input->getArgument('pools') as $id) {
            if ($defaultClearer->hasPool($id)) {
                $pools[$id] = $id;
            } else {
                $pool = $container->get($id);

                if ($pool instanceof CacheItemPoolInterface) {
                    $pools[$id] = $pool;
                } elseif ($pool instanceof Psr6CacheClearer) {
                    $clearers[$id] = $pool;
                } else {
                    throw new \InvalidArgumentException(sprintf('"%s" is not a cache pool nor a cache clearer.', $id));
                }
            }
        }

        foreach ($clearers as $id => $clearer) {
            $io->comment(sprintf('Calling cache clearer: <info>%s</info>', $id));
            $clearer->clear($cacheDir);
        }

        foreach ($pools as $id => $pool) {
            $io->comment(sprintf('Clearing cache pool: <info>%s</info>', $id));

            if ($pool instanceof CacheItemPoolInterface) {
                $pool->clear();
            } else {
                $defaultClearer->clearPool($id);
            }
        }

        $io->success('Cache was successfully cleared.');
    }
}
