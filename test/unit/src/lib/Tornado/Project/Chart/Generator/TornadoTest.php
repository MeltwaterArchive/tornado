<?php

namespace Test\Tornado\Project\Chart\Generator;

use Mockery;

use Tornado\Project\Chart\Generator;
use Tornado\Project\Chart\Generator\Tornado;
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
 * @covers \Tornado\Project\Chart\Generator\Tornado
 */
class TornadoTest extends \PHPUnit_Framework_TestCase
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
                        'getName' => 'fb.author.age x fb.author.gender',
                        'getData' => $this->loadDataFromFixture('tornado/from-dataset-0')
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
                        'getName' => 'fb.author.gender x fb.author.age',
                        'getData' => $this->loadDataFromFixture('tornado/from-dataset-1')
                    ]
                ]
            ],
            [ // #2
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.age'],
                    ['target' => 'fb.region']
                ]),
                'primary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.gender'],
                        ['target' => 'fb.region']
                    ]),
                    'dataset_tornado2'
                ),
                'expected' => [
                    [
                        'getName' => 'Alabama: fb.author.gender x fb.author.age',
                        'getData' => $this->loadDataFromFixture('tornado/from-dataset-2a')
                    ],
                    [
                        'getName' => 'Alaska: fb.author.gender x fb.author.age',
                        'getData' => $this->loadDataFromFixture('tornado/from-dataset-2b')
                    ]
                ]
            ],
            [ // #3
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.age'],
                    ['target' => 'fb.region']
                ]),
                'primary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.gender'],
                        ['target' => 'fb.region']
                    ]),
                    'dataset_tornado2'
                ),
                'expected' => [
                    [
                        'getName' => 'Alabama: fb.author.gender x fb.author.age',
                        'getData' => $this->loadDataFromFixture('tornado/from-dataset-3a')
                    ],
                    [
                        'getName' => 'Alaska: fb.author.gender x fb.author.age',
                        'getData' => $this->loadDataFromFixture('tornado/from-dataset-3b')
                    ]
                ],
                'secondary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.gender'],
                        ['target' => 'fb.region']
                    ]),
                    'dataset_tornado3'
                ),
                Generator::MODE_COMPARE
            ],
            [ // #4
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.age'],
                    ['target' => 'fb.region']
                ]),
                'primary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.gender'],
                        ['target' => 'fb.region']
                    ]),
                    'dataset_tornado2'
                ),
                'expected' => [
                    [
                        'getName' => 'Alabama: fb.author.gender x fb.author.age',
                        'getData' => $this->loadDataFromFixture('tornado/from-dataset-4a')
                    ],
                    [
                        'getName' => 'Alaska: fb.author.gender x fb.author.age',
                        'getData' => $this->loadDataFromFixture('tornado/from-dataset-4b')
                    ]
                ],
                'secondary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.gender']
                    ]),
                    'dataset_tornado4'
                ),
                Generator::MODE_COMPARE
            ],
            [ // #5
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.age'],
                    ['target' => 'fb.region']
                ]),
                'primary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.gender'],
                        ['target' => 'fb.region']
                    ]),
                    'dataset_tornado2'
                ),
                'expected' => [
                    [
                        'getName' => 'Alabama: fb.author.gender x fb.author.age',
                        'getData' => $this->loadDataFromFixture('tornado/from-dataset-5a')
                    ],
                    [
                        'getName' => 'Alaska: fb.author.gender x fb.author.age',
                        'getData' => $this->loadDataFromFixture('tornado/from-dataset-5b')
                    ]
                ],
                'secondary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.region']
                    ]),
                    'dataset_tornado4'
                ),
                Generator::MODE_COMPARE,
                'Tornado\Analyze\DataSet\IncompatibleDimensionsException'
            ],
            [ // #6
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.age'],
                    ['target' => 'fb.region']
                ]),
                'primary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.gender'],
                        ['target' => 'fb.region']
                    ]),
                    'dataset_tornado2'
                ),
                'expected' => [
                    [
                        'getName' => 'Alabama: fb.author.gender x fb.author.age',
                        'getData' => $this->loadDataFromFixture('tornado/from-dataset-6a')
                    ],
                    [
                        'getName' => 'Alaska: fb.author.gender x fb.author.age',
                        'getData' => $this->loadDataFromFixture('tornado/from-dataset-6b')
                    ]
                ],
                'secondary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.gender']
                    ]),
                    'dataset_tornado4'
                ),
                Generator::MODE_COMPARE
            ],
            [ // #7
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.age'],
                    ['target' => 'fb.region']
                ]),
                'primary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.gender'],
                        ['target' => 'fb.region']
                    ]),
                    'dataset_tornado2'
                ),
                'expected' => [
                    [
                        'getName' => 'Alabama: fb.author.gender x fb.author.age',
                        'getData' => $this->loadDataFromFixture('tornado/from-dataset-7a')
                    ],
                    [
                        'getName' => 'Alaska: fb.author.gender x fb.author.age',
                        'getData' => $this->loadDataFromFixture('tornado/from-dataset-7b')
                    ]
                ],
                'secondary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.gender'],
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.region'],
                    ]),
                    'dataset_tornado3'
                ),
                Generator::MODE_BASELINE,
            ],
            [ // #8 - see NEV-145
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                ]),
                'primary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.gender'],
                    ]),
                    'dataset_nev_145'
                ),
                'expected' => [
                    [
                        'getName' => 'fb.author.gender',
                        'getData' => $this->loadDataFromFixture('tornado/from-dataset-8')
                    ]
                ]
            ],
            [ // #9 - unbalanced sets; see NEV-170
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.age'],
                    ['target' => 'fb.region']
                ]),
                'primary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.gender'],
                        ['target' => 'fb.region']
                    ]),
                    'dataset_tornado2a'
                ),
                'expected' => [
                    [
                        'getName' => 'Alabama: fb.author.gender x fb.author.age',
                        'getData' => $this->loadDataFromFixture('tornado/from-dataset-9a')
                    ],
                    [
                        'getName' => 'Alaska: fb.author.gender x fb.author.age',
                        'getData' => $this->loadDataFromFixture('tornado/from-dataset-9b')
                    ]
                ],
                'secondary' => $this->loadDataSetFromFixture(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.gender'],
                        ['target' => 'fb.region']
                    ]),
                    'dataset_tornado3'
                ),
                Generator::MODE_COMPARE
            ],
        ];
    }

    /**
     * @dataProvider fromDataSetProvider
     *
     * @covers \Tornado\Project\Chart\Generator\Tornado::fromDataSet
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
        $obj = new Tornado(new NameGenerator());
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
