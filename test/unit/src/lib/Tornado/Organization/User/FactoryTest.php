<?php

namespace Test\Tornado\Organization\User;

use \Mockery;

use Tornado\Organization\User;
use Tornado\Organization\User\Factory;

use Test\DataSift\ReflectionAccess;

/**
 * FactoryTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Organization\User
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Organization\User\Factory
 */
class FactoryTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess;

    /**
     * @covers ::create
     */
    public function testCreateUser()
    {
        $userFactory = new Factory();
        $email = 'test@email.com';
        $username = 'test';
        $organizationId = 1;
        $user = $userFactory->create([
            'email' => $email,
            'password' => 'abc',
            'username' => $username,
            'organizationId' => $organizationId,
            'noExistingProperty' => 'bla',
            'type' => User::TYPE_NORMAL
        ]);

        $this->assertInstanceOf('\Tornado\Organization\User', $user);

        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($username, $user->getUsername());
        $this->assertTrue(password_verify('abc', $user->getPassword()));

        $this->assertNotEquals('abc', $user->getPassword());
        $this->assertEquals(
            [
                'id' => null,
                'organization_id' => $organizationId,
                'email' => $email,
                'username' => $username,
                'password' => $user->getPassword(),
                'type' => User::TYPE_NORMAL
            ],
            $user->toArray()
        );
    }

    /**
     * @covers ::create
     */
    public function testCreateUniquePassword()
    {
        $userFactory = new Factory();
        $email = 'test@email.com';
        $username = 'test';

        $user = $userFactory->create([
            'email' => $email,
            'password' => 'abc',
            'username' => $username
        ]);
        $passwordHash = $user->getPassword();

        $user = $userFactory->create([
            'email' => $email,
            'password' => 'abc',
            'username' => $username
        ]);
        $this->assertNotEquals($passwordHash, $user->getPassword());
    }

    /**
     * @covers ::update
     */
    public function testUpdateUser()
    {
        $userFactory = new Factory();
        $existingUser = new User();
        $existingUser->setId(1);
        $existingUser->setEmail('test2@email.com');
        $existingUser->setUsername('test2');

        $email = 'test@email.com';
        $username = 'test';
        $organizationId = 1;
        $user = $userFactory->update(
            $existingUser,
            [
                'email' => $email,
                'password' => 'abc',
                'username' => $username,
                'organizationId' => $organizationId,
                'noExistingProperty' => 'bla',
                'type' => User::TYPE_NORMAL
            ]
        );

        $this->assertInstanceOf('\Tornado\Organization\User', $user);

        $this->assertEquals(1, $user->getId());
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($username, $user->getUsername());
        $this->assertTrue(password_verify('abc', $user->getPassword()));

        $this->assertNotEquals('abc', $user->getPassword());
        $this->assertEquals(
            [
                'id' => 1,
                'organization_id' => $organizationId,
                'email' => $email,
                'username' => $username,
                'password' => $user->getPassword(),
                'type' => User::TYPE_NORMAL
            ],
            $user->toArray()
        );
    }

    /**
     * @covers ::setData
     */
    public function testSetData()
    {
        $userFactory = new Factory();

        $email = 'test@email.com';
        $username = 'test';
        $organizationId = 1;

        $user = $this->invokeMethod(
            $userFactory,
            'setData',
            [
                new User(),
                [
                    'email' => $email,
                    'password' => 'abc',
                    'username' => $username,
                    'organizationId' => $organizationId,
                    'noExistingProperty' => 'bla',
                    'type' => User::TYPE_IDENTITY_API
                ]
            ]
        );

        $this->assertInstanceOf('\Tornado\Organization\User', $user);
        $this->assertEquals(
            [
                'id' => null,
                'organization_id' => $organizationId,
                'email' => $email,
                'username' => $username,
                'password' => $user->getPassword(),
                'type' => User::TYPE_IDENTITY_API
            ],
            $user->toArray()
        );
    }
}
