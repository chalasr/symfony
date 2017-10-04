<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Guard\Firewall;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Guard\Token\PreAuthenticationGuardToken;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorInterface;
use Symfony\Component\Security\Guard\AuthenticatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;

/**
 * Authentication listener for the "guard" system.
 *
 * @author Ryan Weaver <ryan@knpuniversity.com>
 * @author Amaury Leroux de Lens <amaury@lerouxdelens.com>
 */
class GuardAuthenticationListener implements ListenerInterface
{
    private $guardHandler;
    private $authenticationManager;
    private $providerKey;
    private $guardAuthenticators;
    private $logger;
    private $rememberMeServices;

    /**
     * @param GuardAuthenticatorHandler         $guardHandler          The Guard handler
     * @param AuthenticationManagerInterface    $authenticationManager An AuthenticationManagerInterface instance
     * @param string                            $providerKey           The provider (i.e. firewall) key
     * @param iterable|AuthenticatorInterface[] $guardAuthenticators   The authenticators, with keys that match what's passed to GuardAuthenticationProvider
     * @param LoggerInterface                   $logger                A LoggerInterface instance
     */
    public function __construct(GuardAuthenticatorHandler $guardHandler, AuthenticationManagerInterface $authenticationManager, $providerKey, $guardAuthenticators, LoggerInterface $logger = null)
    {
        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->guardHandler = $guardHandler;
        $this->authenticationManager = $authenticationManager;
        $this->providerKey = $providerKey;
        $this->guardAuthenticators = $guardAuthenticators;
        $this->logger = $logger;
    }

    /**
     * Iterates over each authenticator to see if each wants to authenticate the request.
     *
     * @param GetResponseEvent $event
     */
    public function handle(GetResponseEvent $event)
    {
        if (null !== $this->logger) {
            $context = array('firewall_key' => $this->providerKey);

            if ($this->guardAuthenticators instanceof \Countable || is_array($this->guardAuthenticators)) {
                $context['authenticators'] = count($this->guardAuthenticators);
            }

            $this->logger->debug('Checking for guard authentication credentials.', $context);
        }

        foreach ($this->guardAuthenticators as $key => $guardAuthenticator) {
            // get a key that's unique to *this* guard authenticator
            // this MUST be the same as GuardAuthenticationProvider
            $uniqueGuardKey = $this->providerKey.'_'.$key;

            $this->executeGuardAuthenticator($uniqueGuardKey, $guardAuthenticator, $event);

            if ($event->hasResponse()) {
                if (null !== $this->logger) {
                    $this->logger->debug('The "{authenticator}" authenticator set the response. Any later authenticator will not be called', array('authenticator' => get_class($guardAuthenticator)));
                }

                break;
            }
        }
    }

