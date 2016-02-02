<?php

namespace Test\Tornado\Project;

use Tornado\Project\Recording;
use DataSift_Pylon;

/**
 * RecordingTest
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
 * @coversDefaultClass      \Tornado\Project\Recording
 */
class RecordingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * DataProvider for testGetterSetter
     *
     * @return array
     */
    public function getterSetterProvider()
    {
        return [
            [
                'setter' => 'setId',
                'value' => 10,
                'getter' => 'getId',
                'expected' => 10
            ],
            [
                'setter' => 'setBrandId',
                'value' => 20,
                'getter' => 'getBrandId',
                'expected' => 20
            ],
            [
                'setter' => 'setProjectId',
                'value' => 200,
                'getter' => 'getProjectId',
                'expected' => 200
            ],
            [
                'setter' => 'setHash',
                'value' => 'csdlHash',
                'getter' => 'getHash',
                'expected' => 'csdlHash'
            ],
            [
                'setter' => 'setDatasiftRecordingId',
                'value' => 'csdlHash',
                'getter' => 'getDatasiftRecordingId',
                'expected' => 'csdlHash'
            ],
            [
                'setter' => 'setName',
                'value' => 'testName',
                'getter' => 'getName',
                'expected' => 'testName'
            ],
            [
                'setter' => 'setStatus',
                'value' => 'started',
                'getter' => 'getStatus',
                'expected' => 'started'
            ],
            [
                'setter' => 'setCsdl',
                'value' => 'csdl query',
                'getter' => 'getCsdl',
                'expected' => 'csdl query'
            ],
            [
                'setter' => 'setVqbGenerated',
                'value' => true,
                'getter' => 'isVqbGenerated',
                'expected' => true
            ],
            [
                'setter' => 'setVqbGenerated',
                'value' => false,
                'getter' => 'isVqbGenerated',
                'expected' => false
            ],
            [
                'setter' => 'setCreatedAt',
                'value' => 1435701600,
                'getter' => 'getCreatedAt',
                'expected' => 1435701600
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
     * @covers       ::getId
     * @covers       ::setId
     * @covers       ::getBrandId
     * @covers       ::setBrandId
     * @covers       ::getProjectId
     * @covers       ::setProjectId
     * @covers       ::getDatasiftRecordingId
     * @covers       ::setDatasiftRecordingId
     * @covers       ::getHash
     * @covers       ::setHash
     * @covers       ::getStatus
     * @covers       ::setStatus
     * @covers       ::setCsdl
     * @covers       ::getCsdl
     * @covers       ::setVqbGenerated
     * @covers       ::isVqbGenerated
     * @covers       ::getName
     * @covers       ::setName
     * @covers       ::setCreatedAt
     * @covers       ::getCreatedAt
     * @covers       ::getPrimaryKey
     * @covers       ::setPrimaryKey
     *
     * @param string $setter
     * @param mixed  $value
     * @param string $getter
     * @param mixed  $expected
     */
    public function testGetterSetter($setter, $value, $getter, $expected)
    {
        $obj = new Recording();
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
        return [
            [
                'data' => [
                    'id' => 10,
                    'brand_id' => 20,
                    'datasift_recording_id' => 'csdlHash',
                    'hash' => 'csdlHash',
                    'name' => 'newName',
                    'status' => 'started',
                    'csdl' => 'csdl query',
                    'vqb_generated' => true,
                    'created_at' => 1435701600
                ],
                'getters' => [
                    'getId' => 10,
                    'getBrandId' => 20,
                    'getDatasiftRecordingId' => 'csdlHash',
                    'getHash' => 'csdlHash',
                    'getName' => 'newName',
                    'getStatus' => 'started',
                    'getCsdl' => 'csdl query',
                    'isVqbGenerated' => true,
                    'getCreatedAt' => 1435701600
                ],
                'expected' => [
                    'id' => 10,
                    'brand_id' => 20,
                    'project_id' => null,
                    'datasift_recording_id' => 'csdlHash',
                    'hash' => 'csdlHash',
                    'name' => 'newName',
                    'status' => 'started',
                    'csdl' => 'csdl query',
                    'vqb_generated' => true,
                    'created_at' => 1435701600
                ],
            ],
            [
                'data' => [
                    'id' => 100,
                    'brand_id' => 2,
                    'project_id' => 200,
                    'datasift_recording_id' => 'csdlHash2',
                    'hash' => 'csdlHash2',
                    'name' => 'newName2',
                    'status' => 'stopped',
                    'csdl' => 'csdl query',
                    'vqb_generated' => true,
                    'created_at' => 1435701600
                ],
                'getters' => [
                    'getId' => 100,
                    'getBrandId' => 2,
                    'getProjectId' => 200,
                    'getDatasiftRecordingId' => 'csdlHash2',
                    'getHash' => 'csdlHash2',
                    'getName' => 'newName2',
                    'getStatus' => 'stopped',
                    'getCsdl' => 'csdl query',
                    'isVqbGenerated' => true,
                    'getCreatedAt' => 1435701600
                ],
                'expected' => [
                    'id' => 100,
                    'brand_id' => 2,
                    'project_id' => 200,
                    'datasift_recording_id' => 'csdlHash2',
                    'hash' => 'csdlHash2',
                    'name' => 'newName2',
                    'status' => 'stopped',
                    'csdl' => 'csdl query',
                    'vqb_generated' => true,
                    'created_at' => 1435701600
                ],
            ],
            [
                'data' => [
                    'id' => 100,
                    'brand_id' => 2,
                    'project_id' => null,
                    'datasift_recording_id' => 'csdlHash2',
                    'hash' => 'csdlHash2',
                    'name' => 'newName2',
                    'status' => 'started',
                    'csdl' => 'csdl query',
                    'vqb_generated' => true,
                    'created_at' => 1435701600
                ],
                'getters' => [
                    'getId' => 100,
                    'getBrandId' => 2,
                    'getProjectId' => null,
                    'getDatasiftRecordingId' => 'csdlHash2',
                    'getHash' => 'csdlHash2',
                    'getName' => 'newName2',
                    'getStatus' => 'started',
                    'getCsdl' => 'csdl query',
                    'isVqbGenerated' => true,
                    'getCreatedAt' => 1435701600
                ],
                'expected' => [
                    'id' => 100,
                    'brand_id' => 2,
                    'project_id' => null,
                    'datasift_recording_id' => 'csdlHash2',
                    'hash' => 'csdlHash2',
                    'name' => 'newName2',
                    'status' => 'started',
                    'csdl' => 'csdl query',
                    'vqb_generated' => true,
                    'created_at' => 1435701600
                ],
            ]
        ];
    }

    /**
     * @covers ::setStatus
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionUnlessValidStatusSet()
    {
        $obj = new Recording();
        $obj->setStatus('wrongString');
    }

    /**
     * @covers ::setHash
     * @covers ::getHash
     * @covers ::setDatasiftRecordingId
     * @covers ::getDatasiftRecordingId
     */
    public function testPopulateHashOrDatasiftRecordingId()
    {
        $obj = new Recording();

        $hash = 'hash';
        $obj->setHash($hash);
        $this->assertEquals($hash, $obj->getHash());
        $this->assertEquals($hash, $obj->getDatasiftRecordingId());

        $recordingId = 'recordingId';
        $obj->setDatasiftRecordingId($recordingId);
        $this->assertEquals($recordingId, $obj->getHash());
        $this->assertEquals($recordingId, $obj->getDatasiftRecordingId());
    }

    /**
     * @dataProvider toFromArrayProvider
     *
     * @covers       ::loadFromArray
     * @covers       ::toArray
     *
     * @param array $data
     * @param array $getters
     * @param array $expected
     */
    public function testToFromArray(array $data, array $getters, array $expected)
    {
        $obj = new Recording();
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
            $item['expected']['volume'] = 0;
            $item['expected'] = json_encode($item['expected']);
        }
        return $data;
    }

    /**
     * @dataProvider toJsonProvider
     *
     * @covers       \Tornado\Project\Recording::jsonSerialize
     *
     * @param array  $data
     * @param string $expected
     */
    public function testJsonSerialization(
        array $data,
        $expected
    ) {
        $obj = new Recording();
        $obj->loadFromArray($data);

        $this->assertEquals($expected, json_encode($obj));
    }

    /**
     * DataProvider for testFromDataSiftRecordingProvider
     *
     * @return array
     */
    public function fromDataSiftRecordingProvider()
    {
        return [
            'Plain hydration (stopped)' => [
                'pylon' => new DataSift_Pylon(
                    null,
                    [
                        'volume' => 10,
                        'status' => 'stopped'
                    ]
                ),
                'overrideExisting' => false,
                'getters' => [
                    'getVolume' => 10,
                    'getStatus' => Recording::STATUS_STOPPED,
                    'getName' => null
                ]
            ],
            'Plain hydration (running)' => [
                'pylon' => new DataSift_Pylon(
                    null,
                    [
                        'volume' => 10,
                        'status' => 'running'
                    ]
                ),
                'overrideExisting' => false,
                'getters' => [
                    'getVolume' => 10,
                    'getStatus' => Recording::STATUS_STARTED,
                    'getName' => null
                ]
            ],
            'Overriding existing' => [
                'pylon' => new DataSift_Pylon(
                    null,
                    [
                        'volume' => 10,
                        'status' => 'stopped',
                        'name' => 'Bob',
                        'start' => 123456
                    ]
                ),
                'overrideExisting' => true,
                'getters' => [
                    'getVolume' => 10,
                    'getStatus' => Recording::STATUS_STOPPED,
                    'getName' => 'Bob',
                    'getCreatedAt' => 123456
                ]
            ]
        ];
    }

    /**
     * @dataProvider fromDataSiftRecordingProvider
     *
     * @covers \Tornado\Project\Recording::fromDataSiftRecording
     *
     * @param \Test\Tornado\Project\DataSift_Pylon $pylon
     * @param boolean $overrideExisting
     * @param array $getters
     */
    public function testFromDataSiftRecording(DataSift_Pylon $pylon, $overrideExisting, array $getters)
    {
        $obj = new Recording();
        $obj->fromDataSiftRecording($pylon, $overrideExisting);
        foreach ($getters as $getter => $expected) {
            $this->assertEquals($expected, $obj->{$getter}());
        }
    }
}
