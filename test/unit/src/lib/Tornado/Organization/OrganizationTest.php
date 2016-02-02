<?php

namespace Test\Tornado\Organization;

use Tornado\Organization\Organization;

/**
 * @author Christopher Hoult <chris.hoult@datasift.com>
 * @covers \Tornado\Organization\Organization
 */
class OrganizationTest extends \PHPUnit_Framework_TestCase
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
                'setter' => 'setName',
                'value' => 'testName',
                'getter' => 'getName',
                'expected' => 'testName'
            ],
            [
                'setter' => 'setSkin',
                'value' => 'testSkin',
                'getter' => 'getSkin',
                'expected' => 'testSkin'
            ],
            [
                'setter' => 'setJwtSecret',
                'value' => 'testSecret',
                'getter' => 'getJwtSecret',
                'expected' => 'testSecret'
            ],
            [
                'setter' => 'setPrimaryKey',
                'value' => 20,
                'getter' => 'getPrimaryKey',
                'expected' => 20
            ],
        ];
    }

    /**
     * @dataProvider getterSetterProvider
     *
     * @param string $setter
     * @param mixed $value
     * @param string $getter
     * @param mixed $expected
     */
    public function testGetterSetter($setter, $value, $getter, $expected)
    {

        $obj = new Organization();
        $obj->{$setter}($value);
        $this->assertEquals($expected, $obj->{$getter}());
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
                    'name' => 'newName',
                    'skin' => 'test',
                    'jwt_secret' => 'tested'
                ],
                'getters' => [
                    'getId' => 10,
                    'getName' => 'newName',
                    'getSkin' => 'test',
                    'getJwtSecret' => 'tested'
                ],
                'expected' => [
                    'id' => 10,
                    'name' => 'newName',
                    'skin' => 'test',
                    'jwt_secret' => 'tested'
                ],
            ]
        ];
    }

    /**
     * @dataProvider toFromArrayProvider
     *
     * @covers \Tornado\Organization\Organization::loadFromArray
     * @covers \Tornado\Organization\Organization::toArray
     *
     * @param array $data
     * @param array $getters
     * @param array $expected
     */
    public function testToFromArray(
        array $data,
        array $getters,
        array $expected
    ) {
        $obj = new Organization();
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
            $item['expected'] = json_encode($item['expected']);
        }
        return $data;
    }

    /**
     * @dataProvider toJsonProvider
     *
     * @covers \Tornado\Organization\Organization::jsonSerialize
     *
     * @param array $data
     * @param string $expected
     */
    public function testJsonSerialization(
        array $data,
        $expected
    ) {
        $obj = new Organization();
        $obj->loadFromArray($data);

        $this->assertEquals($expected, json_encode($obj));
    }
}