    private function executeGuardAuthenticator($uniqueGuardKey, AuthenticatorInterface $guardAuthenticator, GetResponseEvent $event)
    {
        $request = $event->getRequest();
        try {
            if (null !== $this->logger) {
                $this->logger->debug('Calling getCredentials() on guard configurator.', array('firewall_key' => $this->providerKey, 'authenticator' => get_class($guardAuthenticator)));
            }

            // abort the execution of the authenticator if it doesn't support the request.
            if ($guardAuthenticator instanceof GuardAuthenticatorInterface) {
                // deprecated since version 3.4, to be removed in 4.0
                @trigger_error(sprintf('The %s interface is deprecated since version 3.4 and will be removed in 4.0. Implement %s instead.', GuardAuthenticatorInterface::class, AuthenticatorInterface::class), E_USER_DEPRECATED);
                $credentialsCanBeNull = true;
            } else {
                if (true !== $guardAuthenticator->supports($request)) {
                    return;
                }
                // as there was a support for given request,
                // authenticator is expected to give not-null credentials.
                $credentialsCanBeNull = false;
            }

            // allow the authenticator to fetch authentication info from the request
            $credentials = $guardAuthenticator->getCredentials($request);

            if (null === $credentials) {
                // if GuardAuthenticatorInterface is used
                // allow null to skip authentication.
                if ($credentialsCanBeNull) {
                    return;
                }

                // otherwise something went wrong and the authentication must fail
                throw new \UnexpectedValueException(sprintf(
                    'You must return some credentials from %s:getCredentials().
                    To skip authentication, return false from %s::supports().',
                    get_class($guardAuthenticator),
                    get_class($guardAuthenticator)
                ));
            }

            // create a token with the unique key, so that the provider knows which authenticator to use
            $token = new PreAuthenticationGuardToken($credentials, $uniqueGuardKey);

            if (null !== $this->logger) {
                $this->logger->debug('Passing guard token information to the GuardAuthenticationProvider', array('firewall_key' => $this->providerKey, 'authenticator' => get_class($guardAuthenticator)));
            }
            // pass the token into the AuthenticationManager system
            // this indirectly calls GuardAuthenticationProvider::authenticate()
            $token = $this->authenticationManager->authenticate($token);

            if (null !== $this->logger) {
                $this->logger->info('Guard authentication successful!', array('token' => $token, 'authenticator' => get_class($guardAuthenticator)));
            }

            // sets the token on the token storage, etc
            $this->guardHandler->authenticateWithToken($token, $request);
        } catch (AuthenticationException $e) {
            // oh no! Authentication failed!

            if (null !== $this->logger) {
                $this->logger->info('Guard authentication failed.', array('exception' => $e, 'authenticator' => get_class($guardAuthenticator)));
            }

            $response = $this->guardHandler->handleAuthenticationFailure($e, $request, $guardAuthenticator, $this->providerKey);

            if ($response instanceof Response) {
                $event->setResponse($response);
            }

            return;
        }

        // success!
        $response = $this->guardHandler->handleAuthenticationSuccess($token, $request, $guardAuthenticator, $this->providerKey);
        if ($response instanceof Response) {
            if (null !== $this->logger) {
                $this->logger->debug('Guard authenticator set success response.', array('response' => $response, 'authenticator' => get_class($guardAuthenticator)));
            }

            $event->setResponse($response);
        } else {
            if (null !== $this->logger) {
                $this->logger->debug('Guard authenticator set no success response: request continues.', array('authenticator' => get_class($guardAuthenticator)));
            }
        }

        // attempt to trigger the remember me functionality
        $this->triggerRememberMe($guardAuthenticator, $request, $token, $response);
    }

    /**
     * Should be called if this listener will support remember me.
     *
     * @param RememberMeServicesInterface $rememberMeServices
     */
    public function setRememberMeServices(RememberMeServicesInterface $rememberMeServices)
    {
        $this->rememberMeServices = $rememberMeServices;
    }

    /**
     * Checks to see if remember me is supported in the authenticator and
     * on the firewall. If it is, the RememberMeServicesInterface is notified.
     *
     * @param AuthenticatorInterface $guardAuthenticator
     * @param Request                $request
     * @param TokenInterface         $token
     * @param Response               $response
     */
    private function triggerRememberMe(AuthenticatorInterface $guardAuthenticator, Request $request, TokenInterface $token, Response $response = null)
    {
        if (null === $this->rememberMeServices) {
            if (null !== $this->logger) {
                $this->logger->debug('Remember me skipped: it is not configured for the firewall.', array('authenticator' => get_class($guardAuthenticator)));
            }

            return;
        }

        if (!$guardAuthenticator->supportsRememberMe()) {
            if (null !== $this->logger) {
                $this->logger->debug('Remember me skipped: your authenticator does not support it.', array('authenticator' => get_class($guardAuthenticator)));
            }

            return;
        }

        if (!$response instanceof Response) {
            throw new \LogicException(sprintf(
                '%s::onAuthenticationSuccess *must* return a Response if you want to use the remember me functionality. Return a Response, or set remember_me to false under the guard configuration.',
                get_class($guardAuthenticator)
            ));
        }

        $this->rememberMeServices->loginSuccess($request, $response, $token);
    }
}
