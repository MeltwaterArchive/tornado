<?php

namespace Test\Tornado\Project\Recording;

use \Mockery;

use Tornado\Project\Recording\Sample;

/**
 * SampleTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Project\Recording
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Project\Recording\Sample
 */
class SampleTest extends \PHPUnit_Framework_TestCase
{

    /**
     * DataProvider for testGetterSetters
     *
     * @return array
     */
    public function getterSettersProvider()
    {
        return [
            [
                'setter' => 'setId',
                'value' => 10,
                'getter' => 'getId',
                'expected' => 10
            ],
            [
                'setter' => 'setId',
                'value' => 15,
                'getter' => 'getPrimaryKey',
                'expected' => 15
            ],
            [
                'setter' => 'setPrimaryKey',
                'value' => 17,
                'getter' => 'getId',
                'expected' => 17
            ],
            [
                'setter' => 'setPrimaryKey',
                'value' => 17,
                'getter' => 'getPrimaryKey',
                'expected' => 17
            ],
            [
                'setter' => 'setRecordingId',
                'value' => 20,
                'getter' => 'getRecordingId',
                'expected' => 20
            ],
            [
                'setter' => 'setRecordingId',
                'value' => 20,
                'getter' => 'getRecordingId',
                'expected' => 20
            ],
            [
                'setter' => 'setRawData',
                'value' => '{"test":"value"}',
                'getter' => 'getRawData',
                'expected' => '{"test":"value"}'
            ],
            [
                'setter' => 'setData',
                'value' => json_decode('{"test":"value"}'),
                'getter' => 'getRawData',
                'expected' => '{"test":"value"}'
            ],
            [
                'setter' => 'setData',
                'value' => json_decode('{"test":"value"}'),
                'getter' => 'getData',
                'expected' => json_decode('{"test":"value"}')
            ],
            [
                'setter' => 'setRawData',
                'value' => '{"test":"value"}',
                'getter' => 'getData',
                'expected' => json_decode('{"test":"value"}')
            ],
            [
                'setter' => 'setCreatedAt',
                'value' => 123456789,
                'getter' => 'getCreatedAt',
                'expected' => 123456789
            ]
        ];
    }

    /**
     * @dataProvider getterSettersProvider
     *
     * @param string $setter
     * @param mixed $value
     * @param string $getter
     * @param mixed $expected
     */
    public function testGetterSetters($setter, $value, $getter, $expected)
    {
        $sample = new Sample();
        $sample->{$setter}($value);
        $this->assertEquals($expected, $sample->{$getter}());
    }

    /**
     * DataProvider for testArrayMethods
     *
     * @return array
     */
    public function arrayMethodsProvider()
    {
        return [
            [
                'from' => [
                    'id' => 10,
                    'recording_id' => 20,
                    'data' => '{"a":"b"}',
                    'created_at' => 123456789
                ],
                'getters' => [
                    'getId' => 10,
                    'getRecordingId' => 20,
                    'getData' => json_decode('{"a":"b"}'),
                    'getCreatedAt' => 123456789,
                ],
                'to' => [
                    'id' => 10,
                    'recording_id' => 20,
                    'data' => '{"a":"b"}',
                    'created_at' => 123456789,
                    'filter_hash' => null
                ],
                'json' => json_encode([
                    'id' => 10,
                    'recording_id' => 20,
                    'filter_hash' => null,
                    'data' => json_decode('{"a":"b"}'),
                    'created_at' => 123456789
                ]),
            ]
        ];
    }

    /**
     * @dataProvider arrayMethodsProvider
     *
     * @covers ::loadFromArray
     * @covers ::toArray
     * @covers ::jsonSerialize
     *
     * @param array $from
     * @param array $getters
     * @param array $to
     * @param string $json
     */
    public function testArrayMethods(array $from, array $getters, array $to, $json)
    {
        $sample = new Sample();
        $sample->loadFromArray($from);
        foreach ($getters as $getter => $expected) {
            $this->assertEquals($expected, $sample->{$getter}());
        }

        $this->assertEquals($to, $sample->toArray());
        $this->assertEquals($json, json_encode($sample));
    }
}
