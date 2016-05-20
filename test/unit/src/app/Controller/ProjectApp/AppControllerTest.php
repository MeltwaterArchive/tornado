<?php

namespace Test\Controller\ProjectApp;

use Mockery;

use Controller\ProjectApp\AppController;

/**
 * AppControllerTest
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
 * @coversDefaultClass \Controller\ProjectApp\AppController
 */
class AppControllerTest extends \PHPUnit_Framework_TestCase
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
     * @covers ::get
     */
    public function testGet()
    {
        $mocks = $this->getMocks();

        $mocks['workbooks'] = [];
        for ($i = 10; $i < 16; $i++) {
            $mocks['workbooks'][] = Mockery::mock('Tornado\Project\Workbook');
        }

        $mocks['workbookRepo'] = Mockery::mock('Tornado\Project\Workbook\DataMapper');
        $mocks['workbookRepo']->shouldReceive('findByProject')
            ->with($mocks['project'])
            ->andReturn($mocks['workbooks'])
            ->once();

        $mocks['worksheets'] = [];
        for ($i = 1; $i < 5; $i++) {
            $mocks['worksheets'][] = Mockery::mock('Tornado\Project\Worksheet');
        }
        $mocks['worksheetRepo'] = Mockery::mock('Tornado\Project\Worksheet\DataMapper');
        $mocks['worksheetRepo']->shouldReceive('findByWorkbooks')
            ->with($mocks['workbooks'])
            ->andReturn($mocks['worksheets'])
            ->once();

        $mocks['recordings'] = [];
        for ($i = 1; $i < 5; $i++) {
            $mocks['recordings'][] = Mockery::mock('Tornado\Project\Recording');
        }

        $mocks['recordingRepo']->shouldReceive('findRecordingsByWorkbooks')
            ->with($mocks['workbooks'])
            ->andReturn($mocks['recordings']);

        $mocks['datasiftRecording']->shouldReceive('decorateWorkbooks')
            ->with($mocks['workbooks'], $mocks['recordings'])
            ->andReturn($mocks['workbooks']);

        $controller = $this->getController($mocks);
        $controller->setWorkbookRepository($mocks['workbookRepo']);
        $controller->setWorksheetRepository($mocks['worksheetRepo']);

        $result = $controller->get($mocks['projectId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        $resultData = $result->getData();
        $this->assertEquals(200, $result->getHttpCode());

        $this->assertInternalType('array', $resultData);

        $this->assertArrayHasKey('project', $resultData);
        $this->assertSame($mocks['project'], $resultData['project']);

        $this->assertArrayHasKey('workbooks', $resultData);
        $this->assertSame($mocks['workbooks'], $resultData['workbooks']);

        $this->assertArrayHasKey('worksheets', $resultData);
        $this->assertEquals($mocks['worksheets'], $resultData['worksheets']);
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

        $mocks['authManager'] = Mockery::mock('Tornado\Security\Authorization\AccessDecisionManagerInterface', [
            'isGranted' => true
        ]);

        $mocks['recordingRepo'] = Mockery::mock('Tornado\Project\Recording\DataMapper');
        $mocks['datasiftRecording'] = Mockery::mock('Tornado\Project\Recording\DataSiftRecording');

        return $mocks;
    }

    /**
     * @return AppController
     */
    protected function getController(array $mocks)
    {
        $controller = Mockery::mock(AppController::class, [$mocks['recordingRepo'], $mocks['datasiftRecording']])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // mock ProjectDataAwareTrait methods
        $controller->shouldReceive('getProject')
            ->with($mocks['projectId'])
            ->andReturn($mocks['project']);

        return $controller;
    }
}
