<?php

namespace Test\Tornado\Project;

use Tornado\Project\Chart;

/**
 * ChartTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Project
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers      \Tornado\Project\Chart
 */
class ChartTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers \Tornado\Project\Chart::getPrimaryKeyName
     */
    public function testPrimaryKeyName()
    {
        $chart = new Chart();
        $this->assertEquals('id', $chart->getPrimaryKeyName());
    }

    /**
     * DataProvider for testGetterSetter
     *
     * @return array
     */
    public function getterSetterProvider()
    {
        $data = new \stdClass();
        $data->my_data = [0, 1, 2, 3, 4, 5];
        $otherData = new \stdClass();
        $data->other_Data = $otherData;
        $otherData->values = [234, 345345, 34, 86, 345];

        return [
            [
                'setter' => 'setId',
                'value' => 10,
                'getter' => 'getId',
                'expected' => 10
            ],
            [
                'setter' => 'setWorksheetId',
                'value' => 20,
                'getter' => 'getWorksheetId',
                'expected' => 20
            ],
            [
                'setter' => 'setName',
                'value' => 'testName',
                'getter' => 'getName',
                'expected' => 'testName'
            ],
            [
                'setter' => 'setRank',
                'value' => 1,
                'getter' => 'getRank',
                'expected' => 1
            ],
            [
                'setter' => 'setType',
                'value' => 'tornado',
                'getter' => 'getType',
                'expected' => 'tornado'
            ],
            [
                'setter' => 'setData',
                'value' => $data,
                'getter' => 'getData',
                'expected' => $data
            ],
            [
                'setter' => 'setPrimaryKey',
                'value' => 20,
                'getter' => 'getPrimaryKey',
                'expected' => 20
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
        $obj = new Chart();
        $obj->$setter($value);
        $this->assertEquals($expected, $obj->$getter());
    }

    /**
     * DataProvider for testToFromArray
     *
     * @return array
     */
    public function toFromArrayProvider()
    {
        $data = new \stdClass();
        $data->my_data = [0, 1, 2, 3, 4, 5];
        $otherData = new \stdClass();
        $data->other_Data = $otherData;
        $otherData->values = [234, 345345, 34, 86, 345];
        $encodedData = json_encode($data);

        return [
            [
                'data' => [
                    'id' => 10,
                    'worksheet_id' => 20,
                    'name' => 'newName',
                    'rank' => 1,
                    'type' => 'tornado',
                    'data' => $encodedData
                ],
                'getters' => [
                    'getId' => 10,
                    'getWorksheetId' => 20,
                    'getName' => 'newName',
                    'getRank' => 1,
                    'getType' => 'tornado'
                ],
                'expected' => [
                    'id' => 10,
                    'worksheet_id' => 20,
                    'name' => 'newName',
                    'rank' => 1,
                    'type' => 'tornado',
                    'data' => $encodedData
                ]
            ],
            [
                'data' => [
                    'id' => 20,
                    'worksheet_id' => 30,
                    'name' => 'newName#2',
                    'rank' => 2,
                    'type' => 'timeseries',
                    'data' => $encodedData
                ],
                'getters' => [
                    'getId' => 20,
                    'getWorksheetId' => 30,
                    'getName' => 'newName#2',
                    'getRank' => 2,
                    'getType' => 'timeseries'
                ],
                'expected' => [
                    'id' => 20,
                    'worksheet_id' => 30,
                    'name' => 'newName#2',
                    'rank' => 2,
                    'type' => 'timeseries',
                    'data' => $encodedData
                ]
            ]
        ];
    }

    /**
     * @dataProvider toFromArrayProvider
     *
     * @covers       \Tornado\Project\Chart::loadFromArray
     * @covers       \Tornado\Project\Chart::toArray
     *
     * @param array $data
     * @param array $getters
     * @param array $expected
     */
    public function testToFromArray(array $data, array $getters, array $expected)
    {
        $obj = new Chart();
        $obj->loadFromArray($data);

        foreach ($getters as $getter => $value) {
            $this->assertEquals($value, $obj->$getter());
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
            $item['expected'] = json_encode($item['expected']);
        }
        return $data;
    }

    /**
     * @dataProvider toJsonProvider
     *
     * @covers \Tornado\Project\Chart::jsonSerialize
     *
     * @param array $data
     * @param string $expected
     */
    public function testJsonSerialization(
        array $data,
        $expected
    ) {
        $obj = new Chart();
        $obj->loadFromArray($data);

        $this->assertEquals($expected, json_encode($obj));
    }

    /**
     * @dataProvider gettingDecodedDataProvider
     *
     * @covers       \Tornado\Project\Chart::setRawData
     * @covers       \Tornado\Project\Chart::getData
     *
     * @param  string $jsonData
     * @param  stdClass $expectedData
     */
    public function testGettingDecodedData($jsonData, $expectedData)
    {
        $obj = new Chart();
        $obj->setRawData($jsonData);

        $this->assertEquals($expectedData, $obj->getData());
    }

    public function gettingDecodedDataProvider()
    {
        $ret = [];

        for ($i = 1; $i <= 5; $i++) {
            $data = new \stdClass();
            $data->my_data = [234, 342, 23424, 23];
            $data->value = $i;
            $data->series = array_fill(0, $i, 'value-'. $i);

            $ret[] = [
                'jsonData' => json_encode($data),
                'expectedData' => $data
            ];
        }

        return $ret;
    }

    /**
     * DataProvider for testGetDataByMeasure
     *
     * @return array
     */
    public function getDataByMeasureProvider()
    {
        return [
            [ // #0
                'data' => [
                    Chart::MEASURE_INTERACTIONS => [
                        'dave' => 'jan',
                        'naomi' => 'chuck'
                    ],
                    Chart::MEASURE_UNIQUE_AUTHORS => [
                        'davina' => 'john',
                        'dean' => 'charlotte'
                    ],
                ],
                'measure' => Chart::MEASURE_INTERACTIONS,
                'expected' => [
                    'dave' => 'jan',
                    'naomi' => 'chuck',
                ]
            ],
            [ // #1
                'data' => [
                    Chart::MEASURE_INTERACTIONS => [
                        'dave' => 'jan',
                        'naomi' => 'chuck'
                    ],
                    Chart::MEASURE_UNIQUE_AUTHORS => [
                        'davina' => 'john',
                        'dean' => 'charlotte'
                    ],
                ],
                'measure' => 'not gonna happen',
                'expected' => null
            ],
        ];
    }

    /**
     * @dataProvider getDataByMeasureProvider
     *
     * @covers \Tornado\Project\Chart::getData
     *
     * @param stdClass $data
     * @param string $measure
     * @param mixed $expected
     */
    public function testGetDataByMeasure($data, $measure, $expected)
    {
        $obj = new Chart();
        $obj->setData($data);
        $this->assertEquals($expected, $obj->getData($measure));
    }
}
