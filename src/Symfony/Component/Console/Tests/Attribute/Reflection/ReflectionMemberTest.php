<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Tests\Attribute\Reflection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Attribute\Reflection\ReflectionMember;
use Symfony\Component\TypeInfo\Type;

class ReflectionMemberTest extends TestCase
{
    public function testGetTypeInfoWithoutResolverReturnsNull()
    {
        $command = new class {
            public function __invoke(string $name) {}
        };
        $reflection = new \ReflectionMethod($command, '__invoke');
        $parameter = $reflection->getParameters()[0];

        $member = new ReflectionMember($parameter);

        $this->assertNull($member->getTypeInfo());
    }

    /**
     * @requires function \Symfony\Component\TypeInfo\Type::string
     */
    public function testGetTypeInfoWithResolver()
    {
        $command = new class {
            public function __invoke(string $name) {}
        };
        $reflection = new \ReflectionMethod($command, '__invoke');
        $parameter = $reflection->getParameters()[0];

        $expectedType = Type::string();
        $member = new ReflectionMember($parameter, fn () => $expectedType);

        $this->assertSame($expectedType, $member->getTypeInfo());
    }

    /**
     * @requires function \Symfony\Component\TypeInfo\Type::string
     */
    public function testGetTypeInfoIsLazy()
    {
        $command = new class {
            public function __invoke(string $name) {}
        };
        $reflection = new \ReflectionMethod($command, '__invoke');
        $parameter = $reflection->getParameters()[0];

        $callCount = 0;
        $member = new ReflectionMember($parameter, function () use (&$callCount) {
            ++$callCount;
            return Type::string();
        });

        // Not called yet
        $this->assertSame(0, $callCount);

        // First call triggers resolution
        $member->getTypeInfo();
        $this->assertSame(1, $callCount);

        // Second call uses cached value
        $member->getTypeInfo();
        $this->assertSame(1, $callCount);
    }

    /**
     * @requires function \Symfony\Component\TypeInfo\Type::string
     */
    public function testGetTypeInfoCatchesExceptions()
    {
        $command = new class {
            public function __invoke(string $name) {}
        };
        $reflection = new \ReflectionMethod($command, '__invoke');
        $parameter = $reflection->getParameters()[0];

        $member = new ReflectionMember($parameter, fn () => throw new \RuntimeException('Type resolution failed'));

        // Should return null instead of throwing
        $this->assertNull($member->getTypeInfo());
    }
}
