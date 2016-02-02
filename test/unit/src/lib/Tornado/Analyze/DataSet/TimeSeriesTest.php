<?php

namespace Test\Tornado\Analyze\DataSet;

use Tornado\Analyze\Dimension\Collection as DimensionCollection;
use Tornado\Analyze\DataSet;
use Tornado\Analyze\DataSet\TimeSeries;
use Mockery;

use Test\DataSift\FixtureLoader;

/**
 * DimensionTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Analyze
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers      \Tornado\Analyze\DataSet\TimeSeries
 */
class TimeSeriesTest extends \PHPUnit_Framework_TestCase
{

    use FixtureLoader;

    /**
     * DataProvider for testIsCompatible
     *
     * @return array
     */
    public function isCompatibleProvider()
    {
        return [
            [ // #0
                'dataset' => new DataSet(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.gender'],
                        ['target' => 'fb.author.age']
                    ]),
                    [],
                    'hour',
                    1
                ),
                'interval' => 'hour',
                'span' => 1,
                'permissive' => false,
                'expected' => false
            ],
            [ // #1
                'dataset' => new DataSet(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.gender'],
                        ['target' => 'fb.author.age']
                    ]),
                    [],
                    'hour',
                    1
                ),
                'interval' => 'hour',
                'span' => 1,
                'permissive' => false,
                'expected' => false
            ],
            [ // #2
                'dataset' => new TimeSeries(
                    $this->getDimensionCollection([
                        ['target' => 'time']
                    ]),
                    [],
                    'hour',
                    1
                ),
                'interval' => 'hour',
                'span' => 1,
                'permissive' => false,
                'expected' => true
            ],
            [ // #3
                'dataset' => new TimeSeries(
                    $this->getDimensionCollection([
                        ['target' => 'time']
                    ]),
                    [],
                    'hour',
                    1
                ),
                'interval' => 'day',
                'span' => 1,
                'permissive' => false,
                'expected' => false
            ],
            [ // #4
                'dataset' => new TimeSeries(
                    $this->getDimensionCollection([
                        ['target' => 'time']
                    ]),
                    [],
                    'hour',
                    1
                ),
                'interval' => 'day',
                'span' => 1,
                'permissive' => true,
                'expected' => false
            ],
            [ // #5
                'dataset' => new TimeSeries(
                    $this->getDimensionCollection([
                        ['target' => 'time']
                    ]),
                    [],
                    'hour',
                    1
                ),
                'interval' => 'hour',
                'span' => 2,
                'permissive' => false,
                'expected' => false
            ],
            [ // #6
                'dataset' => new TimeSeries(
                    $this->getDimensionCollection([
                        ['target' => 'time']
                    ]),
                    [],
                    'hour',
                    1
                ),
                'interval' => 'hour',
                'span' => 2,
                'permissive' => true,
                'expected' => true
            ],
        ];
    }

    /**
     * @dataProvider isCompatibleProvider
     *
     * @covers \Tornado\Analyze\DataSet\TimeSeries::isCompatible
     *
     * @param \Tornado\Analyze\Dimension\Collection $dimensions
     * @param \Tornado\Analyze\DataSet $dataset
     * @param string $interval
     * @param integer $span
     * @param boolean $permissive
     * @param boolean $expected
     */
    public function testIsCompatible(DataSet $dataset, $interval, $span, $permissive, $expected)
    {
        $object = new TimeSeries($this->getDimensionCollection([['target' => 'time']]), [], $interval, $span);
        $this->assertEquals($expected, $object->isCompatible($dataset, $permissive));
    }

    /**
     * DataProvider for testShift
     *
     * @return array
     */
    public function shiftProvider()
    {
        return [
            [ // #0
                'data' => $this->loadDataSetFixture('dataset_timeseries_1'),
                'seconds' => 100,
                'expected' => [
                    DataSet::KEY_MEASURE_INTERACTIONS => [
                        DataSet::KEY_DIMENSION_TIME => [
                            "1435536100" => [
                                DataSet::KEY_VALUE => 2000,
                                DataSet::KEY_REDACTED => false
                            ],
                            "1435622500" => [
                                DataSet::KEY_VALUE => 3000,
                                DataSet::KEY_REDACTED => false
                            ],
                            "1435708900" => [
                                DataSet::KEY_VALUE => 1000,
                                DataSet::KEY_REDACTED => false
                            ]
                        ]
                    ],
                    DataSet::KEY_MEASURE_UNIQUE_AUTHORS => [
                        DataSet::KEY_DIMENSION_TIME => [
                            "1435536100" => [
                                DataSet::KEY_VALUE => 1000,
                                DataSet::KEY_REDACTED => false
                            ],
                            "1435622500" => [
                                DataSet::KEY_VALUE => 2000,
                                DataSet::KEY_REDACTED => false
                            ],
                            "1435708900" => [
                                DataSet::KEY_VALUE => 1500,
                                DataSet::KEY_REDACTED => false
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider shiftProvider
     *
     * @covers \Tornado\Analyze\DataSet\TimeSeries::shift
     *
     * @param array $data
     * @param integer $seconds
     * @param array $expected
     */
    public function testShift(array $data, $seconds, $expected)
    {
        $object = new TimeSeries(
            $this->getDimensionCollection([['target' => 'time']]),
            $data,
            [],
            'hour',
            1
        );

        $dataset = $object->shift($seconds);
        $this->assertEquals($expected, $dataset->getData());
    }

    /**
     * DataProvider for testGetStartEnd
     *
     * @return array
     */
    public function getStartEndProvider()
    {
        return [
            [
                'data' => $this->loadDataSetFixture('dataset_timeseries_1'),
                'expectedStart' => 1435536000,
                'expectedEnd' => 1435708800
            ]
        ];
    }

    /**
     * @dataProvider getStartEndProvider
     *
     * @covers \Tornado\Analyze\DataSet\TimeSeries::getStart
     * @covers \Tornado\Analyze\DataSet\TimeSeries::getEnd
     *
     * @param array $data
     * @param mixed $expected
     */
    public function testGetStartEnd(array $data, $expectedStart, $expectedEnd)
    {
        $object = new TimeSeries(
            $this->getDimensionCollection([['target' => 'time']]),
            $data,
            [],
            'hour',
            1
        );
        $this->assertEquals($expectedStart, $object->getStart());
        $this->assertEquals($expectedEnd, $object->getEnd());
    }

    /**
     * Loads the fixture before translating references to class constants
     *
     * @param string $fixture
     *
     * @return array
     */
    private function loadDataSetFixture($fixture)
    {
        $fixture = $this->loadFixture("{$fixture}.json");
        $fixture = strtr($fixture, [
            'DataSet::KEY_MEASURE_INTERACTIONS' => DataSet::KEY_MEASURE_INTERACTIONS,
            'DataSet::KEY_MEASURE_UNIQUE_AUTHORS' => DataSet::KEY_MEASURE_UNIQUE_AUTHORS,
            'DataSet::KEY_VALUE' => DataSet::KEY_VALUE,
            'DataSet::KEY_REDACTED' => DataSet::KEY_REDACTED,
        ]);

        return json_decode($fixture, true);
    }

    /**
     * Gets a mocked Dimension\Collection for use in testing
     *
     * @param array $dimensions
     *
     * @return \Tornado\Analyze\Dimension\Collection
     */
    private function getDimensionCollection(array $dimensions)
    {
        $dims = [];
        foreach ($dimensions as $dim) {
            $dimension = Mockery::mock('\Tornado\Analyze\Dimension');
            $dimension->shouldReceive('getTarget')->andReturn($dim['target']);
            if (isset($dim['cardinality'])) {
                $dimension->shouldReceive('getCardinality')->andReturn($dim['cardinality']);
            }
            $dims[] = $dimension;
        }

        $dimensionCollection = new DimensionCollection($dims);

        return $dimensionCollection;
    }
}
