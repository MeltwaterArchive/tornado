<?php

namespace Test\Controller\ProjectApp;

use Mockery;

use Tornado\Controller\Result;

use Tornado\Organization\User;
use Tornado\Project\Chart;
use Tornado\Analyze\Analysis;

use Controller\ProjectApp\WorksheetController;

/**
 * WorksheetControllerTest
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
 * @coversDefaultClass \Controller\ProjectApp\WorksheetController
 */
class WorksheetControllerTest extends \PHPUnit_Framework_TestCase
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
     * @covers ::index
     */
    public function testIndex()
    {
        $mocks = $this->getMocks();

        $mocks['charts'] = [];
        for ($i = 10; $i < 20; $i++) {
            $mocks['charts'][] = Mockery::mock('Tornado\Project\Chart', [
                'getId' => $i,
                'getPrimaryKey' => $i
            ]);
        }

        $mocks['chartsRepo']->shouldReceive('findByWorksheet')
            ->with($mocks['worksheet'])
            ->andReturn($mocks['charts'])
            ->once();

        $controller = $this->getController($mocks);

        $result = $controller->index($mocks['projectId'], $mocks['worksheetId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
        $resultData = $result->getData();
        $this->assertInternalType('array', $resultData);

        $this->assertArrayHasKey('project', $resultData);
        $this->assertSame($mocks['project'], $resultData['project']);

        $this->assertArrayHasKey('worksheet', $resultData);
        $this->assertSame($mocks['worksheet'], $resultData['worksheet']);

        $this->assertArrayHasKey('charts', $resultData);
        $this->assertEquals($mocks['charts'], $resultData['charts']);
    }

    /**
     * @covers ::__construct
     * @covers ::create
     */
    public function testCreateInvalidRequest()
    {
        $mocks = $this->getMocks();

        $postParams = [
            'name' => 'Test Worksheet'
        ];
        $errors = ['error1' => 'error', 'error2' => 'error'];

        $mocks['createForm']->shouldReceive('submit')
            ->with($postParams)
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

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
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
            'name' => 'Test Worksheet',
            'workbook_id' => $mocks['workbookId']
        ];

        $mocks['createForm']->shouldReceive('submit')
            ->with($postParams)
            ->once();
        $mocks['createForm']->shouldReceive('isValid')
            ->andReturn(true)
            ->once();
        $mocks['createForm']->shouldReceive('getData')
            ->andReturn($mocks['worksheet'])
            ->once();

        $mocks['request']->shouldReceive('getPostParams')
            ->andReturn($postParams)
            ->once();

        $mocks['worksheetRepo']->shouldReceive('create')
            ->with($mocks['worksheet'])
            ->once();

        $mocks['workbookRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['workbookId']])
            ->once()
            ->andReturn($mocks['workbook']);

        $controller = $this->getController($mocks);

        $result = $controller->create($mocks['request'], $mocks['projectId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
        $data = $result->getData();
        $this->assertArrayHasKey('project', $data);
        $this->assertSame($mocks['project'], $data['project']);
        $this->assertArrayHasKey('worksheet', $data);
        $this->assertSame($mocks['worksheet'], $data['worksheet']);
        $this->assertEquals(201, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::create
     * @covers ::isUserAllowedToEditWorkbook
     */
    public function testCreateUnlessWorkbookLockedByAnotherUser()
    {
        $mocks = $this->getMocks();

        $postParams = [
            'name' => 'Test Worksheet',
            'workbook_id' => $mocks['workbookId']
        ];
        $mocks['createForm']->shouldReceive('submit')
            ->with($postParams)
            ->once();
        $mocks['createForm']->shouldReceive('isValid')
            ->andReturn(true)
            ->once();
        $mocks['createForm']->shouldReceive('getData')
            ->andReturn($mocks['worksheet'])
            ->once();
        $mocks['request']->shouldReceive('getPostParams')
            ->andReturn($postParams)
            ->once();
        $mocks['workbookRepo']->shouldReceive('findOne')
            ->with(['id' => $mocks['workbookId']])
            ->once()
            ->andReturn($mocks['workbook']);

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

        $result = $controller->create($mocks['request'], $mocks['projectId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
        $this->assertInternalType('array', $result->getData());
        $this->assertEquals([], $result->getData());
        $this->assertEquals(403, $result->getHttpCode());

        $this->assertInternalType('array', $result->getMeta());
        $this->arrayHasKey('error', $result->getMeta());
    }

    /**
     * @covers ::__construct
     * @covers ::update
     */
    public function testUpdateInvalidRequest()
    {
        $mocks = $this->getMocks();

        $postParams = [
            'name' => 'Test Worksheet edited',
            'workbook_id' => $mocks['workbookId']
        ];
        $errors = ['error1' => 'error', 'error2' => 'error'];

        $mocks['updateForm']->shouldReceive('submit')
            ->with($postParams, $mocks['worksheet'])
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

        $result = $controller->update($mocks['request'], $mocks['projectId'], $mocks['worksheetId']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertEmpty($result->getData());
        $this->assertEquals($errors, $result->getMeta());
        $this->assertEquals(400, $result->getHttpCode());
    }

    /**
     * @covers ::__construct
     * @covers ::update
     */
    public function testUpdate()
    {
        $mocks = $this->getMocks();

        $postParams = [
            'name' => 'Test Worksheet edited',
            'workbook_id' => $mocks['workbookId']
        ];

        $mocks['updateForm']->shouldReceive('submit')
            ->with($postParams, $mocks['worksheet'])
            ->once();
        $mocks['updateForm']->shouldReceive('isValid')
            ->andReturn(true)
            ->once();
        $mocks['updateForm']->shouldReceive('getData')
            ->andReturn($mocks['worksheet'])
            ->once();

        $mocks['request']->shouldReceive('getPostParams')
            ->andReturn($postParams)
            ->once();

        $mocks['worksheetRepo']->shouldReceive('update')
            ->with($mocks['worksheet'])
            ->once();

        $controller = $this->getController($mocks);

        $result = $controller->update($mocks['request'], $mocks['projectId'], $mocks['worksheetId']);

        $this->assertInstanceOf(Result::class, $result);
        $data = $result->getData();
        $this->assertArrayHasKey('worksheet', $data);
        $this->assertSame($mocks['worksheet'], $data['worksheet']);
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

        $result = $controller->update($mocks['request'], $mocks['projectId'], $mocks['worksheetId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
        $this->assertInternalType('array', $result->getData());
        $this->assertEquals([], $result->getData());
        $this->assertEquals(403, $result->getHttpCode());

        $this->assertInternalType('array', $result->getMeta());
        $this->arrayHasKey('error', $result->getMeta());
    }

    public function testExport()
    {
        $mocks = $this->getMocks();

        $data = [
            ['fb.author.region','fb.author.gender','interactions'],
            ['England', 'female', 23400],
            ['England', 'male', 23300],
            ['US', 'female', 12300],
            ['US', 'male', 9012]
        ];
        $dataCsv = implode("\n", array_map(function ($row) {
            return implode(',', $row);
        }, $data)) . "\n";
        $this->expectOutputString($dataCsv);

        $mocks['exporter']->shouldReceive('exportWorksheetGenerator')
            ->with($mocks['worksheet'])
            ->andReturn($data)
            ->once();

        $controller = $this->getController($mocks);

        $response = $controller->export($mocks['projectId'], $mocks['worksheetId']);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\StreamedResponse', $response);

        // verify headers
        $this->assertStringMatchesFormat(
            'attachment; filename="%s.csv"',
            $response->headers->get('Content-Disposition')
        );

        $response->sendContent();
    }

    /**
     * @covers ::__construct
     * @covers ::delete
     */
    public function testDelete()
    {
        $mocks = $this->getMocks();

        $mocks['worksheetRepo']->shouldReceive('delete')
            ->with($mocks['worksheet'])
            ->once();

        $controller = $this->getController($mocks);

        $result = $controller->delete($mocks['projectId'], $mocks['worksheetId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
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

        $result = $controller->delete($mocks['projectId'], $mocks['worksheetId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
        $this->assertInternalType('array', $result->getData());
        $this->assertEquals([], $result->getData());
        $this->assertEquals(403, $result->getHttpCode());

        $this->assertInternalType('array', $result->getMeta());
        $this->arrayHasKey('error', $result->getMeta());
    }

    /**
     * @covers ::__construct
     * @covers ::explore
     * @covers ::isUserAllowedToEditWorkbook
     */
    public function testExploreUnlessWorkbookLockedByAnotherUser()
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

        $result = $controller->explore($mocks['request'], $mocks['projectId'], $mocks['worksheetId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
        $this->assertInternalType('array', $result->getData());
        $this->assertEquals([], $result->getData());
        $this->assertEquals(403, $result->getHttpCode());

        $this->assertInternalType('array', $result->getMeta());
        $this->arrayHasKey('error', $result->getMeta());
    }

    public function exploreProvider()
    {
        return [
            [ // #0
                'postParams' => [
                    'workbook_id' => 12,
                    'name' => 'Dave',
                    'explore' => '{"dave":"bob"}',
                    'start' => '',
                    'end' => '',
                    'chart_type' => 'tornado',
                    'type' => 'freqDist'
                ],
                'Dave',
                ['dave' => 'bob'],
                '',
                '',
                'chartType' => Chart::TYPE_TORNADO,
                'analysisType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                true
            ],
            [ // #1
                'postParams' => [
                    'workbook_id' => 12,
                    'name' => 'Dave',
                    'explore' => '{"dave":"bob"}',
                    'start' => '',
                    'end' => ''
                ],
                'Dave',
                ['dave' => 'bob'],
                '',
                '',
                'chartType' => Chart::TYPE_TORNADO,
                'analysisType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                false
            ],
            [ // #2
                'postParams' => [
                    'workbook_id' => 12,
                    'name' => 'Dave',
                    'explore' => '{"dave":"bob"}',
                    'start' => '12345678',
                    'end' => '123456789',
                    'chart_type' => 'tornado',
                    'type' => 'freqDist'
                ],
                'Dave',
                ['dave' => 'bob'],
                '12345678',
                '123456789',
                'chartType' => Chart::TYPE_TORNADO,
                'analysisType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                true
            ],
        ];
    }

    /**
     * @dataProvider exploreProvider
     *
     * @covers ::explore
     *
     * @param array $postParams
     * @param string $name
     * @param array $explore
     * @param mixed $start
     * @param mixed $end
     * @param boolean $validInput
     */
    public function testExplore(
        array $postParams,
        $name,
        $explore,
        $start,
        $end,
        $chartType,
        $analysisType,
        $validInput = true
    ) {
        $mocks = $this->getMocks();

        $postParams2 = $postParams;
        if (isset($postParams['explore']) && is_string($postParams['explore'])) {
            $postParams2['explore'] = json_decode($postParams['explore'], true);
        }
        $mocks['exploreForm']->shouldReceive('submit')
            ->with($postParams2, null, null, ['everyone'])
            ->once();

        $mocks['exploreForm']->shouldReceive('isValid')
            ->andReturn($validInput)
            ->once();

        $errors = [
            'one' => 'error'
        ];

        $exploredWorksheet = Mockery::mock('Tornado\Project\Worksheet');

        if ($validInput) {
            $mocks['exploreForm']->shouldReceive('getData')
                ->andReturn($postParams2)
                ->once();

            $mocks['worksheetRepo']->shouldReceive('create')
                ->with($exploredWorksheet)
                ->once();

            $mocks['explorer']->shouldReceive('explore')
                ->with($mocks['worksheet'], $name, $explore, $start, $end, $chartType, $analysisType)
                ->andReturn($exploredWorksheet)
                ->once();
        } else {
            $mocks['exploreForm']->shouldReceive('getErrors')
                ->andReturn($errors)
                ->once();
        }

        $mocks['request']->shouldReceive('getPostParams')
            ->andReturn($postParams)
            ->once();

        $controller = $this->getController($mocks);

        $result = $controller->explore($mocks['request'], $mocks['projectId'], $mocks['worksheetId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);

        if ($validInput) {
            $this->assertEquals(201, $result->getHttpCode());
            $this->assertEquals([
                'project' => $mocks['project'],
                'workbook' => $mocks['workbook'],
                'worksheet' => $exploredWorksheet
            ], $result->getData());
            $this->assertEquals([], $result->getMeta());

        } else {
            $this->assertEquals(400, $result->getHttpCode());
            $this->assertEquals([], $result->getData());
            $this->assertEquals($errors, $result->getMeta());
        }
    }

    /**
     * @return array
     */
    protected function getMocks()
    {
        $mocks = [];

        $mocks['projectId'] = 10;
        $mocks['workbookId'] = 12;
        $mocks['worksheetId'] = 15;

        $mocks['project'] = Mockery::mock('Tornado\Project\Project', [
            'getId' => $mocks['projectId'],
            'getPrimaryKey' => $mocks['projectId']
        ]);

        $mocks['workbook'] = Mockery::mock('Tornado\Project\Workbook', [
            'getId' => $mocks['workbookId'],
            'getPrimaryKey' => $mocks['workbookId'],
            'getProjectId' => $mocks['projectId']
        ]);

        $mocks['worksheet'] = Mockery::mock('Tornado\Project\Worksheet', [
            'getId' => $mocks['worksheetId'],
            'getPrimaryKey' => $mocks['worksheetId'],
            'getWorkbookId' => $mocks['workbookId'],
            'getName' => 'Test Worksheet'
        ]);

        $mocks['brand'] = Mockery::mock('Tornado\Organization\Brand', [
            'getTargetPermissions' => ['everyone']
        ]);

        $mocks['workbookRepo'] = Mockery::mock('Tornado\Project\Workbook\DataMapper');
        $mocks['worksheetRepo'] = Mockery::mock('Tornado\Project\Worksheet\DataMapper');
        $mocks['chartsRepo'] = Mockery::mock('Tornado\Project\Chart\DataMapper');
        $mocks['createForm'] = Mockery::mock('Tornado\Project\Worksheet\Form\Create');
        $mocks['exploreForm'] = Mockery::mock('Tornado\Project\Worksheet\Form\Explore');
        $mocks['updateForm'] = Mockery::mock('Tornado\Project\Worksheet\Form\Update');
        $mocks['explorer'] = Mockery::mock('Tornado\Project\Worksheet\Explorer');
        $mocks['exporter'] = Mockery::mock('Tornado\Project\Worksheet\Exporter');

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
     * @return WorksheetController
     */
    protected function getController(array $mocks)
    {
        $controller = Mockery::mock(WorksheetController::class, [
            $mocks['chartsRepo'],
            $mocks['createForm'],
            $mocks['exploreForm'],
            $mocks['updateForm'],
            $mocks['explorer'],
            $mocks['exporter'],
            $mocks['locker'],
            $mocks['sessionUser']
        ])->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // mock ProjectDataAwareTrait methods
        $controller->shouldReceive('getProject')
            ->with($mocks['projectId'])
            ->andReturn($mocks['project']);
        $controller->shouldReceive('getProjectDataForWorksheetId')
            ->with($mocks['worksheetId'], $mocks['projectId'])
            ->andReturn([$mocks['project'], $mocks['workbook'], $mocks['worksheet'], $mocks['brand']]);

        $controller->setWorkbookRepository($mocks['workbookRepo']);
        $controller->setWorksheetRepository($mocks['worksheetRepo']);

        return $controller;
    }
}
