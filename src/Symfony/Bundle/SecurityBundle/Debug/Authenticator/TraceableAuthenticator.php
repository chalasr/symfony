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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Guard\Authenticator\GuardBridgeAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\VarDumper\Caster\ClassStub;

/**
 * @author Robin Chalas <robin.chalas@gmail.com>
 *
 * @internal
 *
 * @experimental in Symfony 5.3
 */
final class TraceableAuthenticator implements AuthenticatorInterface
{
    private $authenticator;
    private $successful;
    private $response;
    private $duration;
    private $stub;

    public function __construct(AuthenticatorInterface $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    public function supports(Request $request): ?bool
    {
        return $this->authenticator->supports($request);
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(Request $request): PassportInterface
    {
        $startTime = microtime(true);
        $passport = $this->authenticator->authenticate($request);
        $this->duration = microtime(true) - $startTime;

        return $passport;
    }

    public function createAuthenticatedToken(PassportInterface $passport, string $firewallName): TokenInterface
    {
        return $this->authenticator->createAuthenticatedToken($passport, $firewallName);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $this->successful = true;

        return $this->response = $this->authenticator->onAuthenticationSuccess($request, $token, $firewallName);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->successful = false;

        return $this->response = $this->authenticator->onAuthenticationFailure($request, $exception);
    }

    public function getInfo(): array
    {
        $authenticator = $this->authenticator instanceof GuardBridgeAuthenticator ? $this->authenticator->getGuardAuthenticator() : $this->authenticator;

        return [
            'successful' => $this->successful,
            'response' => $this->response,
            'duration' => $this->duration,
            'stub' => $this->stub ?? $this->stub = ClassStub::wrapCallable($authenticator),
        ];
    }

    public function __call($method, $args)
    {
        return $this->authenticator->{$method}(...$args);
    }
}
