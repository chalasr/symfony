<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Twig\Extension;

use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * SecurityExtension exposes security context features.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @final since Symfony 4.4
 */
class SecurityExtension extends AbstractExtension
{
    private $securityChecker;
    private $tokenStorage;

    public function __construct(AuthorizationCheckerInterface $securityChecker = null, TokenStorageInterface $tokenStorage = null)
    {
        $this->securityChecker = $securityChecker;
        $this->tokenStorage = $tokenStorage;
    }

    public function isGranted($role, $object = null, $field = null)
    {
        if (null === $this->securityChecker) {
            return false;
        }

        if (null !== $field) {
            $object = new FieldVote($object, $field);
        }

        if ('ROLE_PREVIOUS_ADMIN' === $role) {
            @trigger_error('')
        }

        try {
            return $this->securityChecker->isGranted($role, $object);
        } catch (AuthenticationCredentialsNotFoundException $e) {
            return false;
        }
    }

    public function isImpersonating(): bool
    {
        return $this->tokenStorage ? $this->tokenStorage->getToken() instanceof SwitchUserToken : false;
    }

    /**
     * {@inheritdoc}
     *
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('is_granted', [$this, 'isGranted']),
            new TwigFunction('is_impersonating', [$this, 'isImpersonating']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'security';
    }
}
