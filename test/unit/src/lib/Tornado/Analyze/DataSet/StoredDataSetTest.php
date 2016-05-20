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
            ],
            [
                'setter' => 'setBrandId',
                'value' => 10,
                'getter' => 'getBrandId',
                'expected' => 10
            ],
            [
                'setter' => 'setRecordingId',
                'value' => 20,
                'getter' => 'getRecordingId',
                'expected' => 20
            ],
            [
                'setter' => 'setAnalysisType',
                'value' => 'freq',
                'getter' => 'getAnalysisType',
                'expected' => 'freq'
            ],
            [
                'setter' => 'setFilter',
                'value' => 'interaction.content exists',
                'getter' => 'getFilter',
                'expected' => 'interaction.content exists'
            ],
            [
                'setter' => 'setSchedule',
                'value' => 5,
                'getter' => 'getSchedule',
                'expected' => 5
            ],
            [
                'setter' => 'setScheduleUnits',
                'value' => 'day',
                'getter' => 'getScheduleUnits',
                'expected' => 'day'
            ],
            [
                'setter' => 'setTimeRange',
                'value' => 'last week',
                'getter' => 'getTimeRange',
                'expected' => 'last week'
            ],
            [
                'setter' => 'setLastRefreshed',
                'value' => 123456789,
                'getter' => 'getLastRefreshed',
                'expected' => 123456789
            ],
            [
                'setter' => 'setCreatedAt',
                'value' => 123456789,
                'getter' => 'getCreatedAt',
                'expected' => 123456789
            ],
            [
                'setter' => 'setUpdatedAt',
                'value' => 123456789,
                'getter' => 'getUpdatedAt',
                'expected' => 123456789
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
                    'brand_id' => 20,
                    'recording_id' => 30,
                    'name' => 'Facebook US',
                    'dimensions' => 'fb.author.gender,fb.author.age',
                    'visibility' => 'private',
                    'data' => $jsonData,
                    'analysis_type' => 'freq',
                    'filter' => 'interaction.content exists',
                    'schedule' => 5,
                    'schedule_units' => 'days',
                    'time_range' => 'last week',
                    'status' => 'running',
                    'last_refreshed' => 123456789,
                    'created_at' => 223456789,
                    'updated_at' => 323456789,
                ],
                'getters' => [
                    'getId' => 10,
                    'getBrandId' => 20,
                    'getRecordingId' => 30,
                    'getName' => 'Facebook US',
                    'getDimensions' => $dimensions,
                    'getVisibility' => StoredDataSet::VISIBILITY_PRIVATE,
                    'getData' => $data,
                    'getAnalysisType' => 'freq',
                    'getFilter' => 'interaction.content exists',
                    'getSchedule' => 5,
                    'getScheduleUnits' => 'days',
                    'getTimeRange' => 'last week',
                    'getStatus' => 'running',
                    'getLastRefreshed' => 123456789,
                    'getCreatedAt' => 223456789,
                    'getUpdatedAt' => 323456789,
                ],
                'expected' => [
                    'id' => 10,
                    'brand_id' => 20,
                    'recording_id' => 30,
                    'name' => 'Facebook US',
                    'dimensions' => 'fb.author.gender,fb.author.age',
                    'visibility' => 'private',
                    'data' => $jsonData,
                    'analysis_type' => 'freq',
                    'filter' => 'interaction.content exists',
                    'schedule' => 5,
                    'schedule_units' => 'days',
                    'time_range' => 'last week',
                    'status' => 'running',
                    'last_refreshed' => 123456789,
                    'created_at' => 223456789,
                    'updated_at' => 323456789,
                ]
            ],
            [
                'data' => [
                    'id' => 10,
                    'brand_id' => 20,
                    'recording_id' => 30,
                    'name' => 'Facebook US',
                    'dimensions' => ['fb.author.gender', 'fb.author.age'],
                    'visibility' => 'private',
                    'data' => $jsonData,
                    'analysis_type' => 'freq',
                    'filter' => 'interaction.content exists',
                    'schedule' => 5,
                    'schedule_units' => 'days',
                    'time_range' => 'last week',
                    'status' => 'stopped',
                    'last_refreshed' => 123456789,
                    'created_at' => 223456789,
                    'updated_at' => 323456789,
                ],
                'getters' => [
                    'getId' => 10,
                    'getBrandId' => 20,
                    'getRecordingId' => 30,
                    'getName' => 'Facebook US',
                    'getDimensions' => $dimensions,
                    'getVisibility' => StoredDataSet::VISIBILITY_PRIVATE,
                    'getData' => $data,
                    'getAnalysisType' => 'freq',
                    'getFilter' => 'interaction.content exists',
                    'getSchedule' => 5,
                    'getScheduleUnits' => 'days',
                    'getTimeRange' => 'last week',
                    'getStatus' => 'stopped',
                    'getLastRefreshed' => 123456789,
                    'getCreatedAt' => 223456789,
                    'getUpdatedAt' => 323456789,
                ],
                'expected' => [
                    'id' => 10,
                    'brand_id' => 20,
                    'recording_id' => 30,
                    'name' => 'Facebook US',
                    'dimensions' => 'fb.author.gender,fb.author.age',
                    'visibility' => 'private',
                    'data' => $jsonData,
                    'analysis_type' => 'freq',
                    'filter' => 'interaction.content exists',
                    'schedule' => 5,
                    'schedule_units' => 'days',
                    'time_range' => 'last week',
                    'status' => 'stopped',
                    'last_refreshed' => 123456789,
                    'created_at' => 223456789,
                    'updated_at' => 323456789,
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

    /**
     * DataProvider for testStartEnd
     *
     * @return array
     */
    public function startEndProvider()
    {
        return [
            'day' => [
                'timeRange' => StoredDataSet::TIMERANGE_DAY,
                'expectedDiff' => 86400,
            ],
            'week' => [
                'timeRange' => StoredDataSet::TIMERANGE_WEEK,
                'expectedDiff' => 86400 * 7,
            ],
            'month' => [
                'timeRange' => StoredDataSet::TIMERANGE_MONTH,
                'expectedDiff' => 86400 * 31,
            ]
        ];
    }

    /**
     * @dataProvider startEndProvider
     *
     * @covers ::getStart
     * @covers ::getEnd
     *
     * @param string $timeRange
     * @param integer $expectedDiff
     */
    public function testStartEnd($timeRange, $expectedDiff)
    {
        $obj = new StoredDataSet();
        $obj->setTimeRange($timeRange);
        $start = $obj->getStart();
        $end = $obj->getEnd();
        $this->assertTrue($start < $end);
        $this->assertEquals($expectedDiff, $end - $start);
    }
}
