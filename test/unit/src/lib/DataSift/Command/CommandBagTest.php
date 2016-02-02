<?php

namespace Test\DataSift\Command;

use \Mockery;

use DataSift\Command\CommandBag;

/**
 * CommandBagTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\DataSift\Command
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \DataSift\Command\CommandBag
 */
class CommandBagTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::addCommand
     * @covers ::getCommands
     */
    public function testCommandBag()
    {
        $command1 = Mockery::mock('\Symfony\Component\Console\Command\Command');
        $command2 = Mockery::mock('\Symfony\Component\Console\Command\Command');

        $commandBag = new CommandBag();
        $commandBag->addCommand($command1);
        $commandBag->addCommand($command2);

        $this->assertCount(2, $commandBag->getCommands());
        $this->assertEquals([$command1, $command2], $commandBag->getCommands());
    }
}
