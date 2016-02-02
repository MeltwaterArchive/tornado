<?php

namespace Test\Tornado\Project\Chart\Generator;

use Mockery;

use Tornado\Project\Chart\Generator;
use Tornado\Project\Chart\Generator\Histogram;
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
 * @covers \Tornado\Project\Chart\Generator\Histogram
 */
class HistogramTest extends \PHPUnit_Framework_TestCase
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
                    ['target' => 'fb.author.age'],
                    ['target' => 'fb.author.gender']
                ]),
                'primary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.gender']
                    ]),
                    'dataset_tornado1'
                ),
                'expected' => [
                    [
                        'getName' => 'fb.author.age x fb.author.gender: 18-24',
                        'getData' => $this->loadDataFromFixture('histogram/from-dataset-0a')
                    ],
                    [
                        'getName' => 'fb.author.age x fb.author.gender: unknown',
                        'getData' => $this->loadDataFromFixture('histogram/from-dataset-0b')
                    ]
                ]
            ],
            [ // #1
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.age']
                ]),
                'primary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.gender']
                    ]),
                    'dataset_tornado1'
                ),
                'expected' => [
                    [
                        'getName' => 'fb.author.gender x fb.author.age: male',
                        'getData' => $this->loadDataFromFixture('histogram/from-dataset-1a')
                    ],
                    [
                        'getName' => 'fb.author.gender x fb.author.age: unknown',
                        'getData' => $this->loadDataFromFixture('histogram/from-dataset-1b')
                    ]
                ]
            ],
            [ // #2
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.age']
                ]),
                'primary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.gender']
                    ]),
                    'dataset_tornado1'
                ),
                'expected' => [
                    [
                        'getName' => 'fb.author.gender x fb.author.age: male',
                        'getData' => $this->loadDataFromFixture('histogram/from-dataset-2a')
                    ],
                    [
                        'getName' => 'fb.author.gender x fb.author.age: unknown',
                        'getData' => $this->loadDataFromFixture('histogram/from-dataset-2b')
                    ]
                ],
                'secondary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.gender'],
                    ]),
                    'dataset_tornado1a'
                ),
            ],
            [ // #3
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.age']
                ]),
                'primary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.gender']
                    ]),
                    'dataset_tornado1'
                ),
                'expected' => [
                    [
                        'getName' => 'fb.author.gender x fb.author.age: male',
                        'getData' => $this->loadDataFromFixture('histogram/from-dataset-3a')
                    ],
                    [
                        'getName' => 'fb.author.gender x fb.author.age: unknown',
                        'getData' => $this->loadDataFromFixture('histogram/from-dataset-3b')
                    ]
                ],
                'secondary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.gender'],
                    ]),
                    'dataset_tornado1a'
                ),
                'mode' => Generator::MODE_BASELINE
            ],
            [ // #4
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.age']
                ]),
                'primary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.gender']
                    ]),
                    'dataset_tornado1'
                ),
                'expected' => [],
                'secondary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.gender'],
                        ['target' => 'fb.region']
                    ]),
                    'dataset_tornado2'
                ),
                'mode' => Generator::MODE_COMPARE,
                'expectedException' => 'Tornado\Analyze\DataSet\IncompatibleDimensionsException'
            ],
            [ // #5 - single dimension
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender']
                ]),
                'primary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.gender']
                    ]),
                    'dataset_single_dimension'
                ),
                'expected' => [
                    [
                        'getName' => 'fb.author.gender',
                        'getData' => $this->loadDataFromFixture('histogram/from-dataset-single-dimension')
                    ]
                ]
            ],
            [ // #6 - single dimension - comparison
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender']
                ]),
                'primary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.gender']
                    ]),
                    'dataset_single_dimension'
                ),
                'expected' => [
                    [
                        'getName' => 'fb.author.gender',
                        'getData' => $this->loadDataFromFixture('histogram/from-dataset-single-dimension-comparison')
                    ]
                ],
                'secondary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.gender']
                    ]),
                    'dataset_single_dimension'
                ),
                'mode' => Generator::MODE_COMPARE
            ],
            [ // #7 - single dimension - baseline
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender']
                ]),
                'primary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.gender']
                    ]),
                    'dataset_single_dimension'
                ),
                'expected' => [
                    [
                        'getName' => 'fb.author.gender',
                        'getData' => $this->loadDataFromFixture('histogram/from-dataset-single-dimension-baseline')
                    ]
                ],
                'secondary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.gender']
                    ]),
                    'dataset_single_dimension'
                ),
                'mode' => Generator::MODE_BASELINE
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
        $obj = new Histogram(new NameGenerator());
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
     * Creates a Dimension Collection from the requested fixture
     *
     * @param \Tornado\Analyze\Dimension\Collection $dimensions
     * @param string $fixture
     *
     * @return \Tornado\Analyze\DataSet
     */
    private function loadDataSetFromFixture(DimensionCollection $dimensions, $fixture)
    {
        return new DataSet($dimensions, $this->loadDataFromFixture($fixture));
    }

    /**
     * Loads the fixture before translating references to class constants
     *
     * @param string $fixture
     * @param string $fixture
     *
     * @return array
     */
    private function loadDataFromFixture($fixture)
    {
        $fixture = $this->loadFixture("{$fixture}.json");
        $fixture = strtr($fixture, [
            'DataSet::KEY_MEASURE_INTERACTIONS' => DataSet::KEY_MEASURE_INTERACTIONS,
            'DataSet::KEY_MEASURE_UNIQUE_AUTHORS' => DataSet::KEY_MEASURE_UNIQUE_AUTHORS,
            'DataSet::KEY_VALUE' => DataSet::KEY_VALUE,
            'DataSet::KEY_REDACTED' => DataSet::KEY_REDACTED,
            'Generator::MEASURE_INTERACTIONS' => Generator::MEASURE_INTERACTIONS,
            'Generator::MEASURE_UNIQUE_AUTHORS' => Generator::MEASURE_UNIQUE_AUTHORS,
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
