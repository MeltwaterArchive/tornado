<?php

namespace Test\Tornado\Analyze\Analysis;

use Tornado\Analyze\Analysis;
use Tornado\Analyze\Analysis\FrequencyDistribution;

/**
 * FrequencyDistributionTest
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
 * @covers      \Tornado\Analyze\Analysis\FrequencyDistribution
 */
class FrequencyDistributionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * DataProvider for \Tornado\Analyze\Analysis\FrequencyDistribution::__construct
     *
     * @return array
     */
    public function constructProvider()
    {
        return [
            [
                'target' => 'fb.gender'
            ],
            [
                'target' => 'fb.gender',
                'threshold' => 2,
                'start' => 1420066800,
                'end' => 1422572400,
                'filter' => 'interaction.content exists'
            ]
        ];
    }

    /**
     * @dataProvider constructProvider
     *
     * @covers       \Tornado\Analyze\Analysis\FrequencyDistribution::__construct
     * @covers       \Tornado\Analyze\Analysis\FrequencyDistribution::getType
     * @covers       \Tornado\Analyze\Analysis\FrequencyDistribution::getThreshold
     * @covers       \Tornado\Analyze\Analysis::getTarget
     * @covers       \Tornado\Analyze\Analysis::getStart
     * @covers       \Tornado\Analyze\Analysis::getEnd
     * @covers       \Tornado\Analyze\Analysis::getRecording
     * @covers       \Tornado\Analyze\Analysis::getFilter
     *
     * @param string  $target
     * @param integer $threshold
     * @param integer $start
     * @param integer $end
     */
    public function testConstruct($target, $threshold = null, $start = null, $end = null, $filter = null)
    {
        $recordingMockClass = '\Tornado\Project\Recording';
        $recordingMock = $this->getMockBuilder($recordingMockClass)
            ->getMock();
        $recordingMock->expects($this->once())
            ->method('getId')
            ->willReturn(100);

        $obj = new FrequencyDistribution($target, $threshold, $start, $end, $recordingMock, $filter);

        $this->assertInstanceOf('\Tornado\Analyze\Analysis', $obj);
        $this->assertEquals($target, $obj->getTarget());
        $this->assertEquals($threshold, $obj->getThreshold());
        $this->assertEquals($start, $obj->getStart());
        $this->assertEquals($end, $obj->getEnd());
        $this->assertEquals($filter, $obj->getFilter());
        $this->assertEquals(Analysis::TYPE_FREQUENCY_DISTRIBUTION, $obj->getType());

        $this->assertInstanceOf($recordingMockClass, $obj->getRecording());
        $this->assertEquals($recordingMock, $obj->getRecording());
        $this->assertEquals(100, $obj->getRecording()->getId());
    }

    /**
     * @dataProvider constructProvider
     *
     * @covers       \Tornado\Analyze\Analysis\FrequencyDistribution::__construct
     * @covers       \Tornado\Analyze\Analysis::getRecording
     *
     * @param string $target
     */
    public function testConstructAsChildAnalysis($target)
    {
        // not necessary to pass all arguments
        $obj = new FrequencyDistribution($target);

        $this->assertNotInstanceOf('\Tornado\Project\Recording', $obj->getRecording());
        $this->assertNull($obj->getRecording());
    }

    /**
     * @dataProvider constructProvider
     *
     * @covers       \Tornado\Analyze\Analysis\FrequencyDistribution::__construct
     * @covers       \Tornado\Analyze\Analysis::setChild
     * @covers       \Tornado\Analyze\Analysis::getChild
     *
     * @param string  $target
     * @param integer $threshold
     * @param integer $start
     * @param integer $end
     */
    public function testSetGetChild($target, $threshold = null, $start = null, $end = null)
    {
        $analysisMockClass = '\Tornado\Analyze\Analysis';
        $analysisMock = $this->getMockBuilder($analysisMockClass)
            ->getMock();
        $analysisMock->expects($this->once())
            ->method('getType')
            ->willReturn('abstractAnalysis');

        $obj = new FrequencyDistribution($target, $threshold, $start, $end);
        $this->assertNull($obj->getChild());

        $obj->setChild($analysisMock);
        $this->assertNotNull($obj->getChild());
        $this->assertInstanceOf($analysisMockClass, $obj->getChild());
        $this->assertEquals('abstractAnalysis', $obj->getChild()->getType());
    }

    /**
     * @dataProvider constructProvider
     *
     * @covers       \Tornado\Analyze\Analysis\FrequencyDistribution::__construct
     * @covers       \Tornado\Analyze\Analysis::setResults
     * @covers       \Tornado\Analyze\Analysis::getResults
     *
     * @param string  $target
     * @param integer $threshold
     * @param integer $start
     * @param integer $end
     */
    public function testSetResults($target, $threshold = null, $start = null, $end = null)
    {
        $recordingMock = $this->getMockBuilder('\Tornado\Project\Recording')
            ->getMock();

        $obj = new FrequencyDistribution($target, $threshold, $start, $end, $recordingMock);
        $this->assertNull($obj->getResults());

        $results = ['a' => 1, 'b' => ['c' => 2, 'd' => ['e' => 3]]];
        $resultsObject = (object)$results;
        $obj->setResults($resultsObject);
        $this->assertEquals($resultsObject, $obj->getResults());
    }
}
