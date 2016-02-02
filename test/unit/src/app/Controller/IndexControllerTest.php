<?php

namespace Test\Controller;

use Mockery;

use Controller\IndexController;

/**
 * IndexControllerTest
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
 * @coversDefaultClass \Controller\IndexController
 */
class IndexControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers ::index
     */
    public function testIndex()
    {
        $projects = [];
        for ($i = 1; $i <= 5; $i++) {
            $projects[] = Mockery::mock('Tornado\DataMapper\DataObjectInterface', [
                'getPrimaryKey' => $i,
                'toArray' => ['id' => $i]
            ]);
        }
        $brands = [];
        for ($i = 1; $i <= 5; $i++) {
            $brands[] = Mockery::mock('Tornado\DataMapper\DataObjectInterface', [
                'getId' => $i,
                'getPrimaryKey' => $i,
                'toArray' => ['id' => $i]
            ]);
        }
        $brands[0]->projects = $projects;

        $user = Mockery::mock('\Tornado\Organization\User', [
            'getId' => 1
        ]);
        $session = Mockery::mock('\Symfony\Component\HttpFoundation\Session\SessionInterface');
        $session->shouldReceive('get')
            ->once()
            ->with('user')
            ->andReturn($user);
        $brandRepo = Mockery::mock('Tornado\DataMapper\DataMapperInterface');
        $brandRepo->shouldReceive('findUserAssigned')
            ->once()
            ->with($user)
            ->andReturn($brands);
        $projectsRepo = Mockery::mock('Tornado\Project\Project\DataMapper');
        $projectsRepo->shouldReceive('count')
            ->once()
            ->with(['brand_id' => 1])
            ->andReturn(count($projects));
        $projectsRepo->shouldReceive('find')
            ->once()
            ->with(['brand_id' => 1], ['name' => 'desc'], 5, 0)
            ->andReturn($projects);
        $request = Mockery::mock('\DataSift\Http\Request');
        $request->shouldReceive('get')
            ->once()
            ->with('page', 1)
            ->andReturn(1);
        $request->shouldReceive('get')
            ->once()
            ->with('sort', 'name')
            ->andReturn('name');
        $request->shouldReceive('get')
            ->once()
            ->with('perPage', 5)
            ->andReturn(5);
        $request->shouldReceive('get')
            ->once()
            ->with('order', 'asc')
            ->andReturn('desc');

        $controller = new IndexController($session, $brandRepo, $projectsRepo);

        $result = $controller->index($request);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
        $resultData = $result->getData();

        $this->assertInternalType('array', $resultData);
        $this->assertArrayHasKey('brands', $resultData);
        $this->assertArrayHasKey('selectedBrand', $resultData);

        $this->assertEquals($brands, $resultData['brands']);
        $this->assertEquals($brands[0], $resultData['selectedBrand']);
        $this->assertEquals($projects, $resultData['brands'][0]->projects);

        $meta = $result->getMeta();
        $this->assertArrayHasKey('pagination', $meta);
        $this->assertArrayHasKey('brands', $meta);
        $this->assertArrayHasKey('projects', $meta);
        $this->assertEquals(count($brands), $meta['brands']['count']);
        $this->assertEquals(count($projects), $meta['projects']['count']);

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
}
