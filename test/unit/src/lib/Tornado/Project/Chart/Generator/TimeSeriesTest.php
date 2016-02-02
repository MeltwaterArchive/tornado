<?php

namespace Test\Tornado\Project\Chart\Generator;

use Mockery;

use Tornado\Project\Chart\Generator;
use Tornado\Project\Chart\Generator\TimeSeries;
use Tornado\Project\Chart\NameGenerator;
use Tornado\Project\Chart;
use Tornado\Analyze\DataSet;
use Tornado\Analyze\Dimension;
use Tornado\Analyze\Dimension\Collection as DimensionCollection;

use Test\DataSift\FixtureLoader;

/**
 * TornadoTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Project
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers \Tornado\Project\Chart\Generator\TimeSeries
 */
class TimeSeriesTest extends \PHPUnit_Framework_TestCase
{

    use FixtureLoader;

    /**
     * DataProvider for testFromDataSet
     *
     * @return array
     */
    public function fromDataSetProvider()
    {
        return [
            [ // #0
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'time']
                ]),
                'primary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'time']
                    ]),
                    'dataset_timeseries_1'
                ),
                'expected' => [
                    [
                        'getName' => 'Time Series',
                        'getData' => [
                            Generator::MEASURE_INTERACTIONS => [
                                'main' => [
                                    ["1435536000", 2000],
                                    ["1435622400", 3000],
                                    ["1435708800", 1000]
                                ]
                            ],
                            Generator::MEASURE_UNIQUE_AUTHORS => [
                                'main' => [
                                    ["1435536000", 1000],
                                    ["1435622400", 2000],
                                    ["1435708800", 1500]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [ // #1
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'time']
                ]),
                'primary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'time']
                    ]),
                    'dataset_timeseries_1'
                ),
                'expected' => [
                    [
                        'getName' => 'Time Series',
                        'getData' => [
                            Generator::MEASURE_INTERACTIONS => [
                                'main' => [
                                    ["1435536000", 2000],
                                    ["1435622400", 3000],
                                    ["1435708800", 1000]
                                ],
                                'comparison' => [
                                    ["1435536000", 5000],
                                    ["1435622400", 3400],
                                    ["1435708800", 6000]
                                ]
                            ],
                            Generator::MEASURE_UNIQUE_AUTHORS => [
                                'main' => [
                                    ["1435536000", 1000],
                                    ["1435622400", 2000],
                                    ["1435708800", 1500]
                                ],
                                'comparison' => [
                                    ["1435536000", 1200],
                                    ["1435622400", 6000],
                                    ["1435708800", 2500]
                                ]
                            ]
                        ]
                    ]
                ],
                'secondary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'time']
                    ]),
                    'dataset_timeseries_2'
                ),
                Generator::MODE_COMPARE
            ],
            [ // #2
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'time']
                ]),
                'primary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'time']
                    ]),
                    'dataset_timeseries_1'
                ),
                'expected' => [
                    [
                        'getName' => 'Time Series',
                        'getData' => [
                            Generator::MEASURE_INTERACTIONS => [
                                TimeSeries::KEY_MAIN_SERIES => [
                                    ["1435536000", 2000],
                                    ["1435622400", 3000],
                                    ["1435708800", 1000]
                                ],
                                TimeSeries::KEY_COMPARISON_SERIES => [
                                    ["1435536000", 2083],
                                    ["1435622400", 1417],
                                    ["1435708800", 2500]
                                ]
                            ],
                            Generator::MEASURE_UNIQUE_AUTHORS => [
                                TimeSeries::KEY_MAIN_SERIES => [
                                    ["1435536000", 1000],
                                    ["1435622400", 2000],
                                    ["1435708800", 1500]
                                ],
                                TimeSeries::KEY_COMPARISON_SERIES => [
                                    ["1435536000", 557],
                                    ["1435622400", 2784],
                                    ["1435708800", 1160]
                                ]
                            ]
                        ]
                    ]
                ],
                'secondary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'time']
                    ]),
                    'dataset_timeseries_2'
                ),
                Generator::MODE_BASELINE
            ]
        ];
    }

    /**
     * @dataProvider fromDataSetProvider
     *
     * @covers \Tornado\Project\Chart\Generator\Histogram::fromDataSet
     *
     * @param \Test\Tornado\Project\Chart\DimensionCollection $dimensions
     * @param \Tornado\Analyze\DataSet $primary
     * @param array $expected
     * @param \Tornado\Analyze\DataSet|null $secondary
     * @param string $mode
     * @param string $expectedException
     */
    public function testFromDataSet(
        DimensionCollection $dimensions,
        DataSet $primary,
        array $expected,
        DataSet $secondary = null,
        $mode = Generator::MODE_COMPARE,
        $expectedException = false
    ) {
        if ($expectedException) {
            $this->setExpectedException($expectedException);
        }
        $obj = new TimeSeries(new NameGenerator());
        $charts = ($secondary)
            ? $obj->fromDataSet($dimensions, $primary, $secondary, $mode)
            : $obj->fromDataSet($dimensions, $primary);
        $this->assertEquals(count($expected), count($charts));
        foreach ($expected as $idx => $getters) {
            $chart = $charts[$idx];
            $this->assertInstanceOf('\Tornado\Project\Chart', $chart);
            foreach ($getters as $getter => $value) {
                $this->assertEquals($value, $chart->{$getter}());
            }
        }
    }

    /**
     * Loads the fixture before translating references to class constants
     *
     * @param string $fixture
     *
     * @return \Tornado\Analyze\DataSet
     */
    private function loadDataSetFromFixture(DimensionCollection $dimensions, $fixture)
    {
        $fixture = $this->loadFixture("{$fixture}.json");
        $fixture = strtr($fixture, [
            'DataSet::KEY_MEASURE_INTERACTIONS' => DataSet::KEY_MEASURE_INTERACTIONS,
            'DataSet::KEY_MEASURE_UNIQUE_AUTHORS' => DataSet::KEY_MEASURE_UNIQUE_AUTHORS,
            'DataSet::KEY_VALUE' => DataSet::KEY_VALUE,
            'DataSet::KEY_REDACTED' => DataSet::KEY_REDACTED,
        ]);

        $data = json_decode($fixture, true);
        return new DataSet($dimensions, $data);
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
            $dimension->shouldReceive('getLabel')->andReturn(null);
            if (isset($dim['cardinality'])) {
                $dimension->shouldReceive('getCardinality')->andReturn($dim['cardinality']);
            }
            $dims[] = $dimension;
        }

        $dimensionCollection = new DimensionCollection($dims);

        return $dimensionCollection;
    }
}
