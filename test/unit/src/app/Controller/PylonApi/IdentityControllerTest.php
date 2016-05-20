<?php

namespace Test\Controller\PylonApi;

use Mockery;

use Symfony\Component\HttpFoundation\JsonResponse;
use DataSift\Http\Request;
use DataSift\Api\User as DataSiftUser;
use DataSift_Account_Identity;
use DataSift_Account_Identity_Token;
use DataSift_Account_Identity_Limit;

use Tornado\Organization\Agency;
use Tornado\Organization\Brand\DataMapper as BrandRepository;
use Tornado\Organization\Brand;
use Tornado\Organization\User\DataMapper as UserRepository;
use Tornado\Organization\User;

use Controller\PylonApi\IdentityController;

/**
 * IdentityControllerTest
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
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @covers \Controller\PylonApi\IdentityController
 */
class IdentityControllerTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testIndex()
    {
        $mocks = $this->getMocks();
        $controller = $this->getController($mocks);

        $request = new Request([
            'per_page' => 10
        ]);

        $mocks['identityApi']->expects($this->once())
            ->method('getAll')
            ->with(null, 1, 10);

        $response = $controller->index($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testShow()
    {
        $mocks = $this->getMocks();
        $controller = $this->getController($mocks);

        $id = '123test';

        $mocks['identityApi']->expects($this->once())
            ->method('get')
            ->with($id);

        $response = $controller->show($id);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testDelete()
    {
        $mocks = $this->getMocks();
        $controller = $this->getController($mocks);

        $id = '123test';

        $response = $controller->delete(new Request(), $id);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testCreate()
    {
        $mocks = $this->getMocks();

        $label = 'testidentity';
        $id = 'testId123';
        $apiKey = 'secretApiKey';

        $request = $this->getApiRequest(['label' => $label]);
        $request->attributes->set('agency', $mocks['agency']);

        $mocks['identityApi']->expects($this->once())
            ->method('create')
            ->with($label, false, 'active');

        // overwrite this one mock to alter the return value
        $mocks['client'] = Mockery::mock(DataSiftUser::class);
        $mocks['client']->shouldReceive('proxyResponse')
            ->andReturnUsing(function ($action) use ($label, $id, $apiKey) {
                $action();
                return new JsonResponse([
                    'id' => $id,
                    'label' => $label,
                    'api_key' => $apiKey
                ], 201);
            });

        $mocks['brandRepository']->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($brand) use ($mocks, $label, $id, $apiKey) {
                $this->assertInstanceOf(Brand::class, $brand);
                $this->assertEquals($mocks['agencyId'], $brand->getAgencyId());
                $this->assertEquals($label, $brand->getName());
                $this->assertEquals($id, $brand->getDatasiftIdentityId());
                $this->assertEquals($apiKey, $brand->getDatasiftApiKey());
                return true;
            }));

        $mocks['userRepository']->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($user) use ($id, $label, $mocks) {
                $this->assertInstanceOf(User::class, $user);
                $this->assertEquals($mocks['organizationId'], $user->getOrganizationId());
                $this->assertEquals($id, $user->getEmail());
                $this->assertEquals($label, $user->getUsername());
                $this->assertEquals(User::TYPE_IDENTITY_API, $user->getType());
                return true;
            }));

        $controller = $this->getController($mocks);
        $response = $controller->create($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testCreateError()
    {
        $mocks = $this->getMocks();

        $label = 'testidentity';

        $request = $this->getApiRequest(['label' => $label]);
        $request->attributes->set('agency', $mocks['agency']);

        $mocks['identityApi']->expects($this->once())
            ->method('create')
            ->with($label, false, 'active');

        $mocks['brandRepository']->expects($this->never())
            ->method('create');

        $mocks['userRepository']->expects($this->never())
            ->method('create');

        // overwrite this one mock to alter the return value
        $mocks['client'] = Mockery::mock(DataSiftUser::class);
        $mocks['client']->shouldReceive('proxyResponse')
            ->andReturnUsing(function ($action) {
                $action();
                return new JsonResponse([], 403);
            });

        $controller = $this->getController($mocks);
        $response = $controller->create($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testUpdate()
    {
        $mocks = $this->getMocks();

        $id = 'testId123';
        $label = 'Updated Identity';
        $status = 'disabled';
        $apiKey = 'secretApiKey';

        $request = $this->getApiRequest(['label' => $label, 'status' => $status]);
        $request->attributes->set('agency', $mocks['agency']);

        $mocks['identityApi']->expects($this->once())
            ->method('update')
            ->with($id, $label, false, $status);

        // overwrite this one mock to alter the return value
        $mocks['client'] = Mockery::mock(DataSiftUser::class);
        $mocks['client']->shouldReceive('proxyResponse')
            ->andReturnUsing(function ($action) use ($label, $id, $apiKey) {
                $action();
                return new JsonResponse([
                    'id' => $id,
                    'label' => $label,
                    'api_key' => $apiKey
                ], 200);
            });

        $mocks['brandRepository']->expects($this->once())
            ->method('findOne')
            ->with([
                'agency_id' => $mocks['agencyId'],
                'datasift_identity_id' => $id
            ])
            ->will($this->returnValue($mocks['brand']));

        $mocks['brandRepository']->expects($this->once())
            ->method('update')
            ->with($this->identicalTo($mocks['brand']));

        $mocks['userRepository']->expects($this->once())
            ->method('findOne')
            ->with([
                'organization_id' => $mocks['organizationId'],
                'email' => $id,
                'type' => User::TYPE_IDENTITY_API
            ])
            ->will($this->returnValue($mocks['user']));

        $mocks['userRepository']->expects($this->once())
            ->method('update')
            ->with($this->identicalTo($mocks['user']));

        $controller = $this->getController($mocks);
        $response = $controller->update($request, $id);

        $this->assertInstanceOf(JsonResponse::class, $response);

        // assert that brand was updated
        $this->assertEquals($label, $mocks['brand']->getName());
        $this->assertEquals($apiKey, $mocks['brand']->getDatasiftApiKey());

        // assert that user was updated
        $this->assertEquals($label, $mocks['user']->getUsername());
    }

    public function testUpdateError()
    {
        $mocks = $this->getMocks();

        $id = 'testId123';
        $label = 'Updated Identity';
        $status = 'disabled';

        $request = $this->getApiRequest(['label' => $label, 'status' => $status]);
        $request->attributes->set('agency', $mocks['agency']);

        $mocks['identityApi']->expects($this->once())
            ->method('update')
            ->with($id, $label, false, $status);

        // overwrite this one mock to alter the return value
        $mocks['client'] = Mockery::mock(DataSiftUser::class);
        $mocks['client']->shouldReceive('proxyResponse')
            ->andReturnUsing(function ($action) {
                $action();
                return new JsonResponse([], 403);
            });

        $mocks['brandRepository']->expects($this->never())
            ->method('update');

        $mocks['userRepository']->expects($this->never())
            ->method('update');

        $controller = $this->getController($mocks);
        $response = $controller->update($request, $id);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testUpdateNoBrand()
    {
        $mocks = $this->getMocks();

        $id = 'testId123';
        $label = 'Updated Identity';
        $status = 'disabled';
        $apiKey = 'secretApiKey';

        $request = $this->getApiRequest(['label' => $label, 'status' => $status]);
        $request->attributes->set('agency', $mocks['agency']);

        $mocks['identityApi']->expects($this->once())
            ->method('update')
            ->with($id, $label, false, $status);

        // overwrite this one mock to alter the return value
        $mocks['client'] = Mockery::mock(DataSiftUser::class);
        $mocks['client']->shouldReceive('proxyResponse')
            ->andReturnUsing(function ($action) use ($label, $id, $apiKey) {
                $action();
                return new JsonResponse([
                    'id' => $id,
                    'label' => $label,
                    'api_key' => $apiKey
                ], 200);
            });

        $mocks['brandRepository']->expects($this->once())
            ->method('findOne')
            ->with([
                'agency_id' => $mocks['agencyId'],
                'datasift_identity_id' => $id
            ])
            ->will($this->returnValue(null));

        $controller = $this->getController($mocks);
        $controller->update($request, $id);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testUpdateNoUser()
    {
        $mocks = $this->getMocks();

        $id = 'testId123';
        $label = 'Updated Identity';
        $status = 'disabled';
        $apiKey = 'secretApiKey';

        $request = $this->getApiRequest(['label' => $label, 'status' => $status]);
        $request->attributes->set('agency', $mocks['agency']);

        $mocks['identityApi']->expects($this->once())
            ->method('update')
            ->with($id, $label, false, $status);

        // overwrite this one mock to alter the return value
        $mocks['client'] = Mockery::mock(DataSiftUser::class);
        $mocks['client']->shouldReceive('proxyResponse')
            ->andReturnUsing(function ($action) use ($label, $id, $apiKey) {
                $action();
                return new JsonResponse([
                    'id' => $id,
                    'label' => $label,
                    'api_key' => $apiKey
                ], 200);
            });

        $mocks['brandRepository']->expects($this->once())
            ->method('findOne')
            ->with([
                'agency_id' => $mocks['agencyId'],
                'datasift_identity_id' => $id
            ])
            ->will($this->returnValue($mocks['brand']));

        $mocks['brandRepository']->expects($this->once())
            ->method('update')
            ->with($this->identicalTo($mocks['brand']));

        $mocks['userRepository']->expects($this->once())
            ->method('findOne')
            ->with([
                'organization_id' => $mocks['organizationId'],
                'email' => $id,
                'type' => User::TYPE_IDENTITY_API
            ])
            ->will($this->returnValue(null));

        $controller = $this->getController($mocks);
        $controller->update($request, $id);
    }

    public function testUpdateToken()
    {
        $mocks = $this->getMocks();
        $controller = $this->getController($mocks);

        $id = '123test';
        $service = 'facebook';
        $token = 'secretToken';

        $request = $this->getApiRequest(['token' => $token, 'service' => $service]);

        $mocks['identityTokenApi']->expects($this->once())
            ->method('update')
            ->with($id, $service, $token);

        $response = $controller->updateToken($request, $id);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testCreateToken()
    {
        $mocks = $this->getMocks();
        $controller = $this->getController($mocks);

        $id = '123test';
        $service = 'facebook';
        $token = 'secretToken';

        $request = $this->getApiRequest(['token' => $token, 'service' => $service]);

        $mocks['identityTokenApi']->expects($this->once())
            ->method('create')
            ->with($id, $service, $token);

        $response = $controller->createToken($request, $id);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * DataProvider for testTokenList
     *
     * @return array
     */
    public function tokenListProvider()
    {
        return [
            'Happy path' => [
                'request' => $this->getApiRequest([], []),
                'id' => 'abc123',
                'expectedPage' => 1,
                'expectedPerPage' => 25
            ],
            'Paginated' => [
                'request' => $this->getApiRequest(
                    [],
                    [
                        'page' => 10,
                        'per_page' => 20
                    ]
                ),
                'id' => 'abc123',
                'expectedPage' => 10,
                'expectedPerPage' => 20
            ]
        ];
    }

    /**
     * @dataProvider tokenListProvider
     *
     * @covers \Controller\PylonApi\IdentityController::tokenList
     *
     * @param \DataSift\Http\Request $request
     * @param string $id
     * @param integer $expectedPage
     * @param integer $expectedPerPage
     */
    public function testTokenList(Request $request, $id, $expectedPage, $expectedPerPage)
    {
        $response = new JsonResponse();
        $mocks = $this->getMocks();
        $mocks['identityTokenApi']->expects($this->once())
            ->method('getAll')
            ->with($id, $expectedPage, $expectedPerPage)
            ->will($this->returnValue($response));
        $controller = $this->getController($mocks);

        $this->assertEquals($response, $controller->tokenList($request, $id));
    }

    /**
     * DataProvider for testTokenService
     *
     * @return array;
     */
    public function tokenServiceProvider()
    {
        return [
            'Happy path' => [
                'id' => 'abc123',
                'service' => 'facebook'
            ]
        ];
    }

    /**
     * @dataProvider tokenServiceProvider
     *
     * @covers \Controller\PylonApi\IdentityController::tokenService
     *
     * @param string $id
     * @param string $service
     */
    public function testTokenService($id, $service)
    {
        $response = new JsonResponse();
        $mocks = $this->getMocks();
        $mocks['identityTokenApi']->expects($this->once())
            ->method('get')
            ->with($id, $service)
            ->will($this->returnValue($response));
        $controller = $this->getController($mocks);

        $this->assertEquals($response, $controller->tokenService($this->getApiRequest([]), $id, $service));
    }

    /**
     * DataProvider for testLimitList
     *
     * @return array
     */
    public function limitListProvider()
    {
        return [
            'Happy Path' => [
                'request' => $this->getApiRequest(
                    [],
                    [
                        'page' => 10,
                        'per_page' => 50
                    ]
                ),
                'facebook',
                10,
                50
            ],
            'No page' => [
                'request' => $this->getApiRequest(
                    [],
                    [
                        'per_page' => 50
                    ]
                ),
                'facebook',
                1,
                50
            ],
            'No per_page' => [
                'request' => $this->getApiRequest(
                    [],
                    [
                        'page' => 50
                    ]
                ),
                'facebook',
                50,
                25
            ]
        ];
    }

    /**
     * @dataProvider limitListProvider
     *
     * @covers \Controller\PylonApi\IdentityController::limitList
     *
     * @param \DataSift\Http\Request $request
     * @param string $service
     * @param integer $expectedPage
     * @param integer $expectedPerPage
     */
    public function testLimitList(Request $request, $service, $expectedPage, $expectedPerPage)
    {
        $response = new JsonResponse();
        $mocks = $this->getMocks();
        $mocks['identityLimitApi']->expects($this->once())
            ->method('getAll')
            ->with($service, $expectedPage, $expectedPerPage)
            ->will($this->returnValue($response));
        $controller = $this->getController($mocks);

        $this->assertEquals($response, $controller->limitList($request, $service));
    }

    /**
     * DataProvider for testLimitService
     *
     * @return array
     */
    public function limitServiceProvider()
    {
        return [
            'Happy Path' => [
                'request' => $this->getApiRequest([]),
                'abcdef123abcdef123abcdef123abcdef12',
                'facebook'
            ],
        ];
    }

    /**
     * @dataProvider limitListProvider
     *
     * @covers \Controller\PylonApi\IdentityController::limitService
     *
     * @param \DataSift\Http\Request $request
     * @param string $id
     * @param string $service
     */
    public function testLimitService(Request $request, $id, $service)
    {
        $response = new JsonResponse();
        $mocks = $this->getMocks();
        $mocks['identityLimitApi']->expects($this->once())
            ->method('get')
            ->with($id, $service)
            ->will($this->returnValue($response));
        $controller = $this->getController($mocks);

        $this->assertEquals($response, $controller->limitService($request, $id, $service));
    }

    /**
     * DataProvider for testLimitCreate
     *
     * @return array
     */
    public function limitCreateProvider()
    {
        return [
            'Happy Path [POST]' => [
                'request' => $this->getApiRequest([
                    'total_allowance' => 100000
                ]),
                'abcdef123abcdef123abcdef123abcdef12',
                'facebook',
                'facebook',
                100000
            ],
            'Happy Path [PUT]' => [
                'request' => $this->getApiRequest([
                    'total_allowance' => 20000,
                    'service' => 'linkedin'
                ]),
                'abcdef123abcdef123abcdef123abcdef12',
                null,
                'linkedin',
                20000
            ],
        ];
    }

    /**
     * @dataProvider limitCreateProvider
     *
     * @covers \Controller\PylonApi\IdentityController::limitCreate
     *
     * @param \DataSift\Http\Request $request
     * @param string $id
     * @param string|null $service
     * @param string $expectedService
     * @param integer $expectedTotalAllowance
     */
    public function testLimitCreate(Request $request, $id, $service, $expectedService, $expectedTotalAllowance)
    {
        $response = new JsonResponse();
        $mocks = $this->getMocks();
        $mocks['identityLimitApi']->expects($this->once())
            ->method('create')
            ->with($id, $expectedService, $expectedTotalAllowance)
            ->will($this->returnValue($response));
        $controller = $this->getController($mocks);

        $this->assertEquals($response, $controller->limitCreate($request, $id, $service));
    }

    /**
     * DataProvider for testLimitUpdate
     *
     * @return array
     */
    public function limitUpdateProvider()
    {
        return [
            'Happy Path' => [
                'request' => $this->getApiRequest([
                    'total_allowance' => 100000
                ]),
                'abcdef123abcdef123abcdef123abcdef12',
                'facebook',
                100000
            ],
        ];
    }

    /**
     * @dataProvider limitUpdateProvider
     *
     * @covers \Controller\PylonApi\IdentityController::limitUpdate
     *
     * @param \DataSift\Http\Request $request
     * @param string $id
     * @param string|null $service
     * @param string $expectedService
     * @param integer $expectedTotalAllowance
     */
    public function testLimitUpdate(Request $request, $id, $service, $expectedTotalAllowance)
    {
        $response = new JsonResponse();
        $mocks = $this->getMocks();
        $mocks['identityLimitApi']->expects($this->once())
            ->method('update')
            ->with($id, $service, $expectedTotalAllowance)
            ->will($this->returnValue($response));
        $controller = $this->getController($mocks);

        $this->assertEquals($response, $controller->limitUpdate($request, $id, $service));
    }

    /**
     * DataProvider for testLimitRemove
     *
     * @return array
     */
    public function limitRemoveProvider()
    {
        return [
            'Happy Path' => [
                'request' => $this->getApiRequest([]),
                'abcdef123abcdef123abcdef123abcdef12',
                'facebook'
            ],
        ];
    }

    /**
     * @dataProvider limitRemoveProvider
     *
     * @covers \Controller\PylonApi\IdentityController::limitRemove
     *
     * @param \DataSift\Http\Request $request
     * @param string $id
     * @param string $service
     */
    public function testLimitRemove(Request $request, $id, $service)
    {
        $response = new JsonResponse();
        $mocks = $this->getMocks();
        $mocks['identityLimitApi']->expects($this->once())
            ->method('delete')
            ->with($id, $service)
            ->will($this->returnValue($response));
        $controller = $this->getController($mocks);

        $this->assertEquals($response, $controller->limitRemove($request, $id, $service));
    }

    protected function getApiRequest(array $requestBody, array $query = [])
    {
        return new Request($query, [], [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($requestBody));
    }

    protected function getMocks()
    {
        $mocks = [];

        $mocks['brandId'] = 7;
        $mocks['agencyId'] = 9;
        $mocks['organizationId'] = 10;

        $mocks['brand'] = new Brand();
        $mocks['brand']->setId($mocks['brandId']);

        $mocks['agency'] = new Agency();
        $mocks['agency']->setId($mocks['agencyId']);
        $mocks['agency']->setOrganizationId($mocks['organizationId']);

        $mocks['user'] = new User();
        $mocks['user']->setOrganizationId($mocks['organizationId']);

        $mocks['client'] = Mockery::mock(DataSiftUser::class);
        $mocks['client']->shouldReceive('proxyResponse')
            ->andReturnUsing(function ($action) {
                $action();
                return new JsonResponse();
            });

        $mocks['identityApi'] = $this->getMockBuilder(DataSift_Account_Identity::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mocks['identityTokenApi'] = $this->getMockBuilder(DataSift_Account_Identity_Token::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mocks['identityLimitApi'] = $this->getMockBuilder(DataSift_Account_Identity_Limit::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mocks['brandRepository'] = $this->getMockBuilder(BrandRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mocks['userRepository'] = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $mocks;
    }

    protected function getController(array $mocks)
    {
        return new IdentityController(
            $mocks['client'],
            $mocks['identityApi'],
            $mocks['identityTokenApi'],
            $mocks['identityLimitApi'],
            $mocks['brandRepository'],
            $mocks['userRepository']
        );
    }
}
