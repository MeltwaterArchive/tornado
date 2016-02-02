<?php

namespace Test\Tornado\Security\Authorization\Container;

use Mockery;

use Symfony\Component\DependencyInjection\Definition;

use Tornado\Security\Authorization\Container\CompilerPass;

/**
 * CompilerPass
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\DataSift\Command\Container
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Security\Authorization\Container\CompilerPass
 */
class CompilerPassTest extends \PHPUnit_Framework_TestCase
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
    public function testProcessUnlessServiceMissing()
    {
        $container = Mockery::mock('\Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->shouldReceive('has')
            ->once()
            ->with('security.authorization.access_decision_manager')
            ->andReturnNull();

        $compiler = new CompilerPass();
        $result = $compiler->process($container);
        $this->assertNull($result);
    }

    /**
     * @covers ::process
     */
    public function testProcess()
    {
        $container = Mockery::mock('\Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->shouldReceive('has')
            ->once()
            ->with('security.authorization.access_decision_manager')
            ->andReturn(true);
        $definition = new Definition();
        $container->shouldReceive('findDefinition')
            ->once()
            ->with('security.authorization.access_decision_manager')
            ->andReturn($definition);
        $container->shouldReceive('findTaggedServiceIds')
            ->once()
            ->with('voter')
            ->andReturn(['voter' => 'tags']);

        (new CompilerPass())->process($container);
    }
}
