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

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Attribute\Reflection\ReflectionMember;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\Uid\AbstractUid;

/**
 * Resolves an AbstractUid instance from a Command argument or option.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
final class UidValueResolver implements ValueResolverInterface
{
    public function resolve(string $argumentName, InputInterface $input, ReflectionMember $member): iterable
    {
        if ($argument = Argument::tryFrom($member->getMember())) {
            // Check TypeInfo first (for per-element resolution of typed collections)
            $uidClass = $this->getUidClass($member) ?? $argument->typeName;

            if (!is_subclass_of($uidClass, AbstractUid::class)) {
                return [];
            }

            return [$this->resolveArgument($argument, $input, $uidClass)];
        }

        if ($option = Option::tryFrom($member->getMember())) {
            // Check TypeInfo first (for per-element resolution of typed collections)
            $uidClass = $this->getUidClass($member) ?? $option->typeName;

            if (!is_subclass_of($uidClass, AbstractUid::class)) {
                return [];
            }

            return [$this->resolveOption($option, $input, $uidClass)];
        }

        return [];
    }

    /**
     * @return class-string<AbstractUid>|null
     */
    private function getUidClass(ReflectionMember $member): ?string
    {
        $type = $member->getTypeInfo();

        if (!$type instanceof ObjectType) {
            return null;
        }

        $className = $type->getClassName();

        return is_subclass_of($className, AbstractUid::class) ? $className : null;
    }

    /**
     * @param class-string<AbstractUid> $uidClass
     */
    private function resolveArgument(Argument $argument, InputInterface $input, string $uidClass): ?AbstractUid
    {
        $value = $input->getArgument($argument->name);

        if (null === $value) {
            return null;
        }

        if ($value instanceof $uidClass) {
            return $value;
        }

        if (!\is_string($value) || !$uidClass::isValid($value)) {
            throw new InvalidArgumentException(\sprintf('The uid for the "%s" argument is invalid.', $argument->name));
        }

        return $uidClass::fromString($value);
    }

    /**
     * @param class-string<AbstractUid> $uidClass
     */
    private function resolveOption(Option $option, InputInterface $input, string $uidClass): ?AbstractUid
    {
        $value = $input->getOption($option->name);

        if (null === $value) {
            return null;
        }

        if ($value instanceof $uidClass) {
            return $value;
        }

        if (!\is_string($value) || !$uidClass::isValid($value)) {
            throw new InvalidOptionException(\sprintf('The uid for the "--%s" option is invalid.', $option->name));
        }

        return $uidClass::fromString($value);
    }
}
