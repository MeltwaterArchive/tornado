<?php
namespace Test\Tornado\Controller;

use Mockery;

use Test\DataSift\ReflectionAccess;

/**
 * ProjectDataAwareTraitTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Controller
 * @author      Michał Pałys-Dudek
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers \Tornado\Controller\ProjectDataAwareTrait
 */
class ProjectDataAwareTraitTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess;

    public function tearDown()
    {
        Mockery::close();
    }

    public function testGetProject()
    {
        $mocks = $this->getMocks();

        $mocks['projectRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['projectId']])
            ->andReturn($mocks['project'])
            ->once();
        $mocks['authorizationManager']->shouldReceive('isGranted')
            ->with($mocks['project'])
            ->andReturn(true)
            ->once();

        $trait = $this->getTrait($mocks);

        $this->assertSame(
            $mocks['project'],
            $this->invokeMethod($trait, 'getProject', [$mocks['projectId']])
        );
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testGetNotFoundProject()
    {
        $mocks = $this->getMocks();

        $mocks['projectRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['projectId']])
            ->andReturn(null)
            ->once();

        $trait = $this->getTrait($mocks);

        $this->invokeMethod($trait, 'getProject', [$mocks['projectId']]);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testGetDeniedProject()
    {
        $mocks = $this->getMocks();

        $mocks['projectRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['projectId']])
            ->andReturn($mocks['project'])
            ->once();
        $mocks['authorizationManager']->shouldReceive('isGranted')
            ->with($mocks['project'])
            ->andReturn(false)
            ->once();

        $trait = $this->getTrait($mocks);

        $this->invokeMethod($trait, 'getProject', [$mocks['projectId']]);
    }

    public function testGetWorkbook()
    {
        $mocks = $this->getMocks();

        $mocks['workbookRepository']->shouldReceive('findOneByProject')
            ->with($mocks['workbookId'], $mocks['project'])
            ->andReturn($mocks['workbook'])
            ->once();

        $trait = $this->getTrait($mocks);

        $this->assertSame(
            $mocks['workbook'],
            $this->invokeMethod($trait, 'getWorkbook', [$mocks['project'], $mocks['workbookId']])
        );
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testGetNotFoundWorkbook()
    {
        $mocks = $this->getMocks();

        $mocks['workbookRepository']->shouldReceive('findOneByProject')
            ->with($mocks['workbookId'], $mocks['project'])
            ->andReturn(null)
            ->once();

        $trait = $this->getTrait($mocks);

        $this->invokeMethod($trait, 'getWorkbook', [$mocks['project'], $mocks['workbookId']]);
    }

    public function testGetWorkbookForWorksheet()
    {
        $mocks = $this->getMocks();

        $mocks['workbookRepository']->shouldReceive('findOneByWorksheet')
            ->with($mocks['worksheet'])
            ->andReturn($mocks['workbook'])
            ->once();

        $trait = $this->getTrait($mocks);

        $this->assertSame(
            $mocks['workbook'],
            $this->invokeMethod($trait, 'getWorkbookForWorksheet', [$mocks['worksheet']])
        );
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testNotFoundWorkbookForWorksheet()
    {
        $mocks = $this->getMocks();

        $mocks['workbookRepository']->shouldReceive('findOneByWorksheet')
            ->with($mocks['worksheet'])
            ->andReturn(null)
            ->once();

        $trait = $this->getTrait($mocks);

        $this->invokeMethod($trait, 'getWorkbookForWorksheet', [$mocks['worksheet']]);
    }

    public function testGetProjectDataForWorksheetId()
    {
        $mocks = $this->getMocks();

        $mocks['brandRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['brandId']])
            ->andReturn($mocks['brand'])
            ->once();

        $mocks['worksheetRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['worksheetId']])
            ->andReturn($mocks['worksheet'])
            ->once();

        $mocks['workbookRepository']->shouldReceive('findOneByWorksheet')
            ->with($mocks['worksheet'])
            ->andReturn($mocks['workbook'])
            ->once();

        $mocks['projectRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['projectId']])
            ->andReturn($mocks['project'])
            ->once();

        $trait = $this->getTrait($mocks);

        $this->assertEquals(
            [$mocks['project'], $mocks['workbook'], $mocks['worksheet'], $mocks['brand']],
            $this->invokeMethod($trait, 'getProjectDataForWorksheetId', [$mocks['worksheetId']])
        );
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testGetProjectDataForNotFoundWorksheetId()
    {
        $mocks = $this->getMocks();

        $mocks['worksheetRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['worksheetId']])
            ->andReturn(null)
            ->once();

        $trait = $this->getTrait($mocks);

        $this->invokeMethod($trait, 'getProjectDataForWorksheetId', [$mocks['worksheetId']]);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\ConflictHttpException
     */
    public function testGetProjectDataForWorksheetIdConflict()
    {
        $mocks = $this->getMocks();

        $mocks['worksheetRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['worksheetId']])
            ->andReturn($mocks['worksheet'])
            ->once();

        $mocks['workbookRepository']->shouldReceive('findOneByWorksheet')
            ->with($mocks['worksheet'])
            ->andReturn($mocks['workbook'])
            ->once();

        $mocks['projectRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['projectId']])
            ->andReturn($mocks['project'])
            ->once();

        $mocks['brandRepository']->shouldReceive('findOne')
            ->with(['id' => $mocks['brandId']])
            ->andReturn($mocks['brand'])
            ->once();

        $trait = $this->getTrait($mocks);

        $this->assertEquals(
            [$mocks['project'], $mocks['workbook'], $mocks['worksheet'], $mocks['brand']],
            $this->invokeMethod($trait, 'getProjectDataForWorksheetId', [$mocks['worksheetId'], $mocks['projectId']+1])
        );
    }

    protected function getMocks()
    {
        $brandId = 27;
        $brand = Mockery::mock('Tornado\Organization\Brand');

        $projectId = 23;
        $project = Mockery::mock('Tornado\Project\Project', [
            'getId' => $projectId,
            'getBrandId' => $brandId,
            'getPrimaryKey' => $projectId
        ]);

        $workbookId = 12;
        $workbook = Mockery::mock('Tornado\Project\Workbook', [
            'getId' => $workbookId,
            'getPrimaryKey' => $workbookId,
            'getProjectId' => $projectId
        ]);

        $worksheetId = 45;
        $worksheet = Mockery::mock('Tornado\Project\Worksheet', [
            'getId' => $worksheetId,
            'getPrimaryKey' => $worksheetId,
            'getWorkbookId' => $workbookId
        ]);

        $projectRepository = Mockery::mock('Tornado\Project\Project\DataMapper');
        $workbookRepository = Mockery::mock('Tornado\Project\Workbook\DataMapper');
        $worksheetRepository = Mockery::mock('Tornado\Project\Worksheet\DataMapper');
        $brandRepository = Mockery::mock('Tornado\Organization\Brand\DataMapper');

        $authorizationManager = Mockery::mock('Tornado\Security\Authorization\AccessDecisionManagerInterface', [
            'isGranted' => true
        ]);

        return [
            'brandId' => $brandId,
            'brand' => $brand,
            'projectId' => $projectId,
            'project' => $project,
            'workbookId' => $workbookId,
            'workbook' => $workbook,
            'worksheetId' => $worksheetId,
            'worksheet' => $worksheet,
            'projectRepository' => $projectRepository,
            'workbookRepository' => $workbookRepository,
            'worksheetRepository' => $worksheetRepository,
            'brandRepository' => $brandRepository,
            'authorizationManager' => $authorizationManager
        ];
    }

    protected function getTrait(array $mocks)
    {
        $trait = $this->getMockForTrait('Tornado\Controller\ProjectDataAwareTrait');
        $trait->setProjectRepository($mocks['projectRepository']);
        $trait->setWorkbookRepository($mocks['workbookRepository']);
        $trait->setWorksheetRepository($mocks['worksheetRepository']);
        $trait->setAuthorizationManager($mocks['authorizationManager']);
        $trait->setBrandRepository($mocks['brandRepository']);
        return $trait;
    }
}
