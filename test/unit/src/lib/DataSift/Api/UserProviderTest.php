<?php

namespace Test\DataSift\Api;

use DataSift\Pylon\Pylon;
use Mockery;

use DataSift\Api\UserProvider;

/**
 * UserProviderTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\DataSift\Api
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \DataSift\Api\UserProvider
 */
class UserProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function setterGetterProvider()
    {
        return [
            [
                'setter' => 'setUsername',
                'value' => 'test',
                'getter' => 'getUsername'
            ],
            [
                'setter' => 'setApiKey',
                'value' => 'test',
                'getter' => 'getApiKey'
            ],
            [
                'setter' => 'setApiVersion',
                'value' => '1.2',
                'getter' => 'getApiVersion'
            ],
            [
                'setter' => 'setApiUrl',
                'value' => 'http://localhost:50000',
                'getter' => 'getApiUrl'
            ],
            [
                'setter' => 'setApiSsl',
                'value' => false,
                'getter' => 'getApiSsl'
            ]
        ];
    }

    /**
     * @dataProvider setterGetterProvider
     *
     * @covers ::__construct
     * @covers ::setUsername
     * @covers ::getUsername
     * @covers ::setApiKey
     * @covers ::getApiKey
     * @covers ::setApiVersion
     * @covers ::getApiVersion
     */
    public function testGetterSetter($setter, $value, $getter)
    {
        $userProvider = new UserProvider();
        $userProvider->$setter($value);

        $this->assertEquals($value, $userProvider->$getter());
    }

    /**
     * @covers ::__construct
     * @covers ::getInstance
     */
    public function testGetInstance()
    {
        $userProvider = new UserProvider();
        $userProvider->setUsername('test');
        $userProvider->setApiKey('hash');

        $dsUser = $userProvider->getInstance();
        $this->assertInstanceOf('\DataSift_User', $dsUser);
        $this->assertEquals('test', $dsUser->getUsername());
        $this->assertEquals('hash', $dsUser->getAPIKey());
        $this->assertEquals('v1.2', $dsUser->getApiVersion());
    }

    /**
     * @covers ::__construct
     * @covers ::getInstance
     *
     * @expectedException \DataSift_Exception_InvalidData
     */
    public function testGetInstanceUnlessValidCredentialsGiven()
    {
        $userProvider = new UserProvider();
        $userProvider->getInstance();
    }

    /**
     * @covers ::__construct
     * @covers ::setUsername
     * @covers ::getUsername
     * @covers ::setApiKey
     * @covers ::getApiKey
     * @covers ::setApiVersion
     * @covers ::getApiVersion
     *
     * @expectedException \DataSift_Exception_InvalidData
     */
    public function testThrowExceptionUnlessSupportedApiVersionGiven()
    {
        $userProvider = new UserProvider();
        $userProvider->setUsername('test');
        $userProvider->setApiKey('hash');
        $userProvider->setApiVersion('2.0');
    }

    /**
     *
     * @expectedException \DataSift_Exception_AccessDenied
     */
    public function testValidateCredentialsEmpty()
    {
        $userProvider = new UserProvider();
        $userProvider->validateCredentials();
    }

    /**
     * @covers ::validateCredentials
     * @expectedException \DataSift_Exception_AccessDenied
     */
    public function testValidateCredentialsUnhappyPath()
    {
        $dataSiftClient = Mockery::mock(\DataSift_User::class);
        $dataSiftClient->shouldReceive('getUsage')
            ->once()
            ->andThrow(\DataSift_Exception_AccessDenied::class);

        /** @var UserProvider $userProvider */
        $userProvider = Mockery::mock(UserProvider::class)->makePartial();
        $userProvider->shouldReceive('getInstance')
            ->once()
            ->andReturn($dataSiftClient);

        $userProvider->setUsername('test');
        $userProvider->setApiKey('hash');

        $userProvider->validateCredentials();
    }

    /**
     * @covers ::validateCredentials
     */
    public function testValidateCredentialsHappyPath()
    {
        $dataSiftClient = Mockery::mock(\DataSift_User::class);
        $dataSiftClient->shouldReceive('getUsage')
            ->once();

        /** @var UserProvider $userProvider */
        $userProvider = Mockery::mock(UserProvider::class)->makePartial();
        $userProvider->shouldReceive('getInstance')
            ->once()
            ->andReturn($dataSiftClient);

        $userProvider->setUsername('test');
        $userProvider->setApiKey('hash');

        $this->assertTrue($userProvider->validateCredentials());
    }

    /**
     * @covers ::identityHasPremiumPermissions
     */
    public function testIdentityHasPremiumPermissionsHappy()
    {
        $pylonClientMock = Mockery::mock(Pylon::class);
        $pylonClientMock->shouldReceive('getIdentity')
            ->andReturn(['api_key' => md5('test_api')]);
        $pylonClientMock->shouldReceive('validate');
        $userProvider = new UserProvider();
        $userProvider->setUsername('test');
        $userProvider->setApiKey('hash');
        $hasPermissions = $userProvider->identityHasPremiumPermissions(
            $pylonClientMock
        );

        $this->assertTrue($hasPermissions);
    }

    /**
     * @covers ::identityHasPremiumPermissions
     */
    public function testIdentityHasPremiumPermissionsHappyNoPremium()
    {
        $pylonClientMock = Mockery::mock(Pylon::class);
        $pylonClientMock->shouldReceive('getIdentity')
            ->andReturn(['api_key' => md5('test_api')]);
        $pylonClientMock->shouldReceive('validate')
            ->withAnyArgs()
            ->once()
            ->andThrow(\DataSift_Exception_InvalidData::class);
        $userProvider = new UserProvider();
        $userProvider->setUsername('test');
        $userProvider->setApiKey('hash');

        $hasPermissions = $userProvider->identityHasPremiumPermissions(
            $pylonClientMock
        );

        $this->assertFalse($hasPermissions);
    }

    /**
     * DataProvider for testIdentityExists
     *
     * @return array
     */
    public function identityExistsProvider()
    {
        return [
            'Happy path' => [
                'identityId' => 'abc123abc123abc123abc123abc123ab',
                'exists' => true
            ],
            'Sad path' => [
                'identityId' => 'bbc123abc123abc123abc123abc123ab',
                'exists' => false,
                'expectedException' => 'DataSift_Exception_APIError'
            ]
        ];
    }

    /**
     * @dataProvider identityExistsProvider
     *
     * @covers ::identityExists
     *
     * @param string $identityId
     * @param boolean $exists
     * @param string $expectedException
     */
    public function testIdentityExists($identityId, $exists, $expectedException = null)
    {
        $pylonClientMock = Mockery::mock(Pylon::class);
        $should = $pylonClientMock->shouldReceive('getIdentity')
            ->with($identityId);
        $this->setExpectedException($expectedException);
        if ($exists) {
            $should->andReturn(['test' => 'value']);
        } else {
            $should->andThrow(new \DataSift_Exception_APIError('Identity not found'));
        }

        $userProvider = new UserProvider();
        $userProvider->setUsername('test');
        $userProvider->setApiKey('hash');
        $this->assertTrue($userProvider->identityExists($identityId, $pylonClientMock));
    }
}
