<?php

namespace Test\Controller;

use \Mockery;

use Controller\SecurityController;

use Tornado\Organization\User;
use Tornado\Application\Flash\Message as Flash;
use Symfony\Component\HttpFoundation\Response;

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
     * DataProvider for testLogin
     *
     * @return array
     */
    public function loginProvider()
    {
        return [
            'Plain get request' => [
                'method' => 'GET',
                'postParams' => [],
                'queryParams' => [],
                'expectedResponse' => ['data' => ['redirect' => ''], 'meta' => [], 'status' => Response::HTTP_OK]
            ],
            'Get request, with redirect' => [
                'method' => 'GET',
                'postParams' => [],
                'queryParams' => ['redirect' => '/projects'],
                'expectedResponse' => [
                    'data' => ['redirect' => '/projects'],
                    'meta' => [],
                    'status' => Response::HTTP_OK
                ]
            ],
            'Unsuccessful login, invalid form' => [
                'method' => 'POST',
                'postParams' => ['login' => 'test', 'password' => 'why', 'redirect' => '/projects'],
                'queryParams' => ['redirect' => '/projects'],
                'expectedResponse' => [
                    'data' => ['redirect' => '/projects'],
                    'meta' => ['error' => 'message'],
                    'status' => Response::HTTP_BAD_REQUEST
                ],
                'expectedRedirect' => '',
                'loginSuccessful' => false,
                'formValid' => false,
                'userDisabled' => false,
                'formErrors' => ['error' => 'message']
            ],
            'Unsuccessful login, disabled user' => [
                'method' => 'POST',
                'postParams' => ['login' => 'test', 'password' => 'why', 'redirect' => '/projects'],
                'queryParams' => ['redirect' => '/projects'],
                'expectedResponse' => [
                    'data' => [],
                    'meta' => ['__notification' => ['message' => 'Account disabled', 'level' => Flash::LEVEL_ERROR]],
                    'status' => Response::HTTP_BAD_REQUEST
                ],
                'expectedRedirect' => '',
                'loginSuccessful' => false,
                'formValid' => true,
                'userDisabled' => true,
                'formErrors' => []
            ],
            'Successful login' => [
                'method' => 'POST',
                'postParams' => ['login' => 'test', 'password' => 'test', 'redirect' => ''],
                'queryParams' => [],
                'expectedResponse' => ['data' => ['redirect' => ''], 'meta' => [], 'status' => Response::HTTP_OK],
                'expectedRedirect' => '/',
                'loginSuccessful' => true
            ],
            'Successful login with redirect' => [
                'method' => 'POST',
                'postParams' => ['login' => 'test', 'password' => 'test', 'redirect' => '/projects'],
                'queryParams' => [],
                'expectedResponse' => [
                    'data' => ['redirect' => ''],
                    'meta' => [],
                    'status' => Response::HTTP_TEMPORARY_REDIRECT
                ],
                'expectedRedirect' => '/projects',
                'loginSuccessful' => true
            ],
            'Successful login, removes dangerous redirect' => [
                'method' => 'POST',
                'postParams' => ['login' => 'test', 'password' => 'test', 'redirect' => 'http://bbc.com/projects'],
                'queryParams' => [],
                'expectedResponse' => [
                    'data' => ['redirect' => ''],
                    'meta' => [],
                    'status' => Response::HTTP_TEMPORARY_REDIRECT
                ],
                'expectedRedirect' => '/projects',
                'loginSuccessful' => true
            ]
        ];
    }

    /**
     * @dataProvider loginProvider
     *
     * @covers  ::__construct
     * @covers  ::login
     *
     * @param string $method
     * @param array $postParams
     * @param string $queryString
     */
    public function testLogin(
        $method,
        array $postParams,
        array $queryParams,
        array $expectedResponse,
        $expectedRedirect = '',
        $loginSuccessful = false,
        $formValid = true,
        $userDisabled = false,
        array $formErrors = []
    ) {
        $session = Mockery::mock('\Symfony\Component\HttpFoundation\Session\SessionInterface');
        $userId = 10;
        $sessionId = 'abc123';

        $user = new User();
        $user->setId($userId);
        $user->setDisabled(($userDisabled) ? 1 : 0);

        $form = Mockery::mock('\DataSift\Form\FormInterface', [
            'submit' => true,
            'isValid' => $formValid,
            'getData' => $user,
            'getErrors' => $formErrors
        ]);

        $urlGenerator = Mockery::mock('\Symfony\Component\Routing\Generator\UrlGenerator');
        $urlGenerator->shouldReceive('generate')
            ->withArgs(['home'])
            ->andReturn('/');

        $request = Mockery::mock('\DataSift\Http\Request', [
            'getMethod' => $method,
        ]);

        $request->query = new \Symfony\Component\HttpFoundation\ParameterBag($queryParams);
        $request->shouldReceive('getPostParams')
            ->andReturn($postParams);

        $roles = [Mockery::mock('\Tornado\Organization\Role')];

        $jwt = Mockery::mock('\Tornado\Security\Authorization\JWT\Provider');
        $mapper = Mockery::mock('\Tornado\Organization\User\DataMapper');
        $roleMapper = Mockery::mock('\Tornado\Organization\Role\DataMapper');
        $roleMapper->shouldReceive('findUserAssigned')
            ->times(($loginSuccessful) ? 1 : 0)
            ->with($user)
            ->andReturn($roles);

        $passwordManager = Mockery::mock('\Tornado\Organization\User\PasswordManager');
        $sessionHandler = Mockery::mock('\SessionHandlerInterface');
        if ($loginSuccessful) {
            $session->shouldReceive('start')
                ->once()
                ->withNoArgs();
            $session->shouldReceive('set')
                ->once()
                ->withArgs(['user', $user]);

            $session->shouldReceive('getId')
                ->once()
                ->andReturn($sessionId);
            $sessionHandler->shouldReceive('write')
                ->with("session-{$userId}", $sessionId);
        }

        $ctrl = new SecurityController(
            $session,
            $form,
            $urlGenerator,
            $jwt,
            $mapper,
            $roleMapper,
            $form,
            $form,
            $passwordManager,
            $sessionHandler
        );

        $result = $ctrl->login($request);

        if ($expectedRedirect) {
            $this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $result);
            $this->assertEquals($expectedRedirect, $result->getTargetUrl());
        } else {
            $this->assertInstanceOf('\Tornado\Controller\Result', $result);
            $this->assertEquals($expectedResponse['data'], $result->getData());
            $this->assertEquals($expectedResponse['meta'], $result->getMeta());
            $this->assertEquals($expectedResponse['status'], $result->getHttpCode());
            if ($loginSuccessful) {
                $this->assertEquals($roles, $user->getRoles());
            }
        }
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

        $sessionHandler = Mockery::mock('\SessionHandlerInterface');

        $ctrl = new SecurityController(
            $session,
            $form,
            $urlGenerator,
            $jwt,
            $mapper,
            $roleMapper,
            $form,
            $form,
            $passwordManager,
            $sessionHandler
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
        $sessionHandler = Mockery::mock('\SessionHandlerInterface');

        $ctrl = new SecurityController(
            $session,
            $form,
            $urlGenerator,
            $jwt,
            $mapper,
            $roleMapper,
            $form,
            $form,
            $passwordManager,
            $sessionHandler
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
