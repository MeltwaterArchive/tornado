<?php

namespace Test\Command;

use \Mockery;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use Command\DeleteUser;
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
 * @coversDefaultClass  \Command\DeleteUser
 */
class DeleteUserTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess;

    /**
     * @covers ::__construct
     * @covers ::configure
     */
    public function testConfigure()
    {
        $userRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');

        $command = new DeleteUser($userRepo);
        $this->invokeMethod($command, 'configure');

        $this->assertEquals('tornado:user:delete', $command->getName());
        $this->assertNotEmpty($command->getDescription());
        $this->assertNotEmpty($command->getHelp());

        $definition = $command->getDefinition();
        $this->assertInstanceOf('Symfony\Component\Console\Input\InputDefinition', $definition);
        $this->assertTrue($definition->hasArgument('id'));
    }

    /**
     * @covers ::execute
     */
    public function testExecute()
    {
        $application = new Application();
        $user = Mockery::mock('\Tornado\Organization\User');
        $id = 1;
        $userRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface', [
            'findOne' => $user,
            'delete' => true
        ]);

        $commandObj = new DeleteUser($userRepo);
        $application->add($commandObj);

        $command = $application->find($this->getPropertyValue($commandObj, 'name'));

        $this->assertInstanceOf('\Command\DeleteUser', $command);
        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);

        $args = [
            'command' => $command->getName(),
            'id' => $id
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($args);
        $expectedOutput = sprintf("Deleted user with id=\"%d\".\n", $id);

        $this->assertEquals($expectedOutput, $commandTester->getDisplay());
    }

    /**
     * @covers ::execute
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionUnlessUserFound()
    {
        $application = new Application();
        $id = 1;
        $userRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface', [
            'findOne' => false,
            'delete' => true
        ]);

        $commandObj = new DeleteUser($userRepo);
        $application->add($commandObj);

        $command = $application->find($this->getPropertyValue($commandObj, 'name'));

        $args = [
            'command' => $command->getName(),
            'id' => $id
        ];
        $commandTester = new CommandTester($command);
        $commandTester->execute($args);
    }
}
