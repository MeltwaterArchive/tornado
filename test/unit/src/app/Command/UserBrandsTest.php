<?php

namespace Test\Command;

use Mockery;

use Symfony\Component\Console\Tester\CommandTester;

use Command\UserBrands;

use Tornado\DataMapper\DataMapperInterface;

use Test\DataSift\ReflectionAccess;

/**
 * UserBrandsTest
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
 * @coversDefaultClass  \Command\UserBrands
 */
class UserBrandsTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess;

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @param \Tornado\DataMapper\DataMapperInterface $userRepo
     * @param \Tornado\DataMapper\DataMapperInterface $brandRepo
     *
     * @return \Symfony\Component\Console\Tester\CommandTester
     */
    protected function provideCommandTester(DataMapperInterface $userRepo, DataMapperInterface $brandRepo)
    {
        $command = new UserBrands($userRepo, $brandRepo);
        return new CommandTester($command);
    }

    /**
     * @covers ::__construct
     * @covers ::configure
     */
    public function testConfigure()
    {
        $userRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $brandRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $command = new UserBrands($userRepo, $brandRepo);
        $this->invokeMethod($command, 'configure');

        $this->assertEquals('tornado:user:brands', $command->getName());
        $this->assertNotEmpty($command->getDescription());
        $this->assertNotEmpty($command->getHelp());
        
        $definition = $command->getDefinition();
        $this->assertInstanceOf('Symfony\Component\Console\Input\InputDefinition', $definition);
        $this->assertTrue($definition->hasArgument('user_id'));
        $this->assertTrue($definition->hasArgument('brands'));
        $this->assertTrue($definition->hasOption('clear'));
    }

    /**
     * @covers ::__construct
     * @covers ::execute
     *
     * @expectedException \InvalidArgumentException
     */
    public function testExecuteUnlessUserNotFound()
    {
        $userId = 1;
        $userRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $userRepo->shouldReceive('findOne')
            ->once()
            ->with(['id' => $userId])
            ->andReturn(null);
        $brandRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');

        $commandTester = $this->provideCommandTester($userRepo, $brandRepo);
        $commandTester->execute([
            'user_id' => $userId
        ]);
    }

    /**
     * @covers ::__construct
     * @covers ::execute
     */
    public function testExecuteWithClear()
    {
        $userId = 1;
        $user = Mockery::mock('Tornado\Organization\User', [
            'getId' => $userId,
            'getEmail' => 'test@email.com'
        ]);

        $removed = 2;
        $userRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $userRepo->shouldReceive('findOne')
            ->once()
            ->with(['id' => $userId])
            ->andReturn($user);
        $userRepo->shouldReceive('removeBrands')
            ->once()
            ->with($user)
            ->andReturn($removed);

        $brandRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');

        $commandTester = $this->provideCommandTester($userRepo, $brandRepo);
        $commandTester->execute([
            'user_id' => $userId,
            '--clear' => true
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains('Cleared 2 Brands to which User "test@email.com" belonged to.', $output);
    }

    /**
     * @covers ::__construct
     * @covers ::execute
     */
    public function testExecuteWithoutBrandsArg()
    {
        $userId = 1;
        $user = Mockery::mock('Tornado\Organization\User', [
            'getId' => $userId,
            'getEmail' => 'test@email.com'
        ]);

        $userRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $userRepo->shouldReceive('findOne')
            ->once()
            ->with(['id' => $userId])
            ->andReturn($user);
        $userRepo->shouldNotReceive('removeBrands');
        $brandRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $brandRepo->shouldReceive('findUserAssigned')
            ->once()
            ->with($user)
            ->andReturn([
                Mockery::mock('\Tornado\Organization\Brand', [
                    'getName' => 'Test',
                    'getId' => 1
                ]),
                Mockery::mock('\Tornado\Organization\Brand', [
                    'getName' => 'Test2',
                    'getId' => 2
                ])
            ]);

        $commandTester = $this->provideCommandTester($userRepo, $brandRepo);
        $commandTester->execute([
            'user_id' => $userId
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains('User "test@email.com" belongs to the brands:', $output);
        $this->assertContains('Test (id:1)', $output);
        $this->assertContains('Test2 (id:2)', $output);
    }

    /**
     * @covers ::__construct
     * @covers ::execute
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExecuteUnlessUserAllowedBrandsGiven()
    {
        $userId = 1;
        $user = Mockery::mock('Tornado\Organization\User', [
            'getId' => $userId,
            'getEmail' => 'test@email.com'
        ]);

        $userRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $userRepo->shouldReceive('findOne')
            ->once()
            ->with(['id' => $userId])
            ->andReturn($user);
        $userRepo->shouldNotReceive('removeBrands');
        $brandRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $brandRepo->shouldNotReceive('findUserAssigned');
        $brandRepo->shouldReceive('findUserAllowed')
            ->once()
            ->with($user)
            ->andReturn([
                Mockery::mock('\Tornado\Organization\Brand', [
                    'getName' => 'Test',
                    'getId' => 1
                ]),
                Mockery::mock('\Tornado\Organization\Brand', [
                    'getName' => 'Test2',
                    'getId' => 2
                ])
            ]);

        $commandTester = $this->provideCommandTester($userRepo, $brandRepo);
        $commandTester->execute([
            'user_id' => $userId,
            'brands' => '3,4'
        ]);
    }

    /**
     * @covers ::__construct
     * @covers ::execute
     */
    public function testExecuteWithUserAllowedBrands()
    {
        $userId = 1;
        $user = Mockery::mock('Tornado\Organization\User', [
            'getId' => $userId,
            'getEmail' => 'test@email.com'
        ]);
        $brands = [
            Mockery::mock('\Tornado\Organization\Brand', [
                'getName' => 'Test',
                'getId' => 1
            ]),
            Mockery::mock('\Tornado\Organization\Brand', [
                'getName' => 'Test2',
                'getId' => 2
            ])
        ];

        $userRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $userRepo->shouldReceive('findOne')
            ->once()
            ->with(['id' => $userId])
            ->andReturn($user);
        $userRepo->shouldNotReceive('removeBrands');
        $userRepo->shouldReceive('addBrands')
            ->once()
            ->with($user, $brands)
            ->andReturn(null);
        $brandRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $brandRepo->shouldNotReceive('findUserAssigned');
        $brandRepo->shouldReceive('findUserAllowed')
            ->once()
            ->with($user)
            ->andReturn($brands);
        $brandRepo->shouldReceive('findByIds')
            ->once()
            ->with([1,2])
            ->andReturn($brands);

        $commandTester = $this->provideCommandTester($userRepo, $brandRepo);
        $commandTester->execute([
            'user_id' => $userId,
            'brands' => '1,2'
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains('User "test@email.com" has been successfully added to the Brands 1,2.', $output);
    }
}
