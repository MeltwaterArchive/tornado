<?php

namespace Test\Tornado\Application\Flash;

use \Tornado\Application\Flash\Message;

/**
 * Test for Flash Messages
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Application\Flash
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Application\Flash\Message
 */
class MessageTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::__construct
     * @covers ::getMessage
     * @covers ::getLevel
     */
    public function testConstruct()
    {
        $message = 'testMessage';
        $level = 'level';

        $item = new Message($message, $level);
        $this->assertEquals($message, $item->getMessage());
        $this->assertEquals($level, $item->getLevel());
    }
}
