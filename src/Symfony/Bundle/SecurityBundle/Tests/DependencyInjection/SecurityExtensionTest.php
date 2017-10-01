<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\SecurityBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\SecurityBundle\Tests\DependencyInjection\Fixtures\UserProvider\DummyProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SecurityExtensionTest extends TestCase
{
    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage The check_path "/some_area/login_check" for login method "form_login" is not matched by the firewall pattern "/secured_area/.*".
     */
    public function testInvalidCheckPath()
    {
        $container = $this->getRawContainer();

        $container->loadFromExtension('security', array(
            'providers' => array(
                'default' => array('id' => 'foo'),
            ),

            'firewalls' => array(
                'some_firewall' => array(
                    'pattern' => '/secured_area/.*',
                    'form_login' => array(
                        'check_path' => '/some_area/login_check',
                    ),
                    'logout_on_user_change' => true,
                ),
            ),
        ));

        $container->compile();
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage No authentication listener registered for firewall "some_firewall"
     */
    public function testFirewallWithoutAuthenticationListener()
    {
        $container = $this->getRawContainer();

        $container->loadFromExtension('security', array(
            'providers' => array(
                'default' => array('id' => 'foo'),
            ),

            'firewalls' => array(
                'some_firewall' => array(
                    'pattern' => '/.*',
                    'logout_on_user_change' => true,
                ),
            ),
        ));

        $container->compile();
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Unable to create definition for "security.user.provider.concrete.my_foo" user provider
     */
    public function testFirewallWithInvalidUserProvider()
    {
        $container = $this->getRawContainer();

        $extension = $container->getExtension('security');
        $extension->addUserProviderFactory(new DummyProvider());

        $container->loadFromExtension('security', array(
            'providers' => array(
                'my_foo' => array('foo' => array()),
            ),

            'firewalls' => array(
                'some_firewall' => array(
                    'pattern' => '/.*',
                    'http_basic' => array(),
                    'logout_on_user_change' => true,
                ),
            ),
        ));

        $container->compile();
    }

    public function testDisableRoleHierarchyVoter()
    {
        $container = $this->getRawContainer();

        $container->loadFromExtension('security', array(
            'providers' => array(
                'default' => array('id' => 'foo'),
            ),

            'role_hierarchy' => null,

            'firewalls' => array(
                'some_firewall' => array(
                    'pattern' => '/.*',
                    'http_basic' => null,
                    'logout_on_user_change' => true,
                ),
            ),
        ));

        $container->compile();

        $this->assertFalse($container->hasDefinition('security.access.role_hierarchy_voter'));
    }

    /**
     * @group legacy
     * @expectedDeprecation Setting logout_on_user_change to false is deprecated as of 3.4 and will always be true in 4.0. Set logout_on_user_change to true in your firewall configuration.
     */
    public function testDeprecationForUserLogout()
    {
        $container = $this->getRawContainer();

        $container->loadFromExtension('security', array(
            'providers' => array(
                'default' => array('id' => 'foo'),
            ),

            'firewalls' => array(
                'some_firewall' => array(
                    'pattern' => '/.*',
                    'http_basic' => null,
                    'logout_on_user_change' => false,
                ),
            ),
        ));

        $container->compile();
    }

    /**
     */
    public function testSwitchUserIsAutomaticallyStatelessOnStatelessFirewall()
    {
        $container = $this->getRawContainer();

        $container->loadFromExtension('security', array(
            'providers' => array(
                'default' => array('id' => 'foo'),
            ),

            'firewalls' => array(
                'some_firewall' => array(
                    'stateless' => true,
                    'http_basic' => null,
                    'switch_user' => array('stateless' => false),
                    'logout_on_user_change' => true,
                ),
            ),
        ));

        $container->compile();
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Not configuring explicitly the provider on "default" firewall is ambiguous as there is more than one registered provider.
     */
    public function testDeprecationForAmbiguousProvider()
    {
        $container = $this->getRawContainer();

        $container->loadFromExtension('security', array(
            'providers' => array(
                'first' => array('id' => 'foo'),
                'second' => array('id' => 'bar'),
            ),

            'firewalls' => array(
                'default' => array(
                    'http_basic' => null,
                    'logout_on_user_change' => true,
                ),
            ),
        ));

        $container->compile();

        $this->assertTrue($container->getDefinition('security.authentication.switchuser_listener.default')->getArgument(9));
    }

    protected function getRawContainer()
    {
        $container = new ContainerBuilder();
        $security = new SecurityExtension();
        $container->registerExtension($security);

        $bundle = new SecurityBundle();
        $bundle->build($container);

        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());

        return $container;
    }

    protected function getContainer()
    {
        $container = $this->getRawContainer();
        $container->compile();

        return $container;
    }
}
