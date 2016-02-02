<?php

namespace Test\Tornado\Controller\Brand;

use \Mockery;

use Symfony\Component\DependencyInjection\Definition;

use Controller\RecordingController;

use Tornado\Controller\Brand\DataAwareCompilerPass;

/**
 * DataAwareCompilerPassTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Controller\Brand
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass Tornado\Controller\Brand\DataAwareCompilerPass
 */
class DataAwareCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @covers ::process
     */
    public function testProcess()
    {
        $container = Mockery::mock('\Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->shouldReceive('findTaggedServiceIds')
            ->once()
            ->with('brand_data_aware')
            ->andReturn(['serviceId']);
        $definition = Mockery::mock(Definition::class);


        $definition->shouldReceive('getClass')
            ->once()
            ->withNoArgs()
            ->andReturn(RecordingController::class);
        $definition->shouldReceive('addMethodCall');
        $container->shouldReceive('findDefinition')
            ->once()
            ->with('serviceId')
            ->andReturn($definition);

        (new DataAwareCompilerPass())->process($container);
    }

    /**
     * @covers ::process
     *
     * @expectedException \LogicException
     */
    public function testProcessUnlessLogicExceptionThrow()
    {
        $container = Mockery::mock('\Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->shouldReceive('findTaggedServiceIds')
            ->once()
            ->with('brand_data_aware')
            ->andReturn(['serviceId']);
        $definition = Mockery::mock(Definition::class);

        $definition->shouldReceive('getClass')
            ->once()
            ->withNoArgs()
            ->andReturn(Definition::class);
        $container->shouldReceive('findDefinition')
            ->once()
            ->with('serviceId')
            ->andReturn($definition);

        (new DataAwareCompilerPass())->process($container);
    }
}
