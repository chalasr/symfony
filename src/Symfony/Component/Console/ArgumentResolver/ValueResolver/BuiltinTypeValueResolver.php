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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\TypeInfo\Type\CollectionType;

/**
 * Resolves values from #[Argument] or #[Option] attributes for built-in PHP types.
 *
 * Handles: string, bool, int, float, array (including typed collections like list<int>).
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
final class BuiltinTypeValueResolver implements ValueResolverInterface
{
    public function resolve(string $argumentName, InputInterface $input, ReflectionMember $member): iterable
    {
        if ($member->isVariadic()) {
            return [];
        }

        if ($argument = Argument::tryFrom($member->getMember())) {
            if (is_subclass_of($argument->typeName, \BackedEnum::class)) {
                return [];
            }

            $value = $input->getArgument($argument->name);

            // For typed collections (e.g., list<SomeObject>), check if we can handle the element type
            if (\is_array($value) && !$this->canHandleTypedCollection($member)) {
                return []; // Let other resolvers handle non-builtin element types
            }

            return [$this->coerceIfTypedCollection($value, $member, $argument->name)];
        }

        if ($option = Option::tryFrom($member->getMember())) {
            if (is_subclass_of($option->typeName, \BackedEnum::class)) {
                return [];
            }

            $value = $this->resolveOption($option, $input);

            // For typed collections (e.g., list<SomeObject>), check if we can handle the element type
            if (\is_array($value) && !$this->canHandleTypedCollection($member)) {
                return []; // Let other resolvers handle non-builtin element types
            }

            return [$this->coerceIfTypedCollection($value, $member, $option->name)];
        }

        return [];
    }

    private function resolveOption(Option $option, InputInterface $input): mixed
    {
        $value = $input->getOption($option->name);

        if (null === $value && \in_array($option->typeName, Option::ALLOWED_UNION_TYPES, true)) {
            return true;
        }

        if ('array' === $option->typeName && $option->allowNull && [] === $value) {
            return null;
        }

        if ('bool' === $option->typeName) {
            if ($option->allowNull && null === $value) {
                return null;
            }

            return $value ?? $option->default;
        }

        return $value;
    }

    /**
     * Checks if we can handle a typed collection (e.g., list<int>, list<string>).
     * Returns true for builtin element types, false for object types.
     */
    private function canHandleTypedCollection(ReflectionMember $member): bool
    {
        $type = $member->getTypeInfo();

        if (!$type instanceof CollectionType) {
            return true; // Not a typed collection, we can handle it as plain array
        }

        $valueType = $type->getCollectionValueType();

        return $valueType->isIdentifiedBy('int')
            || $valueType->isIdentifiedBy('float')
            || $valueType->isIdentifiedBy('bool')
            || $valueType->isIdentifiedBy('string');
    }

    private function coerceIfTypedCollection(mixed $value, ReflectionMember $member, string $inputName): mixed
    {
        if (!\is_array($value)) {
            return $value;
        }

        $type = $member->getTypeInfo();

        if (!$type instanceof CollectionType) {
            return $value;
        }

        $valueType = $type->getCollectionValueType();

        $coerced = [];
        foreach ($value as $key => $item) {
            $coerced[$key] = $this->coerceValue($item, $valueType, $inputName, $key);
        }

        return $coerced;
    }

    private function coerceValue(mixed $value, mixed $targetType, string $inputName, int|string $index): mixed
    {
        if ($targetType->isIdentifiedBy('int')) {
            return $this->coerceToInt($value, $inputName, $index);
        }

        if ($targetType->isIdentifiedBy('float')) {
            return $this->coerceToFloat($value, $inputName, $index);
        }

        if ($targetType->isIdentifiedBy('bool')) {
            return $this->coerceToBool($value, $inputName, $index);
        }

        return (string) $value;
    }

    private function coerceToInt(mixed $value, string $inputName, int|string $index): int
    {
        if (\is_int($value)) {
            return $value;
        }

        if (\is_string($value) && preg_match('/^-?\d+$/', $value)) {
            return (int) $value;
        }

        throw new InvalidArgumentException(\sprintf('Value "%s" at index %s of "%s" cannot be safely converted to int.', $value, $index, $inputName));
    }

    private function coerceToFloat(mixed $value, string $inputName, int|string $index): float
    {
        if (\is_float($value) || \is_int($value)) {
            return (float) $value;
        }

        if (\is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        throw new InvalidArgumentException(\sprintf('Value "%s" at index %s of "%s" cannot be safely converted to float.', $value, $index, $inputName));
    }

    private function coerceToBool(mixed $value, string $inputName, int|string $index): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\in_array($value, ['true', '1', 1], true)) {
            return true;
        }

        if (\in_array($value, ['false', '0', 0], true)) {
            return false;
        }

        throw new InvalidArgumentException(\sprintf('Value "%s" at index %s of "%s" cannot be safely converted to bool.', $value, $index, $inputName));
    }
}
