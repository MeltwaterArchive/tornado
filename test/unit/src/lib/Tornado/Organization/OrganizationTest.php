<?php

namespace Test\Tornado\Organization;

use Tornado\Organization\Organization;

/**
 * @author Christopher Hoult <chris.hoult@datasift.com>
 * @coversDefaultClass \Tornado\Organization\Organization
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
            [
                'setter' => 'setPermissions',
                'value' => 'a,b',
                'getter' => 'getPermissions',
                'expected' => 'a,b'
            ],
            [
                'setter' => 'setPermissions',
                'value' => ['a', 'b'],
                'getter' => 'getPermissions',
                'expected' => 'a,b'
            ],
            [
                'setter' => 'setAccountLimit',
                'value' => 50,
                'getter' => 'getAccountLimit',
                'expected' => 50
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
                    'jwt_secret' => 'tested',
                    'permissions' => 'a,b',
                    'account_limit' => 5,
                ],
                'getters' => [
                    'getId' => 10,
                    'getName' => 'newName',
                    'getSkin' => 'test',
                    'getJwtSecret' => 'tested',
                    'getPermissions' => 'a,b',
                    'getAccountLimit' => 5
                ],
                'expected' => [
                    'id' => 10,
                    'name' => 'newName',
                    'skin' => 'test',
                    'jwt_secret' => 'tested',
                    'account_limit' => 5,
                    'permissions' => 'a,b'
                ],
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
     * @covers ::jsonSerialize
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

    /**
     * DataProvider for testHasPermission
     *
     * @return array
     */
    public function hasPermissionProvider()
    {
        return [
            'happy' => [
                'permissions' => ['a', 'b'],
                'permission' => 'a',
                'expected' => true
            ],
            'unhappy' => [
                'permissions' => ['a', 'b'],
                'permission' => 'c',
                'expected' => false
            ],
            'null' => [
                'permissions' => null,
                'permission' => 'c',
                'expected' => false
            ]
        ];
    }

    /**
     * @dataProvider hasPermissionProvider
     *
     * @covers ::hasPermission
     *
     * @param mixed $permissions
     * @param string $permission
     * @param boolean $expected
     */
    public function testHasPermission($permissions, $permission, $expected)
    {
        $obj = new Organization();
        $obj->setPermissions($permissions);
        $this->assertEquals($expected, $obj->hasPermission($permission));
    }

    /**
     * DataProvider for testHasReachedAccountLimit
     *
     * @return array
     */
    public function hasReachedAccountLimitProvider()
    {
        return [
            'happy' => [
                'accountLimit' => 50,
                'currentUserCount' => 10,
                'expected' => false
            ],
            'unhappy' => [
                'accountLimit' => 50,
                'currentUserCount' => 60,
                'expected' => true
            ],
            'null' => [
                'accountLimit' => null,
                'currentUserCount' => 60,
                'expected' => false
            ],
            'zero' => [
                'accountLimit' => 0,
                'currentUserCount' => 60,
                'expected' => false
            ]
        ];
    }

    /**
     * @dataProvider hasReachedAccountLimitProvider
     *
     * @covers ::hasReachedAccountLimit
     *
     * @param int $accountLimit
     * @param string $currentUserCount
     * @param boolean $expected
     */
    public function testHasReachedAccountLimit($accountLimit, $currentUserCount, $expected)
    {
        $obj = new Organization();
        $obj->setAccountLimit($accountLimit);
        $this->assertEquals($expected, $obj->hasReachedAccountLimit($currentUserCount));
    }
}
