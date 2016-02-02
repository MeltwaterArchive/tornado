<?php

namespace Test\Tornado\Project\Recording;

use Tornado\Project\Recording\DataSiftRecordingException;

/**
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Project\Recording
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Project\Recording\DataSiftRecordingException
 */
class DataSiftRecordingExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getStatusCode
     */
    public function testConstruct()
    {
        $exc = new DataSiftRecordingException('error', 10, null, 400);

        $this->assertEquals('error', $exc->getMessage());
        $this->assertEquals(400, $exc->getStatusCode());
        $this->assertEquals(10, $exc->getCode());
    }
}
