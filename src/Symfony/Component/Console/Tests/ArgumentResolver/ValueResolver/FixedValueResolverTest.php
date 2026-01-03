<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Tests\ArgumentResolver\ValueResolver;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\ArgumentResolver\ValueResolver\FixedValueResolver;
use Symfony\Component\Console\Attribute\Reflection\ReflectionMember;
use Symfony\Component\Console\Input\ArrayInput;

class FixedValueResolverTest extends TestCase
{
    public function testMissingTypeAndArgumentName(): void
    {
        $this->expectExceptionMessage('Please specify at least a type or an argument name to resolve.');

        new FixedValueResolver('Some value');
    }

    public function testResolverWithClosureValue(): void
    {
        $resolverFunction = static fn () => 'Value A';
        $commandFunction = static fn (string $a) => null;

        $resolver = new FixedValueResolver($resolverFunction, 'string', 'a');
        $parameters = new \ReflectionFunction($commandFunction)->getParameters();
        $member = new ReflectionMember($parameters[0]);

        $result = $resolver->resolve($parameters[0]->getName(), new ArrayInput([]), $member);

        $this->assertSame([$resolverFunction()], $result);
    }

    public function testResolverWithClosureValueAndType(): void
    {
        $resolverFunction = static fn () => 'Value A';
        $commandFunction = static fn (\Closure $a) => null;

        $resolver = new FixedValueResolver($resolverFunction, \Closure::class, 'a');
        $parameters = new \ReflectionFunction($commandFunction)->getParameters();
        $member = new ReflectionMember($parameters[0]);

        $result = $resolver->resolve($parameters[0]->getName(), new ArrayInput([]), $member);

        $this->assertSame([$resolverFunction], $result);
    }

    #[DataProvider('provideTypeNames')]
    public function testResolverWithTypeName(callable $function, ?string $type, ?string $argumentName, mixed $expectedValue): void
    {
        $resolver = new FixedValueResolver($expectedValue, $type, $argumentName);
        $parameters = new \ReflectionFunction($function)->getParameters();
        $member = new ReflectionMember($parameters[0]);

        $result = $resolver->resolve($parameters[0]->getName(), new ArrayInput([]), $member);

        $this->assertSame([$expectedValue], $result);
    }

    public static function provideTypeNames(): iterable
    {
        yield 'only type "string"' =>  [static fn (string $a) => null, 'string', null, 'Some string'];
        yield 'only type "bool"' =>  [static fn (bool $a) => null, 'bool', null, true];
        yield 'only type "int"' =>  [static fn (int $a) => null, 'int', null, 1];
        yield 'only type "float"' =>  [static fn (float $a) => null, 'float', null, 1.0];
        yield 'only type stdClass' =>  [static fn (\stdClass $a) => null, \stdClass::class, null, (object) ['a' => true]];

        yield 'only argument name $a' =>  [static fn (string $a) => null, null, 'a', 'Value A'];
        yield 'only argument name $💪' =>  [static fn (string $💪) => null, null, '💪', 'Value 💪'];

        yield 'both type "string" and argument name $a' =>  [static fn (string $a) => null, null, 'a', 'Value A'];
        yield 'both type "bool" and argument name $a' =>  [static fn (string $a) => null, null, 'a', 'Value A'];
        yield 'both type "int" and argument name $a' =>  [static fn (string $a) => null, null, 'a', 'Value A'];
        yield 'both type "float" and argument name $a' =>  [static fn (string $a) => null, null, 'a', 'Value A'];
        yield 'both type stdClass and argument name $a' =>  [static fn (string $a) => null, null, 'a', 'Value A'];

        yield 'both type "string" and argument name $💪' =>  [static fn (string $💪) => null, null, '💪', 'Value 💪'];
        yield 'both type "bool" and argument name $💪' =>  [static fn (string $💪) => null, null, '💪', 'Value 💪'];
        yield 'both type "int" and argument name $💪' =>  [static fn (string $💪) => null, null, '💪', 'Value 💪'];
        yield 'both type "float" and argument name $💪' =>  [static fn (string $💪) => null, null, '💪', 'Value 💪'];
        yield 'both type stdClass and argument name $💪' =>  [static fn (string $💪) => null, null, '💪', 'Value 💪'];
    }
}
