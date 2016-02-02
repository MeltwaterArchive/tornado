<?php

namespace Test\DataSift\Api;

use Symfony\Component\HttpFoundation\JsonResponse;

use DataSift\Api\User;

use Mockery;

/**
 * UserTest
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
 * @coversDefaultClass \DataSift\Api\User
 */
class UserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Dummy response.
     *
     * @var array
     */
    private $response = [
        'body' => [
            'hobbits' => ['Frodo', 'Sam', 'Merry', 'Pippin'],
            'dwarves' => ['Gimli'],
            'elves' => ['Legolas'],
            'men' => ['Aragorn', 'Boromir'],
            'istari' => ['Gandalf']
        ],
        'status' => 200,
        'headers' => [
            'X-Ring-Bearer' => 'Frodo'
        ]
    ];

    /**
     * @covers ::setDebug
     */
    public function testDebug()
    {
        $user = new User('test', 'test');
        $user->setDebug(true);
        $this->assertTrue($user->getDebug());
        $user->setDebug(false);
        $this->assertFalse($user->getDebug());
    }

    /**
     * @covers ::proxyLastResponse
     */
    public function testProxyLastResponse()
    {
        $user = new User('test', 'test');

        $user->setDebug(true);
        $user->setLastResponse($this->response);

        $proxiedResponse = $user->proxyLastResponse();

        $this->assertInstanceOf(JsonResponse::class, $proxiedResponse);
        $this->assertEquals(200, $proxiedResponse->getStatusCode());
        $this->assertEquals(json_encode($this->response['body']), $proxiedResponse->getContent());
        $this->assertEquals('Frodo', $proxiedResponse->headers->get('X-Ring-Bearer'));
    }

    /**
     * @covers ::proxyLastResponse
     *
     * @expectedException \RuntimeException
     */
    public function testProxyLastResponseWhenNone()
    {
        $user = new User('test', 'test');

        $user->setDebug(true);
        $user->proxyLastResponse();
    }

    public function proxyResponseProvider()
    {
        return [
            'Happy path' => [
                'response' => new JsonResponse(
                    'body',
                    200,
                    ['X-Ring-Bearer' => 'Frodo']
                ),
                'expectedContent' => 'body',
                'expectedCode' => 200,
                'expectedHeaders' => ['X-Ring-Bearer' => 'Frodo']
            ],
            'NEV-438 - invalid data exception' => [
                'response' => new JsonResponse(
                    'body',
                    200,
                    ['X-Ring-Bearer' => 'Frodo']
                ),
                'expectedContent' => ['error' => 'page not found.'],
                'expectedCode' => 400,
                'expectedHeaders' => [],
                'proxyException' => new \DataSift_Exception_InvalidData('page not found.')
            ]
        ];
    }

    /**
     * @dataProvider proxyResponseProvider
     * @covers ::proxyResponse
     *
     * @param JSONResponse $response
     * @param mixed $expectedContent
     * @param integer $expectedCode
     * @param array $expectedHeaders
     * @param \Exception|null $proxyException
     */
    public function testProxyResponse(
        $response,
        $expectedContent,
        $expectedCode,
        array $expectedHeaders,
        $proxyException = null
    ) {
        //$user = new User('test', 'test');
        $user = Mockery::mock('DataSift\Api\User[proxyLastResponse]', ['test', 'test']);
        $user->shouldReceive('proxyLastResponse')
            ->andReturn($response);

        // double check that debug is off
        $user->setDebug(false);

        $proxiedResponse = $user->proxyResponse(function () use ($proxyException) {
            if ($proxyException) {
                throw $proxyException;
            }
        });

        // debug should still be off
        $this->assertFalse($user->getDebug());

        $this->assertInstanceOf(JsonResponse::class, $proxiedResponse);
        $this->assertEquals($expectedCode, $proxiedResponse->getStatusCode());
        $this->assertEquals(json_encode($expectedContent), $proxiedResponse->getContent());
        foreach ($expectedHeaders as $header => $value) {
            $this->assertEquals($value, $proxiedResponse->headers->get($header));
        }
    }
}
