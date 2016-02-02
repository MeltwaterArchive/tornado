<?php

namespace Test\Command;

use Mockery;

use Symfony\Component\Console\Tester\CommandTester;

use Tornado\DataMapper\DataMapperInterface;

use Test\DataSift\ReflectionAccess;

use Command\BrandPermissions;

/**
 * BrandPermissionsTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Command
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass  \Command\BrandPermissions
 */
class BrandPermissionsTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess;

    public function tearDown()
    {
        Mockery::close();
    }

    protected function provideCommandTester(DataMapperInterface $brandRepo)
    {
        $command = new BrandPermissions($brandRepo);
        return new CommandTester($command);
    }

    /**
     * @covers ::__construct
     * @covers ::configure
     */
    public function testConfigure()
    {
        $brandRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $command = new BrandPermissions($brandRepo);
        $this->invokeMethod($command, 'configure');

        $this->assertEquals('tornado:brand:permissions', $command->getName());
        $this->assertNotEmpty($command->getDescription());
        
        $definition = $command->getDefinition();
        $this->assertInstanceOf('Symfony\Component\Console\Input\InputDefinition', $definition);
        $this->assertTrue($definition->hasArgument('brand_id'));
        $this->assertTrue($definition->hasArgument('permissions'));
        $this->assertTrue($definition->hasOption('clear'));
    }

    /**
     * @covers ::__construct
     * @covers ::execute
     */
    public function testExecuteWithClear()
    {
        $brandId = 23;
        $brand = Mockery::mock('Tornado\Organization\Brand', [
            'getName' => 'Test Brand'
        ]);
        $brand->shouldReceive('setTargetPermissions')
            ->with([]);

        $brandRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $brandRepo->shouldReceive('findOne')
            ->with(['id' => $brandId])
            ->andReturn($brand);

        $brandRepo->shouldReceive('update')
            ->with($brand);

        $commandTester = $this->provideCommandTester($brandRepo);
        $commandTester->execute([
            'brand_id' => $brandId,
            '--clear' => true
        ]);
    }

    /**
     * @covers ::__construct
     * @covers ::execute
     */
    public function testExecuteWithoutPermissions()
    {
        $brandId = 23;
        $brand = Mockery::mock('Tornado\Organization\Brand', [
            'getName' => 'Test Brand',
            'getRawTargetPermissions' => 'internal,premium'
        ]);

        $brandRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $brandRepo->shouldReceive('findOne')
            ->with(['id' => $brandId])
            ->andReturn($brand);

        $commandTester = $this->provideCommandTester($brandRepo);
        $commandTester->execute([
            'brand_id' => $brandId
        ]);

        $display = $commandTester->getDisplay();

        $this->assertContains('Test Brand', $display);
        $this->assertContains('internal,premium', $display);
    }

    /**
     * @covers ::__construct
     * @covers ::execute
     */
    public function testExecuteWithPermissions()
    {
        $brandId = 23;
        $brand = Mockery::mock('Tornado\Organization\Brand', [
            'getName' => 'Test Brand',
            'getRawTargetPermissions' => 'internal,everyone'
        ]);
        $brand->shouldReceive('setRawTargetPermissions')
            ->with('internal,everyone');

        $brandRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $brandRepo->shouldReceive('findOne')
            ->with(['id' => $brandId])
            ->andReturn($brand);

        $brandRepo->shouldReceive('update')
            ->with($brand);

        $commandTester = $this->provideCommandTester($brandRepo);
        $commandTester->execute([
            'brand_id' => $brandId,
            'permissions' => 'internal,everyone'
        ]);
    }

    /**
     * @covers ::__construct
     * @covers ::execute
     *
     * @expectedException \InvalidArgumentException
     */
    public function testExecuteWithInvalidBrandId()
    {
        $brandRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface', [
            'findOne' => null
        ]);
        $commandTester = $this->provideCommandTester($brandRepo);
        $commandTester->execute([
            'brand_id' => 34
        ]);
    }
}
