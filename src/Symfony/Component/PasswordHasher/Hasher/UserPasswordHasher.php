<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\PasswordHasher\Hasher;

use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

/**
 * Hashes passwords based on the user and the PasswordHasherFactory.
 *
 * @author Ariel Ferrandini <arielferrandini@gmail.com>
 *
 * @final
 */
class UserPasswordHasher implements UserPasswordHasherInterface
{
    private $hasherFactory;

    public function __construct(PasswordHasherFactoryInterface $hasherFactory)
    {
        $this->hasherFactory = $hasherFactory;
    }

    /**
     * @param PasswordAuthenticatedUserInterface $user
     */
    public function hashPassword($user, string $plainPassword): string
    {
        if (!$user instanceof UserInterface) {
            throw new \TypeError(sprintf('Expected an instance of "%s" as first argument, but got "%s".', UserInterface::class, get_debug_type($user)));
        }

        if (!$user instanceof PasswordAuthenticatedUserInterface) {
            trigger_deprecation('symfony/password-hasher', '5.3', 'Not implementing the "%s" interface in class "%s" while using the "%s" validation constraint is deprecated.', PasswordAuthenticatedUserInterface::class, get_debug_type($user), UserPassword::class);
        }

        $salt = $user->getSalt();
        if ($salt && !$user instanceof LegacyPasswordAuthenticatedUserInterface) {
            trigger_deprecation('symfony/password-hasher', '5.3', 'Returning a string from "getSalt()" without implementing the "%s" interface is deprecated. You should make the "%s" class implement it.', LegacyPasswordAuthenticatedUserInterface::class, get_debug_type($user));
        }

        $hasher = $this->hasherFactory->getPasswordHasher($user);

        return $hasher->hash($plainPassword, $user->getSalt());
    }

    /**
     * @param PasswordAuthenticatedUserInterface $user
     */
    public function isPasswordValid($user, string $plainPassword): bool
    {
        if (!$user instanceof UserInterface) {
            throw new \TypeError(sprintf('Expected an instance of "%s" as first argument, but got "%s".', UserInterface::class, get_debug_type($user)));
        }

        if (!$user instanceof PasswordAuthenticatedUserInterface) {
            trigger_deprecation('symfony/password-hasher', '5.3', 'The "%s()" method expects a "%s" instance as first argument. Not implementing it in class "%s" is deprecated.', __METHOD__, PasswordAuthenticatedUserInterface::class, get_debug_type($user));
        }

        $salt = $user->getSalt();
        if ($salt && !$user instanceof LegacyPasswordAuthenticatedUserInterface) {
            trigger_deprecation('symfony/password-hasher', '5.3', 'Returning a string from "getSalt()" without implementing the "%s" interface is deprecated. You should make the "%s" class implement it.', LegacyPasswordAuthenticatedUserInterface::class, get_debug_type($user));
        }

        if (null === $user->getPassword()) {
            return false;
        }

        $hasher = $this->hasherFactory->getPasswordHasher($user);

        return $hasher->verify($user->getPassword(), $plainPassword, $salt);
    }

    /**
     * @param PasswordAuthenticatedUserInterface $user
     */
    public function needsRehash($user): bool
    {
        if (!$user instanceof UserInterface) {
            throw new \TypeError(sprintf('Expected an instance of "%s" as first argument, but got "%s".', UserInterface::class, get_debug_type($user)));
        }

        if (null === $user->getPassword()) {
            return false;
        }

        if (!$user instanceof PasswordAuthenticatedUserInterface) {
            trigger_deprecation('symfony/password-hasher', '5.3', 'The "%s()" method expects a "%s" instance as first argument. Not implementing it in "%s" is deprecated.', PasswordAuthenticatedUserInterface::class, __METHOD__, get_debug_type($user));
        }

        $salt = $user->getSalt();
        if ($salt && !$user instanceof LegacyPasswordAuthenticatedUserInterface) {
            trigger_deprecation('symfony/password-hasher', '5.3', 'Returning a string from "getSalt()" without implementing the "%s" interface is deprecated. You should make the "%s" class implement it.', LegacyPasswordAuthenticatedUserInterface::class, get_debug_type($user));
        }

        $hasher = $this->hasherFactory->getPasswordHasher($user);

        return $hasher->needsRehash($user->getPassword());
    }
}
