<?php

namespace Test\Tornado\Analyze;

use Tornado\Analyze\Analysis;
use Tornado\Analyze\Analysis\TimeSeries;

/**
 * TimeSeriesTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Analyze
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers      \Tornado\Analyze\Analysis
 * @covers      \Tornado\Analyze\Analysis\TimeSeries
 */
class TimeSeriesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * DataProvider for \Tornado\Analyze\Analysis\TimeSeries::__construct
     *
     * @return array
     */
    public function constructProvider()
    {
        return [
            [
                'target' => 'fb.gender',
                'interval' => 'day',
            ],
            [
                'target' => 'fb.gender',
                'interval' => 'hour',
                'start' => 1420066800,
                'end' => 1422572400,
                'span' => 5,
                'filter' => 'interaction.content exists'
            ]
        ];
    }

    /**
     * @dataProvider constructProvider
     *
     * @covers       \Tornado\Analyze\Analysis\TimeSeries::__construct
     * @covers       \Tornado\Analyze\Analysis\TimeSeries::getType
     * @covers       \Tornado\Analyze\Analysis\TimeSeries::getInterval
     * @covers       \Tornado\Analyze\Analysis\TimeSeries::getSpan
     * @covers       \Tornado\Analyze\Analysis::getTarget
     * @covers       \Tornado\Analyze\Analysis::getStart
     * @covers       \Tornado\Analyze\Analysis::getEnd
     * @covers       \Tornado\Analyze\Analysis::getRecording
     * @covers       \Tornado\Analyze\Analysis::getFilter
     *
     * @param string  $target
     * @param string  $interval
     * @param integer $start
     * @param integer $end
     * @param integer $span
     */
    public function testConstruct($target, $interval, $start = null, $end = null, $span = null, $filter = null)
    {
        $recordingMockClass = '\Tornado\Project\Recording';
        $recordingMock = $this->getMockBuilder($recordingMockClass)
            ->getMock();
        $recordingMock->expects($this->once())
            ->method('getId')
            ->willReturn(100);

        $obj = new TimeSeries($target, $interval, $start, $end, $span, $recordingMock, $filter);

        $this->assertInstanceOf('\Tornado\Analyze\Analysis', $obj);
        $this->assertEquals($target, $obj->getTarget());
        $this->assertEquals($interval, $obj->getInterval());
        $this->assertEquals($start, $obj->getStart());
        $this->assertEquals($end, $obj->getEnd());
        $this->assertEquals($span, $obj->getSpan());
        $this->assertEquals($filter, $obj->getFilter());
        $this->assertEquals(Analysis::TYPE_TIME_SERIES, $obj->getType());

        $this->assertInstanceOf($recordingMockClass, $obj->getRecording());
        $this->assertEquals($recordingMock, $obj->getRecording());
        $this->assertEquals(100, $obj->getRecording()->getId());
    }

    /**
     * @dataProvider constructProvider
     *
     * @covers       \Tornado\Analyze\Analysis\TimeSeries::__construct
     * @covers       \Tornado\Analyze\Analysis::getRecording
     *
     * @param string  $target
     * @param string  $interval
     */
    public function testConstructAsChildAnalysis($target, $interval)
    {
        // not necessary to pass all arguments
        $obj = new TimeSeries($target, $interval);

        $this->assertNotInstanceOf('\Tornado\Project\Recording', $obj->getRecording());
        $this->assertNull($obj->getRecording());
    }

    /**
     * @dataProvider constructProvider
     *
     * @covers       \Tornado\Analyze\Analysis\TimeSeries::__construct
     * @covers       \Tornado\Analyze\Analysis::setChild
     * @covers       \Tornado\Analyze\Analysis::getChild
     *
     * @param string  $target
     * @param string  $interval
     * @param integer $start
     * @param integer $end
     * @param integer $span
     */
    public function testSetGetChild($target, $interval, $start = null, $end = null, $span = null)
    {
        $recordingMock = $this->getMockObject('\Tornado\Project\Recording');
        $analysisMockClass = '\Tornado\Analyze\Analysis';
        $analysisMock = $this->getMockObject($analysisMockClass);
        $analysisMock->expects($this->once())
            ->method('getType')
            ->willReturn('abstractAnalysis');

        $obj = new TimeSeries($target, $interval, $start, $end, $span, $recordingMock);
        $this->assertNull($obj->getChild());

        $obj->setChild($analysisMock);
        $this->assertNotNull($obj->getChild());
        $this->assertInstanceOf($analysisMockClass, $obj->getChild());
        $this->assertEquals('abstractAnalysis', $obj->getChild()->getType());
    }

    /**
     * @dataProvider constructProvider
     *
     * @covers       \Tornado\Analyze\Analysis\TimeSeries::__construct
     * @covers       \Tornado\Analyze\Analysis::setResults
     * @covers       \Tornado\Analyze\Analysis::getResults
     *
     * @param string  $target
     * @param string  $interval
     * @param integer $start
     * @param integer $end
     * @param integer $span
     */
    public function testSetResults($target, $interval, $start = null, $end = null, $span = null)
    {
        $recordingMock = $this->getMockObject('\Tornado\Project\Recording');

        $obj = new TimeSeries($target, $interval, $start, $end, $span, $recordingMock);
        $this->assertNull($obj->getResults());

        $results = ['a' => 1, 'b' => ['c' => 2, 'd' => ['e' => 3]]];
        $resultsObject = (object)$results;
        $obj->setResults($resultsObject);
        $this->assertEquals($resultsObject, $obj->getResults());
    }

    /**
     * Prepares a mock object for given class
     *
     * @param string $class
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockObject($class)
    {
        return $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
