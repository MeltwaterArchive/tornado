<?php

namespace Test\DataSift\Silex;

use \Mockery;

use DataSift\Silex\Bootstrap;

use Tornado\Application\Tornado;

/**
 * BootstrapTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\DataSift
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \DataSift\Silex\Bootstrap
 */
class BootstrapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * DataProvider for testGetter
     *
     * @return array
     */
    public function getterProvider()
    {
        return [
            [
                'configuration_path' => '/var/www/tornado/config',
                'resources_path' => '/var/www/tornado/config',
                'environment' => 'test',
                'debug' => false
            ],
            [
                'configuration_path' => '/var/www/tornado/config',
                'resources_path' => '/var/www/tornado/config',
                'environment' => 'TeSt',
                'debug' => true
            ]
        ];
    }

    /**
     * @dataProvider getterProvider
     *
     * @covers ::__construct
     * @covers ::getConfigurationPath
     * @covers ::getResourcesPath
     * @covers ::getEnvironment
     * @covers ::isDebug
     *
     * @param string $configurationPath
     * @param string $resourcesPath
     * @param string $env
     */
    public function testGetter($configurationPath, $resourcesPath, $env, $debug)
    {
        $bootstrap = new Bootstrap($configurationPath, $resourcesPath, $env, $debug);

        $this->assertEquals($configurationPath, $bootstrap->getConfigurationPath());
        $this->assertEquals($resourcesPath, $bootstrap->getResourcesPath());
        $this->assertEquals(strtolower($env), $bootstrap->getEnvironment());
        $this->assertEquals($debug, $bootstrap->isDebug());
    }

    /**
     * @covers ::buildContainer
     */
    public function testBuildContainer()
    {
        $configPath = __DIR__ . '/Fixtures';
        $resourcesPath = __DIR__ . '/Fixtures';
        $env = 'test';

        $bootstrap = new Bootstrap($configPath, $resourcesPath, $env);

        $container = $bootstrap->buildContainer();

        $this->assertInstanceOf(
            '\Symfony\Component\DependencyInjection\ContainerInterface',
            $container
        );

        // make sure all dirs have been properly defined
        $this->assertEquals($configPath, $container->getParameter('config_dir'));
        $this->assertEquals($resourcesPath, $container->getParameter('resources_dir'));
        $this->assertEquals($env, $container->getParameter('env'));

        $this->assertInstanceOf(
            '\Test\DataSift\Silex\Fixtures\TestController',
            $container->get('test.controller')
        );
        $this->assertEquals(true, $container->getParameter('example'));
    }

    /**
     * @covers ::buildContainer
     * @expectedException \Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException
     */
    public function testGettingDIParameterUnlessItIsNotDefined()
    {
        $bootstrap = new Bootstrap(
            __DIR__ . '/Fixtures',
            __DIR__ . '/Fixtures',
            'test'
        );

        $bootstrap->buildContainer()
            ->getParameter('noExist');
    }

    /**
     * @covers ::buildContainer
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     */
    public function testGettingDIServiceUnlessItIsNotDefined()
    {
        $bootstrap = new Bootstrap(
            __DIR__ . '/Fixtures',
            __DIR__ . '/Fixtures',
            'test'
        );

        $bootstrap->buildContainer()
            ->get('noExist');
    }

    /**
     * @covers ::buildContainer
     * @expectedException \InvalidArgumentException
     */
    public function testBuildContainerUnlessInvalidPathGiven()
    {
        $bootstrap = new Bootstrap(
            'invalidPath',
            __DIR__ . '/Fixtures',
            'test'
        );

        $bootstrap->buildContainer();
    }

    /**
     * @covers ::createApplication
     */
    public function testCreateApplication()
    {
        $bootstrap = new Bootstrap(
            __DIR__ . '/Fixtures',
            __DIR__ . '/Fixtures',
            'test'
        );

        $app = $bootstrap->createApplication(
            Tornado::class,
            $bootstrap->buildContainer()
        );

        $this->assertInstanceOf(
            '\DataSift\Silex\Application',
            $app
        );

        $this->assertInstanceOf(
            '\Silex\Application',
            $app
        );
    }

    /**
     * @covers ::createApplication
     * @expectedException \InvalidArgumentException
     */
    public function testCreateApplicationUnlessInvalidParametersGiven()
    {
        $bootstrap = new Bootstrap(
            __DIR__ . '/Fixtures',
            'invalidParametersPath',
            'test'
        );

        $bootstrap->createApplication(
            Tornado::class,
            $bootstrap->buildContainer()
        );
    }

    /**
     * @covers ::addCompilerPass
     * @covers ::registerCompilerPass
     */
    public function testAddCompilerPass()
    {
        $bootstrap = new Bootstrap(
            __DIR__ . '/Fixtures',
            __DIR__ . '/Fixtures',
            'test'
        );

        $compilerPass = Mockery::mock('\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface', [
            'process' => true
        ]);

        $bootstrap->addCompilerPass($compilerPass);

        $bootstrap->createApplication(
            Tornado::class,
            $bootstrap->buildContainer()
        );
    }
}
