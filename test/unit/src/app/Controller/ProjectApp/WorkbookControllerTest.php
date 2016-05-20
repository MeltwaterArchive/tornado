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
     * DataProvider for testCreate
     *
     * @return array
     */
    public function createProvider()
    {
        return [
            'happy' => [
                'postParams' => [
                    'recording_id' => 20,
                    'name' => 'test name',
                    'template' => ''
                ],
                'project_id' => 10,
                'template' => '',
                'expectedCode' => 201
            ],
            'happy templated' => [
                'postParams' => [
                    'recording_id' => 20,
                    'name' => 'test name',
                    'template' => 'template'
                ],
                'project_id' => 10,
                'template' => 'template',
                'expectedCode' => 201
            ],
            'invalid form' => [
                'postParams' => [
                    'recording_id' => 20,
                    'name' => 'test name',
                    'template' => 'template'
                ],
                'project_id' => 10,
                'template' => 'template',
                'expectedCode' => 400,
                'projectFound' => true,
                'accessGranted' => true,
                'formValid' => false
            ],
            'project not found' => [
                'postParams' => [
                    'recording_id' => 20,
                    'name' => 'test name',
                    'template' => 'template'
                ],
                'project_id' => 10,
                'template' => 'template',
                'expectedCode' => 400,
                'projectFound' => false,
                'accessGranted' => true,
                'formValid' => true,
                'expectedException' => '\Symfony\Component\HttpKernel\Exception\NotFoundHttpException'
            ],
            'access not granted' => [
                'postParams' => [
                    'recording_id' => 20,
                    'name' => 'test name',
                    'template' => 'template'
                ],
                'project_id' => 10,
                'template' => 'template',
                'expectedCode' => 400,
                'projectFound' => true,
                'accessGranted' => false,
                'formValid' => true,
                'expectedException' => '\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException'
            ]
        ];
    }

    /**
     * @dataProvider createProvider
     *
     * @covers ::create
     *
     * @param array $postParams
     * @param integer $projectId
     * @param string $template
     * @param integer $expectedCode
     * @param boolean $projectFound
     * @param boolean $accessGranted
     * @param boolean $formValid
     * @param string $expectedException
     */
    public function testCreate(
        array $postParams,
        $projectId,
        $template,
        $expectedCode,
        $projectFound = true,
        $accessGranted = true,
        $formValid = true,
        $expectedException = ''
    ) {
        $mocks = $this->getMocks();
        $mocks['request']->shouldReceive('getPostParams')
            ->andReturn($postParams);

        $mocks['projectRepo']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $projectId])
            ->andReturn(($projectFound) ? $mocks['project'] : false);

        $workbook = Mockery::mock(Workbook::class, [], ['getRecordingId' => $mocks['recordingId']]);
        $expectedMeta = [];
        $expectedData = [
            'workbook' => $workbook,
            'worksheets' => []
        ];

        $authManager = Mockery::mock('\Tornado\Security\Authorization\AccessDecisionManagerInterface');
        if ($projectFound) {
            $authManager->shouldReceive('isGranted')
                ->with($mocks['project'])
                ->andReturn($accessGranted);
        }

        if ($projectFound && $accessGranted) {
            $mocks['createForm']->shouldReceive('submit')
                ->with(array_merge($postParams, ['project_id' => $projectId]));

            $mocks['createForm']->shouldReceive('isValid')
                ->andReturn($formValid);

            if ($projectFound && $accessGranted && $formValid) {
                $mocks['createForm']->shouldReceive('getData')
                    ->andReturn($workbook);
                $mocks['workbookRepo']->shouldReceive('create')
                    ->once()
                    ->with($workbook);

                if ($template) {
                    $mocks['recordingRepo']->shouldReceive('findOne')
                        ->once()
                        ->with(['id' => $mocks['recordingId']])
                        ->andReturn($mocks['recording']);
                    $worksheets = [
                        Mockery::mock('\Tornado\Project\Worksheet'),
                        Mockery::mock('\Tornado\Project\Worksheet'),
                        Mockery::mock('\Tornado\Project\Worksheet')
                    ];

                    $mocks['worksheetsGenerator']->shouldReceive('generateFromTemplate')
                        ->once()
                        ->with(
                            $workbook,
                            $mocks['recording'],
                            $template
                        )->andReturn($worksheets);

                    $expectedData['worksheets'] = $worksheets;
                }
            } else {
                $errors = ['a' => 'b'];
                $mocks['createForm']->shouldReceive('getErrors')
                    ->once()
                    ->andReturn($errors);
                $expectedData = [];
                $expectedMeta = $errors;
            }
        }

        $controller = $this->getConcreteController($mocks);
        $controller->setAuthorizationManager($authManager);

        if ($expectedException) {
            $this->setExpectedException($expectedException);
        }

        $result = $controller->create($mocks['request'], $projectId);
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals($expectedCode, $result->getHttpCode());
        $this->assertEquals($expectedData, $result->getData());
        $this->assertEquals($expectedMeta, $result->getMeta());
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
     * DataProvider for testTemplates
     *
     * @return array
     */
    public function templatesProvider()
    {
        return [
            'happy' => [
                'templates' => [
                    'test' => [
                        'title' => 'Test',
                        'description' => 'Test desc',
                        'analyses' => []
                    ],
                    'test_two' => [
                        'title' => 'Test 2',
                        'analyses' => []
                    ]
                ],
                'expected' => [
                    ['id' => '', 'title' => '', 'description' => ''],
                    ['id' => 'test', 'title' => 'Test', 'description' => 'Test desc'],
                    ['id' => 'test_two', 'title' => 'Test 2', 'description' => ''],
                ]
            ]
        ];
    }

    /**
     * @dataProvider templatesProvider
     *
     * @covers ::templates
     *
     * @param array $templates
     * @param array $expected
     */
    public function testTemplates(array $templates, array $expected)
    {
        $mocks = $this->getMocks();

        $mocks['templatedAnalyzer']->shouldReceive('getTemplates')
            ->andReturn($templates);

        $controller = $this->getController($mocks);

        $result = $controller->templates();
        $this->assertInstanceOf(Result::class, $result);
        $this->assertEquals(200, $result->getHttpCode());
        $this->assertEquals($expected, $result->getData());
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

    /**
     * @return WorkbookController
     */
    protected function getConcreteController(array $mocks)
    {
        $controller = new WorkbookController(
            $mocks['createForm'],
            $mocks['updateForm'],
            $mocks['recordingRepo'],
            $mocks['worksheetsGenerator'],
            $mocks['templatedAnalyzer'],
            $mocks['locker'],
            $mocks['sessionUser'],
            $mocks['exporter'],
            $mocks['defaultWorkbookTemplate']
        );

        $controller->setProjectRepository($mocks['projectRepo']);
        $controller->setWorkbookRepository($mocks['workbookRepo']);
        $controller->setWorksheetRepository($mocks['worksheetRepo']);

        return $controller;
    }
}
