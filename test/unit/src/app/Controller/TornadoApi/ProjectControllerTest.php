<?php

namespace Test\Controller\TornadoApi;

use Mockery;

use MD\Foundation\Utils\ObjectUtils;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\JsonResponse;

use Controller\TornadoApi\ProjectController;

use DataSift\Http\Request;

use Test\Controller\TornadoApi\ProjectControllerDouble;
use Tornado\DataMapper\DataMapperInterface;
use Tornado\Organization\Agency;
use Tornado\Organization\Brand;
use Tornado\Project\Project;
use Tornado\Project\Recording;

/**
 * ProjectControllerTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Controller\TornadoApi
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass Controller\TornadoApi\ProjectController
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProjectControllerTest extends \PHPUnit_Framework_TestCase
{

    /**
     */
    public function setUp()
    {
        parent::setUp();
        include_once(__DIR__ . '/ProjectControllerDouble.php');
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @covers ::__construct
     * @covers ::show
     * @covers ::getProject
     */
    public function testShow()
    {
        $mocks = $this->getMocks();

        $mocks['projectRepo']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['projectId']])
            ->andReturn($mocks['project']);

        $mocks['recordingRepo']->shouldReceive('findByProject')
            ->once()
            ->with($mocks['project'])
            ->andReturn([$mocks['recording']]);

        $controller = $this->getController($mocks);
        $response = $controller->show($mocks['request'], $mocks['projectId']);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(json_encode([
            'id' => $mocks['projectId'],
            'name' => $mocks['name'],
            'recordings' => [$mocks['dataSiftRecordingId']],
        ]), $response->getContent());
    }

    /**
     * @covers ::__construct
     * @covers ::show
     * @covers ::getProject
     */
    public function testShowUnlessProjectNotFound()
    {
        $mocks = $this->getMocks();

        $mocks['projectRepo']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['projectId']])
            ->andReturnNull();

        $controller = $this->getController($mocks);
        $response = $controller->show($mocks['request'], $mocks['projectId']);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(json_encode(['error' => 'Project not found.']), $response->getContent());
    }

    /**
     * @covers ::__construct
     * @covers ::get
     */
    public function testGetProjectListUnlessAuthorizationFail()
    {
        $mocks = $this->getMocks();
        $mocks['request']->attributes->remove('brand');

        $controller = $this->getController($mocks);
        $response = $controller->get($mocks['request']);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(json_encode(['error' => 'Authorization failed.']), $response->getContent());
    }

    /**
     * @covers ::__construct
     * @covers ::get
     */
    public function testGetEmptyProjectList()
    {
        $mocks = $this->getMocks();

        $mocks['projectRepo']->shouldReceive('count')
            ->once()
            ->with(['brand_id' => $mocks['brandId']])
            ->andReturn(0);

        $controller = $this->getController($mocks);
        $response = $controller->get($mocks['request']);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(json_encode([
            'page' => 0,
            'pages' => 0,
            'per_page' => 25,
            'count' => 0,
            'projects' => [],
        ]), $response->getContent());
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::getProjectViewData
     */
    public function testGetProjectListBasedOnQueryUrl()
    {
        $mocks = $this->getMocks();
        $mocks['request']->query->set('page', 2);
        $mocks['request']->query->set('sort', 'name');
        $mocks['request']->query->set('per_page', 10);
        $mocks['request']->query->set('order', DataMapperInterface::ORDER_DESCENDING);

        $mocks['projectRepo']->shouldReceive('count')
            ->once()
            ->with(['brand_id' => $mocks['brandId']])
            ->andReturn(count($mocks['projects']));
        $mocks['projectRepo']->shouldReceive('find')
            ->once()
            ->with(
                ['brand_id' => $mocks['brandId']],
                ['name' => 'desc'],
                10,
                0
            )
            ->andReturn($mocks['projects']);
        $mocks['recordingRepo']->shouldReceive('findByProjectIds')
            ->once()
            ->with(ObjectUtils::pluck($mocks['projects'], 'id'))
            ->andReturn($mocks['recordings']);

        $projectsResponse = [];
        foreach ($mocks['projects'] as $project) {
            $projectViewData = [];
            $projectViewData['id'] = $project->getId();
            $projectViewData['name'] = $project->getName();
            $projectViewData['recordings'] = ObjectUtils::pluck(
                ObjectUtils::filter($mocks['recordings'], 'project_id', $project->getId()),
                'dataSiftRecordingId'
            );

            $projectsResponse[] = $projectViewData;
        }

        $controller = $this->getController($mocks);
        $response = $controller->get($mocks['request']);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(json_encode([
            'page' => 1,
            'pages' => 1,
            'per_page' => 10,
            'count' => count($mocks['projects']),
            'projects' => $projectsResponse,
        ]), $response->getContent());
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::getProjectViewData
     */
    public function testGetProjectListBasedDefaultParam()
    {
        $mocks = $this->getMocks();

        $mocks['projectRepo']->shouldReceive('count')
            ->once()
            ->with(['brand_id' => $mocks['brandId']])
            ->andReturn(count($mocks['projects']));
        $mocks['projectRepo']->shouldReceive('find')
            ->once()
            ->with(
                ['brand_id' => $mocks['brandId']],
                ['created_at' => 'asc'],
                25,
                0
            )
            ->andReturn($mocks['projects']);
        $mocks['recordingRepo']->shouldReceive('findByProjectIds')
            ->once()
            ->with(ObjectUtils::pluck($mocks['projects'], 'id'))
            ->andReturn($mocks['recordings']);

        $projectsResponse = [];
        foreach ($mocks['projects'] as $project) {
            $projectViewData = [];
            $projectViewData['id'] = $project->getId();
            $projectViewData['name'] = $project->getName();
            $projectViewData['recordings'] = ObjectUtils::pluck(
                ObjectUtils::filter($mocks['recordings'], 'project_id', $project->getId()),
                'dataSiftRecordingId'
            );

            $projectsResponse[] = $projectViewData;
        }

        $controller = $this->getController($mocks);
        $response = $controller->get($mocks['request']);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(json_encode([
            'page' => 1,
            'pages' => 1,
            'per_page' => 25,
            'count' => count($mocks['projects']),
            'projects' => $projectsResponse,
        ]), $response->getContent());
    }

    /**
     * @covers ::__construct
     * @covers ::delete
     * @covers ::getProject
     */
    public function testDeleteUnlessUnauthorized()
    {
        $mocks = $this->getMocks();
        $mocks['request']->attributes->remove('brand');

        $controller = $this->getController($mocks);
        $response = $controller->delete($mocks['request'], $mocks['projectId']);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(json_encode(['error' => 'Authorization failed.']), $response->getContent());
    }

    /**
     * @covers ::__construct
     * @covers ::delete
     * @covers ::getProject
     */
    public function testDeleteUnlessInvalidBrandType()
    {
        $mocks = $this->getMocks();
        $mocks['request']->attributes->set('brand', 'not a valid brand');

        $controller = $this->getController($mocks);
        $response = $controller->delete($mocks['request'], $mocks['projectId']);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals(json_encode(['error' => 'Authorization failed.']), $response->getContent());
    }

    /**
     * @covers ::__construct
     * @covers ::delete
     * @covers ::getProject
     */
    public function testDeleteUnlessProjectNotFound()
    {
        $mocks = $this->getMocks();

        $mocks['projectRepo']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['projectId']])
            ->andReturnNull();

        $controller = $this->getController($mocks);
        $response = $controller->delete($mocks['request'], $mocks['projectId']);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(json_encode(['error' => 'Project not found.']), $response->getContent());
    }

    /**
     * @covers ::__construct
     * @covers ::delete
     * @covers ::getProject
     */
    public function testDeleteUnlessAccessForbidden()
    {
        $mocks = $this->getMocks();

        $mocks['projectRepo']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['projectId']])
            ->andReturn($mocks['project']);
        $mocks['project']->setBrandId($mocks['brandId'] + 1); // make sure its different ID

        $controller = $this->getController($mocks);
        $response = $controller->delete($mocks['request'], $mocks['projectId']);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals(json_encode(['error' => 'Access forbidden.']), $response->getContent());
    }

    /**
     * DataProvider for testCreate
     *
     * @return array
     */
    public function createProvider()
    {
        return [
            'Happy path' => [
                'request' => $this->getRequest(
                    [],
                    [],
                    [
                        'name' => 'testName',
                        'recordings' => ['a', 'b']
                    ],
                    [
                        'brand' => $this->getBrand(['setId' => 10])
                    ]
                ),
                'expected' => [
                    'success' => 'Yay!'
                ],
                'expectedCode' => 201,
                'projectParams' => [
                    'name' => 'testName',
                    'brand_id' => 10
                ],
                'projectValid' => true,
                'recordingIds' => ['a', 'b'],
                'recordings' => ['c', 'd']
            ],
            'Happy path, recording_id' => [
                'request' => $this->getRequest(
                    [],
                    [],
                    [
                        'name' => 'testName',
                        'recording_id' => 'a'
                    ],
                    [
                        'brand' => $this->getBrand(['setId' => 10])
                    ]
                ),
                'expected' => [
                    'success' => 'Yay!'
                ],
                'expectedCode' => 201,
                'projectParams' => [
                    'name' => 'testName',
                    'brand_id' => 10
                ],
                'projectValid' => true,
                'recordingIds' => ['a'],
                'recordings' => ['c', 'd']
            ],
            'No brand' => [
                'request' => $this->getRequest(),
                'expected' => [
                    'error' => 'Authorization failed.'
                ],
                'expectedCode' => 401
            ],
            'Bad brand' => [
                'request' => $this->getRequest([], [], [], ['brand' => 'Not a Brand']),
                'expected' => [
                    'error' => 'Authorization failed.'
                ],
                'expectedCode' => 401
            ],
            'Recordings bad' => [
                'request' => $this->getRequest(
                    [],
                    [],
                    [
                        'recordings' => 'dave'
                    ],
                    [
                        'brand' => $this->getBrand(['setId' => 10])
                    ]
                ),
                'expected' => [
                    'error' => 'The recordings field must be an array of recording ids'
                ],
                'expectedCode' => 400
            ],
            'Invalid Project data' => [
                'request' => $this->getRequest(
                    [],
                    [],
                    [
                        'name' => 'testName',
                        'recordings' => ['a', 'b']
                    ],
                    [
                        'brand' => $this->getBrand(['setId' => 10])
                    ]
                ),
                'expected' => [
                    'errors' => ['Invalid']
                ],
                'expectedCode' => 400,
                'projectParams' => [
                    'name' => 'testName',
                    'brand_id' => 10
                ],
                'projectValid' => false
            ],
            'processRecordings fails' => [
                'request' => $this->getRequest(
                    [],
                    [],
                    [
                        'name' => 'testName',
                        'recordings' => ['a', 'b']
                    ],
                    [
                        'brand' => $this->getBrand(['setId' => 10])
                    ]
                ),
                'expected' => [
                    'error' => 'Message'
                ],
                'expectedCode' => 499,
                'projectParams' => [
                    'name' => 'testName',
                    'brand_id' => 10
                ],
                'projectValid' => true,
                'recordingIds' => ['a', 'b'],
                'recordings' => new JsonResponse(['error' => 'Message'], 499)
            ]
        ];
    }

    /**
     * @dataProvider createProvider
     *
     * @covers ::create
     *
     * @param \DataSift\Http\Request $request
     * @param array $expected
     * @param integer $expectedCode
     * @param array $projectParams
     * @param boolean $projectValid
     * @param array $recordingIds
     * @param mixed $recordings
     */
    public function testCreate(
        Request $request,
        array $expected,
        $expectedCode,
        array $projectParams = [],
        $projectValid = true,
        $recordingIds = [],
        $recordings = false
    ) {
        $mocks = $this->getMocks();

        $projectForm = $mocks['createProjectForm'];
        $projectForm->shouldReceive('submit')
            ->withArgs([$projectParams]);

        $projectForm->shouldReceive('isValid')
            ->andReturn($projectValid);

        $projectForm->shouldReceive('getErrors')
            ->andReturn(isset($expected['errors']) ? $expected['errors'] : '');

        $project = Mockery::mock('\Tornado\Project\Project');
        $project->shouldReceive('setType')
            ->times(($projectValid && is_array($recordings)) ? 1 : 0)
            ->withArgs([Project::TYPE_API]);
        $project->shouldReceive('setRecordingFilter')
            ->times(($projectValid && is_array($recordings)) ? 1 : 0)
            ->withArgs([Project::RECORDING_FILTER_API]);

        $projectForm->shouldReceive('getData')
            ->andReturn($project);

        $mocks['projectRepo']->shouldReceive('create')
            ->times(($projectValid && is_array($recordings)) ? 1 : 0)
            ->withArgs([$project]);

        $controller = $this->getMock(
            '\Test\Controller\TornadoApi\ProjectControllerDouble',
            [
                'processRecordings',
                'saveRecordings',
                'getProjectViewData'
            ],
            [
                $mocks['recordingRepo'],
                $mocks['projectRepo'],
                $mocks['createProjectForm'],
                $mocks['updateProjectForm'],
                $mocks['createRecordingForm'],
                $mocks['pylon']
            ]
        );

        if ($projectValid) {
            $controller->expects($this->any())
                ->method('processRecordings')
                ->with($request->get('brand'), $recordingIds)
                ->will($this->returnValue($recordings));

            if (is_array($recordings)) {
                $controller->expects($this->once())
                    ->method('saveRecordings')
                    ->with($recordings, $project);

                $controller->expects($this->once())
                    ->method('getProjectViewData')
                    ->with($project, $recordings)
                    ->will($this->returnValue($expected));
            }
        }

        $response = $controller->create($request);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(json_encode($expected), $response->getContent());
        $this->assertEquals($expectedCode, $response->getStatusCode());
    }

    /**
     * DataProvider for testUpdate
     *
     * @return array
     */
    public function updateProvider()
    {
        return [
            'Happy path, name, no recordings' => [
                'request' => $this->getRequest(
                    [],
                    [],
                    [
                        'name' => 'bob'
                    ],
                    ['brand' => $this->getBrand(['setId' => 20])]
                ),
                10,
                'expected' => [
                    'all' => 'good'
                ],
                'expectedCode' => 200,
                'project' => $this->getProject(['setBrandId' => 20, 'setName' => 'dave']),
                'projectParams' => [
                    'name' => 'bob'
                ],
                'projectValid' => true
            ],
            'Happy path, name, no recordings' => [
                'request' => $this->getRequest(
                    [],
                    [],
                    [
                        'name' => 'bob',
                        'recordings' => ['a', 'b']
                    ],
                    ['brand' => $this->getBrand(['setId' => 20])]
                ),
                10,
                'expected' => [
                    'all' => 'good'
                ],
                'expectedCode' => 200,
                'project' => $this->getProject(['setBrandId' => 20, 'setName' => 'dave']),
                'projectParams' => [
                    'name' => 'bob'
                ],
                'projectValid' => true,
                'recordingIds' => ['a', 'b'],
                'recordings' => [
                    'a' => $this->getRecording(['setDataSiftRecordingId' => 'a']),
                    'b' => $this->getRecording(['setDataSiftRecordingId' => 'b'])
                ]
            ],
            'No brand' => [
                'request' => $this->getRequest(),
                10,
                'expected' => [
                    'error' => 'Authorization failed.'
                ],
                'expectedCode' => 401
            ],
            'Bad brand' => [
                'request' => $this->getRequest([], [], [], ['brand' => 'Not a Brand']),
                10,
                'expected' => [
                    'error' => 'Authorization failed.'
                ],
                'expectedCode' => 401
            ],
            'Bad project' => [
                'request' => $this->getRequest(
                    [],
                    [],
                    [],
                    ['brand' => $this->getBrand(['setId' => 20])]
                ),
                10,
                'expected' => [
                    'error' => 'Project not found.'
                ],
                'expectedCode' => 404
            ],
            'Project does not match Brand' => [
                'request' => $this->getRequest(
                    [],
                    [],
                    [],
                    ['brand' => $this->getBrand(['setId' => 20])]
                ),
                10,
                'expected' => [
                    'error' => 'Access forbidden.'
                ],
                'expectedCode' => 403,
                'project' => $this->getProject(['setBrandId' => 30])
            ],
            'Project data invalid' => [
                'request' => $this->getRequest(
                    [],
                    [],
                    [
                        'name' => 'bob'
                    ],
                    ['brand' => $this->getBrand(['setId' => 20])]
                ),
                10,
                'expected' => [
                    'errors' => ['BAD']
                ],
                'expectedCode' => 400,
                'project' => $this->getProject(['setBrandId' => 20, 'setName' => 'dave']),
                'projectParams' => [
                    'name' => 'bob'
                ],
                'projectValid' => false,
            ],
        ];
    }

    /**
     * @dataProvider updateProvider
     *
     * @covers ::update
     * @covers ::getProject
     *
     * @param \DataSift\Http\Request $request
     * @param string $id
     * @param array $expected
     * @param integer $expectedCode
     * @param \Tornado\Project\Project $project
     * @param array $projectParams
     * @param boolean $projectValid
     * @param array $recordingIds
     * @param mixed $recordings
     */
    public function testUpdate(
        Request $request,
        $id,
        array $expected,
        $expectedCode,
        Project $project = null,
        array $projectParams = [],
        $projectValid = false,
        $recordingIds = [],
        $recordings = false
    ) {
        $mocks = $this->getMocks();

        $mocks['projectRepo']->shouldReceive('findOne')
            ->withArgs([['id' => $id]])
            ->andReturn($project);

        $projectForm = $mocks['updateProjectForm'];

        $projectForm->shouldReceive('submit')
            ->withArgs([$projectParams, $project]);

        $projectForm->shouldReceive('isValid')
            ->andReturn($projectValid);

        $projectForm->shouldReceive('getErrors')
            ->andReturn(isset($expected['errors']) ? $expected['errors'] : '');

        $project = Mockery::mock('\Tornado\Project\Project');

        $projectForm->shouldReceive('getData')
            ->andReturn($project);

        $mocks['projectRepo']->shouldReceive('update')
            ->times(($projectValid) ? 1 : 0)
            ->withArgs([$project]);

        $existingRecordings = [
            $this->getRecording(['setDataSiftRecordingId' => 'a']),
            $this->getRecording(['setDataSiftRecordingId' => 'b'])
        ];

        $mocks['recordingRepo']->shouldReceive('findByProject')
            ->withArgs([$project])
            ->andReturn($existingRecordings);

        $controller = $this->getMock(
            '\Test\Controller\TornadoApi\ProjectControllerDouble',
            [
                'processRecordings',
                'saveRecordings',
                'getProjectViewData'
            ],
            [
                $mocks['recordingRepo'],
                $mocks['projectRepo'],
                $mocks['createProjectForm'],
                $mocks['updateProjectForm'],
                $mocks['createRecordingForm'],
                $mocks['pylon']
            ]
        );

        if ($projectValid) {
            if (is_array($recordings)) {
                $controller->expects($this->once())
                    ->method('processRecordings')
                    ->with($request->get('brand'), $recordingIds)
                    ->will($this->returnValue($recordings));
                $controller->expects($this->once())
                    ->method('saveRecordings')
                    ->with($recordings, $project)
                    ->will($this->returnValue($recordings));
            } else {
                $recordings = $existingRecordings;
            }
            $controller->expects($this->any())
                    ->method('getProjectViewData')
                    ->with($project, $recordings)
                    ->will($this->returnValue($expected));
        }

        $response = $controller->update($request, $id);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(json_encode($expected), $response->getContent());
        $this->assertEquals($expectedCode, $response->getStatusCode());
    }

    /**
     * @covers ::__construct
     * @covers ::delete
     * @covers ::getProject
     */
    public function testDelete()
    {
        $mocks = $this->getMocks();

        $mocks['project']->setBrandId($mocks['brandId']);
        $mocks['projectRepo']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['projectId']])
            ->andReturn($mocks['project']);
        $mocks['projectRepo']->shouldReceive('delete')
            ->once()
            ->with($mocks['project'])
            ->andReturnNull();

        $controller = $this->getController($mocks);
        $response = $controller->delete($mocks['request'], $mocks['projectId']);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals(json_encode([]), $response->getContent());
    }

    /**
     * DataProvider for testSaveRecordings
     *
     * @return array
     */
    public function saveRecordingsProvider()
    {
        $project = new Project();
        $project->setId(10);

        $recording1 = new Recording();

        $recording2 = new Recording();
        $recording2->setProjectId(10);

        $recording3 = new Recording();

        return [
            'No recordings' => [
                'recordings' => [],
                'project' => $project,
                'clearDelta' => false,
                'expectedRecordings' => [],
            ],
            'One recording, no delta required' => [
                'recordings' => [
                    'one' => $recording1
                ],
                'project' => $project,
                'cleanDelta' => false,
                'expectedRecordings' => [
                    'one' => $recording1
                ],

            ],
            'Two recordings, delta required' => [
                'recordings' => [
                    'one' => $recording3,
                    'two' => $recording2
                ],
                'project' => $project,
                'cleanDelta' => true,
                'expectedRecordings' => [
                    'two' => $recording2
                ],
            ]
        ];
    }

    /**
     * @dataProvider saveRecordingsProvider
     *
     * @covers ::saveRecordings
     *
     * @param array $recordings
     * @param array $expectedRecordings
     * @param \Tornado\Project\Project $project
     */
    public function testSaveRecordings(array $recordings, Project $project, $cleanDelta, array $expectedRecordings)
    {
        $mocks = $this->getMocks();
        $mocks['recordingRepo']
            ->shouldReceive('upsert')
            ->times(count($recordings));
        $controller = $this->getControllerDouble($mocks);
        $output = $controller->saveRecordings($recordings, $project, $cleanDelta);
        $this->assertEquals($expectedRecordings, $output);

        if ($project) {
            foreach ($output as $recording) {
                $this->assertEquals($project->getId(), $recording->getProjectId());
            }
        }
    }

    /**
     * DataProvider for testProcessRecordings
     *
     * @return array
     */
    public function processRecordingsProvider()
    {
        $project = new Project();
        $project->setId(20);

        $recording1 = new Recording();
        $recording2 = new Recording();
        $recording3 = new Recording();
        $recording4 = new Recording();

        return [
            'Invalid hash' => [
                'brand' => new Brand(),
                'recordingIds' => [
                    'a'
                ],
                'recordings' => [],
                'expected' => new JsonResponse([], 400)
            ],
            'Hash not found' => [
                'brand' => new Brand(),
                'recordingIds' => [
                    'abc123abc123abc123abc123abc123ab'
                ],
                'recordings' => [],
                'expected' => new JsonResponse([], 404),
                null,
                new \RuntimeException('Error', 404)
            ],
            'Good list, no project' => [
                'brand' => new Brand(),
                'recordingIds' => [
                    'abc123abc123abc123abc123abc123ab',
                    'bbc123abc123abc123abc123abc123ab'
                ],
                'recordings' => [
                    'abc123abc123abc123abc123abc123ab' => $recording1,
                    'bbc123abc123abc123abc123abc123ab' => $recording2,
                ],
                'expected' => [
                    'abc123abc123abc123abc123abc123ab' => $recording1,
                    'bbc123abc123abc123abc123abc123ab' => $recording2,
                ]
            ],
            'Good list, project' => [
                'brand' => new Brand(),
                'recordingIds' => [
                    'abc123abc123abc123abc123abc123ab',
                    'bbc123abc123abc123abc123abc123ab'
                ],
                'recordings' => [
                    'abc123abc123abc123abc123abc123ab' => $recording3,
                    'bbc123abc123abc123abc123abc123ab' => $recording4,
                ],
                'expected' => [
                    'abc123abc123abc123abc123abc123ab' => $recording3,
                    'bbc123abc123abc123abc123abc123ab' => $recording4,
                ],
                'project' => $project
            ]
        ];
    }

    /**
     * @dataProvider processRecordingsProvider
     *
     * @covers ::processRecordings
     *
     * @param \Tornado\Organization\Brand $brand
     * @param array $recordingIds
     * @param array $recordings
     * @param mixed $expected
     * @param \Tornado\Project\Project|null $project
     * @param \Exception $importException
     */
    public function testProcessRecordings(
        Brand $brand,
        array $recordingIds,
        array $recordings,
        $expected,
        Project $project = null,
        $importException = null
    ) {

        $controller = $this->getMock(
            '\Test\Controller\TornadoApi\ProjectControllerDouble',
            ['findOrImportRecording'],
            [],
            '',
            false
        );

        $self = $this; // 'orrible...
        $controller->expects($this->any())
            ->method('findOrImportRecording')
            ->will($this->returnCallback(
                function (
                    Brand $callBrand,
                    $recordingId,
                    Project $callProject = null
                ) use (
                    $importException,
                    $recordings,
                    $brand,
                    $project,
                    $self
                ) {
                    if ($importException) {
                        throw $importException;
                    }

                    $self->assertEquals($brand, $callBrand);
                    $self->assertEquals($project, $callProject);

                    return (isset($recordings[$recordingId])) ? $recordings[$recordingId] : false;
                }
            ));

        $result = $controller->processRecordings($brand, $recordingIds, $project);

        if ($expected instanceof JsonResponse) {
            $this->assertInstanceOf('\Symfony\Component\HttpFoundation\JsonResponse', $result);
            $this->assertEquals($expected->getStatusCode(), $result->getStatusCode());
        } else {
            $this->assertEquals($expected, $result);
            if ($project) {
                foreach ($result as $item) {
                    $this->assertEquals($project->getId(), $item->getProjectId());
                }
            }
        }
    }

    /**
     * DataProvider for testFindOrImportRecording
     *
     * @return array
     */
    public function findOrImportRecordingProvider()
    {
        return [
            'Not found' => [
                'brand' => $this->getBrand(['setId' => 20]),
                'id' => 'abc123abc123abc123abc123abc123ab',
                'foundInTornado' => false,
                'foundInPylon' => false,
                'recording' => null,
                'project' => null,
                'expectedException' => '\RuntimeException',
                'expectedExceptionCode' => 404
            ],
            'Found in Pylon' => [
                'brand' => $this->getBrand(['setId' => 20]),
                'id' => 'abc123abc123abc123abc123abc123ab',
                'foundInTornado' => false,
                'foundInPylon' => true,
                'recording' => new Recording()
            ],
            'Found in Pylon, with Project' => [
                'brand' => $this->getBrand(['setId' => 20]),
                'id' => 'abc123abc123abc123abc123abc123ab',
                'foundInTornado' => false,
                'foundInPylon' => true,
                'recording' => new Recording(),
                'project' => $this->getProject(['setId' => 30])
            ],
            'Found in Tornado' => [
                'brand' => $this->getBrand(['setId' => 20]),
                'id' => 'abc123abc123abc123abc123abc123ab',
                'foundInTornado' => true,
                'foundInPylon' => false,
                'recording' => new Recording()
            ],
            'Found in Tornado, with no Project' => [
                'brand' => $this->getBrand(['setId' => 20]),
                'id' => 'abc123abc123abc123abc123abc123ab',
                'foundInTornado' => true,
                'foundInPylon' => false,
                'recording' => new Recording(),
                'project' => $this->getProject(['setId' => 30])
            ],
            'Found in Tornado, with the same Project' => [
                'brand' => $this->getBrand(['setId' => 20]),
                'id' => 'abc123abc123abc123abc123abc123ab',
                'foundInTornado' => true,
                'foundInPylon' => false,
                'recording' => $this->getRecording(['setProjectId' => 30]),
                'project' => $this->getProject(['setId' => 30])
            ],
            'Found in Tornado, with a different Project' => [
                'brand' => $this->getBrand(['setId' => 20]),
                'id' => 'abc123abc123abc123abc123abc123ab',
                'foundInTornado' => true,
                'foundInPylon' => false,
                'recording' => $this->getRecording(['setProjectId' => 20]),
                'project' => $this->getProject(['setId' => 30]),
                'expectedException' => '\RuntimeException',
                'expectedExceptionCode' => 409
            ]
        ];
    }

    /**
     * @dataProvider findOrImportRecordingProvider
     *
     * @covers ::findOrImportRecording
     *
     * @param \Tornado\Organization\Brand $brand
     * @param string $id
     * @param boolean $foundInTornado
     * @param boolean $foundInPylon
     * @param \Tornado\Project\Recording $recording
     * @param \Tornado\Project\Project $project
     * @param string $expectedException
     * @param integer $expectedExceptionCode
     */
    public function testFindOrImportRecording(
        Brand $brand,
        $id,
        $foundInTornado,
        $foundInPylon,
        Recording $recording = null,
        Project $project = null,
        $expectedException = '',
        $expectedExceptionCode = 0
    ) {

        $mocks = $this->getMocks();
        $mocks['recordingRepo']->shouldReceive('findOne')
            ->withArgs([['brand_id' => $brand->getId(), 'datasift_recording_id' => $id]])
            ->andReturn(($foundInTornado) ? $recording : false);

        $mocks['recordingRepo']->shouldReceive('importRecording')
            ->times(($foundInTornado) ? 0 : 1)
            ->withArgs([$mocks['pylon'], $id])
            ->andReturn(($foundInPylon) ? $recording : false);

        $controller = $this->getControllerDouble($mocks);

        try {
            $this->assertEquals($recording, $controller->findOrImportRecording($brand, $id, $project));
        } catch (\Exception $ex) {
            if ($expectedException) {
                $this->assertInstanceOf($expectedException, $ex);
                if ($expectedExceptionCode) {
                    $this->assertEquals($expectedExceptionCode, $ex->getCode());
                }
                return true;
            }
            throw $ex;
        }

        if ($expectedException) {
            $this->fail("'Expected an Exception of type {$expectedException}");
        }
    }

    /**
     * @param array $mocks
     *
     * @return \Controller\TornadoApi\ProjectController
     */
    protected function getController(array $mocks)
    {
        return new ProjectController(
            $mocks['recordingRepo'],
            $mocks['projectRepo'],
            $mocks['createProjectForm'],
            $mocks['updateProjectForm'],
            $mocks['createRecordingForm'],
            $mocks['pylon']
        );
    }

    /**
     * @param array $mocks
     *
     * @return \Test\Controller\TornadoApi\ProjectControllerDouble
     */
    protected function getControllerDouble(array $mocks)
    {
        return new ProjectControllerDouble(
            $mocks['recordingRepo'],
            $mocks['projectRepo'],
            $mocks['createProjectForm'],
            $mocks['updateProjectForm'],
            $mocks['createRecordingForm'],
            $mocks['pylon']
        );
    }

    /**
     * Prepares mocks for this test
     *
     * @return array
     */
    protected function getMocks()
    {
        $recordingRepo = Mockery::mock('\Tornado\Project\Recording\DataMapper');
        $projectRepo = Mockery::mock('\Tornado\Project\Project\DataMapper');
        $createProjectForm = Mockery::mock('\Tornado\Project\Project\Form\Create');
        $updateProjectForm = Mockery::mock('\Tornado\Project\Project\Form\Update');
        $createRecordingForm = Mockery::mock('\Tornado\Project\Recording\Form\Create');
        $pylon = Mockery::mock('\DataSift_Pylon');

        $recordingId = 99;
        $brandId = 10;
        $dataSiftRecordingId = 'hash';
        $hash = $dataSiftRecordingId;
        $recording = new Recording();
        $recording->setId($recordingId);
        $recording->setBrandId($brandId);
        $recording->setDatasiftRecordingId($dataSiftRecordingId);

        $brand = new Brand();
        $brand->setId($brandId);

        $name = 'test';
        $projectId = 20;
        $project = new Project();
        $project->setId($projectId);
        $project->setName($name);
        $project->setBrandId($brandId);
        $project->setRecordingFilter(Project::RECORDING_FILTER_API);
        $recording->setProjectId($projectId);

        $request = new Request();
        $request->attributes->set('brand', $brand);
        $request->request->set('recording_id', $dataSiftRecordingId);
        $request->request->set('name', $name);

        $recordings[] = $recording;
        for ($i = 1; $i <= 5; $i++) {
            $rec = new Recording();
            $rec->setId($i);
            $rec->setBrandId($brandId);
            $rec->setDatasiftRecordingId($dataSiftRecordingId);
        }
        $projects[] = $project;
        for ($i = 1; $i <= 5; $i++) {
            $pro = new Project();
            $pro->setId($i);
            $pro->setName('test' . $i);
            $pro->setRecordingFilter(Project::RECORDING_FILTER_API);

            $projects[] = $pro;
        }

        return [
            'recordingRepo' => $recordingRepo,
            'createProjectForm' => $createProjectForm,
            'updateProjectForm' => $updateProjectForm,
            'createRecordingForm' => $createRecordingForm,
            'projectRepo' => $projectRepo,
            'recording' => $recording,
            'brand' => $brand,
            'brandId' => $brandId,
            'recordingId' => $recordingId,
            'hash' => $hash,
            'dataSiftRecordingId' => $dataSiftRecordingId,
            'project' => $project,
            'brand' => $brand,
            'name' => $name,
            'projectId' => $projectId,
            'request' => $request,
            'recordings' => $recordings,
            'projects' => $projects,
            'pylon' => $pylon
        ];
    }

    /**
     * Gets a Brand for testing
     *
     * @param array $setters
     *
     * @return \Tornado\Organization\Brand
     */
    private function getBrand(array $setters = [])
    {
        $brand = new Brand();
        foreach ($setters as $setter => $value) {
            $brand->{$setter}($value);
        }

        return $brand;
    }

    /**
     * Gets a Project for testing
     *
     * @param array $setters
     *
     * @return \Tornado\Project\Project
     */
    private function getProject(array $setters = [])
    {
        $project = new Project();
        foreach ($setters as $setter => $value) {
            $project->{$setter}($value);
        }

        return $project;
    }

    /**
     * Gets a Recording for testing
     *
     * @param array $setters
     *
     * @return \Tornado\Project\Recording
     */
    private function getRecording(array $setters = [])
    {
        $recording = new Recording();
        foreach ($setters as $setter => $value) {
            $recording->{$setter}($value);
        }

        return $recording;
    }

    /**
     * Gets a Request for testing
     *
     * @param array $headers
     * @param array $query
     * @param array $body
     * @param array $attributes
     *
     * @return \DataSift\Http\Request
     */
    private function getRequest(
        array $headers = [],
        array $query = [],
        array $body = [],
        array $attributes = []
    ) {
        $request = new Request();
        $request->headers = new ParameterBag($headers);
        $request->query = new ParameterBag($query);
        $request->request = new ParameterBag($body);
        $request->attributes = new ParameterBag($attributes);

        return $request;
    }
}
