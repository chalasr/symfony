<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\ArgumentResolver;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\ArgumentResolver\Exception\NearMissValueResolverException;
use Symfony\Component\Console\ArgumentResolver\Exception\ResolverNotFoundException;
use Symfony\Component\Console\ArgumentResolver\ValueResolver as Resolver;
use Symfony\Component\Console\ArgumentResolver\ValueResolver\ValueResolverInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Attribute\Reflection\ReflectionMember;
use Symfony\Component\Console\Attribute\ValueResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolverInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * Resolves the arguments passed to a console command.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
final class ArgumentResolver implements ArgumentResolverInterface
{
    /**
     * @param iterable<mixed, ValueResolverInterface> $argumentValueResolvers
     */
    public function __construct(
        private iterable $argumentValueResolvers = [],
        private ?ContainerInterface $namedResolvers = null,
        private ?TypeResolverInterface $typeResolver = null,
    ) {
    }

    public function getArguments(InputInterface $input, callable $command, ?\ReflectionFunctionAbstract $reflector = null): array
    {
        $reflector ??= new \ReflectionFunction($command(...));

        $argumentReflectors = [];
        foreach ($reflector->getParameters() as $param) {
            $typeInfoResolver = null !== $this->typeResolver
                ? fn () => $this->typeResolver->resolve($param)
                : null;

            $argumentReflectors[$param->getName()] = new ReflectionMember($param, $typeInfoResolver);
        }

        $arguments = [];

        foreach ($argumentReflectors as $argumentName => $member) {
            $argumentValueResolvers = $this->argumentValueResolvers;
            $disabledResolvers = [];

            if ($this->namedResolvers && $attributes = $member->getAttributes(ValueResolver::class)) {
                $resolverName = null;
                foreach ($attributes as $attribute) {
                    if ($attribute->disabled) {
                        $disabledResolvers[$attribute->resolver] = true;
                    } elseif ($resolverName) {
                        throw new \LogicException(\sprintf('You can only pin one resolver per argument, but argument "$%s" of "%s()" has more.', $member->getName(), $member->getSourceName()));
                    } else {
                        $resolverName = $attribute->resolver;
                    }
                }

                if ($resolverName) {
                    if (!$this->namedResolvers->has($resolverName)) {
                        throw new ResolverNotFoundException($resolverName, $this->namedResolvers instanceof ServiceProviderInterface ? array_keys($this->namedResolvers->getProvidedServices()) : []);
                    }

                    $argumentValueResolvers = [
                        $this->namedResolvers->get($resolverName),
                    ];
                }
            }

            $valueResolverExceptions = [];
            foreach ($argumentValueResolvers as $name => $resolver) {
                if (isset($disabledResolvers[\is_int($name) ? $resolver::class : $name])) {
                    continue;
                }

                try {
                    $count = 0;
                    foreach ($resolver->resolve($argumentName, $input, $member) as $argument) {
                        ++$count;
                        $arguments[] = $argument;
                    }
                } catch (NearMissValueResolverException $e) {
                    $valueResolverExceptions[] = $e;
                }

                if (1 < $count && !$member->isVariadic()) {
                    throw new \InvalidArgumentException(\sprintf('"%s::resolve()" must yield at most one value for non-variadic arguments.', get_debug_type($resolver)));
                }

                if ($count) {
                    continue 2;
                }
            }

            // Try per-element resolution for typed collections (e.g., list<CustomObject>)
            if ($resolved = $this->resolveCollectionElements($argumentName, $input, $member, $argumentValueResolvers, $disabledResolvers)) {
                foreach ($resolved as $element) {
                    $arguments[] = $element;
                }
                continue;
            }

            // For variadic parameters with explicit input mapping, 0 values is valid
            if ($member->isVariadic() && (Argument::tryFrom($member->getMember()) || Option::tryFrom($member->getMember()))) {
                continue;
            }

            $type = $member->getType();
            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

            if ($typeName && \in_array($typeName, [
                InputInterface::class,
                OutputInterface::class,
                SymfonyStyle::class,
                Cursor::class,
                \Symfony\Component\Console\Application::class,
                Command::class,
            ], true)) {
                continue;
            }

            $reasons = array_map(static fn (NearMissValueResolverException $e) => $e->getMessage(), $valueResolverExceptions);
            if (!$reasons) {
                $reasons[] = \sprintf('The parameter has no #[Argument], #[Option], or #[MapInput] attribute, and its type "%s" cannot be auto-resolved.', $typeName ?? 'unknown');
                $reasons[] = 'Add an attribute to map this parameter to command input.';
            }

            throw new \RuntimeException(\sprintf('Could not resolve parameter "$%s" of command "%s".'."\n\n".'Possible reasons:'."\n".'  • '.implode("\n  • ", $reasons), $member->getName(), $member->getSourceName()));
        }

        return $arguments;
    }

