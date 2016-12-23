<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Templating;

@trigger_error('The '.TimedPhpEngine::class.' class is deprecated since version 3.4 and will be removed in 4.0. Use Twig instead.', E_USER_DEPRECATED);

use Psr\Container\ContainerInterface;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Templating\Loader\LoaderInterface;

/**
 * Times the time spent to render a template.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @deprecated since version 3.4, to be removed in 4.0. Use Twig instead.
 */
class TimedPhpEngine extends PhpEngine
{
    protected $stopwatch;

    /**
     * @param TemplateNameParserInterface $parser    A TemplateNameParserInterface instance
     * @param ContainerInterface          $container A ContainerInterface instance
     * @param LoaderInterface             $loader    A LoaderInterface instance
     * @param Stopwatch                   $stopwatch A Stopwatch instance
     * @param GlobalVariables             $globals   A GlobalVariables instance
     */
    public function __construct(TemplateNameParserInterface $parser, ContainerInterface $container, LoaderInterface $loader, Stopwatch $stopwatch, GlobalVariables $globals = null)
    {
        parent::__construct($parser, $container, $loader, $globals);

        $this->stopwatch = $stopwatch;
    }

    /**
     * {@inheritdoc}
     */
    public function render($name, array $parameters = array())
    {
        $e = $this->stopwatch->start(sprintf('template.php (%s)', $name), 'template');

        $ret = parent::render($name, $parameters);

        $e->stop();

        return $ret;
    }
}
