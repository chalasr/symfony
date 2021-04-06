<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\SecurityBundle\Debug\Authenticator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Http\Firewall\AbstractListener;
use Symfony\Component\Security\Http\Firewall\AuthenticatorManagerListener;

/**
 * Decorates the AuthenticatorManagerListener to collect information about security authenticators.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 *
 * @experimental in Symfony 5.3
 */
class TraceableAuthenticatorManagerListener extends AbstractListener
{
    private $authenticationManagerListener;
    private $authenticatorsInfo = [];

    public function __construct(AuthenticatorManagerListener $authenticationManagerListener)
    {
        $this->authenticationManagerListener = $authenticationManagerListener;
    }

    public function supports(Request $request): ?bool
    {
        return $this->authenticationManagerListener->supports($request);
    }

    public function authenticate(RequestEvent $event)
    {
        $request = $event->getRequest();

        if (!$authenticators = $request->attributes->get('_security_authenticators')) {
            return null;
        }

        foreach ($authenticators as $key => $authenticator) {
            $authenticators[$key] = new TraceableAuthenticator($authenticator);
        }

        $request->attributes->set('_security_authenticators', $authenticators);

        $this->authenticationManagerListener->authenticate($event);

        foreach ($authenticators as $authenticator) {
            $this->authenticatorsInfo[] = $authenticator->getInfo();
        }
    }

    public function getAuthenticatorsInfo(): array
    {
        return $this->authenticatorsInfo;
    }
}
