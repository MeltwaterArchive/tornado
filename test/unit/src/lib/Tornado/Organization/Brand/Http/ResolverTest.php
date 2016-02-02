<?php

namespace Test\Tornado\Organization\Brand\Http;

use Mockery;

use Symfony\Component\HttpFoundation\ParameterBag;

use Tornado\Organization\Brand;
use Tornado\Organization\Brand\Http\Resolver;
use Tornado\Organization\User;
use Tornado\Project\Project;
use Tornado\Project\Recording;
use Tornado\Project\Workbook;
use Tornado\Project\Worksheet;

/**
 * ResolverTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Organization\Brand\Http
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Organization\Brand\Http\Resolver
 */
class ResolverTest extends \PHPUnit_Framework_TestCase
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
     * @covers ::resolve
     */
    public function testResolveUnlessSessionUserExists()
    {
        $mocks = $this->getMocks();
        $mocks['sessionUser'] = null;

        $resolver = $this->getResolver($mocks);

        $result = $resolver->resolve($mocks['request']);

        $this->assertNull($result);
    }

    /**
     * @covers ::__construct
     * @covers ::resolve
     */
    public function testSkipResolvingUnlessResolverAttributeSet()
    {
        $mocks = $this->getMocks();
        $mocks['request']->attributes = new ParameterBag([]);

        $resolver = $this->getResolver($mocks);

        $result = $resolver->resolve($mocks['request']);

        $this->assertNull($result);
    }

    /**
     * @covers ::__construct
     * @covers ::resolve
     */
    public function testResolveBrandWhenBrandIdGiven()
    {
        $mocks = $this->getMocks();
        $mocks['request']->attributes = new ParameterBag([
            Resolver::RESOLVER_ATTRIBUTE => 'brandId',
            'brandId' => 1
        ]);
        $mocks['brandRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => 1])
            ->andReturn($mocks['brands'][0]);

        $resolver = $this->getResolver($mocks);

        $result = $resolver->resolve($mocks['request']);

        $this->assertInstanceOf('\Tornado\Organization\Brand', $result);
        $this->assertEquals(1, $result->getId());
    }

    public function resolveByProjectIdProvider()
    {
        return [
            'Brand found' => [
                'brand' => new Brand()
            ],
            'Brand not found' => [
                'brand' => null
            ]
        ];
    }

    /**
     * @dataProvider resolveByProjectIdProvider
     *
     * @covers ::__construct
     * @covers ::resolve
     * @covers ::resolveByProjectId
     */
    public function testResolveBrandWhenProjectIdGiven(Brand $expected = null)
    {
        $mocks = $this->getMocks();
        $mocks['request']->attributes = new ParameterBag([
            Resolver::RESOLVER_ATTRIBUTE => 'projectId',
            'projectId' => 20
        ]);
        $project = new Project();
        $project->setId(20);
        $project->setBrandId(1);
        $mocks['projectRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => 20])
            ->andReturn($project);
        $mocks['brandRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => 1])
            ->andReturn($expected);

        $resolver = $this->getResolver($mocks);

        $brand = $resolver->resolve($mocks['request']);

        $this->assertEquals($expected, $brand);
    }

    /**
     * @covers ::__construct
     * @covers ::resolve
     */
    public function testResolveBrandWhenRecordingIdGiven()
    {
        $mocks = $this->getMocks();
        $mocks['request']->attributes = new ParameterBag([
            Resolver::RESOLVER_ATTRIBUTE => 'recordingId',
            'recordingId' => 20
        ]);
        $project = new Recording();
        $project->setId(20);
        $project->setBrandId(1);
        $mocks['recordingRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => 20])
            ->andReturn($project);
        $mocks['brandRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => 1])
            ->andReturn($mocks['brands'][0]);

        $resolver = $this->getResolver($mocks);

        $result = $resolver->resolve($mocks['request']);

        $this->assertInstanceOf('\Tornado\Organization\Brand', $result);
        $this->assertEquals(1, $result->getId());
    }

    /**
     * @covers ::__construct
     * @covers ::resolve
     * @covers ::normalizePostParams
     * @covers ::resolveByProjectId
     * @covers ::resolveByWorkbookId
     */
    public function testResolveBrandWhenWorkbookIdIdGiven()
    {
        $mocks = $this->getMocks();
        $mocks['request']->attributes = new ParameterBag([
            Resolver::RESOLVER_ATTRIBUTE => 'workbookId'
        ]);
        $mocks['request']->shouldReceive('getMethod')
            ->once()
            ->withNoArgs()
            ->andReturn('POST');
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn(['workbook_id' => 20]);
        $workbook = new Workbook();
        $workbook->setId(20);
        $workbook->setProjectId(10);
        $mocks['workbookRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => 20])
            ->andReturn($workbook);
        $project = new Project();
        $project->setId(10);
        $project->setBrandId(1);
        $mocks['projectRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => 10])
            ->andReturn($project);
        $mocks['brandRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => 1])
            ->andReturn($mocks['brands'][0]);

        $resolver = $this->getResolver($mocks);

        $result = $resolver->resolve($mocks['request']);

        $this->assertInstanceOf('\Tornado\Organization\Brand', $result);
        $this->assertEquals(1, $result->getId());
    }

    /**
     * @covers ::__construct
     * @covers ::resolve
     * @covers ::normalizePostParams
     * @covers ::resolveByProjectId
     * @covers ::resolveByWorkbookId
     */
    public function testResolveBrandWhenWorksheetIdIdGiven()
    {
        $mocks = $this->getMocks();
        $mocks['request']->attributes = new ParameterBag([
            Resolver::RESOLVER_ATTRIBUTE => 'worksheetId'
        ]);
        $mocks['request']->shouldReceive('getMethod')
            ->once()
            ->withNoArgs()
            ->andReturn('POST');
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn(['worksheet_id' => 30]);
        $worksheet = new Worksheet();
        $worksheet->setId(30);
        $worksheet->setWorkbookId(20);
        $mocks['worksheetRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => 30])
            ->andReturn($worksheet);
        $workbook = new Workbook();
        $workbook->setId(20);
        $workbook->setProjectId(10);
        $mocks['workbookRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => 20])
            ->andReturn($workbook);
        $project = new Project();
        $project->setId(10);
        $project->setBrandId(1);
        $mocks['projectRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => 10])
            ->andReturn($project);
        $mocks['brandRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => 1])
            ->andReturn($mocks['brands'][0]);

        $resolver = $this->getResolver($mocks);

        $result = $resolver->resolve($mocks['request']);

        $this->assertInstanceOf('\Tornado\Organization\Brand', $result);
        $this->assertEquals(1, $result->getId());
    }

    /**
     * @covers ::__construct
     * @covers ::resolve
     */
    public function testResolveBrandByBrandIdWhenAllOptionsExists()
    {
        $mocks = $this->getMocks();
        $mocks['request']->attributes = new ParameterBag([
            Resolver::RESOLVER_ATTRIBUTE => 'brandId',
            'brandId' => 1,
            'projectId' => 20,
            'recordingId' => 20,
            'workbookId' => 30,
            'worksheetId' => 40
        ]);
        $mocks['projectRepository']->shouldReceive('findOne')
            ->never();
        $mocks['recordingRepository']->shouldReceive('findOne')
            ->never();
        $mocks['workbookRepository']->shouldReceive('findOne')
            ->never();
        $mocks['worksheetRepository']->shouldReceive('findOne')
            ->never();
        $mocks['brandRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => 1])
            ->andReturn($mocks['brands'][0]);

        $resolver = $this->getResolver($mocks);

        $result = $resolver->resolve($mocks['request']);

        $this->assertInstanceOf('\Tornado\Organization\Brand', $result);
        $this->assertEquals(1, $result->getId());
    }

    /**
     * @covers ::__construct
     * @covers ::resolve
     * @covers ::normalizePostParams
     */
    public function testResolveBrandByBrandIdWhenAllOptionsExistsForPostRequest()
    {
        $mocks = $this->getMocks();
        $mocks['request']->attributes = new ParameterBag([
            Resolver::RESOLVER_ATTRIBUTE => 'brandId'
        ]);
        $mocks['request']->shouldReceive('getMethod')
            ->once()
            ->withNoArgs()
            ->andReturn('POST');
        $mocks['request']->shouldReceive('getPostParams')
            ->once()
            ->withNoArgs()
            ->andReturn([
                'brand_id' => 1,
                'project_id' => 20,
                'recording_id' => 20,
                'workbook_id' => 30,
                'worksheet_id' => 40
            ]);

        $mocks['projectRepository']->shouldReceive('findOne')
            ->never();
        $mocks['recordingRepository']->shouldReceive('findOne')
            ->never();
        $mocks['workbookRepository']->shouldReceive('findOne')
            ->never();
        $mocks['worksheetRepository']->shouldReceive('findOne')
            ->never();
        $mocks['brandRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => 1])
            ->andReturn($mocks['brands'][0]);

        $resolver = $this->getResolver($mocks);

        $result = $resolver->resolve($mocks['request']);

        $this->assertInstanceOf('\Tornado\Organization\Brand', $result);
        $this->assertEquals(1, $result->getId());
    }

    /**
     * @param array $mocks
     *
     * @return \Tornado\Organization\Brand\Http\Resolver
     */
    protected function getResolver(array $mocks)
    {
        return new Resolver(
            $mocks['brandRepository'],
            $mocks['projectRepository'],
            $mocks['recordingRepository'],
            $mocks['workbookRepository'],
            $mocks['worksheetRepository'],
            $mocks['sessionUser']
        );
    }

    /**
     * Creates test mocks
     *
     * @return array
     */
    protected function getMocks()
    {
        $request = Mockery::mock('\DataSift\Http\Request', [
            'getMethod' => 'GET'
        ]);

        $params = [];
        $request->attributes = new ParameterBag($params);

        $brands = [];
        for ($i = 1; $i < 5; $i++) {
            $brand = new Brand();
            $brand->setId($i);

            $brands[] = $brand;
        }

        return [
            'sessionUser' => new User(),
            'brandRepository' => Mockery::mock('\Tornado\Organization\Brand\DataMapper'),
            'projectRepository' => Mockery::mock('\Tornado\Project\Project\DataMapper'),
            'recordingRepository' => Mockery::mock('\Tornado\Project\Recording\DataMapper'),
            'workbookRepository' => Mockery::mock('\Tornado\Project\Workbook\DataMapper'),
            'worksheetRepository' => Mockery::mock('\Tornado\Project\Worksheet\DataMapper'),
            'request' => $request,
            'params' => $params,
            'brands' => $brands
        ];
    }
}
