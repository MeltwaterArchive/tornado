<?php

namespace Test\Tornado\Organization;

use Tornado\Organization\Brand;

/**
 * @author Christopher Hoult <chris.hoult@datasift.com>
 * @covers \Tornado\Organization\Brand
 */
class BrandTest extends \PHPUnit_Framework_TestCase
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
                'setter' => 'setAgencyId',
                'value' => 20,
                'getter' => 'getAgencyId',
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
                'setter' => 'setDatasiftIdentityId',
                'value' => 'def456',
                'getter' => 'getDatasiftIdentityId',
                'expected' => 'def456'
            ],
            [
                'setter' => 'setDatasiftApiKey',
                'value' => 'abc123',
                'getter' => 'getDatasiftApiKey',
                'expected' => 'abc123'
            ],
            [
                'setter' => 'setTargetPermissions',
                'value' => ['everyone', 'internal'],
                'getter' => 'getTargetPermissions',
                'expected' => ['everyone', 'internal']
            ],
            [
                'setter' => 'setTargetPermissions',
                'value' => ['everyone', 'premium'],
                'getter' => 'getRawTargetPermissions',
                'expected' => 'everyone,premium'
            ],
            [
                'setter' => 'setRawTargetPermissions',
                'value' => 'everyone,premium',
                'getter' => 'getTargetPermissions',
                'expected' => ['everyone', 'premium']
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

        $obj = new Brand();
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
            'Underscored' => [
                'data' => [
                    'id' => 10,
                    'agency_id' => 20,
                    'name' => 'newName',
                    'datasift_username' => 'Override Username',
                    'datasift_identity_id' => 'def456',
                    'datasift_apikey' => 'abc123',
                    'target_permissions' => 'premium,internal'
                ],
                'getters' => [
                    'getId' => 10,
                    'getAgencyId' => 20,
                    'getName' => 'newName',
                    'getDatasiftUsername' => 'Override Username',
                    'getDatasiftIdentityId' => 'def456',
                    'getDatasiftApiKey' => 'abc123',
                    'getTargetPermissions' => ['premium', 'internal']
                ],
                'expected' => [
                    'id' => 10,
                    'agency_id' => 20,
                    'name' => 'newName',
                    'datasift_username' => 'Override Username',
                    'datasift_identity_id' => 'def456',
                    'datasift_apikey' => 'abc123',
                    'target_permissions' => 'premium,internal'
                ],
            ],
            'CamelCased' => [
                'data' => [
                    'id' => 10,
                    'agencyId' => 20,
                    'name' => 'newName',
                    'datasiftUsername' => 'Override Username',
                    'datasiftIdentityId' => 'def456',
                    'datasiftApikey' => 'abc123',
                    'targetPermissions' => 'premium,internal'
                ],
                'getters' => [
                    'getId' => 10,
                    'getAgencyId' => 20,
                    'getName' => 'newName',
                    'getDatasiftUsername' => 'Override Username',
                    'getDatasiftIdentityId' => 'def456',
                    'getDatasiftApiKey' => 'abc123',
                    'getTargetPermissions' => ['premium', 'internal']
                ],
                'expected' => [
                    'id' => 10,
                    'agency_id' => 20,
                    'name' => 'newName',
                    'datasift_username' => 'Override Username',
                    'datasift_identity_id' => 'def456',
                    'datasift_apikey' => 'abc123',
                    'target_permissions' => 'premium,internal'
                ],
            ]
        ];
    }

    /**
     * @dataProvider toFromArrayProvider
     *
     * @covers \Tornado\Organization\Brand::loadFromArray
     * @covers \Tornado\Organization\Brand::toArray
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
        $obj = new Brand();
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
            $item['expected']['target_permissions'] = explode(',', $item['expected']['target_permissions']);
            $item['expected'] = json_encode($item['expected']);
        }
        return $data;
    }

    /**
     * @dataProvider toJsonProvider
     *
     * @covers \Tornado\Organization\Brand::jsonSerialize
     *
     * @param array $data
     * @param string $expected
     */
    public function testJsonSerialization(
        array $data,
        $expected
    ) {
        $obj = new Brand();
        $obj->loadFromArray($data);

        $this->assertEquals($expected, json_encode($obj));
    }

    /**
     * @covers \Tornado\Organization\Brand::setTargetPermissions
     *
     * @expectedException \InvalidArgumentException
     */
    public function testSettingInvalidPermission()
    {
        $obj = new Brand();
        $obj->setTargetPermissions(['lipsum']);
    }
}
