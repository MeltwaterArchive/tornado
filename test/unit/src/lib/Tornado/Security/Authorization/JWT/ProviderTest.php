<?php

namespace Test\Tornado\Security\Authorization\JWT;

use Mockery;
use Tornado\Security\Authorization\JWT\Provider;

/**
 * ProviderTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Security\Authorization
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Security\Authorization\JWT\Provider
 */
class ProviderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::__construct
     * @covers ::offsetExists
     * @covers ::offsetGet
     */
    public function testArrayAccess()
    {
        $keyMapper = Mockery::mock('\Tornado\Security\Authorization\JWT\KeyDataMapper');
        $jwt = Mockery::mock('\Tornado\Security\Authorization\JWT\JWT');

        $key = 'My Key';
        $kid = 'Key Id';

        $provider = new Provider($keyMapper, $jwt);
        $keyMapper->shouldReceive('getJwtKey')
            ->with($kid)
            ->once()
            ->andReturn($key);

        $this->assertTrue($provider->offsetExists($kid));
        $this->assertEquals($key, $provider->offsetGet($kid));
        $this->assertEquals($key, $provider->offsetGet($kid)); // Testing the ->once() above
    }

    /**
     * DataProvider for testValidateToken
     *
     * @return array
     */
    public function validateTokenProvider()
    {
        return [
            'Everything is good' => [
                'token' => 'test.one',
                'payload' => (object)[
                    'test' => 'two',
                    'sub' => 'four'
                ],
                'header' => (object)[
                    'kid' => 'myiss'
                ],
                'expected' => (object)[
                    'test' => 'two',
                    'sub' => 'four',
                    'iss' => 'myiss'
                ]
            ],
            'No sub in header' => [
                'token' => 'test.one',
                'payload' => (object)[
                    'test' => 'two',
                    'whoa' => 'I know kung-fu'
                ],
                'header' => (object)[
                    'kid' => 'myiss'
                ],
                'expected' => (object)[
                    'test' => 'two',
                    'sub' => 'four',
                    'iss' => 'myiss'
                ],
                'expectedException' => '\Tornado\Security\Authorization\JWT\Exception'
            ]
        ];
    }

    /**
     * @dataProvider validateTokenProvider
     *
     * @covers ::validateToken
     *
     * @param string $token
     * @param stdClass $payload
     * @param stdClass $header
     * @param stdClass $expected
     * @param string|false $expectedException
     */
    public function testValidateToken($token, $payload, $header, $expected, $expectedException = false)
    {
        $keyMapper = Mockery::mock('\Tornado\Security\Authorization\JWT\KeyDataMapper');
        $jwt = Mockery::mock('\Tornado\Security\Authorization\JWT\JWT');

        $provider = new Provider($keyMapper, $jwt);

        $jwt->shouldReceive('decode')
            ->with($token, $provider)
            ->andReturn($payload);

        $jwt->shouldReceive('getHeader')
            ->with($token)
            ->andReturn($header);

        if ($expectedException) {
            $this->setExpectedException($expectedException);
        }

        $this->assertEquals($expected, $provider->validateToken($token));
    }
}
