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

@trigger_error('The '.RouterHelper::class.' class is deprecated since version 3.4 and will be removed in 4.0. Use Twig instead.', E_USER_DEPRECATED);

use Symfony\Component\Templating\Helper\Helper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * RouterHelper manages links between pages in a template context.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @deprecated since version 3.4, to be removed in 4.0. Use Twig instead.
 */
class RouterHelper extends Helper
{
    protected $generator;

    /**
     * @param UrlGeneratorInterface $router A Router instance
     */
    public function __construct(UrlGeneratorInterface $router)
    {
        $this->generator = $router;
    }

    /**
     * Generates a URL reference (as an absolute or relative path) to the route with the given parameters.
     *
     * @param string $name       The name of the route
     * @param mixed  $parameters An array of parameters
     * @param bool   $relative   Whether to generate a relative or absolute path
     *
     * @return string The generated URL reference
     *
     * @see UrlGeneratorInterface
     */
    public function path($name, $parameters = array(), $relative = false)
    {
        return $this->generator->generate($name, $parameters, $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    /**
     * Generates a URL reference (as an absolute URL or network path) to the route with the given parameters.
     *
     * @param string $name           The name of the route
     * @param mixed  $parameters     An array of parameters
     * @param bool   $schemeRelative Whether to omit the scheme in the generated URL reference
     *
     * @return string The generated URL reference
     *
     * @see UrlGeneratorInterface
     */
    public function url($name, $parameters = array(), $schemeRelative = false)
    {
        return $this->generator->generate($name, $parameters, $schemeRelative ? UrlGeneratorInterface::NETWORK_PATH : UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'router';
    }
}
