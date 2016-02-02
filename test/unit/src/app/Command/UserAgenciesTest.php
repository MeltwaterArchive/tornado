<?php

namespace Test\Command;

use Mockery;

use Symfony\Component\Console\Tester\CommandTester;

use Command\UserAgencies;

use Tornado\DataMapper\DataMapperInterface;

use Test\DataSift\ReflectionAccess;

/**
 * UserAgenciesTest
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
 * @coversDefaultClass  \Command\UserAgencies
 */
class UserAgenciesTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess;

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @param \Tornado\DataMapper\DataMapperInterface $userRepo
     * @param \Tornado\DataMapper\DataMapperInterface $agencyRepo
     *
     * @return \Symfony\Component\Console\Tester\CommandTester
     */
    protected function provideCommandTester(DataMapperInterface $userRepo, DataMapperInterface $agencyRepo)
    {
        $command = new UserAgencies($userRepo, $agencyRepo);
        return new CommandTester($command);
    }

    /**
     * @covers ::__construct
     * @covers ::configure
     */
    public function testConfigure()
    {
        $userRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $agencyRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $command = new UserAgencies($userRepo, $agencyRepo);
        $this->invokeMethod($command, 'configure');

        $this->assertEquals('tornado:user:agencies', $command->getName());
        $this->assertNotEmpty($command->getDescription());
        $this->assertNotEmpty($command->getHelp());
        
        $definition = $command->getDefinition();
        $this->assertInstanceOf('Symfony\Component\Console\Input\InputDefinition', $definition);
        $this->assertTrue($definition->hasArgument('user_id'));
        $this->assertTrue($definition->hasArgument('agencies'));
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
        $agencyRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');

        $commandTester = $this->provideCommandTester($userRepo, $agencyRepo);
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
        $userRepo->shouldReceive('removeAgencies')
            ->once()
            ->with($user)
            ->andReturn($removed);

        $agencyRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');

        $commandTester = $this->provideCommandTester($userRepo, $agencyRepo);
        $commandTester->execute([
            'user_id' => $userId,
            '--clear' => true
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains('Cleared 2 Agencies to which User "test@email.com" belonged to.', $output);
    }

    /**
     * @covers ::__construct
     * @covers ::execute
     */
    public function testExecuteWithoutAgenciesArg()
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
        $userRepo->shouldNotReceive('removeAgencies');
        $agencyRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $agencyRepo->shouldReceive('findUserAssigned')
            ->once()
            ->with($user)
            ->andReturn([
                Mockery::mock('\Tornado\Organization\Agency', [
                    'getName' => 'Test',
                    'getId' => 1
                ]),
                Mockery::mock('\Tornado\Organization\Agency', [
                    'getName' => 'Test2',
                    'getId' => 2
                ])
            ]);

        $commandTester = $this->provideCommandTester($userRepo, $agencyRepo);
        $commandTester->execute([
            'user_id' => $userId
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains('User "test@email.com" belongs to the agencies:', $output);
        $this->assertContains('Test (id:1)', $output);
        $this->assertContains('Test2 (id:2)', $output);
    }

    /**
     * @covers ::__construct
     * @covers ::execute
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExecuteUnlessUserAllowedAgenciesGiven()
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
        $userRepo->shouldNotReceive('removeAgencies');
        $agencyRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $agencyRepo->shouldNotReceive('findUserAssigned');
        $agencyRepo->shouldReceive('findUserAllowed')
            ->once()
            ->with($user)
            ->andReturn([
                Mockery::mock('\Tornado\Organization\Agency', [
                    'getName' => 'Test',
                    'getId' => 1
                ]),
                Mockery::mock('\Tornado\Organization\Agency', [
                    'getName' => 'Test2',
                    'getId' => 2
                ])
            ]);

        $commandTester = $this->provideCommandTester($userRepo, $agencyRepo);
        $commandTester->execute([
            'user_id' => $userId,
            'agencies' => '3,4'
        ]);
    }

    /**
     * @covers ::__construct
     * @covers ::execute
     */
    public function testExecuteWithUserAllowedAgencies()
    {
        $userId = 1;
        $user = Mockery::mock('Tornado\Organization\User', [
            'getId' => $userId,
            'getEmail' => 'test@email.com'
        ]);
        $agencies = [
            Mockery::mock('\Tornado\Organization\Agency', [
                'getName' => 'Test',
                'getId' => 1
            ]),
            Mockery::mock('\Tornado\Organization\Agency', [
                'getName' => 'Test2',
                'getId' => 2
            ])
        ];

        $userRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $userRepo->shouldReceive('findOne')
            ->once()
            ->with(['id' => $userId])
            ->andReturn($user);
        $userRepo->shouldNotReceive('removeAgencies');
        $userRepo->shouldReceive('addAgencies')
            ->once()
            ->with($user, $agencies)
            ->andReturn(null);
        $agencyRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $agencyRepo->shouldNotReceive('findUserAssigned');
        $agencyRepo->shouldReceive('findUserAllowed')
            ->once()
            ->with($user)
            ->andReturn($agencies);
        $agencyRepo->shouldReceive('findByIds')
            ->once()
            ->with([1,2])
            ->andReturn($agencies);

        $commandTester = $this->provideCommandTester($userRepo, $agencyRepo);
        $commandTester->execute([
            'user_id' => $userId,
            'agencies' => '1,2'
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains('User "test@email.com" has been successfully added to the Agencies 1,2.', $output);
    }
}
