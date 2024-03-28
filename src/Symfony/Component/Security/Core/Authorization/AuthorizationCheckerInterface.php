<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\Authorization;

/**
 * The AuthorizationCheckerInterface.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface AuthorizationCheckerInterface
{
    /**
     * Checks if the attributes are granted against the current 
     * authentication token and optionally supplied subject.
     *
     * @param mixed $attribute A single attribute to vote on
     * @param mixed $subject
     *
     * @return bool
     */
    public function isGranted(mixed $attribute, mixed $subject = null): bool;
}
