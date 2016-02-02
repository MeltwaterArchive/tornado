<?php

namespace Test\Controller\TornadoApi;

use Mockery;

use MD\Foundation\Utils\ObjectUtils;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\JsonResponse;

use Controller\TornadoApi\RecordingController;

use DataSift\Http\Request;

use Test\Controller\TornadoApi\ProjectControllerDouble;
use Tornado\DataMapper\DataMapperInterface;
use Tornado\Organization\Agency;
use Tornado\Organization\Brand;
use Tornado\Project\Project;
use Tornado\Project\Recording;

/**
 * RecordingControllerTest
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
 * @coversDefaultClass Controller\TornadoApi\RecordingController
 *
 * @xSuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RecordingControllerTest extends \PHPUnit_Framework_TestCase
{

    /**
     */
    public function setUp()
    {
        parent::setUp();
        //include_once(__DIR__ . '/ProjectControllerDouble.php');
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * DataProvider for testCreate
     *
     * @return array
     */
    public function createProvider()
    {
        return [
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
            'Invalid form' => [
                'request' => $this->getRequest(
                    [],
                    [],
                    [
                        'name' => 'test',
                        'hash' => 'abc123abc123abc123abc123abc123ab'
                    ],
                    ['brand' => $this->getBrand(['setId' => 10])]
                ),
                'expected' => [
                    'errors' => 'Bad'
                ],
                'expectedCode' => 400,
                'recordingParams' => [
                    'name' => 'test',
                    'hash' => 'abc123abc123abc123abc123abc123ab',
                    'csdl' => '// Created via API',
                    'brand_id' => 10
                ],
                'recordingValid' => false
            ],
            'Recording exists' => [
                'request' => $this->getRequest(
                    [],
                    [],
                    [
                        'name' => 'test',
                        'hash' => 'abc123abc123abc123abc123abc123ab'
                    ],
                    ['brand' => $this->getBrand(['setId' => 10])]
                ),
                'expected' => [
                    'error' => 'Recording abc123abc123abc123abc123abc123ab already exists'
                ],
                'expectedCode' => 409,
                'recordingParams' => [
                    'name' => 'test',
                    'hash' => 'abc123abc123abc123abc123abc123ab',
                    'csdl' => '// Created via API',
                    'brand_id' => 10
                ],
                'recordingValid' => true,
                'recording' => $this->getRecording(),
                'recordingExists' => true
            ],
            'Subscription exists' => [
                'request' => $this->getRequest(
                    [],
                    [],
                    [
                        'name' => 'test',
                        'hash' => 'abc123abc123abc123abc123abc123ab'
                    ],
                    ['brand' => $this->getBrand(['setId' => 10])]
                ),
                'expected' => [
                    'id' => 'abc123abc123abc123abc123abc123ab',
                    'hash' => 'abc123abc123abc123abc123abc123ab',
                    'name' => 'test',
                    'status' => 'started',
                    'created_at' => 123456,
                ],
                'expectedCode' => 201,
                'recordingParams' => [
                    'name' => 'test',
                    'hash' => 'abc123abc123abc123abc123abc123ab',
                    'csdl' => '// Created via API',
                    'brand_id' => 10
                ],
                'recordingValid' => true,
                'recording' => $this->getRecording([
                    'setName' => 'Once',
                    'setHash' => 'abc123abc123abc123abc123abc123ab',
                    'setDataSiftRecordingId' => 'abc123abc123abc123abc123abc123ab',
                    'setStatus' => 'started',
                    'setCreatedAt' => 123456
                ]),
                'recordingExists' => false,
                'subscriptionExists' => true
            ],
            'Hash does not exist' => [
                'request' => $this->getRequest(
                    [],
                    [],
                    [
                        'name' => 'test',
                        'hash' => 'abc123abc123abc123abc123abc123ab'
                    ],
                    ['brand' => $this->getBrand(['setId' => 10])]
                ),
                'expected' => [
                    'error' => 'The referenced CSDL does not exist'
                ],
                'expectedCode' => 404,
                'recordingParams' => [
                    'name' => 'test',
                    'hash' => 'abc123abc123abc123abc123abc123ab',
                    'csdl' => '// Created via API',
                    'brand_id' => 10
                ],
                'recordingValid' => true,
                'recording' => null,
                'recordingExists' => false,
                'subscriptionExists' => false,
                'hashExists' => false
            ],
            'Hash exists' => [
                'request' => $this->getRequest(
                    [],
                    [],
                    [
                        'name' => 'test',
                        'hash' => 'abc123abc123abc123abc123abc123ab'
                    ],
                    ['brand' => $this->getBrand(['setId' => 10])]
                ),
                'expected' => [
                    'id' => 'abc123abc123abc123abc123abc123ab',
                    'hash' => 'abc123abc123abc123abc123abc123ab',
                    'name' => 'test',
                    'status' => 'stopped',
                    'created_at' => 123456,
                ],
                'expectedCode' => 201,
                'recordingParams' => [
                    'name' => 'test',
                    'hash' => 'abc123abc123abc123abc123abc123ab',
                    'csdl' => '// Created via API',
                    'brand_id' => 10
                ],
                'recordingValid' => true,
                'recording' => null,
                'recordingExists' => false,
                'subscriptionExists' => false,
                'hashExists' => true
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
     * @param array $recordingParams
     * @param boolean $recordingValid
     * @param array $recordingIds
     * @param mixed $recordings
     */
    public function testCreate(
        Request $request,
        array $expected,
        $expectedCode,
        array $recordingParams = [],
        $recordingValid = false,
        Recording $recording = null,
        $recordingExists = false,
        $subscriptionExists = false,
        $hashExists = false
    ) {

        $brandId = (isset($recordingParams['brand_id'])) ? $recordingParams['brand_id'] : null;

        $pylon = Mockery::mock('\DataSift\Pylon\Pylon');

        $recordingForm = Mockery::mock('\Tornado\Project\Recording\Form\Create');
        $recordingForm->shouldReceive('submit')
            ->withArgs([$recordingParams]);

        $recordingForm->shouldReceive('isValid')
            ->andReturn($recordingValid);

        $recordingForm->shouldReceive('getErrors')
            ->andReturn(isset($expected['errors']) ? $expected['errors'] : '');

        $recordingRepo = Mockery::mock('\Tornado\Project\Recording\DataMapper');

        if ($recordingValid) {
            $recordingRepo->shouldReceive('findOne')
                ->withArgs([['brand_id' => $brandId, 'datasift_recording_id' => $recordingParams['hash']]])
                ->andReturn(($recordingExists) ? $recording : null);

            $recordingRepo->shouldReceive('importRecording')
                ->withArgs([$pylon, $recordingParams['hash']])
                ->andReturn(($subscriptionExists) ? $recording : null);

            $pylon->shouldReceive('hashExists')
                ->withArgs([$recordingParams['hash']])
                ->andReturn($hashExists);

            $testRecording = new Recording();
            $testRecording->setCsdl($recordingParams['csdl']);
            $testRecording->setName($recordingParams['name']);
            $testRecording->setHash($recordingParams['hash']);
            $testRecording->setBrandId($brandId);

            $recordingForm->shouldReceive('getData')
                ->andReturn($testRecording);

            $recordingRepo->shouldReceive('upsert')
                ->withArgs([($hashExists && !$subscriptionExists) ? $testRecording : $recording]);
        }

        $controller = new RecordingController($recordingRepo, $recordingForm, $pylon);

        $response = $controller->create($request);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $response);
        $this->assertEquals($expectedCode, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        if ($expectedCode == 201) {
            $this->assertTrue(isset($content['created_at']));
            unset($content['created_at']);
            unset($expected['created_at']);
        }

        $this->assertEquals($expected, $content);
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
    public function xtestFindOrImportRecording(
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
