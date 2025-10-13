<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\ArgumentResolver\ValueResolver\ValueResolverInterface;
use Symfony\Component\Console\Tester\ApplicationTester;

#[Group('functional')]
class ConsoleArgumentResolverTest extends AbstractWebTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!interface_exists(ValueResolverInterface::class)) {
            self::markTestSkipped('Console ArgumentResolver not available.');
        }

        parent::setUpBeforeClass();
    }

    protected function setUp(): void
    {
        static::bootKernel(['test_case' => 'ConsoleArgumentResolver', 'root_config' => 'config.yml']);
    }

    public function testCustomArgumentResolver()
    {
        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run([
            'command' => 'app:custom-type',
            'name' => 'test-value',
            'count' => '10',
            'status' => 'inactive',
            '--format' => 'xml',
        ]);

        $tester->assertCommandIsSuccessful();
        $output = $tester->getDisplay();

        $this->assertStringContainsString('CustomType value: resolved:test-value', $output);
        $this->assertStringContainsString('Name: test-value', $output);
        $this->assertStringContainsString('Count: 10', $output);
        $this->assertStringContainsString('Status: inactive', $output);
        $this->assertStringContainsString('Date: '.date('Y-m-d'), $output);
        $this->assertStringContainsString('Service: Service injected!', $output);
        $this->assertStringContainsString('Format: xml', $output);
        $this->assertStringContainsString('CustomOption: option:xml', $output);
    }

    public function testMethodBasedCommandInvoke()
    {
        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run([
            'command' => 'app:method-invoke',
            'name' => 'test-invoke',
        ]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Invoke: Service injected! - test-invoke', $tester->getDisplay());
    }

    public function testMethodBasedCommandCmd1()
    {
        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run([
            'command' => 'app:method-cmd1',
            'value' => 'test-cmd1',
        ]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Cmd1: Service injected! - test-cmd1', $tester->getDisplay());
    }

    public function testMethodBasedCommandCmd2()
    {
        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run([
            'command' => 'app:method-cmd2',
            'data' => 'test-cmd2',
        ]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Cmd2: Service injected! - test-cmd2', $tester->getDisplay());
    }

    public function testAdvancedFeatures()
    {
        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run([
            'command' => 'app:advanced',
            'name' => 'test-advanced',
        ]);

        $tester->assertCommandIsSuccessful();
        $output = $tester->getDisplay();

        $this->assertStringContainsString('Autowired: Service injected!', $output);
        $this->assertStringContainsString('Environment:', $output);
        $this->assertStringContainsString('Targeted: Service injected!', $output);
        $this->assertStringContainsString('Regular: Service injected!', $output);
        $this->assertStringContainsString('Name: test-advanced', $output);
    }

    public function testEmptyCommandWithCleanupPass()
    {
        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run([
            'command' => 'app:empty',
            'name' => 'test-empty',
        ]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Name: test-empty', $tester->getDisplay());
    }

    public function testAutoTaggedValueResolver()
    {
        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run([
            'command' => 'app:auto-tagged-resolver',
        ]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Auto-tagged: auto-tagged-value', $tester->getDisplay());
    }

    public function testTargetedValueResolver()
    {
        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run([
            'command' => 'app:targeted-resolver',
        ]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Targeted: targeted-value', $tester->getDisplay());
    }

    public function testUidValueResolver()
    {
        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run([
            'command' => 'app:uid',
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            '--reference' => '01ARZ3NDEKTSV4RRFFQ69G5FAV',
        ]);

        $tester->assertCommandIsSuccessful();
        $output = $tester->getDisplay();

        $this->assertStringContainsString('UUID: 550e8400-e29b-41d4-a716-446655440000', $output);
        $this->assertStringContainsString('ULID: 01ARZ3NDEKTSV4RRFFQ69G5FAV', $output);
    }

    public function testUidValueResolverWithoutOptionalOption()
    {
        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run([
            'command' => 'app:uid',
            'id' => '550e8400-e29b-41d4-a716-446655440000',
        ]);

        $tester->assertCommandIsSuccessful();
        $output = $tester->getDisplay();

        $this->assertStringContainsString('UUID: 550e8400-e29b-41d4-a716-446655440000', $output);
        $this->assertStringContainsString('ULID: none', $output);
    }

    public function testVariadicValueResolver()
    {
        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        $tester = new ApplicationTester($application);
        $tester->run([
            'command' => 'app:variadic',
            'files' => ['file1.txt', 'file2.txt', 'file3.txt'],
        ]);

        $tester->assertCommandIsSuccessful();
        $output = $tester->getDisplay();

        $this->assertStringContainsString('Files: file1.txt, file2.txt, file3.txt', $output);
        $this->assertStringContainsString('Count: 3', $output);
    }

    public function testVariadicValueResolverWithEmptyArray()
    {
        $application = new Application(static::$kernel);
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        $tester = new ApplicationTester($application);
        $tester->run([
            'command' => 'app:variadic',
        ]);

        $tester->assertCommandIsSuccessful();
        $output = $tester->getDisplay();

        $this->assertStringContainsString('Files: ', $output);
        $this->assertStringContainsString('Count: 0', $output);
    }
}
