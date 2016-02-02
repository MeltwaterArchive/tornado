<?php

namespace Test\Tornado\Analyze;

use Tornado\Analyze\Dimension\Collection as DimensionCollection;
use Tornado\Analyze\DataSet;
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
 * @covers      \Tornado\Analyze\DataSet
 */
class DataSetTest extends \PHPUnit_Framework_TestCase
{

    use FixtureLoader;

    /**
     * @covers \Tornado\Analyze\DataSet::__construct
     * @covers \Tornado\Analyze\DataSet::getDimensions
     * @covers \Tornado\Analyze\DataSet::getData
     */
    public function testBasic()
    {
        $dimensions = Mockery::mock('Tornado\Analyze\Dimension\Collection');
        $data = ["a" => "b"];

        $obj = new DataSet($dimensions, $data);
        $this->assertEquals($dimensions, $obj->getDimensions());
        $this->assertEquals($data, $obj->getData());
    }

    /**
     * DataProvider for testPivot
     *
     * @return array
     */
    public function pivotProvider()
    {
        return [
            [ // #0
                "dimensions" => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.age']
                ]),
                "input" => $this->loadDataSetFixture('dataset_pivot1'),
                "pivotTo" => $this->getDimensionCollection([
                    ['target' => 'fb.author.age'],
                    ['target' => 'fb.author.gender']
                ]),
                "allowSubset" => false,
                "expected" => $this->loadDataSetFixture('dataset_pivot1_age_gender'),
            ],
            [ // #1
                "dimensions" => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.age'],
                    ['target' => 'fb.region']
                ]),
                "input" => $this->loadDataSetFixture('dataset_pivot2'),
                "pivotTo" => $this->getDimensionCollection([
                    ['target' => 'fb.author.age'],
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.region']
                ]),
                "allowSubset" => false,
                "expected" => $this->loadDataSetFixture('dataset_pivot2_age_gender_region'),
            ],
            [ // #2
                "dimensions" => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.age']
                ]),
                "input" => $this->loadDataSetFixture('dataset_pivot1'),
                "pivotTo" => $this->getDimensionCollection([
                    ['target' => 'fb.author.age'],
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.region']
                ]),
                "allowSubset" => false,
                "expected" => array(),
                'expectedException' => 'RuntimeException'
            ],
            [ // #3
                "dimensions" => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.age']
                ]),
                "input" => $this->loadDataSetFixture('dataset_pivot1'),
                "pivotTo" => $this->getDimensionCollection([
                    ['target' => 'fb.author.age'],
                    ['target' => 'fb.author.region']
                ]),
                "allowSubset" => false,
                "expected" => array(),
                'expectedException' => 'Tornado\Analyze\DataSet\IncompatibleDimensionsException'
            ],
            [ // #4
                "dimensions" => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.age']
                ]),
                "input" => $this->loadDataSetFixture('dataset_pivot1'),
                "pivotTo" => $this->getDimensionCollection([
                    ['target' => 'fb.author.age'],
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.region']
                ]),
                "allowSubset" => true,
                "expected" => $this->loadDataSetFixture('dataset_pivot1_age_gender'),
            ],
        ];
    }

    /**
     * @dataProvider pivotProvider
     *
     * @covers \Tornado\Analyze\DataSet::pivot
     *
     * @param \Tornado\Analyze\Dimension\Collection $dimensions
     * @param array $input
     * @param \Tornado\Analyze\Dimension\Collection $pivotTo
     * @param boolean $allowSubset
     * @param array $expected
     * @param string $expectedException
     */
    public function testPivot(
        DimensionCollection $dimensions,
        array $input,
        DimensionCollection $pivotTo,
        $allowSubset,
        array $expected,
        $expectedException = false
    ) {
        $obj = new DataSet($dimensions, $input);
        if ($expectedException) {
            $this->setExpectedException($expectedException);
        }
        $newObj = $obj->pivot($pivotTo, $allowSubset);
        $this->assertEquals($expected, $newObj->getData());
    }

    /**
     * DataProvider for testGetSimple
     *
     * @return array
     */
    public function getSimpleProvider()
    {
        return [
            [ // #0
                "data" => [
                    DataSet::KEY_MEASURE_INTERACTIONS => [
                        DataSet::KEY_VALUE => 20,
                        DataSet::KEY_REDACTED => false,
                        DataSet::KEY_DIMENSION_PREFIX . 'gender' => [
                            'male' => [
                                DataSet::KEY_VALUE => 10,
                                DataSet::KEY_REDACTED => false,
                            ],
                            'female' => [
                                DataSet::KEY_VALUE => 10,
                                DataSet::KEY_REDACTED => false,
                            ]
                        ]
                    ]
                ],
                "mode" => DataSet::MEASURE_INTERACTIONS,
                "expected" => [
                    DataSet::KEY_DIMENSION_PREFIX . "gender:male" => 10,
                    DataSet::KEY_DIMENSION_PREFIX . "gender:female" => 10
                ]
            ],
            [ // #1
                "data" => [
                    DataSet::KEY_MEASURE_INTERACTIONS => [
                        DataSet::KEY_VALUE => 20,
                        DataSet::KEY_REDACTED => false,
                        DataSet::KEY_DIMENSION_PREFIX . 'gender' => [
                            'male' => [
                                DataSet::KEY_VALUE => 10,
                                DataSet::KEY_REDACTED => false,
                                DataSet::KEY_DIMENSION_PREFIX . 'age' => [
                                    "unknown" => [
                                        DataSet::KEY_VALUE => 3,
                                        DataSet::KEY_REDACTED => false,
                                    ],
                                    "18-24" => [
                                        DataSet::KEY_VALUE => 7,
                                        DataSet::KEY_REDACTED => false,
                                    ]
                                ]
                            ],
                            'female' => [
                                DataSet::KEY_VALUE => 10,
                                DataSet::KEY_REDACTED => false,
                                DataSet::KEY_DIMENSION_PREFIX . 'age' => [
                                    "unknown" => [
                                        DataSet::KEY_VALUE => 6,
                                        DataSet::KEY_REDACTED => false,
                                    ],
                                    "18-24" => [
                                        DataSet::KEY_VALUE => 4,
                                        DataSet::KEY_REDACTED => false,
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                "mode" => DataSet::MEASURE_INTERACTIONS,
                "expected" => [
                    DataSet::KEY_DIMENSION_PREFIX . "gender:male" => [
                        DataSet::KEY_DIMENSION_PREFIX . "age:unknown" => 3,
                        DataSet::KEY_DIMENSION_PREFIX . "age:18-24" => 7
                    ],
                    DataSet::KEY_DIMENSION_PREFIX . "gender:female" => [
                        DataSet::KEY_DIMENSION_PREFIX . "age:unknown" => 6,
                        DataSet::KEY_DIMENSION_PREFIX . "age:18-24" => 4
                    ]
                ]
            ],
            [ // #2
                "data" => [
                    DataSet::KEY_MEASURE_INTERACTIONS => [
                        DataSet::KEY_VALUE => 20,
                        DataSet::KEY_REDACTED => false,
                        DataSet::KEY_DIMENSION_PREFIX . 'gender' => [
                            'male' => [
                                DataSet::KEY_VALUE => 9,
                                DataSet::KEY_REDACTED => false,
                                DataSet::KEY_DIMENSION_PREFIX . 'age' => [
                                    "unknown" => [
                                        DataSet::KEY_VALUE => 3,
                                        DataSet::KEY_REDACTED => false,
                                        DataSet::KEY_DIMENSION_PREFIX . 'region' => [
                                            'alabama' => [
                                                DataSet::KEY_VALUE => 1,
                                                DataSet::KEY_REDACTED => false,
                                            ],
                                            'alaska' => [
                                                DataSet::KEY_VALUE => 2,
                                                DataSet::KEY_REDACTED => false,
                                            ]
                                        ]
                                    ],
                                    "18-24" => [
                                        DataSet::KEY_VALUE => 6,
                                        DataSet::KEY_REDACTED => false,
                                        DataSet::KEY_DIMENSION_PREFIX . 'region' => [
                                            'alabama' => [
                                                DataSet::KEY_VALUE => 4,
                                                DataSet::KEY_REDACTED => false,
                                            ],
                                            'alaska' => [
                                                DataSet::KEY_VALUE => 2,
                                                DataSet::KEY_REDACTED => false,
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            'female' => [
                                DataSet::KEY_VALUE => 7,
                                DataSet::KEY_REDACTED => false,
                                DataSet::KEY_DIMENSION_PREFIX . 'age' => [
                                    "unknown" => [
                                        DataSet::KEY_VALUE => 3,
                                        DataSet::KEY_REDACTED => false,
                                        DataSet::KEY_DIMENSION_PREFIX . 'region' => [
                                            'alabama' => [
                                                DataSet::KEY_VALUE => 1,
                                                DataSet::KEY_REDACTED => false,
                                            ],
                                            'alaska' => [
                                                DataSet::KEY_VALUE => 2,
                                                DataSet::KEY_REDACTED => false,
                                            ]
                                        ]
                                    ],
                                    "18-24" => [
                                        DataSet::KEY_VALUE => 4,
                                        DataSet::KEY_REDACTED => false,
                                        DataSet::KEY_DIMENSION_PREFIX . 'region' => [
                                            'alabama' => [
                                                DataSet::KEY_VALUE => 2,
                                                DataSet::KEY_REDACTED => false,
                                            ],
                                            'alaska' => [
                                                DataSet::KEY_VALUE => 2,
                                                DataSet::KEY_REDACTED => false,
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                "mode" => DataSet::MEASURE_INTERACTIONS,
                "expected" => [
                    DataSet::KEY_DIMENSION_PREFIX . "gender:male" => [
                        DataSet::KEY_DIMENSION_PREFIX . "age:unknown" => [
                            DataSet::KEY_DIMENSION_PREFIX . "region:alabama" => 1,
                            DataSet::KEY_DIMENSION_PREFIX . "region:alaska" => 2
                        ],
                        DataSet::KEY_DIMENSION_PREFIX . "age:18-24" => [
                            DataSet::KEY_DIMENSION_PREFIX . "region:alabama" => 4,
                            DataSet::KEY_DIMENSION_PREFIX . "region:alaska" => 2
                        ]
                    ],
                    DataSet::KEY_DIMENSION_PREFIX . "gender:female" => [
                        DataSet::KEY_DIMENSION_PREFIX . "age:unknown" => [
                            DataSet::KEY_DIMENSION_PREFIX . "region:alabama" => 1,
                            DataSet::KEY_DIMENSION_PREFIX . "region:alaska" => 2
                        ],
                        DataSet::KEY_DIMENSION_PREFIX . "age:18-24" => [
                            DataSet::KEY_DIMENSION_PREFIX . "region:alabama" => 2,
                            DataSet::KEY_DIMENSION_PREFIX . "region:alaska" => 2
                        ]
                    ]
                ]
            ],
            [ // #3
                "data" => [
                    DataSet::KEY_MEASURE_INTERACTIONS => [
                        DataSet::KEY_VALUE => 20,
                        DataSet::KEY_REDACTED => false,
                        DataSet::KEY_DIMENSION_PREFIX . 'gender' => [
                            'male' => [
                                DataSet::KEY_VALUE => 10,
                                DataSet::KEY_REDACTED => false,
                                DataSet::KEY_DIMENSION_PREFIX . 'age' => [
                                    "unknown" => [
                                        DataSet::KEY_VALUE => 3,
                                        DataSet::KEY_REDACTED => false,
                                    ],
                                    "18-24" => [
                                        DataSet::KEY_VALUE => 7,
                                        DataSet::KEY_REDACTED => false,
                                    ]
                                ]
                            ],
                            'female' => [
                                DataSet::KEY_VALUE => 10,
                                DataSet::KEY_REDACTED => false,
                                DataSet::KEY_DIMENSION_PREFIX . 'age' => [
                                    "unknown" => [
                                        DataSet::KEY_VALUE => 6,
                                        DataSet::KEY_REDACTED => false,
                                    ],
                                    "18-24" => [
                                        DataSet::KEY_VALUE => 4,
                                        DataSet::KEY_REDACTED => false,
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                "mode" => 'gibberish',
                "expected" => [],
                "expectedException" => '\InvalidArgumentException'
            ],
            [ // #4
                "data" => [
                    DataSet::KEY_MEASURE_INTERACTIONS => [
                        DataSet::KEY_VALUE => 20,
                        DataSet::KEY_REDACTED => false,
                        DataSet::KEY_DIMENSION_PREFIX . 'gender' => [
                            'male' => [
                                DataSet::KEY_VALUE => 10,
                                DataSet::KEY_REDACTED => false,
                                DataSet::KEY_DIMENSION_PREFIX . 'age' => [
                                    "unknown" => [
                                        DataSet::KEY_VALUE => 3,
                                        DataSet::KEY_REDACTED => false,
                                    ],
                                    "18-24" => [
                                        DataSet::KEY_VALUE => 7,
                                        DataSet::KEY_REDACTED => false,
                                    ]
                                ]
                            ],
                            'female' => [
                                DataSet::KEY_VALUE => 10,
                                DataSet::KEY_REDACTED => false,
                                DataSet::KEY_DIMENSION_PREFIX . 'age' => [
                                    "unknown" => [
                                        DataSet::KEY_VALUE => 6,
                                        DataSet::KEY_REDACTED => false,
                                    ],
                                    "18-24" => [
                                        DataSet::KEY_VALUE => 4,
                                        DataSet::KEY_REDACTED => false,
                                    ]
                                ]
                            ]
                        ]
                    ],
                    DataSet::KEY_MEASURE_UNIQUE_AUTHORS => [
                        DataSet::KEY_VALUE => 20,
                        DataSet::KEY_REDACTED => false,
                        DataSet::KEY_DIMENSION_PREFIX . 'gender' => [
                            'male' => [
                                DataSet::KEY_VALUE => 10,
                                DataSet::KEY_REDACTED => false,
                                DataSet::KEY_DIMENSION_PREFIX . 'age' => [
                                    "unknown" => [
                                        DataSet::KEY_VALUE => 13,
                                        DataSet::KEY_REDACTED => false,
                                    ],
                                    "18-24" => [
                                        DataSet::KEY_VALUE => 17,
                                        DataSet::KEY_REDACTED => false,
                                    ]
                                ]
                            ],
                            'female' => [
                                DataSet::KEY_VALUE => 10,
                                DataSet::KEY_REDACTED => false,
                                DataSet::KEY_DIMENSION_PREFIX . 'age' => [
                                    "unknown" => [
                                        DataSet::KEY_VALUE => 16,
                                        DataSet::KEY_REDACTED => false,
                                    ],
                                    "18-24" => [
                                        DataSet::KEY_VALUE => 14,
                                        DataSet::KEY_REDACTED => false,
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                "mode" => false,
                "expected" => [
                    DataSet::KEY_MEASURE_INTERACTIONS => [
                        DataSet::KEY_DIMENSION_PREFIX . "gender:male" => [
                            DataSet::KEY_DIMENSION_PREFIX . "age:unknown" => 3,
                            DataSet::KEY_DIMENSION_PREFIX . "age:18-24" => 7
                        ],
                        DataSet::KEY_DIMENSION_PREFIX . "gender:female" => [
                            DataSet::KEY_DIMENSION_PREFIX . "age:unknown" => 6,
                            DataSet::KEY_DIMENSION_PREFIX . "age:18-24" => 4
                        ]
                    ],
                    DataSet::KEY_MEASURE_UNIQUE_AUTHORS => [
                        DataSet::KEY_DIMENSION_PREFIX . "gender:male" => [
                            DataSet::KEY_DIMENSION_PREFIX . "age:unknown" => 13,
                            DataSet::KEY_DIMENSION_PREFIX . "age:18-24" => 17
                        ],
                        DataSet::KEY_DIMENSION_PREFIX . "gender:female" => [
                            DataSet::KEY_DIMENSION_PREFIX . "age:unknown" => 16,
                            DataSet::KEY_DIMENSION_PREFIX . "age:18-24" => 14
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider getSimpleProvider
     *
     * @covers \Tornado\Analyze\DataSet::getSimple
     *
     * @param array $data
     * @param string $mode
     * @param array $expected
     * @param string $expectedException
     */
    public function testGetSimple(array $data, $mode, array $expected, $expectedException = '')
    {
        $obj = new DataSet(new DimensionCollection(), $data);
        if ($expectedException) {
            $this->setExpectedException($expectedException);
        }
        $this->assertEquals($expected, $obj->getSimple($mode));
    }

    /**
     * DataProvider for testIsCompatible
     *
     * @return array
     */
    public function isCompatibleProvider()
    {
        return [
            [ // #0
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.age']
                ]),
                'dataset' => new DataSet(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.gender'],
                        ['target' => 'fb.author.age']
                    ]),
                    []
                ),
                'permissive' => false,
                'expected' => true
            ],
            [ // #1
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'fb.author.age'],
                    ['target' => 'fb.author.gender']
                ]),
                'dataset' => new DataSet(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.gender'],
                        ['target' => 'fb.author.age']
                    ]),
                    []
                ),
                'permissive' => false,
                'expected' => true
            ],
            [ // #2
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.age'],
                ]),
                'dataset' => new DataSet(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.gender'],
                        ['target' => 'fb.author.age'],
                        ['target' => 'fb.author.region']
                    ]),
                    []
                ),
                'permissive' => true,
                'expected' => true
            ],
            [ // #3
                'dimensions' => $this->getDimensionCollection([
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.age'],
                    ['target' => 'fb.author.region']
                ]),
                'dataset' => new DataSet(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.gender'],
                        ['target' => 'fb.author.age']
                    ]),
                    []
                ),
                'permissive' => false,
                'expected' => false
            ],
            [ // #4
                'dimensions' => $this->getDimensionCollection(
                    [
                        ['target' => 'fb.author.gender'],
                        ['target' => 'fb.author.age'],
                    ]
                ),
                'dataset' => new DataSet(
                    $this->getDimensionCollection([
                        ['target' => 'fb.author.gender'],
                        ['target' => 'fb.author.region']
                    ]),
                    []
                ),
                'permissive' => false,
                'expected' => false
            ],
        ];
    }

    /**
     * @dataProvider isCompatibleProvider
     *
     * @covers \Tornado\Analyze\DataSet::isCompatible
     *
     * @param \Tornado\Analyze\Dimension\Collection $dimensions
     * @param \Tornado\Analyze\DataSet $dataset
     * @param boolean $permissive
     * @param boolean $expected
     */
    public function testIsCompatible(DimensionCollection $dimensions, DataSet $dataset, $permissive, $expected)
    {
        $object = new DataSet($dimensions, []);
        $this->assertEquals($expected, $object->isCompatible($dataset, $permissive));
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
