<?php

namespace Test\Command;

use \Mockery;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Command\CreateUser;

use Tornado\Organization\User;
use Tornado\Organization\User\Factory;

use Test\DataSift\ReflectionAccess;

/**
 * CreateUserTest
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
 * @coversDefaultClass  \Command\CreateUser
 */
class CreateUserTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess;

    /**
     * @covers ::__construct
     * @covers ::configure
     */
    public function testConfigure()
    {
        $userRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $brandRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $validator = Mockery::mock('\Symfony\Component\Validator\Validator\ValidatorInterface');

        $command = new CreateUser($userRepo, $brandRepo, $validator, new Factory());
        $this->invokeMethod($command, 'configure');

        $this->assertEquals('tornado:user:create', $command->getName());
        $this->assertNotEmpty($command->getDescription());
        $this->assertNotEmpty($command->getHelp());

        $definition = $command->getDefinition();
        $this->assertInstanceOf('Symfony\Component\Console\Input\InputDefinition', $definition);
        $this->assertTrue($definition->hasArgument('email'));
        $this->assertTrue($definition->hasArgument('organization'));
        $this->assertTrue($definition->hasArgument('password'));
        $this->assertTrue($definition->hasArgument('username'));
        $this->assertTrue($definition->hasOption('admin'));
    }

    /**
     * @covers ::execute
     */
    public function testExecute()
    {
        $application = new Application();
        $organization = Mockery::mock('\Tornado\Organization\Organization', [
            'getId' => 1
        ]);
        $userRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface', [
            'create' => true,
            'findByOrganization' => false
        ]);
        $organizationRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface', [
            'findOne' => $organization
        ]);
        $validator = Mockery::mock('\Symfony\Component\Validator\Validator\ValidatorInterface', [
            'validate' => []
        ]);

        $commandObj = new CreateUser($userRepo, $organizationRepo, $validator, new Factory());
        $application->add($commandObj);

        $command = $application->find($this->getPropertyValue($commandObj, 'name'));

        $this->assertInstanceOf('\Command\CreateUser', $command);
        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);

        $args = [
            'command' => $command->getName(),
            'email' => 'test@email.com',
            'organization' => 'test',
            'password' => 'abc',
            'username' => 'test'
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($args);
        $expectedOutput = sprintf(
            "Created user \"%s\" (email: \"%s\") for organization \"%s\".\n",
            $args['username'],
            $args['email'],
            $args['organization']
        );

        $this->assertEquals($expectedOutput, $commandTester->getDisplay());
    }

    /**
     * @covers ::execute
     * @covers ::validateOrganizationArgument
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionUnlessOrganizationFound()
    {
        $application = new Application();
        $userRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface', [
            'create' => true,
            'findByOrganization' => false
        ]);
        $organizationRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface', [
            'findOne' => false
        ]);
        $validator = Mockery::mock('\Symfony\Component\Validator\Validator\ValidatorInterface', [
            'validate' => ['error1']
        ]);

        $commandObj = new CreateUser($userRepo, $organizationRepo, $validator, new Factory());
        $application->add($commandObj);

        $command = $application->find($this->getPropertyValue($commandObj, 'name'));

        $args = [
            'command' => $command->getName(),
            'email' => 'test@email.com',
            'organization' => 'test',
            'password' => 'abc',
            'username' => 'test'
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($args);
    }

    /**
     * @covers ::execute
     * @covers ::validateEmailArgument
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionUnlessValidationPass()
    {
        $application = new Application();
        $organization = Mockery::mock('\Tornado\Organization\Organization', [
            'getId' => 1
        ]);
        $userRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface', [
            'create' => true,
            'findByOrganization' => false
        ]);
        $organizationRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface', [
            'findOne' => $organization
        ]);
        $validator = Mockery::mock('\Symfony\Component\Validator\Validator\ValidatorInterface', [
            'validate' => ['error1']
        ]);

        $commandObj = new CreateUser($userRepo, $organizationRepo, $validator, new Factory());
        $application->add($commandObj);

        $command = $application->find($this->getPropertyValue($commandObj, 'name'));

        $args = [
            'command' => $command->getName(),
            'email' => 'testemail.com',
            'organization' => 'test',
            'password' => 'abc',
            'username' => 'test'
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($args);
    }

    /**
     * @covers ::execute
     * @covers ::validateEmailArgument
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionUnlessEmailAlreadyUsed()
    {
        $application = new Application();
        $organization = Mockery::mock('\Tornado\Organization\Organization', [
            'getId' => 1,
            'getName' => 'test'
        ]);
        $userRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface', [
            'create' => true,
        ]);
        $user = new User();
        $userRepo->shouldReceive('findByOrganization')
            ->once()
            ->with($organization, ['email' => 'test@email.com'])
            ->andReturn($user);
        $organizationRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface', [
            'findOne' => $organization
        ]);
        $validator = Mockery::mock('\Symfony\Component\Validator\Validator\ValidatorInterface', [
            'validate' => []
        ]);

        $commandObj = new CreateUser($userRepo, $organizationRepo, $validator, new Factory());
        $application->add($commandObj);

        $command = $application->find($this->getPropertyValue($commandObj, 'name'));

        $args = [
            'command' => $command->getName(),
            'email' => 'test@email.com',
            'organization' => 'test',
            'password' => 'abc',
            'username' => 'test'
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($args);
    }

    /**
     * @covers ::execute
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionUnlessUniqueUserDataGiven()
    {
        $application = new Application();
        $organization = Mockery::mock('\Tornado\Organization\Organization', [
            'getId' => 1,
            'getName' => 'test'
        ]);
        $userRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface', [
            'create' => true,
            'findByOrganization' => true
        ]);
        $organizationRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface', [
            'findOne' => $organization
        ]);
        $validator = Mockery::mock('\Symfony\Component\Validator\Validator\ValidatorInterface', [
            'validate' => []
        ]);

        $commandObj = new CreateUser($userRepo, $organizationRepo, $validator, new Factory());
        $application->add($commandObj);

        $command = $application->find($this->getPropertyValue($commandObj, 'name'));

        $args = [
            'command' => $command->getName(),
            'email' => 'test@email.com',
            'organization' => 'test',
            'password' => 'abc',
            'username' => 'test'
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($args);
    }
}
