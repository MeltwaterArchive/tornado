<?php

namespace Test\Controller;

use \Mockery;

use Controller\SecurityController;

use Tornado\Organization\User;

/**
 * SecurityControllerTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Controller
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Controller\SecurityController
 */
class SecurityControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers  ::__construct
     * @covers  ::login
     */
    public function testLoginGetRequest()
    {
        $session = Mockery::mock('\Symfony\Component\HttpFoundation\Session\SessionInterface');
        $form = Mockery::mock('\DataSift\Form\FormInterface');
        $urlGenerator = Mockery::mock('\Symfony\Component\Routing\Generator\UrlGenerator');
        $request = Mockery::mock('\DataSift\Http\Request', [
            'getMethod' => 'GET'
        ]);

        $request->query = new \Symfony\Component\HttpFoundation\ParameterBag();

        $jwt = Mockery::mock('\Tornado\Security\Authorization\JWT\Provider');
        $mapper = Mockery::mock('\Tornado\Organization\User\DataMapper');
        $roleMapper = Mockery::mock('\Tornado\Organization\Role\DataMapper');
        $passwordManager = Mockery::mock('\Tornado\Organization\User\PasswordManager');

        $ctrl = new SecurityController(
            $session,
            $form,
            $urlGenerator,
            $jwt,
            $mapper,
            $roleMapper,
            $form,
            $form,
            $passwordManager
        );
        
        $result = $ctrl->login($request);

        $this->assertInstanceOf('\Tornado\Controller\Result', $result);
        $this->assertEquals([], $result->getData());
        $this->assertEquals([], $result->getMeta());
        $this->assertEquals(200, $result->getHttpCode());
    }

    /**
     * @covers  ::__construct
     * @covers  ::login
     */
    public function testSuccessfulLogin()
    {
        $user = new User();
        $postParams = ['login' => 'test', 'password' => 'test'];
        $request = Mockery::mock('\DataSift\Http\Request', [
            'getMethod' => 'POST'
        ]);
        $request->shouldReceive('getPostParams')
            ->once()
            ->andReturn($postParams);
        $form = Mockery::mock('\DataSift\Form\FormInterface', [
            'submit' => true,
            'isValid' => true,
            'getData' => $user
        ]);
        $session = Mockery::mock('\Symfony\Component\HttpFoundation\Session\SessionInterface');
        $session->shouldReceive('start')
            ->once()
            ->withNoArgs();
        $session->shouldReceive('set')
            ->once()
            ->withArgs(['user', $user]);
        $urlGenerator = Mockery::mock('\Symfony\Component\Routing\Generator\UrlGenerator');
        $urlGenerator->shouldReceive('generate')
            ->once()
            ->withArgs(['home'])
            ->andReturn('/');

        $jwt = Mockery::mock('\Tornado\Security\Authorization\JWT\Provider');
        $mapper = Mockery::mock('\Tornado\Organization\User\DataMapper');
        $roleMapper = Mockery::mock('\Tornado\Organization\Role\DataMapper');
        $roleMapper->shouldReceive('findUserAssigned')
            ->once()
            ->with($user)
            ->andReturn([]);

        $passwordManager = Mockery::mock('\Tornado\Organization\User\PasswordManager');

        $ctrl = new SecurityController(
            $session,
            $form,
            $urlGenerator,
            $jwt,
            $mapper,
            $roleMapper,
            $form,
            $form,
            $passwordManager
        );

        $result = $ctrl->login($request);

        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $result);
        $this->assertEquals('/', $result->getTargetUrl());
        $this->assertEquals(302, $result->getStatusCode());
    }

    /**
     * @covers  ::__construct
     * @covers  ::login
     */
    public function testSuccessfulLoginUnlessInvalidData()
    {
        $postParams = ['login' => 'test', 'password' => 'test'];
        $request = Mockery::mock('\DataSift\Http\Request', [
            'getMethod' => 'POST'
        ]);
        $request->shouldReceive('getPostParams')
            ->once()
            ->andReturn($postParams);
        $form = Mockery::mock('\DataSift\Form\FormInterface', [
            'submit' => true,
            'isValid' => false,
            'getErrors' => ['password' => 'Incorrect password.']
        ]);
        $session = Mockery::mock('\Symfony\Component\HttpFoundation\Session\SessionInterface');
        $session->shouldReceive('start')
            ->never();
        $session->shouldReceive('set')
            ->never();
        $urlGenerator = Mockery::mock('\Symfony\Component\Routing\Generator\UrlGenerator');
        $urlGenerator->shouldReceive('generate')
            ->never();

        $jwt = Mockery::mock('\Tornado\Security\Authorization\JWT\Provider');
        $mapper = Mockery::mock('\Tornado\Organization\User\DataMapper');
        $roleMapper = Mockery::mock('\Tornado\Organization\Role\DataMapper');

        $passwordManager = Mockery::mock('\Tornado\Organization\User\PasswordManager');

        $ctrl = new SecurityController(
            $session,
            $form,
            $urlGenerator,
            $jwt,
            $mapper,
            $roleMapper,
            $form,
            $form,
            $passwordManager
        );

        $result = $ctrl->login($request);

        $this->assertNotInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $result);
        $this->assertInstanceOf('\Tornado\Controller\Result', $result);

        $this->assertEquals([], $result->getData());
        $this->assertEquals(['password' => 'Incorrect password.'], $result->getMeta());
        $this->assertEquals(400, $result->getHttpCode());
    }

    /**
     * @covers  ::__construct
     * @covers  ::logout
     */
    public function testLogout()
    {
        $session = Mockery::mock('\Symfony\Component\HttpFoundation\Session\SessionInterface');
        $session->shouldReceive('remove')
            ->withArgs(['user'])
            ->once()
            ->andReturn(true);

        $form = Mockery::mock('\DataSift\Form\FormInterface');
        $urlGenerator = Mockery::mock('\Symfony\Component\Routing\Generator\UrlGenerator', [
            'generate' => '/login'
        ]);

        $jwt = Mockery::mock('\Tornado\Security\Authorization\JWT\Provider');
        $mapper = Mockery::mock('\Tornado\Organization\User\DataMapper');
        $roleMapper = Mockery::mock('\Tornado\Organization\Role\DataMapper');

        $passwordManager = Mockery::mock('\Tornado\Organization\User\PasswordManager');

        $ctrl = new SecurityController(
            $session,
            $form,
            $urlGenerator,
            $jwt,
            $mapper,
            $roleMapper,
            $form,
            $form,
            $passwordManager
        );

        $result = $ctrl->logout();

        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $result);
        $this->assertEquals('/login', $result->getTargetUrl());
        $this->assertEquals(302, $result->getStatusCode());
    }

    public function jwtProvider()
    {
        return [
            'Happy path with no URL' => [
                'token' => 'My Token',
                'payload' => (object)[
                    'iss' => 10,
                    'sub' => 'testuser'
                ],
                'user' => Mockery::Mock('\Tornado\Organization\User'),
                'redirect' => true
            ],
            'Happy path with full URL' => [
                'token' => 'My Token',
                'payload' => (object)[
                    'iss' => 10,
                    'sub' => 'testuser',
                    'url' => 'http://test.com/tested'
                ],
                'user' => Mockery::Mock('\Tornado\Organization\User'),
                'redirect' => '/tested'
            ],
            'Valid token, user not found' => [
                'token' => 'My Token',
                'payload' => (object)[
                    'iss' => 10,
                    'sub' => 'testuser',
                    'url' => 'http://test.com/tested'
                ],
                'user' => false
            ],
            'Invalid token' => [
                'token' => 'My Token',
                'payload' => (object)[
                    'iss' => 10,
                    'sub' => 'testuser',
                    'url' => 'http://test.com/tested'
                ],
                'user' => false,
                'redirect' => false,
                'jwtException' => new \RuntimeException('Test Message')
            ]
        ];
    }

    /**
     * @dataProvider jwtProvider
     *
     * @covers ::login
     * @covers ::doJwt
     *
     * @param string $token
     * @param stdClass $payload
     * @param User $user
     * @param string|false $redirect
     * @param Exception|false $jwtException
     */
    public function testJWT($token, $payload, $user, $redirect = false, $jwtException = false)
    {
        $request = Mockery::mock('\DataSift\Http\Request', [
            'getMethod' => 'GET'
        ]);

        $request->query = new \Symfony\Component\HttpFoundation\ParameterBag(['jwt' => $token]);

        $session = Mockery::mock('\Symfony\Component\HttpFoundation\Session\SessionInterface');
        $form = Mockery::mock('\DataSift\Form\FormInterface');
        $urlGenerator = Mockery::mock('\Symfony\Component\Routing\Generator\UrlGenerator');
        $urlGenerator->shouldReceive('generate')
                     ->with('home')
                     ->andReturn('home');

        $jwt = Mockery::mock('\Tornado\Security\Authorization\JWT\Provider');
        $should = $jwt->shouldReceive('validateToken')
            ->once()
            ->with($token);

        if ($jwtException) {
            $should->andThrowExceptions([$jwtException]);
        } else {
            $should->andReturn($payload);
        }

        $mapper = Mockery::mock('\Tornado\Organization\User\DataMapper');
        if (!$jwtException) {
            $mapper->shouldReceive('findOne')
                    ->with([
                        'organization_id' => $payload->iss,
                        'username' => $payload->sub
                    ])
                    ->andReturn($user);
        }

        if ($user) {
            $session->shouldReceive('start')->once();
            $session->shouldReceive('set')->once()->with('user', $user);
        } else {
            $session->shouldNotReceive('start');
            $session->shouldNotReceive('set');
        }
        $roleMapper = Mockery::mock('\Tornado\Organization\Role\DataMapper');

        $passwordManager = Mockery::mock('\Tornado\Organization\User\PasswordManager');

        $ctrl = new SecurityController(
            $session,
            $form,
            $urlGenerator,
            $jwt,
            $mapper,
            $roleMapper,
            $form,
            $form,
            $passwordManager
        );

        $result = $ctrl->login($request);

        if ($redirect) {
            $this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $result);
            if ($redirect === true) {
                $redirect = 'home';
            }
            $this->assertEquals($redirect, $result->getTargetUrl());
        } else {
            $this->assertInstanceOf('\Tornado\Controller\Result', $result);
            $error = ($jwtException)
                ? $jwtException->getMessage()
                : 'Invalid `sub` element in an otherwise valid token';
            $this->assertEquals(['login' => $error], $result->getMeta());
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
