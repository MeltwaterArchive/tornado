<?php

namespace Test\Controller\PylonApi;

use Mockery;

use Symfony\Component\HttpFoundation\JsonResponse;

use DataSift\Http\Request;
use DataSift\Api\User as DataSiftUser;

use Tornado\Organization\Brand;
use Tornado\Project\Project;
use Tornado\Project\Recording\DataMapper as RecordingRepository;
use Tornado\Project\Recording;
use Tornado\Project\Workbook;

use Controller\PylonApi\PylonController;

/**
 * PylonControllerTest
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
 * @covers \Controller\PylonApi\PylonController
 */
class PylonControllerTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testValidate()
    {
        $mocks = $this->getMocks();
        $controller = $this->getController($mocks);

        $requestBody = ['csdl' => 'fb.content any "lotr,frodo"'];
        $request = $this->getApiRequest($requestBody);

        $mocks['datasiftUser']->shouldReceive('post')
            ->with('pylon/validate', $requestBody)
            ->once();

        $response = $controller->validate($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testCompile()
    {
        $mocks = $this->getMocks();
        $controller = $this->getController($mocks);

        $requestBody = ['csdl' => 'fb.content any "lotr,frodo"'];
        $request = $this->getApiRequest($requestBody);

        $mocks['pylon']->expects($this->once())
            ->method('compile')
            ->with($requestBody['csdl']);

        $response = $controller->compile($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testStop()
    {
        $mocks = $this->getMocks();

        // overwrite this one mock to alter the return value
        $mocks['datasiftUser'] = Mockery::mock(DataSiftUser::class);
        $mocks['datasiftUser']->shouldReceive('proxyResponse')
            ->andReturnUsing(function ($action) {
                $action();
                return new JsonResponse([], 204);
            });

        $requestBody = ['hash' => $mocks['hash']];
        $brand = Mockery::mock('\Tornado\Organization\Brand');
        $brand->shouldReceive('getId')->andReturn($mocks['brandId']);
        $request = $this->getApiRequest($requestBody, [], ['brand' => $brand]);

        $mocks['recordingRepository']->expects($this->once())
            ->method('findOne')
            ->with(['hash' => $requestBody['hash'], 'brand_id' => $mocks['brandId']])
            ->will($this->returnValue($mocks['recording']));

        $mocks['recordingRepository']->expects($this->once())
            ->method('update')
            ->with($mocks['recording']);

        $controller = $this->getController($mocks);
        $response = $controller->stop($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Recording::STATUS_STOPPED, $mocks['recording']->getStatus());
    }

    public function testStopError()
    {
        $mocks = $this->getMocks();

        // overwrite this one mock to alter the return value
        $mocks['datasiftUser'] = Mockery::mock(DataSiftUser::class);
        $mocks['datasiftUser']->shouldReceive('proxyResponse')
            ->andReturnUsing(function ($action) {
                $action();
                return new JsonResponse([], 409);
            });

        $requestBody = ['hash' => $mocks['hash']];
        $request = $this->getApiRequest($requestBody);

        $mocks['recordingRepository']->expects($this->never())
            ->method('findOne');

        $controller = $this->getController($mocks);
        $response = $controller->stop($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testStart()
    {
        $mocks = $this->getMocks();

        // overwrite this one mock to alter the return value
        $mocks['datasiftUser'] = Mockery::mock(DataSiftUser::class);
        $mocks['datasiftUser']->shouldReceive('proxyResponse')
            ->andReturnUsing(function ($action) {
                $action();
                return new JsonResponse([], 204);
            });

        $requestBody = ['hash' => $mocks['hash'], 'name' => 'Test Recording'];
        $brand = Mockery::mock('\Tornado\Organization\Brand');
        $brand->shouldReceive('getId')->andReturn($mocks['brandId']);
        $request = $this->getApiRequest($requestBody, [], ['brand' => $brand]);

        $mocks['recordingRepository']->expects($this->once())
            ->method('findOne')
            ->with(['hash' => $requestBody['hash'], 'brand_id' => $mocks['brandId']])
            ->will($this->returnValue($mocks['recording']));

        $mocks['recordingRepository']->expects($this->once())
            ->method('upsert')
            ->with($mocks['recording']);

        $controller = $this->getController($mocks);
        $response = $controller->start($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(Recording::STATUS_STARTED, $mocks['recording']->getStatus());
    }

    public function testStartError()
    {
        $mocks = $this->getMocks();

        // overwrite this one mock to alter the return value
        $mocks['datasiftUser'] = Mockery::mock(DataSiftUser::class);
        $mocks['datasiftUser']->shouldReceive('proxyResponse')
            ->andReturnUsing(function ($action) {
                $action();
                return new JsonResponse([], 409);
            });

        $requestBody = ['hash' => $mocks['hash']];
        $request = $this->getApiRequest($requestBody);

        $mocks['recordingRepository']->expects($this->never())
            ->method('findOne');

        $controller = $this->getController($mocks);
        $response = $controller->stop($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testGet()
    {
        $mocks = $this->getMocks();
        $mocks['pylon'] = Mockery::mock(\DataSift_Pylon::class);
        $mocks['pylon']->shouldReceive('get')
            ->once()
            ->with($mocks['datasiftUser'], $mocks['hash']);

        $controller = $this->getController($mocks);

        $query = ['hash' => $mocks['hash']];
        $request = $this->getApiRequest([], $query);

        $response = $controller->get($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testGetAllWithDefaultParams()
    {
        $mocks = $this->getMocks();
        $mocks['pylon'] = Mockery::mock(\DataSift_Pylon::class);
        $mocks['pylon']->shouldReceive('get')
            ->never();
        $mocks['pylon']->shouldReceive('getAll')
            ->once()
            ->with($mocks['datasiftUser'], 1, 20);

        $controller = $this->getController($mocks);

        $request = $this->getApiRequest([]);
        $response = $controller->get($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testGetAllWithCustomParams()
    {
        $mocks = $this->getMocks();
        $mocks['pylon'] = Mockery::mock(\DataSift_Pylon::class);
        $mocks['pylon']->shouldReceive('get')
            ->never();
        $mocks['pylon']->shouldReceive('getAll')
            ->once()
            ->with($mocks['datasiftUser'], 10, 5);

        $controller = $this->getController($mocks);

        $request = $this->getApiRequest([], ['page' => 10, 'per_page' => 5]);
        $response = $controller->get($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * Creates request test object
     *
     * @param array $requestBody
     * @param array $query
     *
     * @return \DataSift\Http\Request
     */
    protected function getApiRequest(array $requestBody, array $query = [], array $attributes = [])
    {
        return new Request(
            $query,
            [],
            $attributes,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestBody)
        );
    }

    /**
     * Creates test mocks
     *
     * @return array
     */
    protected function getMocks()
    {
        $mocks = [];

        $mocks['hash'] = '123123qwe';
        $mocks['brandId'] = 123;
        $mocks['recordingId'] = 333;

        $mocks['recording'] = new Recording();
        $mocks['recording']->setId($mocks['recordingId']);
        $mocks['recording']->setHash($mocks['hash']);
        $mocks['recording']->setBrandId($mocks['brandId']);

        $mocks['datasiftUser'] = Mockery::mock(DataSiftUser::class);
        $mocks['datasiftUser']->shouldReceive('proxyResponse')
            ->andReturnUsing(function ($action) {
                $action();
                return new JsonResponse();
            });

        $mocks['pylon'] = $this->getMockBuilder(\DataSift_Pylon::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mocks['recordingRepository'] = $this->getMockBuilder(RecordingRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $mocks;
    }

    /**
     * Gets Controller instance
     *
     * @param array $mocks
     *
     * @return \Controller\PylonApi\PylonController
     */
    protected function getController(array $mocks)
    {
        $controller = new PylonController(
            $mocks['datasiftUser'],
            $mocks['pylon'],
            $mocks['recordingRepository']
        );
        return $controller;
    }
}
