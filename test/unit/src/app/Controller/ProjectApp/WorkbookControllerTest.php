<?php

namespace Test\Controller\ProjectApp;

use Mockery;

use Controller\ProjectApp\WorkbookController;

use Tornado\DataMapper\DataMapperInterface;

use Tornado\Controller\ProjectDataAwareInterface;
use Tornado\Controller\Result;
use Tornado\Organization\User;
use Tornado\Project\Project;
use Tornado\Project\Recording;
use Tornado\Project\Workbook;
use Tornado\Project\Worksheet;

/**
 * WorkbookControllerTest
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
 * @coversDefaultClass \Controller\ProjectApp\WorkbookController
 */
class WorkbookControllerTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testIsProjectDataAware()
    {
        $controller = $this->getController($this->getMocks());
        $this->assertInstanceOf(ProjectDataAwareInterface::class, $controller);
    }

    /**
     * @covers ::__construct
     * @covers ::workbooks
     */
    public function testWorkbooks()
    {
        $mocks = $this->getMocks();

        $mocks['workbooks'] = [];
        for ($i = 1; $i < 6; $i++) {
            $mocks['workbooks'][] = new Workbook();
        }

        $mocks['worksheets'] = [];
        for ($i = 10; $i < 20; $i++) {
            $mocks['worksheets'][] = new Worksheet();
        }

        $mocks['workbookRepo']->shouldReceive('findByProject')
            ->with($mocks['project'])
            ->andReturn($mocks['workbooks'])
            ->once();

        $mocks['worksheetRepo']->shouldReceive('findByWorkbooks')
            ->with($mocks['workbooks'])
            ->andReturn($mocks['worksheets'])
            ->once();

        $controller = $this->getController($mocks);

        $result = $controller->workbooks($mocks['projectId']);

        $this->assertInstanceOf(Result::class, $result);
        $data = $result->getData();
        $this->assertArrayHasKey('workbooks', $data);
        $this->assertEquals($mocks['workbooks'], $data['workbooks']);
    }

    /**
     * @covers ::__construct
     * @covers ::workbook
     */
    public function testWorkbook()
    {
        $mocks = $this->getMocks();

        $mocks['worksheets'] = [];
        for ($i = 10; $i < 20; $i++) {
            $mocks['worksheets'][] = new Worksheet();
        }

        $mocks['worksheetRepo']->shouldReceive('findByWorkbook')
            ->with($mocks['workbook'])
            ->andReturn($mocks['worksheets'])
            ->once();

        $controller = $this->getController($mocks);

        $result = $controller->workbook($mocks['projectId'], $mocks['workbookId']);

        $this->assertInstanceOf(Result::class, $result);
        $data = $result->getData();
        $this->assertArrayHasKey('workbook', $data);
        $this->assertSame($mocks['workbook'], $data['workbook']);
    }

    /**
     * @covers ::__construct
     * @covers ::create
     */
    public function testCreateInvalidRequest()
    {
        $mocks = $this->getMocks();

        $postParams = [
            'name' => 'Test Workbook'
        ];
        $postParamsWithProjectId = array_merge($postParams, ['project_id' => $mocks['projectId']]);
        $errors = ['error1' => 'error', 'error2' => 'error'];

        $mocks['createForm']->shouldReceive('submit')
            ->with($postParamsWithProjectId)
            ->once();
        $mocks['createForm']->shouldReceive('isValid')
            ->andReturn(false)
            ->once();
        $mocks['createForm']->shouldReceive('getErrors')
            ->andReturn($errors)
            ->once();

        $mocks['request']->shouldReceive('getPostParams')
            ->andReturn($postParams)
            ->once();

        $controller = $this->getController($mocks);

        $result = $controller->create($mocks['request'], $mocks['projectId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEmpty($result->getData());
        $this->assertEquals($errors, $result->getMeta());
        $this->assertEquals(400, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::create
     */
    public function testCreate()
    {
        $mocks = $this->getMocks();

        $postParams = [
            'name' => 'Test Workbook',
            'recording_id' => $mocks['recordingId']
        ];
        $postParamsWithProjectId = array_merge($postParams, ['project_id' => $mocks['projectId']]);

        $mocks['createForm']->shouldReceive('submit')
            ->with($postParamsWithProjectId)
            ->once();
        $mocks['createForm']->shouldReceive('isValid')
            ->andReturn(true)
            ->once();
        $mocks['createForm']->shouldReceive('getData')
            ->andReturn($mocks['workbook'])
            ->once();

        $mocks['request']->shouldReceive('getPostParams')
            ->andReturn($postParams)
            ->once();

        $mocks['workbookRepo']->shouldReceive('create')
            ->with($mocks['workbook'])
            ->once();

        $mocks['project']->setFresh(0);

        $controller = $this->getController($mocks);

        $result = $controller->create($mocks['request'], $mocks['projectId']);

        $this->assertInstanceOf(Result::class, $result);
        $data = $result->getData();
        $this->assertArrayHasKey('workbook', $data);
        $this->assertSame($mocks['workbook'], $data['workbook']);
        $this->assertArrayHasKey('worksheets', $data);
        $this->assertInternalType('array', $data['worksheets']);
        $this->assertEmpty($data['worksheets']);
        $this->assertEquals(201, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::create
     */
    public function testCreateOnFreshProject()
    {
        $mocks = $this->getMocks();

        $postParams = [
            'name' => 'Test Workbook',
            'recording_id' => $mocks['recordingId']
        ];
        $postParamsWithProjectId = array_merge($postParams, ['project_id' => $mocks['projectId']]);

        $mocks['createForm']->shouldReceive('submit')
            ->with($postParamsWithProjectId)
            ->once();
        $mocks['createForm']->shouldReceive('isValid')
            ->andReturn(true)
            ->once();
        $mocks['createForm']->shouldReceive('getData')
            ->andReturn($mocks['workbook'])
            ->once();

        $mocks['request']->shouldReceive('getPostParams')
            ->andReturn($postParams)
            ->once();

        $mocks['workbookRepo']->shouldReceive('create')
            ->with($mocks['workbook'])
            ->once();

        $mocks['project']->setFresh(1);

        $mocks['recordingRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['recordingId']])
            ->andReturn($mocks['recording'])
            ->once();

        $mocks['worksheets'] = [];
        for ($i = 1; $i < 3; $i++) {
            $mocks['worksheets'][] = new Worksheet();
        }

        $mocks['worksheetsGenerator']->shouldReceive('generateFromTemplate')
            ->with($mocks['workbook'], $mocks['recording'], $mocks['defaultWorkbookTemplate'])
            ->andReturn($mocks['worksheets'])
            ->once();

        $mocks['projectRepo']->shouldReceive('update')
            ->with($mocks['project'])
            ->once();

        $controller = $this->getController($mocks);

        $result = $controller->create($mocks['request'], $mocks['projectId']);

        $this->assertInstanceOf(Result::class, $result);
        $data = $result->getData();
        $this->assertArrayHasKey('workbook', $data);
        $this->assertSame($mocks['workbook'], $data['workbook']);
        $this->assertArrayHasKey('worksheets', $data);
        $this->assertSame($mocks['worksheets'], $data['worksheets']);
        $this->assertEquals(0, $mocks['project']->getFresh());
        $this->assertEquals(201, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::createDefaults
     */
    public function testCreateDefaults()
    {
        $mocks = $this->getMocks();

        $mocks['project']->setFresh(1);
        $mocks['project']->setType(Project::TYPE_API);
        $mocks['project']->setRecordingFilter(Project::RECORDING_FILTER_API);

        $mocks['recordingRepo']->shouldReceive('findByProject')
            ->with($mocks['project'])
            ->andReturn([$mocks['recording']])
            ->once();

        $mocks['templatedAnalyzer']->shouldReceive('readTemplate')
            ->with($mocks['defaultWorkbookTemplate'])
            ->andReturn(['title' => 'Default Workbook'])
            ->once();

        $mocks['createForm']->shouldReceive('submit')
            ->with([
                'project_id' => $mocks['projectId'],
                'name' => 'Default Workbook',
                'recording_id' => $mocks['recordingId']
            ])
            ->once();
        $mocks['createForm']->shouldReceive('isValid')
            ->andReturn(true)
            ->once();
        $mocks['createForm']->shouldReceive('getData')
            ->andReturn($mocks['workbook'])
            ->once();

        $mocks['workbookRepo']->shouldReceive('create')
            ->with($mocks['workbook'])
            ->once();

        $mocks['worksheets'] = [];
        for ($i = 1; $i < 3; $i++) {
            $mocks['worksheets'][] = new Worksheet();
        }

        $mocks['worksheetsGenerator']->shouldReceive('generateFromTemplate')
            ->with($mocks['workbook'], $mocks['recording'], $mocks['defaultWorkbookTemplate'])
            ->andReturn($mocks['worksheets'])
            ->once();

        $mocks['projectRepo']->shouldReceive('update')
            ->with($mocks['project'])
            ->once();

        $controller = $this->getController($mocks);

        $result = $controller->createDefaults($mocks['projectId']);

        $this->assertInstanceOf(Result::class, $result);
        $data = $result->getData();
        $this->assertArrayHasKey('workbook', $data);
        $this->assertSame($mocks['workbook'], $data['workbook']);
        $this->assertArrayHasKey('worksheets', $data);
        $this->assertSame($mocks['worksheets'], $data['worksheets']);
        $this->assertEquals(0, $mocks['project']->getFresh());
        $this->assertEquals(201, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::createDefaults
     */
    public function testCreateDefaultsOnNotFreshProject()
    {
        $mocks = $this->getMocks();

        $mocks['project']->setFresh(0);

        $controller = $this->getController($mocks);

        $result = $controller->createDefaults($mocks['projectId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(400, $result->getHttpCode());
        $meta = $result->getMeta();
        $this->assertArrayHasKey('error', $meta);
    }

    /**
     * @covers ::__construct
     * @covers ::createDefaults
     */
    public function testCreateDefaultsOnNotApiProject()
    {
        $mocks = $this->getMocks();

        $mocks['project']->setFresh(1);
        $mocks['project']->setType(Project::TYPE_NORMAL);

        $controller = $this->getController($mocks);

        $result = $controller->createDefaults($mocks['projectId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(400, $result->getHttpCode());
        $meta = $result->getMeta();
        $this->assertArrayHasKey('error', $meta);
    }

    /**
     * @covers ::__construct
     * @covers ::createDefaults
     */
    public function testCreateDefaultsWithoutDefaultRecording()
    {
        $mocks = $this->getMocks();

        $mocks['project']->setFresh(1);
        $mocks['project']->setType(Project::TYPE_API);
        $mocks['project']->setRecordingFilter(Project::RECORDING_FILTER_API);

        $mocks['recordingRepo']->shouldReceive('findByProject')
            ->with($mocks['project'])
            ->andReturn([]);

        $controller = $this->getController($mocks);

        $result = $controller->createDefaults($mocks['projectId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(404, $result->getHttpCode());
        $meta = $result->getMeta();
        $this->assertArrayHasKey('error', $meta);
    }

    /**
     * @covers ::__construct
     * @covers ::createDefaults
     */
    public function testCreateDefaultsWithInvalidData()
    {
        $mocks = $this->getMocks();

        $mocks['project']->setFresh(1);
        $mocks['project']->setType(Project::TYPE_API);
        $mocks['project']->setRecordingFilter(Project::RECORDING_FILTER_API);

        $mocks['recordingRepo']->shouldReceive('findByProject')
            ->with($mocks['project'])
            ->andReturn([$mocks['recording']])
            ->once();

        $mocks['templatedAnalyzer']->shouldReceive('readTemplate')
            ->with($mocks['defaultWorkbookTemplate'])
            ->andReturn(['title' => ''])
            ->once();

        $mocks['createForm']->shouldReceive('submit')
            ->with([
                'project_id' => $mocks['projectId'],
                'name' => '',
                'recording_id' => $mocks['recordingId']
            ])
            ->once();
        $mocks['createForm']->shouldReceive('isValid')
            ->andReturn(false)
            ->once();

        $mocks['errors'] = ['name' => 'not_blank'];

        $mocks['createForm']->shouldReceive('getErrors')
            ->andReturn($mocks['errors'])
            ->once();

        $controller = $this->getController($mocks);

        $result = $controller->createDefaults($mocks['projectId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(400, $result->getHttpCode());
        $meta = $result->getMeta();
        $this->assertArrayHasKey('errors', $meta);
        $this->assertEquals($mocks['errors'], $meta['errors']);
    }

    /**
     * @covers ::__construct
     * @covers ::update
     */
    public function testUpdateInvalidRequest()
    {
        $mocks = $this->getMocks();

        $postParams = [
            'name' => 'Test Workbook edited'
        ];
        $errors = ['error1' => 'error', 'error2' => 'error'];

        $mocks['updateForm']->shouldReceive('submit')
            ->with($postParams, $mocks['workbook'])
            ->once();
        $mocks['updateForm']->shouldReceive('isValid')
            ->andReturn(false)
            ->once();
        $mocks['updateForm']->shouldReceive('getErrors')
            ->andReturn($errors)
            ->once();

        $mocks['request']->shouldReceive('getPostParams')
            ->andReturn($postParams)
            ->once();

        $controller = $this->getController($mocks);

        $result = $controller->update($mocks['request'], $mocks['projectId'], $mocks['workbookId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEmpty($result->getData());
        $this->assertEquals($errors, $result->getMeta());
        $this->assertEquals(400, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::update
     * @covers ::isUserAllowedToEditWorkbook
     */
    public function testUpdateUnlessWorkbookLockedByAnotherUser()
    {
        $mocks = $this->getMocks();

        $mocks['locker'] = Mockery::mock('\Tornado\Project\Workbook\Locker');
        $mocks['locker']->shouldReceive('isLocked')
            ->with($mocks['workbook'])
            ->once()
            ->andReturn(true);
        $mocks['locker']->shouldReceive('isGranted')
            ->with($mocks['workbook'], $mocks['sessionUser'])
            ->once()
            ->andReturn(false);
        $mocks['locker']->shouldReceive('getLockingUser')
            ->with()
            ->once()
            ->andReturn($mocks['lockingUser']);

        $controller = $this->getController($mocks);

        $result = $controller->update($mocks['request'], $mocks['projectId'], $mocks['workbookId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEmpty($result->getData());
        $this->assertInternalType('array', $result->getMeta());
        $this->assertArrayHasKey('error', $result->getMeta());
        $this->assertEquals(403, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::update
     */
    public function testUpdate()
    {
        $mocks = $this->getMocks();

        $postParams = [
            'name' => 'Test Workbook edited'
        ];

        $mocks['worksheets'] = [];
        for ($i = 10; $i < 20; $i++) {
            $mocks['worksheets'][] = new Worksheet();
        }

        $mocks['updateForm']->shouldReceive('submit')
            ->with($postParams, $mocks['workbook'])
            ->once();
        $mocks['updateForm']->shouldReceive('isValid')
            ->andReturn(true)
            ->once();
        $mocks['updateForm']->shouldReceive('getData')
            ->andReturn($mocks['workbook'])
            ->once();

        $mocks['request']->shouldReceive('getPostParams')
            ->andReturn($postParams)
            ->once();

        $mocks['workbookRepo']->shouldReceive('update')
            ->with($mocks['workbook'])
            ->once();

        $mocks['worksheetRepo']->shouldReceive('findByWorkbook')
            ->with($mocks['workbook'])
            ->andReturn($mocks['worksheets'])
            ->once();

        $controller = $this->getController($mocks);

        $result = $controller->update($mocks['request'], $mocks['projectId'], $mocks['workbookId']);

        $this->assertInstanceOf(Result::class, $result);
        $data = $result->getData();
        $this->assertArrayHasKey('workbook', $data);
        $this->assertSame($mocks['workbook'], $data['workbook']);
        $this->assertEquals(200, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::delete
     */
    public function testDelete()
    {
        $mocks = $this->getMocks();
        $mocks['locker'] = Mockery::mock('\Tornado\Project\Workbook\Locker');
        $mocks['locker']->shouldReceive('isLocked')
            ->with($mocks['workbook'])
            ->once()
            ->andReturn(false);
        $mocks['workbookRepo']->shouldReceive('delete')
            ->with($mocks['workbook'])
            ->once();

        $controller = $this->getController($mocks);

        $result = $controller->delete($mocks['projectId'], $mocks['workbookId']);

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

        $mocks['locker'] = Mockery::mock('\Tornado\Project\Workbook\Locker');
        $mocks['locker']->shouldReceive('isLocked')
            ->with($mocks['workbook'])
            ->once()
            ->andReturn(true);
        $mocks['locker']->shouldReceive('isGranted')
            ->with($mocks['workbook'], $mocks['sessionUser'])
            ->once()
            ->andReturn(false);
        $mocks['locker']->shouldReceive('getLockingUser')
            ->with()
            ->once()
            ->andReturn($mocks['lockingUser']);

        $controller = $this->getController($mocks);

        $result = $controller->delete($mocks['projectId'], $mocks['workbookId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEmpty($result->getData());
        $this->assertInternalType('array', $result->getMeta());
        $this->assertArrayHasKey('error', $result->getMeta());
        $this->assertEquals(403, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::lock
     */
    public function testLockUnlessLockedBefore()
    {
        $mocks = $this->getMocks();

        $mocks['locker'] = Mockery::mock('\Tornado\Project\Workbook\Locker');
        $mocks['locker']->shouldReceive('isLocked')
            ->with($mocks['workbook'])
            ->once()
            ->andReturn(false);
        $mocks['locker']->shouldReceive('lock')
            ->with($mocks['workbook'], $mocks['sessionUser'])
            ->once()
            ->andReturn(true);

        $controller = $this->getController($mocks);

        $result = $controller->lock($mocks['projectId'], $mocks['workbookId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEmpty($result->getData());
        $this->assertEmpty($result->getMeta());
        $this->assertEquals(201, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::lock
     */
    public function testLockUnlessWorkbookLockedByAnotherUser()
    {
        $mocks = $this->getMocks();

        $mocks['locker'] = Mockery::mock('\Tornado\Project\Workbook\Locker');
        $mocks['locker']->shouldReceive('isLocked')
            ->with($mocks['workbook'])
            ->once()
            ->andReturn(true);
        $mocks['locker']->shouldReceive('isGranted')
            ->once()
            ->with($mocks['workbook'], $mocks['sessionUser'])
            ->andReturn(false);
        $mocks['locker']->shouldReceive('getLockingUser')
            ->with()
            ->once()
            ->andReturn($mocks['lockingUser']);

        $controller = $this->getController($mocks);

        $result = $controller->lock($mocks['projectId'], $mocks['workbookId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEmpty($result->getData());

        $this->assertInternalType('array', $result->getMeta());
        $this->assertArrayHasKey('error', $result->getMeta());
        $this->assertEquals(403, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::lock
     */
    public function testLock()
    {
        $mocks = $this->getMocks();

        $mocks['locker'] = Mockery::mock('\Tornado\Project\Workbook\Locker');
        $mocks['locker']->shouldReceive('isLocked')
            ->with($mocks['workbook'])
            ->once()
            ->andReturn(true);
        $mocks['locker']->shouldReceive('isGranted')
            ->once()
            ->with($mocks['workbook'], $mocks['sessionUser'])
            ->andReturn(true);
        $mocks['locker']->shouldReceive('lock')
            ->with($mocks['workbook'], $mocks['sessionUser'])
            ->once()
            ->andReturn(true);

        $controller = $this->getController($mocks);

        $result = $controller->lock($mocks['projectId'], $mocks['workbookId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEmpty($result->getData());
        $this->assertEmpty($result->getMeta());
        $this->assertEquals(201, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::ttlReset
     */
    public function testTtlResetUnlessNotLocked()
    {
        $mocks = $this->getMocks();

        $mocks['locker'] = Mockery::mock('\Tornado\Project\Workbook\Locker');
        $mocks['locker']->shouldReceive('isLocked')
            ->with($mocks['workbook'])
            ->once()
            ->andReturn(false);

        $controller = $this->getController($mocks);

        $result = $controller->ttlReset($mocks['projectId'], $mocks['workbookId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEmpty($result->getData());
        $this->assertInternalType('array', $result->getMeta());
        $this->assertArrayHasKey('error', $result->getMeta());
        $this->assertEquals(404, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::ttlReset
     */
    public function testTtlResetUnlessLockedByAnotherUser()
    {
        $mocks = $this->getMocks();

        $mocks['locker'] = Mockery::mock('\Tornado\Project\Workbook\Locker');
        $mocks['locker']->shouldReceive('isLocked')
            ->with($mocks['workbook'])
            ->once()
            ->andReturn(true);
        $mocks['locker']->shouldReceive('isGranted')
            ->once()
            ->with($mocks['workbook'], $mocks['sessionUser'])
            ->andReturn(false);
        $mocks['locker']->shouldReceive('getLockingUser')
            ->with()
            ->once()
            ->andReturn($mocks['lockingUser']);

        $controller = $this->getController($mocks);

        $result = $controller->ttlReset($mocks['projectId'], $mocks['workbookId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEmpty($result->getData());
        $this->assertInternalType('array', $result->getMeta());
        $this->assertArrayHasKey('error', $result->getMeta());
        $this->assertEquals(403, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::ttlReset
     */
    public function testTtlResetUnlessCounterExceeded()
    {
        $mocks = $this->getMocks();

        $mocks['locker'] = Mockery::mock('\Tornado\Project\Workbook\Locker');
        $mocks['locker']->shouldReceive('isLocked')
            ->with($mocks['workbook'])
            ->once()
            ->andReturn(true);
        $mocks['locker']->shouldReceive('isGranted')
            ->once()
            ->with($mocks['workbook'], $mocks['sessionUser'])
            ->andReturn(true);
        $mocks['locker']->shouldReceive('resetTtl')
            ->with($mocks['workbook'], $mocks['sessionUser'])
            ->once()
            ->andReturn(false);
        $mocks['locker']->shouldReceive('getTtl')
            ->with()
            ->once()
            ->andReturn(120);

        $controller = $this->getController($mocks);

        $result = $controller->ttlReset($mocks['projectId'], $mocks['workbookId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEmpty($result->getData());
        $this->assertInternalType('array', $result->getMeta());
        $this->assertArrayHasKey('error', $result->getMeta());
        $this->assertEquals(409, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::ttlReset
     */
    public function testTtlReset()
    {
        $mocks = $this->getMocks();

        $mocks['locker'] = Mockery::mock('\Tornado\Project\Workbook\Locker');
        $mocks['locker']->shouldReceive('isLocked')
            ->with($mocks['workbook'])
            ->once()
            ->andReturn(true);
        $mocks['locker']->shouldReceive('isGranted')
            ->once()
            ->with($mocks['workbook'], $mocks['sessionUser'])
            ->andReturn(true);
        $mocks['locker']->shouldReceive('resetTtl')
            ->with($mocks['workbook'], $mocks['sessionUser'])
            ->once()
            ->andReturn(10);
        $mocks['locker']->shouldReceive('getTtl')
            ->with()
            ->once()
            ->andReturn(120);

        $controller = $this->getController($mocks);

        $result = $controller->ttlReset($mocks['projectId'], $mocks['workbookId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEmpty($result->getData());

        $meta = $result->getMeta();
        $this->assertInternalType('array', $meta);
        $this->assertArrayHasKey('remaining_counter', $meta);
        $this->assertEquals(10, $meta['remaining_counter']);
        $this->assertArrayHasKey('ttl', $meta);
        $this->assertEquals(120, $meta['ttl']);

        $this->assertEquals(200, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::unlock
     */
    public function testSuccessUnlockResponseEvenNotLockedBefore()
    {
        $mocks = $this->getMocks();

        $mocks['locker'] = Mockery::mock('\Tornado\Project\Workbook\Locker');
        $mocks['locker']->shouldReceive('isLocked')
            ->with($mocks['workbook'])
            ->once()
            ->andReturn(false);

        $controller = $this->getController($mocks);

        $result = $controller->unlock($mocks['projectId'], $mocks['workbookId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEmpty($result->getData());
        $this->assertEmpty($result->getMeta());
        $this->assertEquals(204, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::unlock
     */
    public function testUnlockUnlessLockedByAnotherUser()
    {
        $mocks = $this->getMocks();

        $mocks['locker'] = Mockery::mock('\Tornado\Project\Workbook\Locker');
        $mocks['locker']->shouldReceive('isLocked')
            ->with($mocks['workbook'])
            ->once()
            ->andReturn(true);
        $mocks['locker']->shouldReceive('isGranted')
            ->with($mocks['workbook'], $mocks['sessionUser'])
            ->once()
            ->andReturn(false);

        $controller = $this->getController($mocks);

        $result = $controller->unlock($mocks['projectId'], $mocks['workbookId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEmpty($result->getData());

        $this->assertInternalType('array', $result->getMeta());
        $this->assertArrayHasKey('error', $result->getMeta());
        $this->assertEquals(403, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::unlock
     */
    public function testUnlock()
    {
        $mocks = $this->getMocks();

        $mocks['locker'] = Mockery::mock('\Tornado\Project\Workbook\Locker');
        $mocks['locker']->shouldReceive('isLocked')
            ->with($mocks['workbook'])
            ->once()
            ->andReturn(true);
        $mocks['locker']->shouldReceive('isGranted')
            ->with($mocks['workbook'], $mocks['sessionUser'])
            ->once()
            ->andReturn(true);
        $mocks['locker']->shouldReceive('unlock')
            ->with($mocks['workbook'])
            ->once()
            ->andReturn(true);

        $controller = $this->getController($mocks);

        $result = $controller->unlock($mocks['projectId'], $mocks['workbookId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEmpty($result->getData());
        $this->assertEmpty($result->getMeta());

        $this->assertEquals(204, $result->getHttpCode());
    }

    public function testExport()
    {
        $mocks = $this->getMocks();

        $worksheets = ['a', 'b', 'c'];
        $mocks['worksheetRepo']->shouldReceive('find')
            ->with(['workbook_id' => $mocks['workbookId']], ['rank' => DataMapperInterface::ORDER_ASCENDING])
            ->andReturn($worksheets);

        $mocks['exporter']->shouldReceive('exportWorksheets')
            ->with(Mockery::any(), $worksheets)
            ->andReturn(true);

        $controller = $this->getController($mocks);
        $response = $controller->export($mocks['projectId'], $mocks['workbookId']);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\StreamedResponse', $response);

        // verify headers
        $this->assertStringMatchesFormat(
            'attachment; filename="%s.zip"',
            $response->headers->get('Content-Disposition')
        );

        $response->sendContent();
    }

    /**
     * @return array
     */
    protected function getMocks()
    {
        $mocks = [];

        $mocks['projectId'] = 10;
        $mocks['workbookId'] = 15;
        $mocks['recordingId'] = 5;

        $mocks['project'] = new Project();
        $mocks['project']->setId($mocks['projectId']);

        $mocks['workbook'] = new Workbook();
        $mocks['workbook']->setId($mocks['workbookId']);
        $mocks['workbook']->setRecordingId($mocks['recordingId']);
        $mocks['workbook']->setName('test workbook');

        $mocks['recording'] = new Recording();
        $mocks['recording']->setId($mocks['recordingId']);

        $mocks['defaultWorkbookTemplate'] = 'deftpl';

        $mocks['projectRepo'] = Mockery::mock('Tornado\Project\Project\DataMapper');
        $mocks['workbookRepo'] = Mockery::mock('Tornado\Project\Workbook\DataMapper');
        $mocks['worksheetRepo'] = Mockery::mock('Tornado\Project\Worksheet\DataMapper');
        $mocks['recordingRepo'] = Mockery::mock('Tornado\Project\Recording\DataMapper');
        $mocks['createForm'] = Mockery::mock('Tornado\Project\Workbook\Form\Create');
        $mocks['updateForm'] = Mockery::mock('Tornado\Project\Workbook\Form\Update');
        $mocks['worksheetsGenerator'] = Mockery::mock('Tornado\Project\Worksheet\Generator');
        $mocks['templatedAnalyzer'] = Mockery::mock('Tornado\Analyze\TemplatedAnalyzer');

        $mocks['request'] = Mockery::mock('\DataSift\Http\Request');

        $mocks['locker'] = Mockery::mock('\Tornado\Project\Workbook\Locker');
        $mocks['userId'] = 10;
        $mocks['sessionUser']= new User();
        $mocks['sessionUser']->setId($mocks['userId']);

        $mocks['exporter'] = Mockery::mock('\Tornado\Project\Worksheet\Exporter');

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
     * @return WorkbookController
     */
    protected function getController(array $mocks)
    {
        $controller = Mockery::mock(WorkbookController::class, [
            $mocks['createForm'],
            $mocks['updateForm'],
            $mocks['recordingRepo'],
            $mocks['worksheetsGenerator'],
            $mocks['templatedAnalyzer'],
            $mocks['locker'],
            $mocks['sessionUser'],
            $mocks['exporter'],
            $mocks['defaultWorkbookTemplate']
        ])->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // mock ProjectDataAwareTrait methods
        $controller->shouldReceive('getProject')
            ->with($mocks['projectId'])
            ->andReturn($mocks['project']);
        $controller->shouldReceive('getWorkbook')
            ->with($mocks['project'], $mocks['workbookId'])
            ->andReturn($mocks['workbook']);

        $controller->setProjectRepository($mocks['projectRepo']);
        $controller->setWorkbookRepository($mocks['workbookRepo']);
        $controller->setWorksheetRepository($mocks['worksheetRepo']);

        return $controller;
    }
}
