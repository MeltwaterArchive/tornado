<?php

namespace Test\Tornado\Security\Http;

use \Mockery;

use Tornado\Security\Http\AuthenticationFirewall;
use \Symfony\Component\HttpFoundation\ParameterBag;

/**
 * AuthenticationFirewallTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Security\Http
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass      \Tornado\Security\Http\AuthenticationFirewall
 */
class AuthenticationFirewallTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers ::isGranted
     */
    public function testSessionRemovedIfSessionExistsAndRequestedUriIsLogin()
    {
        $user = Mockery::mock('\Tornado\Organization\User');
        $request = Mockery::mock('\DataSift\Http\Request', [
            'getPathInfo' => '/login',
            'getMethod' => 'GET'
        ]);
        $request->attributes = new ParameterBag();

        $session = Mockery::mock('\Symfony\Component\HttpFoundation\Session\SessionInterface', [
            'get' => $user
        ]);
        $urlGenerator = Mockery::mock('\Symfony\Component\Routing\Generator\UrlGenerator');
        $urlGenerator->shouldReceive('generate')
            ->withArgs(['login'])
            ->times(1)
            ->andReturn('/login')
            ->getMock();
        $session->shouldReceive('remove')
            ->withArgs(['user'])
            ->times(1);

        $firewall = new AuthenticationFirewall($request, $session, $urlGenerator);
        $firewallResponse = $firewall->isGranted();

        $this->assertNotInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $firewallResponse);
        $this->assertNull($firewallResponse);
    }

    /**
     * @covers ::__construct
     * @covers ::isGranted
     */
    public function testDoNothingIfSessionExistsAndRequestedUriIsNotLogin()
    {
        $user = Mockery::mock('\Tornado\Organization\User');
        $request = Mockery::mock('\DataSift\Http\Request', [
            'getPathInfo' => '/',
            'getMethod' => 'GET'
        ]);
        $request->attributes = new ParameterBag();

        $session = Mockery::mock('\Symfony\Component\HttpFoundation\Session\SessionInterface', [
            'get' => $user
        ]);
        $urlGenerator = Mockery::mock('\Symfony\Component\Routing\Generator\UrlGenerator');
        $urlGenerator->shouldReceive('generate')
            ->withArgs(['login'])
            ->times(1)
            ->andReturn('/login')
            ->getMock();

        $firewall = new AuthenticationFirewall($request, $session, $urlGenerator);
        $firewallResponse = $firewall->isGranted();

        $this->assertNotInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $firewallResponse);
        $this->assertNull($firewallResponse);
    }

    /**
     * @covers ::__construct
     * @covers ::isGranted
     */
    public function testRedirectToLoginIfSessionDoesNotExists()
    {
        $request = Mockery::mock('\DataSift\Http\Request', [
            'getPathInfo' => '/',
            'getMethod' => 'GET'
        ]);
        $request->attributes = new ParameterBag();

        $session = Mockery::mock('\Symfony\Component\HttpFoundation\Session\SessionInterface', [
            'get' => null
        ]);
        $urlGenerator = Mockery::mock('\Symfony\Component\Routing\Generator\UrlGenerator');
        $urlGenerator->shouldReceive('generate')
            ->withArgs(['login'])
            ->times(1)
            ->andReturn('/login')
            ->getMock();

        $firewall = new AuthenticationFirewall($request, $session, $urlGenerator);
        $firewallResponse = $firewall->isGranted();

        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $firewallResponse);
        $this->assertEquals('/login', $firewallResponse->getTargetUrl());
        $this->assertEquals(302, $firewallResponse->getStatusCode());
    }

    /**
     * @covers ::__construct
     * @covers ::isGranted
     */
    public function testRedirectToLoginUnlessSessionUserIsTornadoUser()
    {
        $request = Mockery::mock('\DataSift\Http\Request', [
            'getPathInfo' => '/'
        ]);
        $request->attributes = new ParameterBag();

        $session = Mockery::mock('\Symfony\Component\HttpFoundation\Session\SessionInterface', [
            'get' => new \StdClass()
        ]);
        $urlGenerator = Mockery::mock('\Symfony\Component\Routing\Generator\UrlGenerator');
        $urlGenerator->shouldReceive('generate')
            ->withArgs(['login'])
            ->times(1)
            ->andReturn('/login')
            ->getMock();

        $firewall = new AuthenticationFirewall($request, $session, $urlGenerator);
        $firewallResponse = $firewall->isGranted();

        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $firewallResponse);
        $this->assertEquals('/login', $firewallResponse->getTargetUrl());
        $this->assertEquals(302, $firewallResponse->getStatusCode());
    }

    /**
     * @covers ::__construct
     * @covers ::isGranted
     */
    public function testDoNothingIfSessionDoesNotExistsAndRequestedUriIsLogin()
    {
        $request = Mockery::mock('\DataSift\Http\Request', [
            'getPathInfo' => '/login',
            'getMethod' => 'GET'
        ]);
        $request->attributes = new ParameterBag(
            [AuthenticationFirewall::AUTHENTICATION_ATTR => AuthenticationFirewall::AUTHENTICATION_OFF]
        );

        $session = Mockery::mock('\Symfony\Component\HttpFoundation\Session\SessionInterface', [
            'get' => null
        ]);
        $urlGenerator = Mockery::mock('\Symfony\Component\Routing\Generator\UrlGenerator');
        $urlGenerator->shouldReceive('generate')
            ->withArgs(['login'])
            ->times(1)
            ->andReturn('/login')
            ->getMock();

        $session->shouldReceive('remove')
            ->withArgs(['user'])
            ->times(1);

        $firewall = new AuthenticationFirewall($request, $session, $urlGenerator);
        $firewallResponse = $firewall->isGranted();

        $this->assertNotInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $firewallResponse);
        $this->assertNull($firewallResponse);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }
}
