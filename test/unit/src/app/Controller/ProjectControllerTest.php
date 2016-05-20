<?php

namespace Test\Controller;

use Mockery;

use Controller\ProjectController;
use Test\DataSift\ReflectionAccess;

/**
 * ProjectControllerTest
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
 * @coversDefaultClass \Controller\ProjectController
 */
class ProjectControllerTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess;

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }

    public function testIsProjectDataAware()
    {
        $controller = $this->getController($this->getMocks());
        $this->assertInstanceOf('Tornado\Controller\ProjectDataAwareInterface', $controller);
    }

    /**
     * @covers ::__construct
     * @covers ::create
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testCreateBrandNotFound()
    {
        $mocks = $this->getMocks();

        $mocks['brandRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['brandId']])
            ->andReturn(null)
            ->once();

        $controller = $this->getController($mocks);

        $controller->create($mocks['request'], $mocks['brandId']);
    }

    /**
     * @covers ::__construct
     * @covers ::create
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testCreateThrowExceptionUnlessAccessIsGranted()
    {
        $mocks = $this->getMocks();

        $mocks['brandRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['brandId']])
            ->andReturn($mocks['brand'])
            ->once();

        $mocks['authManager'] = Mockery::mock('Tornado\Security\Authorization\AccessDecisionManagerInterface');
        $mocks['authManager']->shouldReceive('isGranted')
            ->with($mocks['brand'])
            ->andReturn(false)
            ->once();

        $controller = $this->getController($mocks);

        $controller->create($mocks['request'], $mocks['brandId']);
    }

    /**
     * @covers ::__construct
     * @covers ::create
     */
    public function testCreateHttpGetToReturnTemplate()
    {
        $mocks = $this->getMocks();

        $mocks['brandRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['brandId']])
            ->andReturn($mocks['brand'])
            ->once();

        $mocks['brandRepo']->shouldReceive('findUserAssigned')
            ->with($mocks['user'])
            ->andReturn($mocks['brands'])
            ->once();

        $mocks['request']->shouldReceive('getMethod')
            ->once()
            ->withNoArgs()
            ->andReturn('GET');

        $controller = $this->getController($mocks);

        $result = $controller->create($mocks['request'], $mocks['brandId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        $resultData = $result->getData();
        $this->assertEquals(200, $result->getHttpCode());
        $this->assertInternalType('array', $resultData);

        $this->assertArrayHasKey('selectedBrand', $resultData);
        $this->assertSame($mocks['brands'][0], $resultData['selectedBrand']);

        $this->assertArrayHasKey('brands', $resultData);
        $this->assertEquals($mocks['brands'], $resultData['brands']);
    }

    /**
     * @covers ::__construct
     * @covers ::create
     */
    public function testCreateUnlessInvalidDataGiven()
    {
        $mocks = $this->getMocks();

        $mocks['url'] = sprintf('/projects/%s/create-workbook', $mocks['brandId'], $mocks['projectId']);

        $mocks['brandRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['brandId']])
            ->andReturn($mocks['brand'])
            ->once();

        $mocks['brandRepo']->shouldReceive('findUserAssigned')
            ->with($mocks['user'])
            ->andReturn($mocks['brands'])
            ->once();

        $mocks['request']->shouldReceive('getMethod')
            ->once()
            ->withNoArgs()
            ->andReturn('POST');

        $mocks['projectName'] = 'testProject';
        $mocks['project']->shouldReceive('getName')
            ->andReturn($mocks['projectName']);

        $mocks['urlGenerator']->shouldReceive('generate')
            ->with('project.get', ['projectId' => $mocks['projectId']])
            ->once()
            ->andReturn($mocks['url']);

        $mocks['projectRepo']->shouldReceive('create')
            ->with($mocks['project'])
            ->once()
            ->andReturn(null);

        $mocks['createForm']->shouldReceive('submit')
            ->once()
            ->with(['name' => $mocks['projectName'], 'brand_id' => $mocks['brandId']])
            ->andReturnNull();
        $mocks['createForm']->shouldReceive('isValid')
            ->once()
            ->withNoArgs()
            ->andReturn(true);
        $mocks['createForm']->shouldReceive('getData')
            ->once()
            ->withNoArgs()
            ->andReturn($mocks['project']);
        $mocks['createForm']->shouldReceive('getErrors')
            ->never();

        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn(['name' => $mocks['projectName']]);

        $controller = $this->getController($mocks);

        $result = $controller->create($mocks['request'], $mocks['brandId']);

        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $result);
        $this->assertEquals($mocks['url'], $result->getTargetUrl());
    }

    /**
     * @covers ::__construct
     * @covers ::create
     */
    public function testNotCreateDueToInvalidDataGiven()
    {
        $mocks = $this->getMocks();
        $mocks['authManager']->shouldReceive('isGranted')
            ->once()
            ->with($mocks['brands'][0])
            ->andReturn(true);
        $mocks['urlGenerator']->shouldReceive('generate')
            ->never();
        $mocks['projectRepo']->shouldReceive('create')
            ->never($mocks['project']);
        $mocks['brandRepo']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['brandId']])
            ->andReturn($mocks['brands'][0]);
        $mocks['brandRepo']->shouldReceive('findUserAssigned')
            ->once()
            ->with($mocks['user'])
            ->andReturn($mocks['brands']);
        $mocks['createForm']->shouldReceive('submit')
            ->once()
            ->with(['name' => $mocks['projectName'], 'brand_id' => $mocks['brandId']])
            ->andReturnNull();
        $mocks['createForm']->shouldReceive('isValid')
            ->once()
            ->withNoArgs()
            ->andReturn(false);
        $mocks['createForm']->shouldReceive('getErrors')
            ->once()
            ->andReturn(['name' => 'Invalid name.']);
        $mocks['createForm']->shouldReceive('getData')
            ->never();
        $mocks['request']->shouldReceive('getMethod')
            ->once()
            ->withNoArgs()
            ->andReturn('POST');
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn(['name' => $mocks['projectName']]);

        $controller = $this->getController($mocks);

        $result = $controller->create($mocks['request'], $mocks['brandId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        $resultData = $result->getData();
        $this->assertEquals(400, $result->getHttpCode());
        $this->assertInternalType('array', $resultData);

        $this->assertArrayHasKey('selectedBrand', $resultData);
        $this->assertSame($mocks['brands'][0], $resultData['selectedBrand']);

        $this->assertArrayHasKey('brands', $resultData);
        $this->assertEquals($mocks['brands'], $resultData['brands']);

        $responseMeta = $result->getMeta();
        $this->assertInternalType('array', $responseMeta);

        $this->assertArrayHasKey('name', $responseMeta);
        $this->assertEquals('Invalid name.', $responseMeta['name']);
    }

    /**
     * @covers ::__construct
     * @covers ::update
     */
    public function testUpdateForReturnTemplate()
    {
        $mocks = $this->getMocks();

        $mocks['urlGenerator']->shouldReceive('generate')
            ->never();
        $mocks['brandRepo']->shouldReceive('findOneByProject')
            ->once()
            ->with($mocks['project'])
            ->andReturn($mocks['brands'][0]);
        $mocks['brandRepo']->shouldReceive('findUserAssigned')
            ->once()
            ->with($mocks['user'])
            ->andReturn($mocks['brands']);
        $mocks['updateForm']->shouldReceive('submit')
            ->never();
        $mocks['request']->shouldReceive('getMethod')
            ->once()
            ->withNoArgs()
            ->andReturn('GET');

        $controller = $this->getController($mocks);

        $result = $controller->update($mocks['request'], $mocks['projectId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        $resultData = $result->getData();
        $this->assertEquals(200, $result->getHttpCode());
        $this->assertInternalType('array', $resultData);

        $this->assertArrayHasKey('selectedBrand', $resultData);
        $this->assertSame($mocks['brands'][0], $resultData['selectedBrand']);

        $this->assertArrayHasKey('brands', $resultData);
        $this->assertEquals($mocks['brands'], $resultData['brands']);
    }

    /**
     * @covers ::__construct
     * @covers ::update
     * @covers \Tornado\Controller\ProjectDataAwareTrait
     */
    public function testUpdateUnlessInvalidDataGiven()
    {
        $mocks = $this->getMocks();
        $mocks['urlGenerator']->shouldReceive('generate')
            ->never();
        $mocks['projectRepo']->shouldReceive('update')
            ->once()
            ->with($mocks['project'])
            ->andReturnNull();
        $mocks['brandRepo']->shouldReceive('findOneByProject')
            ->once()
            ->with($mocks['project'])
            ->andReturn($mocks['brands'][0]);
        $mocks['brandRepo']->shouldReceive('findUserAssigned')
            ->once()
            ->with($mocks['user'])
            ->andReturn($mocks['brands']);
        $mocks['updateForm']->shouldReceive('submit')
            ->once()
            ->with(['brand_id' => $mocks['brandId'], 'name' => $mocks['projectName']], $mocks['project'])
            ->andReturnNull();
        $mocks['updateForm']->shouldReceive('isValid')
            ->once()
            ->withNoArgs()
            ->andReturn(true);
        $mocks['updateForm']->shouldReceive('getData')
            ->once()
            ->withNoArgs()
            ->andReturn($mocks['project']);
        $mocks['request']->shouldReceive('getMethod')
            ->once()
            ->withNoArgs()
            ->andReturn('POST');
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn(['name' => $mocks['projectName']]);

        $redirectUrl = 'testUrl';

        $mocks['urlGenerator']->shouldReceive('generate')
            ->with('project.update', ['projectId' => $mocks['projectId']])
            ->andReturn($redirectUrl);

        $controller = $this->getController($mocks);

        $result = $controller->update($mocks['request'], $mocks['projectId']);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $result);
        $this->assertEquals($redirectUrl, $result->getTargetUrl());
    }

    /**
     * @covers ::__construct
     * @covers ::update
     * @covers \Tornado\Controller\ProjectDataAwareTrait
     */
    public function testNotUpdateDueToInvalidDataGiven()
    {
        $mocks = $this->getMocks();
        $mocks['urlGenerator']->shouldReceive('generate')
            ->never();
        $mocks['projectRepo']->shouldReceive('update')
            ->never();
        $mocks['brandRepo']->shouldReceive('findOneByProject')
            ->once()
            ->with($mocks['project'])
            ->andReturn($mocks['brands'][0]);
        $mocks['brandRepo']->shouldReceive('findUserAssigned')
            ->once()
            ->with($mocks['user'])
            ->andReturn($mocks['brands']);
        $mocks['updateForm']->shouldReceive('submit')
            ->once()
            ->with(['brand_id' => $mocks['brandId'], 'name' => $mocks['projectName']], $mocks['project'])
            ->andReturnNull();
        $mocks['updateForm']->shouldReceive('isValid')
            ->once()
            ->withNoArgs()
            ->andReturn(false);
        $mocks['updateForm']->shouldReceive('getData')
            ->never();
        $mocks['updateForm']->shouldReceive('getErrors')
            ->once()
            ->andReturn(['name' => 'Invalid name.']);
        $mocks['request']->shouldReceive('getMethod')
            ->once()
            ->withNoArgs()
            ->andReturn('POST');
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn(['name' => $mocks['projectName']]);

        $controller = $this->getController($mocks);
        $result = $controller->update($mocks['request'], $mocks['projectId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        $resultData = $result->getData();
        $this->assertEquals(400, $result->getHttpCode());
        $this->assertInternalType('array', $resultData);

        $this->assertArrayHasKey('selectedBrand', $resultData);
        $this->assertSame($mocks['brands'][0], $resultData['selectedBrand']);

        $this->assertArrayHasKey('brands', $resultData);
        $this->assertEquals($mocks['brands'], $resultData['brands']);

        $responseMeta = $result->getMeta();
        $this->assertInternalType('array', $responseMeta);

        $this->assertArrayHasKey('name', $responseMeta);
        $this->assertEquals('Invalid name.', $responseMeta['name']);
    }

    /**
     * @covers ::__construct
     * @covers ::delete
     * @covers \Tornado\Controller\ProjectDataAwareTrait
     */
    public function testDelete()
    {
        $mocks = $this->getMocks();
        $mocks['urlGenerator']->shouldReceive('generate')
            ->once()
            ->with('brand.get', ['brandId' => $mocks['brandId']])
            ->andReturn(sprintf('/brands/%s', $mocks['brandId']));
        $mocks['projectRepo']->shouldReceive('delete')
            ->once()
            ->with($mocks['project'])
            ->andReturn(1);

        $controller = $this->getController($mocks);

        $result = $controller->delete($mocks['projectId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        $resultMeta = $result->getMeta();
        $this->assertEquals(200, $result->getHttpCode());
        $this->assertInternalType('array', $resultMeta);

        $this->arrayHasKey('redirect_uri', $resultMeta);
        $this->assertEquals('/brands/' . $mocks['brandId'], $resultMeta['redirect_uri']);
    }

    /**
     * @covers ::batch
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function testThrowExceptionUnlessSupportedBatchActionGiven()
    {
        $mocks = $this->getMocks();
        $params = [
            'action' => 'noSupported'
        ];
        $mocks['brandRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['brandId']])
            ->andReturn($mocks['brand'])
            ->once();
        $mocks['authManager']->shouldReceive('isGranted')
            ->with($mocks['brand'])
            ->andReturn(true)
            ->once();
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn($params);

        $controller = $this->getController($mocks);

        $controller->batch($mocks['request'], $mocks['brandId']);
    }

    /**
     * @covers ::batch
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function testThrowExceptionUnlessActionGiven()
    {
        $mocks = $this->getMocks();
        $params = [
            'ids' => [1,2]
        ];
        $mocks['brandRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['brandId']])
            ->andReturn($mocks['brand'])
            ->once();
        $mocks['authManager']->shouldReceive('isGranted')
            ->with($mocks['brand'])
            ->andReturn(true)
            ->once();
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn($params);

        $controller = $this->getController($mocks);

        $controller->batch($mocks['request'], $mocks['brandId']);
    }

    /**
     * @covers ::batch
     */
    public function testReturnRedirectResponseUnlessArrayOfIdsGiven()
    {
        $mocks = $this->getMocks();

        $mocks['brandRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['brandId']])
            ->andReturn($mocks['brand']);
        $mocks['authManager']->shouldReceive('isGranted')
            ->with($mocks['brand'])
            ->andReturn(true);
        $mocks['urlGenerator']->shouldReceive('generate')
            ->with('brand.get', ['brandId' => $mocks['brandId']])
            ->andReturn('/brands/' . $mocks['brandId']);

        // no ids
        $params = [
            'action' => 'delete'
        ];
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn($params);
        $controller = $this->getController($mocks);
        $result = $controller->batch($mocks['request'], $mocks['brandId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        $resultMeta = $result->getMeta();
        $this->assertEquals(400, $result->getHttpCode());
        $this->assertInternalType('array', $resultMeta);

        $this->arrayHasKey('redirect_uri', $resultMeta);
        $this->assertEquals('/brands/' . $mocks['brandId'], $resultMeta['redirect_uri']);

        // ids as string
        $params = [
            'ids' => 'string',
            'action' => 'delete'
        ];
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn($params);

        $controller = $this->getController($mocks);
        $result = $controller->batch($mocks['request'], $mocks['brandId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        $resultMeta = $result->getMeta();
        $this->assertEquals(400, $result->getHttpCode());
        $this->assertInternalType('array', $resultMeta);

        $this->arrayHasKey('redirect_uri', $resultMeta);
        $this->assertEquals('/brands/' . $mocks['brandId'], $resultMeta['redirect_uri']);

        // ids as empty array
        $params = [
            'ids' => [],
            'action' => 'delete'
        ];
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn($params);

        $controller = $this->getController($mocks);
        $result = $controller->batch($mocks['request'], $mocks['brandId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        $resultMeta = $result->getMeta();
        $this->assertEquals(400, $result->getHttpCode());
        $this->assertInternalType('array', $resultMeta);

        $this->arrayHasKey('redirect_uri', $resultMeta);
        $this->assertEquals('/brands/' . $mocks['brandId'], $resultMeta['redirect_uri']);
    }

    /**
     * @covers ::batch
     */
    public function testBatch()
    {
        $mocks = $this->getMocks();

        $mocks['brandRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['brandId']])
            ->andReturn($mocks['brand']);
        $mocks['authManager']->shouldReceive('isGranted')
            ->with($mocks['brand'])
            ->andReturn(true);
        $mocks['urlGenerator']->shouldReceive('generate')
            ->with('brand.get', ['brandId' => $mocks['brandId']])
            ->andReturn('/brands/' . $mocks['brandId']);
        $params = [
            'ids' => [1,2],
            'action' => 'delete'
        ];
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn($params);
        $mocks['projectRepo']->shouldReceive('deleteProjectsByBrand')
            ->once()
            ->with($mocks['brand'], [1,2])
            ->andReturn(2);

        $controller = $this->getController($mocks);
        $result = $controller->batch($mocks['request'], $mocks['brandId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        $resultMeta = $result->getMeta();
        $this->assertEquals(200, $result->getHttpCode());
        $this->assertInternalType('array', $resultMeta);

        $this->arrayHasKey('redirect_uri', $resultMeta);
        $this->assertEquals('/brands/' . $mocks['brandId'], $resultMeta['redirect_uri']);
    }

    /**
     * @covers ::getBrand
     */
    public function testGetBrand()
    {
        $mocks = $this->getMocks();

        $mocks['brandRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['brandId']])
            ->andReturn($mocks['brand'])
            ->once();
        $mocks['authManager']->shouldReceive('isGranted')
            ->with($mocks['brand'])
            ->andReturn(true)
            ->once();

        $controller = $this->getController($mocks);

        $result = $this->invokeMethod($controller, 'getBrand', [$mocks['brandId']]);

        $this->assertSame($mocks['brand'], $result);
    }

    /**
     * @covers ::getBrand
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testGetNotFoundBrand()
    {
        $mocks = $this->getMocks();

        $mocks['brandRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['brandId']])
            ->andReturn(null)
            ->once();
        $mocks['authManager']->shouldReceive('isGranted')
            ->never();

        $controller = $this->getController($mocks);

        $this->invokeMethod($controller, 'getBrand', [$mocks['brandId']]);
    }

    /**
     * @covers ::getBrand
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testGetDeniedBrand()
    {
        $mocks = $this->getMocks();

        $mocks['brandRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['brandId']])
            ->andReturn($mocks['brand'])
            ->once();
        $mocks['authManager']->shouldReceive('isGranted')
            ->with($mocks['brand'])
            ->andReturn(false)
            ->once();

        $controller = $this->getController($mocks);

        $this->invokeMethod($controller, 'getBrand', [$mocks['brandId']]);
    }

    /**
     * @return array
     */
    protected function getMocks()
    {
        $mocks = [];

        $mocks['projectId'] = 10;
        $mocks['brandId'] = 21;
        $mocks['projectName'] = 'test';

        $mocks['project'] = Mockery::mock('Tornado\Project\Project', [
            'getId' => $mocks['projectId'],
            'getPrimaryKey' => $mocks['projectId'],
            'getBrandId' => $mocks['brandId']
        ]);

        $mocks['brand'] = Mockery::mock('Tornado\Organization\Brand', [
            'getId' => $mocks['brandId'],
            'getPrimaryKey' => $mocks['brandId']
        ]);

        $mocks['brands'] = [$mocks['brand']];
        for ($i = 1; $i < 5; $i++) {
            $mocks['brands'][] = Mockery::mock('Tornado\Organization\Brand', [
                'getPrimaryKey' => $i,
                'getId' => $i
            ]);
        }

        $mocks['projectRepo'] = Mockery::mock('Tornado\Project\Project\DataMapper');
        $mocks['brandRepo'] = Mockery::mock('Tornado\Organization\Brand\DataMapper');
        $mocks['worksheetRepo'] = Mockery::mock('Tornado\Project\Worksheet\DataMapper');
        $mocks['session'] = Mockery::mock('Symfony\Component\HttpFoundation\Session\SessionInterface');
        $mocks['urlGenerator'] = Mockery::mock('Symfony\Component\Routing\Generator\UrlGenerator');
        $mocks['createForm'] = Mockery::mock('Tornado\Project\Project\Form\Create');
        $mocks['updateForm'] = Mockery::mock('Tornado\Project\Project\Form\Update');

        $mocks['authManager'] = Mockery::mock('Tornado\Security\Authorization\AccessDecisionManagerInterface', [
            'isGranted' => true
        ]);

        $mocks['user'] = Mockery::mock('Tornado\Organization\User');
        $mocks['session']->shouldReceive('get')
            ->with('user')
            ->andReturn($mocks['user']);

        $mocks['request'] = Mockery::mock('DataSift\Http\Request');

        return $mocks;
    }

    /**
     * @return ProjectController
     */
    protected function getController(array $mocks)
    {
        $controller = Mockery::mock(ProjectController::class, [
            $mocks['session'],
            $mocks['urlGenerator'],
            $mocks['brandRepo'],
            $mocks['createForm'],
            $mocks['updateForm']
        ])->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // mock ProjectDataAwareTrait methods
        $controller->shouldReceive('getProject')
            ->with($mocks['projectId'])
            ->andReturn($mocks['project']);

        $controller->setProjectRepository($mocks['projectRepo']);
        $controller->setAuthorizationManager($mocks['authManager']);
        $controller->setWorksheetRepository($mocks['worksheetRepo']);

        return $controller;
    }
}
