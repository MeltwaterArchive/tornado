<?php

namespace Test\Tornado\Analyze\Analysis;

use Tornado\Analyze\Analysis\Collection;
use Tornado\Analyze\Analysis\FrequencyDistribution;
use Tornado\Analyze\Analysis\TimeSeries;

/**
 * CollectionTest
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
 * @covers      \Tornado\Analyze\Analysis\Collection
 */
class CollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testSetGetTitle()
    {
        $collection = new Collection([]);
        $collection->setTitle('Test');
        $this->assertEquals('Test', $collection->getTitle());
    }

    /**
     * DataProvider for testSetGetAnalyses()
     *
     * @return array
     */
    public function setGetAnalysesProvider()
    {
        $freqDistA = new FrequencyDistribution('fd.a', 2);
        $freqDistB = new FrequencyDistribution('fd.b');
        $timeSeriesA = new TimeSeries('ts.a', 'hour');
        $timeSeriesB = new TimeSeries('ts.b', 'day');

        return [
            [ // 0
                'analyses' => [$freqDistA, $freqDistB],
                'expected' => [$freqDistA, $freqDistB]
            ],
            [ // 1
                'analyses' => [$timeSeriesA, $timeSeriesB],
                'expected' => [$timeSeriesA, $timeSeriesB]
            ],
            [ // 2
                'analyses' => [$freqDistA, $freqDistB, $timeSeriesA, $timeSeriesB],
                'expected' => [$freqDistA, $freqDistB, $timeSeriesA, $timeSeriesB]
            ]
        ];
    }

    /**
     * @dataProvider setGetAnalysesProvider
     *
     * @covers       \Tornado\Analyze\Analysis\Collection::setAnalyses
     * @covers       \Tornado\Analyze\Analysis\Collection::getAnalyses
     *
     * @param   array $analyses
     * @param   array $expected
     */
    public function testSetGetAnalyses(array $analyses, array $expected)
    {
        $obj = new Collection([new FrequencyDistribution('test.target')]);
        $obj->setAnalyses($analyses);

        $this->assertEquals($expected, $obj->getAnalyses());
        $this->assertEquals(count($expected), count($obj->getAnalyses()));
    }

    /**
     * @dataProvider setGetAnalysesProvider
     *
     * @covers       \Tornado\Analyze\Analysis\Collection::addAnalysis
     * @covers       \Tornado\Analyze\Analysis\Collection::getAnalyses
     *
     * @param   array $analyses
     * @param   array $expected
     */
    public function testAddAnalysis(array $analyses, array $expected)
    {
        $obj = new Collection($analyses);
        $this->assertEquals($expected, $obj->getAnalyses());
        $this->assertEquals(count($expected), count($obj->getAnalyses()));

        $analysis = new FrequencyDistribution('test.target');
        $obj->addAnalysis($analysis);
        $this->assertNotEquals($expected, $obj->getAnalyses());
        $this->assertNotEquals(count($expected), count($obj->getAnalyses()));

        $analyses[] = $analysis;
        $this->assertEquals($analyses, $obj->getAnalyses());
        $this->assertEquals(count($analyses), count($obj->getAnalyses()));
    }
}
