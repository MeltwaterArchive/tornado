<?php

namespace Test\Tornado\Organization;

use Tornado\Organization\Agency;

/**
 * @author Christopher Hoult <chris.hoult@datasift.com>
 * @covers \Tornado\Organization\Agency
 */
class AgencyTest extends \PHPUnit_Framework_TestCase
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
                'setter' => 'setOrganizationId',
                'value' => 20,
                'getter' => 'getOrganizationId',
                'expected' => 20
            ],
            [
                'setter' => 'setName',
                'value' => 'testName',
                'getter' => 'getName',
                'expected' => 'testName'
            ],
            [
                'setter' => 'setPrimaryKey',
                'value' => 20,
                'getter' => 'getPrimaryKey',
                'expected' => 20
            ],
            [
                'setter' => 'setDatasiftUsername',
                'value' => 'datasift',
                'getter' => 'getDatasiftUsername',
                'expected' => 'datasift'
            ],
            [
                'setter' => 'setDatasiftApiKey',
                'value' => 'abc123',
                'getter' => 'getDatasiftApiKey',
                'expected' => 'abc123'
            ],
            [
                'setter' => 'setSkin',
                'value' => 'zipline',
                'getter' => 'getSkin',
                'expected' => 'zipline'
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

        $obj = new Agency();
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
            'Underscores' => [
                'data' => [
                    'id' => 10,
                    'organization_id' => 20,
                    'name' => 'newName',
                    'datasift_username' => 'datasift',
                    'datasift_apikey' => 'abc123',
                    'skin' => 'zipline'
                ],
                'getters' => [
                    'getId' => 10,
                    'getOrganizationId' => 20,
                    'getName' => 'newName',
                    'getDatasiftUsername' => 'datasift',
                    'getDatasiftApiKey' => 'abc123',
                    'getSkin' => 'zipline'
                ],
                'expected' => [
                    'id' => 10,
                    'organization_id' => 20,
                    'name' => 'newName',
                    'datasift_username' => 'datasift',
                    'datasift_apikey' => 'abc123',
                    'skin' => 'zipline'
                ],
            ],
            'Test no underscores' => [
                'data' => [
                    'id' => 10,
                    'organizationId' => 20,
                    'name' => 'newName',
                    'datasiftUsername' => 'datasift',
                    'datasiftApikey' => 'abc123',
                    'skin' => 'zipline'
                ],
                'getters' => [
                    'getId' => 10,
                    'getOrganizationId' => 20,
                    'getName' => 'newName',
                    'getDatasiftUsername' => 'datasift',
                    'getDatasiftApiKey' => 'abc123',
                    'getSkin' => 'zipline'
                ],
                'expected' => [
                    'id' => 10,
                    'organization_id' => 20,
                    'name' => 'newName',
                    'datasift_username' => 'datasift',
                    'datasift_apikey' => 'abc123',
                    'skin' => 'zipline'
                ],
            ]
        ];
    }

    /**
     * @dataProvider toFromArrayProvider
     *
     * @covers \Tornado\Organization\Agency::loadFromArray
     * @covers \Tornado\Organization\Agency::toArray
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
        $obj = new Agency();
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
     * @covers \Tornado\Organization\Agency::jsonSerialize
     *
     * @param array $data
     * @param string $expected
     */
    public function testJsonSerialization(
        array $data,
        $expected
    ) {
        $obj = new Agency();
        $obj->loadFromArray($data);

        $this->assertEquals($expected, json_encode($obj));
    }
}
