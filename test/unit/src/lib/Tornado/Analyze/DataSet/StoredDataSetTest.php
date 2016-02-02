<?php

namespace Test\Tornado\Analyze\DataSet;

use Tornado\Analyze\DataSet\StoredDataSet;
use Tornado\Analyze\DataSet;
use Tornado\Analyze\Dimension\Collection as DimensionCollection;
use Tornado\Analyze\Dimension;

/**
 * StoredDataSetTest
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
 * @coversDefaultClass      \Tornado\Analyze\DataSet\StoredDataSet
 */
class StoredDataSetTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::__construct
     * @covers ::getPrimaryKeyName
     */
    public function testPrimaryKeyName()
    {
        $dataset = new StoredDataSet();
        $this->assertEquals('id', $dataset->getPrimaryKeyName());
    }

    /**
     * DataProvider for testGetterSetter
     *
     * @return array
     */
    public function getterSetterProvider()
    {
        $data = [
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
        ];
        $jsonData = json_encode($data);

        $dimensionsArray = ['fb.author.gender', 'fb.author.age'];
        $dimensionsCsv = 'fb.author.gender,fb.author.age';
        $dimensions = new DimensionCollection();
        $dimensions->addDimension(new Dimension('fb.author.gender'));
        $dimensions->addDimension(new Dimension('fb.author.age'));

        return [
            [
                'setter' => 'setId',
                'value' => 10,
                'getter' => 'getId',
                'expected' => 10
            ],
            [
                'setter' => 'setPrimaryKey',
                'value' => 10,
                'getter' => 'getPrimaryKey',
                'expected' => 10
            ],
            [
                'setter' => 'setName',
                'value' => 'Facebook UK',
                'getter' => 'getName',
                'expected' => 'Facebook UK'
            ],
            [
                'setter' => 'setVisibility',
                'value' => StoredDataSet::VISIBILITY_PRIVATE,
                'getter' => 'getVisibility',
                'expected' => StoredDataSet::VISIBILITY_PRIVATE
            ],
            [
                'setter' => 'setDimensions',
                'value' => $dimensions,
                'getter' => 'getDimensions',
                'expected' => $dimensions
            ],
            [
                'setter' => 'setDimensions',
                'value' => $dimensions,
                'getter' => 'getRawDimensions',
                'expected' => $dimensionsCsv
            ],
            [
                'setter' => 'setDimensions',
                'value' => $dimensionsArray,
                'getter' => 'getRawDimensions',
                'expected' => $dimensionsCsv
            ],
            [
                'setter' => 'setDimensions',
                'value' => $dimensionsCsv,
                'getter' => 'getRawDimensions',
                'expected' => $dimensionsCsv
            ],
            [
                'setter' => 'setDimensions',
                'value' => $dimensionsArray,
                'getter' => 'getDimensions',
                'expected' => $dimensions
            ],
            [
                'setter' => 'setDimensions',
                'value' => $dimensionsCsv,
                'getter' => 'getDimensions',
                'expected' => $dimensions
            ],
            [
                'setter' => 'setData',
                'value' => $data,
                'getter' => 'getData',
                'expected' => $data
            ],
            [
                'setter' => 'setData',
                'value' => $jsonData,
                'getter' => 'getData',
                'expected' => $data
            ],
            [
                'setter' => 'setData',
                'value' => $data,
                'getter' => 'getRawData',
                'expected' => $jsonData
            ]
        ];
    }

    /**
     * @dataProvider getterSetterProvider
     *
     * @param string $setter
     * @param mixed  $value
     * @param string $getter
     * @param mixed  $expected
     */
    public function testGetterSetter($setter, $value, $getter, $expected)
    {
        $obj = new StoredDataSet();
        $obj->$setter($value);
        $this->assertEquals($expected, $obj->{$getter}());
    }

    /**
     * @expectedException \InvalidArgumentException
     *
     * @covers ::setDimensions
     */
    public function testSetDimensionInvalid()
    {
        $obj = new StoredDataSet();
        $obj->setDimensions(24);
    }

    /**
     * DataProvider for testToFromArray
     *
     * @return array
     */
    public function toFromArrayProvider()
    {
        $data = [
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
        ];
        $jsonData = json_encode($data);

        $dimensions = new DimensionCollection();
        $dimensions->addDimension(new Dimension('fb.author.gender'));
        $dimensions->addDimension(new Dimension('fb.author.age'));

        return [
            [
                'data' => [
                    'id' => 10,
                    'name' => 'Facebook US',
                    'dimensions' => 'fb.author.gender,fb.author.age',
                    'visibility' => 'private',
                    'data' => $jsonData
                ],
                'getters' => [
                    'getId' => 10,
                    'getName' => 'Facebook US',
                    'getDimensions' => $dimensions,
                    'getVisibility' => StoredDataSet::VISIBILITY_PRIVATE,
                    'getData' => $data
                ],
                'expected' => [
                    'id' => 10,
                    'name' => 'Facebook US',
                    'dimensions' => 'fb.author.gender,fb.author.age',
                    'visibility' => 'private',
                    'data' => $jsonData
                ]
            ],
            [
                'data' => [
                    'id' => 10,
                    'name' => 'Facebook US',
                    'dimensions' => ['fb.author.gender', 'fb.author.age'],
                    'visibility' => 'private',
                    'data' => $jsonData
                ],
                'getters' => [
                    'getId' => 10,
                    'getName' => 'Facebook US',
                    'getDimensions' => $dimensions,
                    'getVisibility' => StoredDataSet::VISIBILITY_PRIVATE,
                    'getData' => $data
                ],
                'expected' => [
                    'id' => 10,
                    'name' => 'Facebook US',
                    'dimensions' => 'fb.author.gender,fb.author.age',
                    'visibility' => 'private',
                    'data' => $jsonData
                ]
            ]
        ];
    }

    /**
     * @dataProvider toFromArrayProvider
     *
     * @covers ::loadFromArray
     * @covers ::toArray
     *
     * @param array $data
     * @param array $getters
     * @param array $expected
     */
    public function testToFromArray(array $data, array $getters, array $expected)
    {
        $obj = new StoredDataSet();
        $obj->loadFromArray($data);

        foreach ($getters as $getter => $value) {
            $this->assertEquals($value, $obj->{$getter}());
        }

        $this->assertEquals($expected, $obj->toArray());
    }

    /**
     * Data provider for testJsonSerialization
     *
     * @return array
     */
    public function toJsonProvider()
    {
        $data = $this->toFromArrayProvider();
        foreach ($data as &$item) {
            unset($item['getters']);
            $item['expected']['dimensions'] = explode(',', $item['expected']['dimensions']);
            unset($item['expected']['data']); // = json_decode($item['expected']['data']);
            $item['expected'] = json_encode($item['expected']);
        }
        return $data;
    }

    /**
     * @dataProvider toJsonProvider
     *
     * @covers ::jsonSerialize
     *
     * @param array $data
     * @param string $expected
     */
    public function testJsonSerialization(
        array $data,
        $expected
    ) {
        $obj = new StoredDataSet();
        $obj->loadFromArray($data);

        $this->assertEquals($expected, json_encode($obj));
    }
}
