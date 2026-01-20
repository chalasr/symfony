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
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Symfony\Component\TypeInfo\Type\CollectionType;

/**
 * Resolves a BackedEnum instance from a Command argument or option.
 *
 * Handles both single values and typed collections like list<SomeBackedEnum>.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
final class BackedEnumValueResolver implements ValueResolverInterface
{
    public function resolve(string $argumentName, InputInterface $input, ReflectionMember $member): iterable
    {
        if ($argument = Argument::tryFrom($member->getMember())) {
            if (!is_subclass_of($argument->typeName, \BackedEnum::class)) {
                // Check for typed collection of enums via TypeInfo
                if ($enumClass = $this->getCollectionEnumClass($member)) {
                    return [$this->resolveArgumentCollection($argument, $input, $enumClass)];
                }

                return [];
            }

            return [$this->resolveArgument($argument, $input)];
        }

        if ($option = Option::tryFrom($member->getMember())) {
            if (!is_subclass_of($option->typeName, \BackedEnum::class)) {
                // Check for typed collection of enums via TypeInfo
                if ($enumClass = $this->getCollectionEnumClass($member)) {
                    return [$this->resolveOptionCollection($option, $input, $enumClass)];
                }

                return [];
            }

            return [$this->resolveOption($option, $input)];
        }

        return [];
    }

    /**
     * @return class-string<\BackedEnum>|null
     */
    private function getCollectionEnumClass(ReflectionMember $member): ?string
    {
        $type = $member->getTypeInfo();

        if (!$type instanceof CollectionType) {
            return null;
        }

        $valueType = $type->getCollectionValueType();

        if (!$valueType instanceof BackedEnumType) {
            return null;
        }

        return $valueType->getClassName();
    }

    private function resolveArgument(Argument $argument, InputInterface $input): ?\BackedEnum
    {
        $value = $input->getArgument($argument->name);

        if (null === $value) {
            return null;
        }

        if ($value instanceof $argument->typeName) {
            return $value;
        }

        if (!\is_string($value) && !\is_int($value)) {
            throw InvalidArgumentException::fromEnumValue($argument->name, get_debug_type($value), $argument->suggestedValues);
        }

        return $argument->typeName::tryFrom($value)
            ?? throw InvalidArgumentException::fromEnumValue($argument->name, $value, $argument->suggestedValues);
    }

    /**
     * @param class-string<\BackedEnum> $enumClass
     *
     * @return array<\BackedEnum>
     */
    private function resolveArgumentCollection(Argument $argument, InputInterface $input, string $enumClass): array
    {
        $values = $input->getArgument($argument->name);

        if (!\is_array($values)) {
            return [];
        }

        $resolved = [];
        foreach ($values as $key => $value) {
            if ($value instanceof $enumClass) {
                $resolved[$key] = $value;
                continue;
            }

            if (!\is_string($value) && !\is_int($value)) {
                throw InvalidArgumentException::fromEnumValue($argument->name, get_debug_type($value), $argument->suggestedValues);
            }

            $resolved[$key] = $enumClass::tryFrom($value)
                ?? throw InvalidArgumentException::fromEnumValue($argument->name, $value, $argument->suggestedValues);
        }

        return $resolved;
    }

    private function resolveOption(Option $option, InputInterface $input): ?\BackedEnum
    {
        $value = $input->getOption($option->name);

        if (null === $value) {
            return null;
        }

        if ($value instanceof $option->typeName) {
            return $value;
        }

        if (!\is_string($value) && !\is_int($value)) {
            throw InvalidOptionException::fromEnumValue($option->name, get_debug_type($value), $option->suggestedValues);
        }

        return $option->typeName::tryFrom($value)
            ?? throw InvalidOptionException::fromEnumValue($option->name, $value, $option->suggestedValues);
    }

    /**
     * @param class-string<\BackedEnum> $enumClass
     *
     * @return array<\BackedEnum>
     */
    private function resolveOptionCollection(Option $option, InputInterface $input, string $enumClass): array
    {
        $values = $input->getOption($option->name);

        if (!\is_array($values)) {
            return [];
        }

        $resolved = [];
        foreach ($values as $key => $value) {
            if ($value instanceof $enumClass) {
                $resolved[$key] = $value;
                continue;
            }

            if (!\is_string($value) && !\is_int($value)) {
                throw InvalidOptionException::fromEnumValue($option->name, get_debug_type($value), $option->suggestedValues);
            }

            $resolved[$key] = $enumClass::tryFrom($value)
                ?? throw InvalidOptionException::fromEnumValue($option->name, $value, $option->suggestedValues);
        }

        return $resolved;
    }
}
