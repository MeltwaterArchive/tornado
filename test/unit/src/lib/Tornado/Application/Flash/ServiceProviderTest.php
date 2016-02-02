<?php

namespace Test\Tornado\Application\Flash;

use \Mockery;

use \Tornado\Application\Flash\Message;
use \Tornado\Application\Flash\ServiceProvider;

use \DataSift\Http\Request;

use \Symfony\Component\HttpKernel\HttpKernelInterface;
use \Symfony\Component\HttpKernel\Event\GetResponseEvent;
use \Symfony\Component\HttpFoundation\ParameterBag;

use \Symfony\Component\HttpKernel\KernelEvents;
use \Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Test for Flash Messages
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Application\Flash
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Application\Flash\ServiceProvider
 */
class ServiceProviderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @covers ::setFlashToStore
     * @covers ::getFlashToStore
     */
    public function testGetSetFlashToStore()
    {
        $message = 'testMessage';
        $level = 'leveltest';

        $provider = new ServiceProvider();

        $this->assertNull($provider->getFlashToStore());

        $provider->setFlashToStore($message, $level);

        $flash = $provider->getFlashToStore();
        $this->assertInstanceOf('\Tornado\Application\Flash\Message', $flash);
        $this->assertEquals($message, $flash->getMessage());
        $this->assertEquals($level, $flash->getLevel());
    }

    /**
     * DataProvider for testOnKernelRequest
     *
     * @return array
     */
    public function onKernelRequestProvider()
    {
        return [
            'Not a main request' => [
                'event' => $this->getRequestEvent(
                    'type'
                ),
                'expectedFlash' => null
            ],
            'No cookie' => [
                'event' => $this->getRequestEvent(
                    HttpKernelInterface::MASTER_REQUEST,
                    ['test' => 'x']
                ),
                'expectedFlash' => null
            ],
            'Invalid cookie' => [
                'event' => $this->getRequestEvent(
                    HttpKernelInterface::MASTER_REQUEST,
                    [ServiceProvider::COOKIE_NAME => 'x']
                ),
                'expectedFlash' => null
            ],
            'Valid cookie' => [
                'event' => $this->getRequestEvent(
                    HttpKernelInterface::MASTER_REQUEST,
                    [ServiceProvider::COOKIE_NAME => 'level:message']
                ),
                'expectedFlash' => new Message('message', 'level')
            ]
        ];
    }

    /**
     * @dataProvider onKernelRequestProvider
     *
     * @covers ::onKernelRequest
     * @covers ::getFlash
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     * @param mixed $expectedFlash
     */
    public function testOnKernelRequest(GetResponseEvent $event, $expectedFlash)
    {
        $provider = new ServiceProvider();

        $provider->onKernelRequest($event);
        if ($expectedFlash) {
            $flash = $provider->getFlash();
            $this->assertInstanceOf('\Tornado\Application\Flash\Message', $flash);
            $this->assertEquals($expectedFlash->getMessage(), $flash->getMessage());
            $this->assertEquals($expectedFlash->getLevel(), $flash->getLevel());
        } else {
            $this->assertNull($provider->getFlash());
        }
    }

    /**
     * DataProvider for testOnKernelResponse
     *
     * @return array
     */
    public function onKernelResponseProvider()
    {
        return [
            'Not master request' => [
                'event' => $this->getFilterResponseEvent('test', 'test', false),
                'message' => false
            ],
            'No message' => [
                'event' => $this->getFilterResponseEvent(
                    HttpKernelInterface::MASTER_REQUEST
                ),
                'message' => ''
            ],
            'Message' => [
                'event' => $this->getFilterResponseEvent(
                    HttpKernelInterface::MASTER_REQUEST
                ),
                'message' => 'test',
                'level' => 'testLevel',
                'testLevel:test'
            ]
        ];
    }

    /**
     * @dataProvider onKernelResponseProvider
     *
     * @covers ::onKernelResponse
     *
     * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
     * @param string $message
     * @param string $level
     */
    public function testOnKernelResponse(FilterResponseEvent $event, $message, $level = '', $expected = null)
    {
        $provider = new ServiceProvider();

        if ($message) {
            $provider->setFlashToStore($message, $level);
        }

        $provider->onKernelResponse($event);
        $cookies = $event->getResponse()->headers->getCookies();
        $this->assertTrue(is_array($cookies));
        if ($message === false) {
            $this->assertEquals(0, count($cookies));
        } else {
            $this->assertEquals(1, count($cookies));
            $cookie = $cookies[0];
            $this->assertInstanceOf('\Symfony\Component\HttpFoundation\Cookie', $cookie);
            $this->assertEquals($expected, $cookie->getValue());
        }
    }

    /**
     * @covers ::boot
     */
    public function testBoot()
    {
        $provider = new ServiceProvider();
        $application = Mockery::mock('\Silex\Application[match]'); // Cheeky, but intentional...

        $dispatcher = Mockery::mock('\Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $dispatcher->shouldReceive('addListener')
            ->with(KernelEvents::REQUEST, [$provider, 'onKernelRequest'], 1);

        $dispatcher->shouldReceive('addListener')
            ->with(KernelEvents::RESPONSE, [$provider, 'onKernelResponse'], 1);

        $application['dispatcher'] = $dispatcher;

        $provider->boot($application);
    }

    /**
     * Gets a request event for the purposes of testing
     *
     * @param string $requestType
     *
     * @return \Symfony\Component\HttpKernel\Event\GetResponseEvent
     */
    private function getRequestEvent($requestType, array $cookies = [])
    {
        $cookieBag = new ParameterBag($cookies);
        $request = new Request();
        $request->cookies = $cookieBag;

        $event = Mockery::mock(
            '\Symfony\Component\HttpKernel\Event\GetResponseEvent',
            [
                'getRequestType' => $requestType,
                'getRequest' => $request
            ]
        );

        return $event;
    }

    /**
     * Gets a filter response event for the purposes of testing
     *
     * @param string $requestType
     *
     * @return \Symfony\Component\HttpKernel\Event\GetResponseEvent
     */
    private function getFilterResponseEvent($requestType)
    {

        $headers = new \Symfony\Component\HttpFoundation\ResponseHeaderBag();

        $response = Mockery::mock('\Symfony\Component\HttpFoundation\Response');
        $response->headers = $headers;

        $event = Mockery::mock(
            '\Symfony\Component\HttpKernel\Event\FilterResponseEvent',
            [
                'getRequestType' => $requestType,
                'getResponse' => $response
            ]
        );

        return $event;
    }
}
