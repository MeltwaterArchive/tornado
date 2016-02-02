<?php

namespace Test\Tornado\Project;

use Tornado\Project\Project;

/**
 * ProjectTest
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
 * @covers      \Tornado\Project\Project
 */
class ProjectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionUnlessValidType()
    {
        $obj = new Project();
        $obj->setType(10);
    }

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
                'setter' => 'setName',
                'value' => 'testName',
                'getter' => 'getName',
                'expected' => 'testName'
            ],
            [
                'setter' => 'setType',
                'value' => 0,
                'getter' => 'getType',
                'expected' => 0
            ],
            [
                'setter' => 'setRecordingFilter',
                'value' => null,
                'getter' => 'getRecordingFilter',
                'expected' => null
            ],
            [
                'setter' => 'setFresh',
                'value' => 1,
                'getter' => 'getFresh',
                'expected' => 1
            ],
            [
                'setter' => 'setFresh',
                'value' => 1,
                'getter' => 'isFresh',
                'expected' => true
            ],
            [
                'setter' => 'setFresh',
                'value' => 0,
                'getter' => 'isFresh',
                'expected' => false
            ],
            [
                'setter' => 'setPrimaryKey',
                'value' => 20,
                'getter' => 'getPrimaryKey',
                'expected' => 20
            ],
            [
                'setter' => 'setCreatedAt',
                'value' => 1435701600,
                'getter' => 'getCreatedAt',
                'expected' => 1435701600
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
        $obj = new Project();
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
                    'name' => 'newName',
                    'created_at' => 1435701600
                ],
                'getters' => [
                    'getId' => 10,
                    'getBrandId' => 20,
                    'getName' => 'newName',
                    'getCreatedAt' => 1435701600
                ],
                'expected' => [
                    'id' => 10,
                    'brand_id' => 20,
                    'name' => 'newName',
                    'type' => 0,
                    'recording_filter' => null,
                    'fresh' => 1,
                    'created_at' => 1435701600
                ],
            ],
            [
                'data' => [
                    'id' => 20,
                    'brand_id' => 30,
                    'name' => 'newName#2',
                    'type' => 1,
                    'recording_filter' => 1,
                    'fresh' => 0,
                    'created_at' => 1435701600
                ],
                'getters' => [
                    'getId' => 20,
                    'getBrandId' => 30,
                    'getName' => 'newName#2',
                    'getCreatedAt' => 1435701600,
                    'getFresh' => 0
                ],
                'expected' => [
                    'id' => 20,
                    'brand_id' => 30,
                    'name' => 'newName#2',
                    'type' => 1,
                    'recording_filter' => 1,
                    'fresh' => 0,
                    'created_at' => 1435701600
                ],
            ]
        ];
    }

    /**
     * @dataProvider toFromArrayProvider
     *
     * @covers       \Tornado\Project\Project::loadFromArray
     * @covers       \Tornado\Project\Project::toArray
     *
     * @param array $data
     * @param array $getters
     * @param array $expected
     */
    public function testToFromArray(array $data, array $getters, array $expected)
    {
        $obj = new Project();
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
     * @covers \Tornado\Project\Project::jsonSerialize
     *
     * @param array $data
     * @param string $expected
     */
    public function testJsonSerialization(
        array $data,
        $expected
    ) {
        $obj = new Project();
        $obj->loadFromArray($data);

        $this->assertEquals($expected, json_encode($obj));
    }
}
