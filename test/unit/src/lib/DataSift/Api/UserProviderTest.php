<?php

namespace Test\DataSift\Api;

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
}
