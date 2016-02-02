<?php

namespace Test\Controller;

use Mockery;

use Controller\BrandController;

/**
 * BrandControllerTest
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
 * @coversDefaultClass \Controller\BrandController
 */
class BrandControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::getBrand
     */
    public function testGet()
    {
        $mocks = $this->getMocks();
        $mocks['session']->shouldReceive('get')
            ->once()
            ->with('user')
            ->andReturn($mocks['user']);
        $mocks['accessDecisionManager']->shouldReceive('isGranted')
            ->once()
            ->with($mocks['brands'][0])
            ->andReturn(true);
        $mocks['brandRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['brandId']])
            ->andReturn($mocks['brands'][0]);
        $mocks['brandRepository']->shouldReceive('findUserAssigned')
            ->once()
            ->with($mocks['user'])
            ->andReturn($mocks['brands']);
        $mocks['projectRepository']->shouldReceive('count')
            ->once()
            ->with(['brand_id' => $mocks['brandId']])
            ->andReturn(count($mocks['projects']));
        $mocks['projectRepository']->shouldReceive('find')
            ->once()
            ->with(['brand_id' => $mocks['brandId']], ['name' => 'desc'], 5, 0)
            ->andReturn($mocks['projects']);
        $mocks['request']->shouldReceive('get')
            ->once()
            ->with('page', 1)
            ->andReturn(1);
        $mocks['request']->shouldReceive('get')
            ->once()
            ->with('sort', 'name')
            ->andReturn('name');
        $mocks['request']->shouldReceive('get')
            ->once()
            ->with('perPage', 5)
            ->andReturn(5);
        $mocks['request']->shouldReceive('get')
            ->once()
            ->with('order', 'asc')
            ->andReturn('desc');

        $controller = new BrandController(
            $mocks['session'],
            $mocks['accessDecisionManager'],
            $mocks['brandRepository'],
            $mocks['projectRepository'],
            $mocks['recordingRepository'],
            $mocks['datasiftRecording'],
            $mocks['pylon'],
            $mocks['url_generator']
        );

        $result = $controller->get($mocks['request'], $mocks['brandId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
        $resultData = $result->getData();

        $this->assertInternalType('array', $resultData);
        $this->assertArrayHasKey('selectedBrand', $resultData);
        $this->assertArrayHasKey('brands', $resultData);

        $this->assertEquals($mocks['brands'][0], $resultData['selectedBrand']);
        $this->assertEquals($mocks['brands'], $resultData['brands']);
        $this->assertEquals($mocks['projects'], $resultData['brands'][0]->projects);

        $meta = $result->getMeta();
        $this->assertArrayHasKey('pagination', $meta);
        $this->assertArrayHasKey('brands', $meta);
        $this->assertArrayHasKey('projects', $meta);
        $this->assertEquals(count($mocks['brands']), $meta['brands']['count']);
        $this->assertEquals(count($mocks['projects']), $meta['projects']['count']);

        $this->assertInstanceOf('\Tornado\DataMapper\Paginator', $meta['pagination']);
        $meta['pagination'] = $meta['pagination']->toArray();
        $this->assertArrayHasKey('firstPage', $meta['pagination']);
        $this->assertArrayHasKey('currentPage', $meta['pagination']);
        $this->assertArrayHasKey('totalPages', $meta['pagination']);
        $this->assertArrayHasKey('nextPage', $meta['pagination']);
        $this->assertArrayHasKey('previousPage', $meta['pagination']);
        $this->assertArrayHasKey('totalItemsCount', $meta['pagination']);
        $this->assertArrayHasKey('perPage', $meta['pagination']);
        $this->assertArrayHasKey('sortBy', $meta['pagination']);
        $this->assertArrayHasKey('order', $meta['pagination']);
    }

    /**
     * @covers ::__construct
     * @covers ::get
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testGetThrowExceptionUnlessBrandFound()
    {
        $mocks = $this->getMocks();

        $mocks['brandRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['brandId']])
            ->andReturn(null);
        $mocks['accessDecisionManager']->shouldReceive('isGranted')
            ->never();
        $request = Mockery::mock('\DataSift\Http\Request');
        $request->shouldReceive('get')
            ->never();

        $controller = new BrandController(
            $mocks['session'],
            $mocks['accessDecisionManager'],
            $mocks['brandRepository'],
            $mocks['projectRepository'],
            $mocks['recordingRepository'],
            $mocks['datasiftRecording'],
            $mocks['pylon'],
            $mocks['url_generator']
        );

        $controller->get($mocks['request'], $mocks['brandId']);
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::getBrand
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testGetThrowExceptionUnlessAccessIsGranted()
    {
        $mocks = $this->getMocks();
        $mocks['brandRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['brandId']])
            ->andReturn($mocks['brands'][0]);
        $mocks['brandRepository']->shouldReceive('findUserAssigned')
            ->never();
        $mocks['accessDecisionManager']->shouldReceive('isGranted')
            ->once()
            ->with($mocks['brands'][0])
            ->andReturn(false);
        $mocks['request']->shouldReceive('get')
            ->never();

        $controller = new BrandController(
            $mocks['session'],
            $mocks['accessDecisionManager'],
            $mocks['brandRepository'],
            $mocks['projectRepository'],
            $mocks['recordingRepository'],
            $mocks['datasiftRecording'],
            $mocks['pylon'],
            $mocks['url_generator']
        );

        $controller->get($mocks['request'], $mocks['brandId']);
    }

    /**
     * @covers ::__construct
     * @covers ::getRecordings
     * @covers ::getBrand
     */
    public function testGetRecordings()
    {
        $mocks = $this->getMocks();
        $mocks['session']->shouldReceive('get')
            ->once()
            ->with('user')
            ->andReturn($mocks['user']);
        $mocks['accessDecisionManager']->shouldReceive('isGranted')
            ->once()
            ->with($mocks['brands'][0])
            ->andReturn(true);
        $mocks['brandRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['brandId']])
            ->andReturn($mocks['brands'][0]);
        $mocks['brandRepository']->shouldReceive('findUserAssigned')
            ->once()
            ->with($mocks['user'])
            ->andReturn($mocks['brands']);
        $mocks['recordingRepository']->shouldReceive('count')
            ->once()
            ->with(['brand_id' => $mocks['brandId']])
            ->andReturn(count($mocks['projects']));
        $mocks['recordingRepository']->shouldReceive('find')
            ->once()
            ->with(['brand_id' => $mocks['brandId']], ['name' => 'desc'], 5, 0)
            ->andReturn($mocks['recordings']);
        $mocks['request']->shouldReceive('get')
            ->once()
            ->with('page', 1)
            ->andReturn(1);
        $mocks['request']->shouldReceive('get')
            ->once()
            ->with('sort', 'name')
            ->andReturn('name');
        $mocks['request']->shouldReceive('get')
            ->once()
            ->with('perPage', 5)
            ->andReturn(5);
        $mocks['request']->shouldReceive('get')
            ->once()
            ->with('order', 'asc')
            ->andReturn('desc');

        $mocks['datasiftRecording']
            ->shouldReceive('decorateRecordings')
            ->once()
            ->with($mocks['recordings'])
            ->andReturn($mocks['recordings']);

        $controller = new BrandController(
            $mocks['session'],
            $mocks['accessDecisionManager'],
            $mocks['brandRepository'],
            $mocks['projectRepository'],
            $mocks['recordingRepository'],
            $mocks['datasiftRecording'],
            $mocks['pylon'],
            $mocks['url_generator']
        );

        $result = $controller->getRecordings($mocks['request'], $mocks['brandId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
        $resultData = $result->getData();

        $this->assertInternalType('array', $resultData);
        $this->assertArrayHasKey('selectedBrand', $resultData);
        $this->assertArrayHasKey('brands', $resultData);

        $this->assertEquals($mocks['brands'][0], $resultData['selectedBrand']);
        $this->assertEquals($mocks['brands'], $resultData['brands']);
        $this->assertEquals($mocks['recordings'], $resultData['brands'][0]->recordings);

        $meta = $result->getMeta();
        $this->assertArrayHasKey('pagination', $meta);
        $this->assertArrayHasKey('brands', $meta);
        $this->assertArrayHasKey('recordings', $meta);
        $this->assertEquals(count($mocks['brands']), $meta['brands']['count']);
        $this->assertEquals(count($mocks['recordings']), $meta['recordings']['count']);

        $this->assertInstanceOf('\Tornado\DataMapper\Paginator', $meta['pagination']);
        $meta['pagination'] = $meta['pagination']->toArray();
        $this->assertArrayHasKey('firstPage', $meta['pagination']);
        $this->assertArrayHasKey('currentPage', $meta['pagination']);
        $this->assertArrayHasKey('totalPages', $meta['pagination']);
        $this->assertArrayHasKey('nextPage', $meta['pagination']);
        $this->assertArrayHasKey('previousPage', $meta['pagination']);
        $this->assertArrayHasKey('totalItemsCount', $meta['pagination']);
        $this->assertArrayHasKey('perPage', $meta['pagination']);
        $this->assertArrayHasKey('sortBy', $meta['pagination']);
        $this->assertArrayHasKey('order', $meta['pagination']);
    }

    /**
     * @covers ::__construct
     * @covers ::getRecordings
     * @covers ::getBrand
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testGetRecordingsThrowExceptionUnlessBrandFound()
    {
        $mocks = $this->getMocks();

        $mocks['brandRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['brandId']])
            ->andReturn(null);
        $mocks['accessDecisionManager']->shouldReceive('isGranted')
            ->never();
        $request = Mockery::mock('\DataSift\Http\Request');
        $request->shouldReceive('get')
            ->never();

        $controller = new BrandController(
            $mocks['session'],
            $mocks['accessDecisionManager'],
            $mocks['brandRepository'],
            $mocks['projectRepository'],
            $mocks['recordingRepository'],
            $mocks['datasiftRecording'],
            $mocks['pylon'],
            $mocks['url_generator']
        );

        $controller->getRecordings($mocks['request'], $mocks['brandId']);
    }

    /**
     * @covers ::__construct
     * @covers ::getRecordings
     * @covers ::getBrand
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testGetRecordingsThrowExceptionUnlessAccessIsGranted()
    {
        $mocks = $this->getMocks();
        $mocks['brandRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['brandId']])
            ->andReturn($mocks['brands'][0]);
        $mocks['brandRepository']->shouldReceive('findUserAssigned')
            ->never();
        $mocks['accessDecisionManager']->shouldReceive('isGranted')
            ->once()
            ->with($mocks['brands'][0])
            ->andReturn(false);
        $mocks['request']->shouldReceive('get')
            ->never();

        $controller = new BrandController(
            $mocks['session'],
            $mocks['accessDecisionManager'],
            $mocks['brandRepository'],
            $mocks['projectRepository'],
            $mocks['recordingRepository'],
            $mocks['datasiftRecording'],
            $mocks['pylon'],
            $mocks['url_generator']
        );

        $controller->getRecordings($mocks['request'], $mocks['brandId']);
    }

    /**
     * Provides mocks for the tests
     *
     * @return array
     */
    protected function getMocks()
    {
        $projects = [];
        $recordings = [];
        $brands = [];

        $brandId = 1;
        $userId = 1;
        for ($i = 1; $i <= 5; $i++) {
            $recordings[] = Mockery::mock('Tornado\Project\Recorings', [
                'getPrimaryKey' => $i,
                'getBrandId' => $brandId,
                'toArray' => ['id' => $i]
            ]);
        }
        for ($i = 1; $i <= 5; $i++) {
            $projects[] = Mockery::mock('Tornado\Project\Project', [
                'getPrimaryKey' => $i,
                'getBrandId' => $brandId,
                'toArray' => ['id' => $i]
            ]);
        }
        for ($i = 1; $i <= 5; $i++) {
            $brands[] = Mockery::mock('Tornado\Organization\Brand', [
                'getId' => $i,
                'getPrimaryKey' => $i,
                'toArray' => ['id' => $i]
            ]);
        }
        $brands[0]->projects = $projects;
        $brands[0]->recordings = $recordings;

        $user = Mockery::mock('\Tornado\Organization\User', [
            'getId' => $userId
        ]);

        $session = Mockery::mock('\Symfony\Component\HttpFoundation\Session\SessionInterface');
        $accessDecisionManager = Mockery::mock('\Tornado\Security\Authorization\AccessDecisionManagerInterface');
        $brandRepo = Mockery::mock('Tornado\Organization\Brand\DataMapper');
        $projectRepo = Mockery::mock('Tornado\Project\Project\DataMapper');
        $recordingRepo = Mockery::mock('Tornado\Project\Recording\DataMapper');
        $request = Mockery::mock('\DataSift\Http\Request');
        $dataSiftRecording = Mockery::mock('\Tornado\Project\Recording\DataSiftRecording');
        $pylon = Mockery::mock('\DataSift\Pylon\Pylon');
        $urlGenerator = Mockery::mock('\Symfony\Component\Routing\Generator\UrlGenerator');
        return [
            'brandId' => $brandId,
            'projects' => $projects,
            'recordings' => $recordings,
            'brands' => $brands,
            'userId' => $userId,
            'user' => $user,
            'session' => $session,
            'accessDecisionManager' => $accessDecisionManager,
            'brandRepository' => $brandRepo,
            'projectRepository' => $projectRepo,
            'recordingRepository' => $recordingRepo,
            'request' => $request,
            'datasiftRecording' => $dataSiftRecording,
            'pylon' => $pylon,
            'url_generator' => $urlGenerator
        ];
    }
}