    /**
     * @return iterable<int, ValueResolverInterface>
     */
    public static function getDefaultArgumentValueResolvers(): iterable
    {
        $builtinTypeResolver = new Resolver\BuiltinTypeValueResolver();
        $backedEnumResolver = new Resolver\BackedEnumValueResolver();
        $dateTimeResolver = new Resolver\DateTimeValueResolver();

        return [
            $backedEnumResolver,
            new Resolver\UidValueResolver(),
            $builtinTypeResolver,
            new Resolver\MapInputValueResolver($builtinTypeResolver, $backedEnumResolver, $dateTimeResolver),
            $dateTimeResolver,
            new Resolver\DefaultValueResolver(),
            new Resolver\VariadicValueResolver(),
        ];
    }

    /**
     * Resolves collection elements individually when the collection type is known via TypeInfo.
     *
     * This handles cases like `list<CustomObject>` where CustomObject has its own
     * resolver that doesn't handle lists on its own.
     *
     * @param iterable<ValueResolverInterface> $resolvers
     * @param array<string, bool>              $disabledResolvers
     *
     * @return list<mixed>|null Resolved elements or null if not applicable
     */
    private function resolveCollectionElements(string $argumentName, InputInterface $input, ReflectionMember $member, iterable $resolvers, array $disabledResolvers): ?array
    {
        $type = $member->getTypeInfo();

        if (!$type instanceof CollectionType) {
            return null;
        }

        // Get input mapping to retrieve raw values
        $inputMapping = Argument::tryFrom($member->getMember()) ?? Option::tryFrom($member->getMember());
        if (!$inputMapping) {
            return null;
        }

        $inputName = $inputMapping->name;
        $rawValues = $inputMapping instanceof Argument
            ? $input->getArgument($inputName)
            : $input->getOption($inputName);

        if (!\is_array($rawValues)) {
            return null;
        }

        if ([] === $rawValues) {
            return [];
        }

        $elementType = $type->getCollectionValueType();
        $elementMember = $member->withTypeInfo($elementType);
        $resolved = [];

        foreach ($rawValues as $index => $rawValue) {
            // Create synthetic input with single element value
            $syntheticInput = $this->createSyntheticInput($inputMapping, $inputName, $rawValue);

            $elementResolved = false;
            foreach ($resolvers as $name => $resolver) {
                if (isset($disabledResolvers[\is_int($name) ? $resolver::class : $name])) {
                    continue;
                }

                foreach ($resolver->resolve($argumentName, $syntheticInput, $elementMember) as $value) {
                    $resolved[$index] = $value;
                    $elementResolved = true;
                    break 2;
                }
            }

            if (!$elementResolved) {
                // No resolver could handle this element type
                return null;
            }
        }

        return $resolved;
    }

    /**
     * Creates a synthetic input containing a single element value.
     */
    private function createSyntheticInput(Argument|Option $inputMapping, string $inputName, mixed $value): InputInterface
    {
        if ($inputMapping instanceof Argument) {
            return new ArrayInput([$inputName => $value]);
        }

        return new ArrayInput(['--'.$inputName => $value]);
    }
}
