<?php

namespace Test\Tornado\Organization;

use Tornado\Organization\User;
use Tornado\Organization\Role;

/**
 * UserTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Organization
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass      \Tornado\Organization\User
 */
class UserTest extends \PHPUnit_Framework_TestCase
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
                'setter' => 'setEmail',
                'value' => 'test@email.com',
                'getter' => 'getEmail',
                'expected' => 'test@email.com'
            ],
            [
                'setter' => 'setPrimaryKey',
                'value' => 20,
                'getter' => 'getPrimaryKey',
                'expected' => 20
            ],
            [
                'setter' => null,
                'value' => null,
                'getter' => 'getPrimaryKeyName',
                'expected' => 'id'
            ],
            [
                'setter' => 'setPassword',
                'value' => 'plainPassword',
                'getter' => 'getPassword',
                'expected' => 'plainPassword'
            ],
            [
                'setter' => 'setUsername',
                'value' => 'datasift',
                'getter' => 'getUsername',
                'expected' => 'datasift'
            ],
            [
                'setter' => 'setEmail',
                'value' => 'test@datasift.com',
                'getter' => 'getImage',
                'expected' => '//www.gravatar.com/avatar/' . md5('test@datasift.com')
            ],
            [
                'setter' => 'setType',
                'value' => User::TYPE_NORMAL,
                'getter' => 'getType',
                'expected' => User::TYPE_NORMAL
            ],
        ];
    }

    /**
     * @dataProvider getterSetterProvider
     *
     * @covers  ::setId
     * @covers  ::getId
     * @covers  ::setPrimaryKey
     * @covers  ::getPrimaryKey
     * @covers  ::getPrimaryKeyName
     * @covers  ::setOrganizationId
     * @covers  ::getOrganizationId
     * @covers  ::setEmail
     * @covers  ::getEmail
     * @covers  ::setPassword
     * @covers  ::getPassword
     * @covers  ::setUsername
     * @covers  ::getUsername
     * @covers  ::getImage
     * @covers  ::setType
     * @covers  ::getType
     *
     * @param string $setter
     * @param mixed  $value
     * @param string $getter
     * @param mixed  $expected
     */
    public function testGetterSetter($setter, $value, $getter, $expected)
    {

        $obj = new User();

        if ($setter) {
            $obj->{$setter}($value);
        }

        $this->assertEquals($expected, $obj->{$getter}());
    }

    /**
     * DataProvider for testHasRole
     *
     * @return array
     */
    public function hasRoleProvider()
    {
        $roleA = new Role();
        $roleA->setName('rolea');

        $roleB = new Role();
        $roleB->setName('rolea');

        return [
            'Has role' => [
                'user' => $this->getUserWithRoles([$roleA, $roleB]),
                'role' => $roleB,
                'expected' => true
            ],
            'Has role (string)' => [
                'user' => $this->getUserWithRoles([$roleA, $roleB]),
                'role' => 'roleA',
                'expected' => true
            ],
            'Does not have role' => [
                'user' => $this->getUserWithRoles([$roleA, $roleB]),
                'role' => 'roleC',
                'expected' => false
            ],
            'SPA only short-circuit' => [
                'user' => $this->getUserWithRoles([$roleA, $roleB], User::TYPE_IDENTITY_API),
                'role' => 'role_spaonly',
                'expected' => true
            ],
            'Double-check SPA only short-circuit' => [
                'user' => $this->getUserWithRoles([$roleA, $roleB], User::TYPE_NORMAL),
                'role' => 'role_spaonly',
                'expected' => false
            ]
        ];
    }

    /**
     * @dataProvider hasRoleProvider
     *
     * @covers ::hasRole
     *
     * @param \Tornado\Organization\User $user
     * @param mixed $role
     * @param boolean $expected
     */
    public function testHasRole(User $user, $role, $expected)
    {
        $this->assertEquals($expected, $user->hasRole($role));
    }

    /**
     * DataProvider for testIsAdmin
     *
     * @return array
     */
    public function isAdminProvider()
    {
        return [
            'Is admin' => [
                'roles' => [
                    Role::ROLE_ADMIN,
                    'somethingelse'
                ],
                'expected' => true
            ],
            'Is superadmin' => [
                'roles' => [
                    Role::ROLE_SUPERADMIN,
                    'somethingelse'
                ],
                'expected' => false
            ],
            'Is both' => [
                'roles' => [
                    Role::ROLE_ADMIN,
                    Role::ROLE_SUPERADMIN,
                    'somethingelse'
                ],
                'expected' => true
            ]
        ];
    }

    /**
     * @dataProvider isAdminProvider
     *
     * @covers ::isAdmin
     *
     * @param array $roles
     * @param boolean $expected
     */
    public function testIsAdmin(array $roles, $expected)
    {
        $user = new User();
        foreach ($roles as $roleName) {
            $role = new Role();
            $role->setName(strtolower($roleName));
            $user->addRole($role);
        }

        $this->assertEquals($expected, $user->isAdmin());
    }

    /**
     * DataProvider for testIsAdmin
     *
     * @return array
     */
    public function isSuperAdminProvider()
    {
        return [
            'Is admin' => [
                'roles' => [
                    Role::ROLE_ADMIN,
                    'somethingelse'
                ],
                'expected' => false
            ],
            'Is superadmin' => [
                'roles' => [
                    Role::ROLE_SUPERADMIN,
                    'somethingelse'
                ],
                'expected' => true
            ],
            'Is both' => [
                'roles' => [
                    Role::ROLE_ADMIN,
                    Role::ROLE_SUPERADMIN,
                    'somethingelse'
                ],
                'expected' => true
            ]
        ];
    }

    /**
     * @dataProvider isSuperAdminProvider
     *
     * @covers ::isSuperAdmin
     *
     * @param array $roles
     * @param boolean $expected
     */
    public function testIsSuperAdmin(array $roles, $expected)
    {
        $user = new User();
        foreach ($roles as $roleName) {
            $role = new Role();
            $role->setName(strtolower($roleName));
            $user->addRole($role);
        }

        $this->assertEquals($expected, $user->isSuperAdmin());
    }

    /**
     * DataProvider for testIsAdmin
     *
     * @return array
     */
    public function isSpaOnlyProvider()
    {
        return [
            'Is SPA only' => [
                'roles' => [
                    Role::ROLE_SPAONLY,
                    'somethingelse'
                ],
                'expected' => true
            ],
            'Is superadmin' => [
                'roles' => [
                    Role::ROLE_SUPERADMIN,
                    'somethingelse'
                ],
                'expected' => false
            ],
            'Is both' => [
                'roles' => [
                    Role::ROLE_SPAONLY,
                    Role::ROLE_SUPERADMIN,
                    'somethingelse'
                ],
                'expected' => true
            ]
        ];
    }

    /**
     * @dataProvider isSpaOnlyProvider
     *
     * @covers ::isSpaOnly
     *
     * @param array $roles
     * @param boolean $expected
     */
    public function testIsSpaOnly(array $roles, $expected)
    {
        $user = new User();
        foreach ($roles as $roleName) {
            $role = new Role();
            $role->setName(strtolower($roleName));
            $user->addRole($role);
        }

        $this->assertEquals($expected, $user->isSpaOnly());
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
                    'organization_id' => 20,
                    'email' => 'test@email.com',
                    'password' => 'plainPassword',
                    'username' => 'newName',
                    'type' => User::TYPE_IDENTITY_API
                ],
                'getters' => [
                    'getId' => 10,
                    'getOrganizationId' => 20,
                    'getEmail' => 'test@email.com',
                    'getPassword' => 'plainPassword',
                    'getUsername' => 'newName',
                    'getType' => User::TYPE_IDENTITY_API
                ],
                'expected' => [
                    'id' => 10,
                    'organization_id' => 20,
                    'email' => 'test@email.com',
                    'password' => 'plainPassword',
                    'username' => 'newName',
                    'type' => User::TYPE_IDENTITY_API
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
        $obj = new User();
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
            unset($item['expected']['password']);
            $item['getters']['getImage'] = '//www.gravatar.com/avatar/' . md5($item['data']['email']);
            $item['expected']['image'] = $item['getters']['getImage'];
            $item['expected'] = json_encode($item['expected']);
        }
        return $data;
    }

    /**
     * @dataProvider toJsonProvider
     *
     * @covers       ::jsonSerialize
     *
     * @param array  $data
     * @param string $expected
     */
    public function testJsonSerialization(array $data, $expected)
    {
        $obj = new User();
        $obj->loadFromArray($data);

        $this->assertEquals($expected, json_encode($obj));
    }

    /**
     * Gets a User with a list of Roles for testing
     *
     * @param array $roles
     * @param integer $type
     *
     * @return \Tornado\Organization\User
     */
    private function getUserWithRoles(array $roles, $type = User::TYPE_NORMAL)
    {
        $user = new User();
        $user->setRoles($roles);
        $user->setType($type);
        return $user;
    }
}
