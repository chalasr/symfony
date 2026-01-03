<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\ArgumentResolver\ValueResolver;

use Symfony\Component\Console\Attribute\Reflection\ReflectionMember;
use Symfony\Component\Console\Input\InputInterface;
use function Symfony\Component\Translation\t;

/**
 * Yields a value that matches class or type, or argument name.
 * Value can be provided as a Closure, and if done so and the argument type is *not* a closure, then the closure will be executed at runtime to fetch the internal value.
 *
 * @author Alex "Pierstoval" Rock <pierstoval@gmail.com>
 */
final class FixedValueResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly mixed $value,
        private readonly ?string $type = null,
        private readonly ?string $argumentName = null,
    ) {
        if (!$this->type && !$this->argumentName) {
            throw new \InvalidArgumentException('Please specify at least a type or an argument name to resolve.');
        }
    }

    public function resolve(string $argumentName, InputInterface $input, ReflectionMember $member): iterable
    {
        if (
            (!$this->argumentName || ($this->argumentName === $argumentName))
            && (!$this->type || ($this->type === $member->getType()?->getName()))
        ) {
            return [$this->getValue()];
        }

        return [];
    }

    private function getValue(): mixed
    {
        if ($this->value instanceof \Closure && $this->type !== \Closure::class) {
            return ($this->value)();
        }

        return $this->value;
    }
}
