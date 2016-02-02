<?php

namespace Test\Controller\ProjectApp;

use Mockery;

use Controller\ProjectApp\ChartController;

use Tornado\Controller\Result;
use Tornado\Organization\User;
use Tornado\Project\Chart;
use Tornado\Project\Workbook;

/**
 * ChartControllerTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Controller\ProjectApp
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Controller\ProjectApp\ChartController
 */
class ChartControllerTest extends \PHPUnit_Framework_TestCase
{
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
     * @covers ::update
     * @covers ::isUserAllowedToEditWorkbook
     */
    public function testUpdate()
    {
        $mocks = $this->getMocks();

        $mocks['locker'] = Mockery::mock('\Tornado\Project\Workbook\Locker');
        $mocks['locker']->shouldReceive('isLocked')
            ->once()
            ->with($mocks['workbook'])
            ->andReturn(false);

        $mocks['chartRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['chartId']])
            ->andReturn($mocks['chart'])
            ->once();

        $postParams = ['name' => 'Updated Name'];
        $mocks['request']->shouldReceive('getPostParams')
            ->andReturn($postParams)
            ->once();

        $mocks['updateForm']->shouldReceive('submit')
            ->with($postParams, $mocks['chart'])
            ->once();
        $mocks['updateForm']->shouldReceive('isValid')
            ->andReturn(true)
            ->once();
        $mocks['updateForm']->shouldReceive('getData')
            ->andReturn($mocks['chart'])
            ->once();

        $mocks['chartRepo']->shouldReceive('update')
            ->with($mocks['chart'])
            ->once();

        $mocks['worksheetRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['worksheetId']])
            ->once()
            ->andReturn($mocks['worksheet']);
        $mocks['workbookRepo']->shouldReceive('findOneByWorksheet')
            ->with($mocks['worksheet'])
            ->once()
            ->andReturn($mocks['workbook']);

        $controller = $this->getController($mocks);

         $result = $controller->update($mocks['request'], $mocks['projectId'], $mocks['chartId']);

        $this->assertInstanceOf(Result::class, $result);
        $data = $result->getData();
        $this->assertArrayHasKey('chart', $data);
        $this->assertSame($mocks['chart'], $data['chart']);
        $this->assertEquals(200, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::update
     * @covers ::isUserAllowedToEditWorkbook
     */
    public function testUpdateUnlessWorkbookLockedByAnotherUser()
    {
        $mocks = $this->getMocks();

        $mocks['chartRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['chartId']])
            ->andReturn($mocks['chart'])
            ->once();
        $mocks['worksheetRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['worksheetId']])
            ->once()
            ->andReturn($mocks['worksheet']);
        $mocks['workbookRepo']->shouldReceive('findOneByWorksheet')
            ->with($mocks['worksheet'])
            ->once()
            ->andReturn($mocks['workbook']);
        $mocks['locker'] = Mockery::mock('\Tornado\Project\Workbook\Locker');
        $mocks['locker']->shouldReceive('isLocked')
            ->once()
            ->with($mocks['workbook'])
            ->andReturn(true);
        $mocks['locker']->shouldReceive('isGranted')
            ->once()
            ->with($mocks['workbook'], $mocks['sessionUser'])
            ->andReturn(false);
        $mocks['locker']->shouldReceive('getLockingUser')
            ->once()
            ->with()
            ->andReturn($mocks['lockingUser']);

        $controller = $this->getController($mocks);

        $result = $controller->update($mocks['request'], $mocks['projectId'], $mocks['chartId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEmpty($result->getData());

        $this->assertArrayHasKey('error', $result->getMeta());
        $this->assertEquals(403, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::update
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testUpdateNotFound()
    {
        $mocks = $this->getMocks();

        $mocks['chartRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['chartId']])
            ->andReturn(null)
            ->once();

        $controller = $this->getController($mocks);

        $controller->update($mocks['request'], $mocks['projectId'], $mocks['chartId']);
    }

    /**
     * @covers ::__construct
     * @covers ::update
     */
    public function testUpdateInvalidRequest()
    {
        $mocks = $this->getMocks();

        $mocks['chartRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['chartId']])
            ->andReturn($mocks['chart'])
            ->once();
        $mocks['worksheetRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['worksheetId']])
            ->once()
            ->andReturn($mocks['worksheet']);
        $mocks['workbookRepo']->shouldReceive('findOneByWorksheet')
            ->with($mocks['worksheet'])
            ->once()
            ->andReturn($mocks['workbook']);

        $postParams = ['name' => 'Updated Name'];
        $mocks['request']->shouldReceive('getPostParams')
            ->andReturn($postParams)
            ->once();

        $mocks['updateForm']->shouldReceive('submit')
            ->with($postParams, $mocks['chart'])
            ->once();
        $mocks['updateForm']->shouldReceive('isValid')
            ->andReturn(false)
            ->once();

        $errors = ['error1' => 'error', 'error2' => 'error'];
        $mocks['updateForm']->shouldReceive('getErrors')
            ->andReturn($errors)
            ->once();

        $controller = $this->getController($mocks);

        $result = $controller->update($mocks['request'], $mocks['projectId'], $mocks['chartId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEmpty($result->getData());
        $this->assertEquals($errors, $result->getMeta());
        $this->assertEquals(400, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::delete
     */
    public function testDelete()
    {
        $mocks = $this->getMocks();

        $mocks['chartRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['chartId']])
            ->andReturn($mocks['chart'])
            ->once();
        $mocks['worksheetRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['worksheetId']])
            ->once()
            ->andReturn($mocks['worksheet']);
        $mocks['workbookRepo']->shouldReceive('findOneByWorksheet')
            ->with($mocks['worksheet'])
            ->once()
            ->andReturn($mocks['workbook']);

        $mocks['chartRepo']->shouldReceive('delete')
            ->with($mocks['chart'])
            ->once();

        $controller = $this->getController($mocks);

        $result = $controller->delete($mocks['projectId'], $mocks['chartId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(200, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::delete
     * @covers ::isUserAllowedToEditWorkbook
     */
    public function testDeleteUnlessWorkbookLockedByAnotherUser()
    {
        $mocks = $this->getMocks();

        $mocks['chartRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['chartId']])
            ->andReturn($mocks['chart'])
            ->once();
        $mocks['worksheetRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['worksheetId']])
            ->once()
            ->andReturn($mocks['worksheet']);
        $mocks['workbookRepo']->shouldReceive('findOneByWorksheet')
            ->with($mocks['worksheet'])
            ->once()
            ->andReturn($mocks['workbook']);

        $mocks['locker'] = Mockery::mock('\Tornado\Project\Workbook\Locker');
        $mocks['locker']->shouldReceive('isLocked')
            ->once()
            ->with($mocks['workbook'])
            ->andReturn(true);
        $mocks['locker']->shouldReceive('isGranted')
            ->once()
            ->with($mocks['workbook'], $mocks['sessionUser'])
            ->andReturn(false);
        $mocks['locker']->shouldReceive('getLockingUser')
            ->once()
            ->with()
            ->andReturn($mocks['lockingUser']);

        $controller = $this->getController($mocks);

        $result = $controller->delete($mocks['projectId'], $mocks['chartId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEmpty($result->getData());

        $this->assertArrayHasKey('error', $result->getMeta());
        $this->assertEquals(403, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::delete
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testDeleteNotFound()
    {
        $mocks = $this->getMocks();

        $mocks['chartRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['chartId']])
            ->andReturn(null)
            ->once();

        $controller = $this->getController($mocks);

        $controller->delete($mocks['projectId'], $mocks['chartId']);
    }

    /**
     * @return array
     */
    protected function getMocks()
    {
        $mocks = [];

        $mocks['projectId'] = 10;

        $mocks['project'] = Mockery::mock('Tornado\Project\Project', [
            'getId' => $mocks['projectId'],
            'getPrimaryKey' => $mocks['projectId']
        ]);

        $mocks['projectRepo'] = Mockery::mock('Tornado\Project\Project\DataMapper');
        $mocks['workbookRepo'] = Mockery::mock('Tornado\Project\Workbook\DataMapper');
        $mocks['worksheetRepo'] = Mockery::mock('Tornado\Project\Worksheet\DataMapper');

        $mocks['workbookId'] = 15;
        $mocks['worksheetId'] = 10;
        $mocks['recordingId'] = 5;

        $mocks['worksheet'] = Mockery::mock('Tornado\Project\Worksheet', [
            'getId' => $mocks['worksheetId'],
            'getPrimaryKey' => $mocks['worksheetId'],
            'getWorkbookId' => $mocks['workbookId'],
            'getName' => 'Test Worksheet'
        ]);

        $mocks['workbook'] = new Workbook();
        $mocks['workbook']->setId($mocks['workbookId']);
        $mocks['workbook']->setRecordingId($mocks['recordingId']);

        $mocks['chartId'] = 123;
        $mocks['chart'] = new Chart();
        $mocks['chart']->setId($mocks['chartId']);
        $mocks['chart']->setWorksheetId($mocks['worksheetId']);

        $mocks['chartRepo'] = Mockery::mock('Tornado\Project\Chart\DataMapper');
        $mocks['updateForm'] = Mockery::mock('Tornado\Project\Chart\Form\Update');

        $mocks['request'] = Mockery::mock('\DataSift\Http\Request');

        $mocks['locker'] = Mockery::mock('\Tornado\Project\Workbook\Locker');
        $mocks['userId'] = 10;
        $mocks['sessionUser']= new User();
        $mocks['sessionUser']->setId($mocks['userId']);

        $mocks['lockingUser'] = new User();
        $mocks['lockingUser']->setId(100);
        $mocks['lockingUser']->setEmail('test@test.com');

        $mocks['locker']->shouldReceive('isLocked')
            ->with($mocks['workbook'])
            ->andReturn(false);
        $mocks['locker']->shouldReceive('isGranted')
            ->with($mocks['workbook'], $mocks['sessionUser'])
            ->andReturn(true);

        return $mocks;
    }

    /**
     * @return ChartController
     */
    protected function getController(array $mocks)
    {
        $controller = Mockery::mock(ChartController::class, [
            $mocks['chartRepo'],
            $mocks['updateForm'],
            $mocks['locker'],
            $mocks['sessionUser']
        ])->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // mock ProjectDataAwareTrait methods
        $controller->shouldReceive('getProject')
            ->with($mocks['projectId'])
            ->andReturn($mocks['project']);

        $controller->setProjectRepository($mocks['projectRepo']);
        $controller->setWorkbookRepository($mocks['workbookRepo']);
        $controller->setWorksheetRepository($mocks['worksheetRepo']);

        return $controller;
    }
}
