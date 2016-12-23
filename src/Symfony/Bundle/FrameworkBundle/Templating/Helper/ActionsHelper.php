<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Templating\Helper;

@trigger_error('The '.ActionsHelper::class.' class is deprecated since version 3.4 and will be removed in 4.0. Use Twig instead.', E_USER_DEPRECATED);

use Symfony\Component\Templating\Helper\Helper;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\HttpKernel\Controller\ControllerReference;

/**
 * ActionsHelper manages action inclusions.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @deprecated since version 3.4, to be removed in 4.0. Use Twig instead.
 */
class ActionsHelper extends Helper
{
    private $handler;

    /**
     * @param FragmentHandler $handler A FragmentHandler instance
     */
    public function __construct(FragmentHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Returns the fragment content for a given URI.
     *
     * @param string $uri     A URI
     * @param array  $options An array of options
     *
     * @return string The fragment content
     *
     * @see FragmentHandler::render()
     */
    public function render($uri, array $options = array())
    {
        $strategy = isset($options['strategy']) ? $options['strategy'] : 'inline';
        unset($options['strategy']);

        return $this->handler->render($uri, $strategy, $options);
    }

    public function controller($controller, $attributes = array(), $query = array())
    {
        return new ControllerReference($controller, $attributes, $query);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'actions';
    }
}
