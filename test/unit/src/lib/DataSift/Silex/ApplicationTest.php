<?php

namespace Test\DataSift\Silex;

use Symfony\Component\Routing\RouteCollection;

use DataSift\Silex\Bootstrap;

use Tornado\Application\Tornado;

/**
 * ApplicationTest
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
 * @coversDefaultClass \DataSift\Silex\Application
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->bootstrap = new Bootstrap(
            __DIR__ . '/Fixtures',
            __DIR__ . '/Fixtures',
            'test'
        );
    }

    /**
     * @covers ::__construct
     * @covers ::registerRoutes
     * @covers ::registerProviders
     */
    public function testConstructor()
    {
        $app = $this->bootstrap->createApplication(
            Tornado::class,
            $this->bootstrap->buildContainer()
        );

        $this->assertInstanceOf(
            '\DataSift\Silex\Application',
            $app
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     *
     * @covers ::registerRoutes
     * @covers ::registerProviders
     */
    public function testCreatingApplicationUnlessInvalidPathGiven()
    {
        $bootstrap = new Bootstrap(
            __DIR__ . '/Fixtures',
            'invalidParamatersPath',
            'test'
        );

        $bootstrap->createApplication(
            Tornado::class,
            $bootstrap->buildContainer()
        );
    }

    /**
     * @covers ::__construct
     * @covers ::registerRoutes
     * @covers ::registerProviders
     *
     * @expectedException \InvalidArgumentException
     */
    public function testCreatingApplicationUnlessRequiredParametersAreMissing()
    {
        $bootstrap = new Bootstrap(
            __DIR__ . '/Fixtures',
            'invalidParamatersPath',
            'test'
        );

        $bootstrap->createApplication(
            Tornado::class,
            $bootstrap->buildContainer()
        );
    }

    /**
     * @covers ::__construct
     * @covers ::registerRoutes
     * @covers ::registerProviders
     * @covers ::isDebug
     */
    public function testDebugSetting()
    {
        $app = $this->bootstrap->createApplication(
            Tornado::class,
            $this->bootstrap->buildContainer()
        );
        $this->assertFalse($app->isDebug());

        $bootstrap = new Bootstrap(
            __DIR__ . '/Fixtures',
            __DIR__ . '/Fixtures',
            'development',
            true
        );
        $app2 = $bootstrap->createApplication(
            Tornado::class,
            $bootstrap->buildContainer()
        );
        $this->assertTrue($app2->isDebug());
    }

    /**
     * @covers ::__construct
     * @covers ::registerRoutes
     * @covers ::registerProviders
     * @covers ::getEnvironment
     */
    public function testEnvironmentSetting()
    {
        $app = $this->bootstrap->createApplication(
            Tornado::class,
            $this->bootstrap->buildContainer()
        );
        $this->assertEquals('test', $app->getEnvironment());

        $bootstrap = new Bootstrap(
            __DIR__ . '/Fixtures',
            __DIR__ . '/Fixtures',
            'development'
        );
        $app2 = $bootstrap->createApplication(
            Tornado::class,
            $bootstrap->buildContainer()
        );
        $this->assertEquals('development', $app2->getEnvironment());
    }

    /**
     * @covers ::getContainer
     */
    public function testGetContainer()
    {
        $app = $this->bootstrap->createApplication(
            Tornado::class,
            $this->bootstrap->buildContainer()
        );

        $this->assertInstanceOf(
            '\Symfony\Component\DependencyInjection\ContainerInterface',
            $app->getContainer()
        );
    }

    /**
     * @covers ::registerRoutes
     * @covers ::getRoutes
     */
    public function testRegisterRoutes()
    {
        $app = $this->bootstrap->createApplication(
            Tornado::class,
            $this->bootstrap->buildContainer()
        );

        /** @var $routes RouteCollection */
        $routes = $app->getRoutes();

        $this->assertInstanceOf(
            '\Symfony\Component\Routing\RouteCollection',
            $routes
        );

        $this->assertEquals(null, $routes->get('doesNotExistRoute'));
        $this->assertInstanceOf(
            '\Symfony\Component\Routing\Route',
            $routes->get('test')
        );
        $this->assertEquals('/test', $routes->get('test')->getPath());
    }

    /**
     * @covers ::offsetGet
     * @covers ::offsetExists
     */
    public function testSettingAndGettingPimpleOffsets()
    {
        $app = $this->bootstrap->createApplication(
            Tornado::class,
            $this->bootstrap->buildContainer()
        );

        $app['lorem'] = 'ipsum';
        $this->assertEquals('ipsum', $app['lorem']);

        // dummy service is defined in services.yml for these tests, but it cannot be instantiated
        try {
            $dummy = $app['dummy'];
            $this->fail(sprintf(
                'Expected RuntimeException from the container was not thrown, instead it returned %s',
                gettype($dummy)
            ));
        } catch (\RuntimeException $e) {
        }
        // so we want to make sure that setting it now it will "overshadow" it in the app and take precedence
        $app['dummy'] = new \stdClass();
        $this->assertInstanceOf('\stdClass', $app['dummy']);
    }
}
