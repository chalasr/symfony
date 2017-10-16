<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Functional\Bundle\TestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FragmentController extends AbstractController
{
    public function indexAction()
    {
        return $this->render('fragment.html.twig', array('bar' => new Bar()));
    }

    public function inlinedAction($options, $_format)
    {
        return new Response($options['bar']->getBar().' '.$_format);
    }

    public function customFormatAction($_format)
    {
        return new Response($_format);
    }

    public function customLocaleAction(Request $request)
    {
        return new Response($request->getLocale());
    }

    public function forwardLocaleAction(Request $request)
    {
        return new Response($request->getLocale());
    }
}

class Bar
{
    private $bar = 'bar';

    public function getBar()
    {
        return $this->bar;
    }
}
