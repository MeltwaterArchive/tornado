<?php

namespace Test\Tornado\Security\Http;

use \Mockery;

use Tornado\Security\Http\AuthenticationFirewall;
use \Symfony\Component\HttpFoundation\ParameterBag;
use \DataSift\Http\Request;
use Tornado\Organization\User;

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
     * DataProvider for testIsGranted
     *
     * @return array
     */
    public function isGrantedProvider()
    {
        return [
            'Do Nothing If Session Does Not Exist And Requested Uri Is Login' => [
                'user' => null,
                'method' => 'GET',
                'url' => '/login',
                'queryString' => '',
                'access' => AuthenticationFirewall::AUTHENTICATION_OFF,
                'expectedRedirect' => '',
                'expectedSessionClear' => true
            ],
            'Session Removed If Session Exists And Requested Uri Is Login' => [
                'user' => new User(),
                'method' => 'GET',
                'url' => '/login',
                'queryString' => '',
                'access' => AuthenticationFirewall::AUTHENTICATION_OFF,
                'expectedRedirect' => '',
                'expectedSessionClear' => true
            ],
            'Do Nothing If Session Exists And Requested Uri Is Not Login' => [
                'user' => new User(),
                'method' => 'GET',
                'url' => '/',
                'queryString' => '',
                'access' => AuthenticationFirewall::AUTHENTICATION_ON,
            ],
            'Redirect To Login If Session Does Not Exist' => [
                'user' => null,
                'method' => 'GET',
                'url' => '/',
                'queryString' => '',
                'access' => AuthenticationFirewall::AUTHENTICATION_ON,
                'expectedRedirect' => '/login?redirect=' . urlencode('/')
            ],
            'Redirect To Login Unless Session User Is Tornado User' => [
                'user' => new \stdClass(),
                'method' => 'GET',
                'url' => '/',
                'queryString' => '',
                'access' => AuthenticationFirewall::AUTHENTICATION_ON,
                'expectedRedirect' => '/login?redirect=' . urlencode('/')
            ],
        ];
    }

    /**
     * @dataProvider isGrantedProvider
     *
     * @covers ::__construct
     * @covers ::isGranted
     *
     * @param mixed $user
     * @param string $method
     * @param string $url
     * @param string $queryString
     * @param string $access
     * @param string $expectedRedirect
     * @param boolean $expectedSessionClear
     * @param string $loginUrl
     */
    public function testIsGranted(
        $user,
        $method,
        $url,
        $queryString,
        $access,
        $expectedRedirect = '',
        $expectedSessionClear = false,
        $loginUrl = '/login'
    ) {
        $request = Mockery::mock('\DataSift\Http\Request', [
            'getPathInfo' => $url,
            'getMethod' => $method,
            'getQueryString' => $queryString
        ]);

        $data = [];
        if ($access) {
            $data[AuthenticationFirewall::AUTHENTICATION_ATTR] = $access;
        }
        $request->attributes = new ParameterBag($data);

        $session = Mockery::mock('\Symfony\Component\HttpFoundation\Session\SessionInterface');
        $session->shouldReceive('get')
            ->with('user')
            ->andReturn($user);

        $urlGenerator = Mockery::mock('\Symfony\Component\Routing\Generator\UrlGenerator');
        $urlGenerator->shouldReceive('generate')
            ->with('login')
            ->andReturn($loginUrl);

        if ($expectedSessionClear) {
            $session->shouldReceive('remove')
                ->once()
                ->with('user');
        } else {
            $session->shouldReceive('remove')
                ->never();
        }

        $firewall = new AuthenticationFirewall($request, $session, $urlGenerator);
        $firewallResponse = $firewall->isGranted();

        if ($expectedRedirect) {
            $this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $firewallResponse);
            $this->assertEquals($expectedRedirect, $firewallResponse->getTargetUrl());
            $this->assertEquals(302, $firewallResponse->getStatusCode());
        } else {
            $this->assertNull($firewallResponse);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }
}
