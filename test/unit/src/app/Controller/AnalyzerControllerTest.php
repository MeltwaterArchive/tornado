<?php

namespace Test\Controller;

use Mockery;

use Controller\AnalyzerController;

use Tornado\Analyze\Analysis;
use Tornado\Analyze\Dimension;
use Tornado\Analyze\Dimension\Collection;
use Tornado\Organization\User;
use Tornado\Organization\Brand;
use Tornado\Project\Chart;
use Tornado\Project\Chart\NameGenerator;
use Tornado\Project\Project;
use Tornado\Project\Recording;
use Tornado\Project\Workbook;
use Tornado\Project\Worksheet;

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
 * @covers      \Controller\AnalyzerController
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects,PHPMD.NPathComplexity)
 */
class AnalyzerControllerTest extends \PHPUnit_Framework_TestCase
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
     * DataProvider for testReturns200HttpStatusCodeUnlessInvalidRequestDataComes
     *
     * @return array
     */
    public function returns200Provider()
    {
        return [
            [ // #0
                'target' => 'fb.author.gender',
                'type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION
            ],
            [ // #1
                'target' => '',
                'type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                'expectedTarget' => Dimension::TIME
            ],
        ];
    }

    /**
     * @dataProvider returns200Provider
     *
     * @covers \Controller\AnalyzerController::__construct
     * @covers \Controller\AnalyzerController::create
     *
     * @param string $target
     * @param string $type
     * @param string $expectedTarget
     */
    public function testReturns200HttpStatusCodeUnlessInvalidRequestDataComes($target, $type, $expectedTarget = false)
    {
        $mocks = $this->getMocks($target, $type, $expectedTarget);
        $controller = $this->getController($mocks);

        // do the test

        $result = $controller->create($mocks['request']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
        $this->assertInternalType('array', $result->getData());

        $this->assertEquals(200, $result->getHttpCode());
        $this->assertEquals(['charts_count' => 2], $result->getMeta());

        $responseData = $result->getData();
        $this->assertEquals($mocks['charts'], $responseData);
        $this->assertCount(count($mocks['charts']), $responseData);
        $this->assertInstanceOf('\Tornado\Project\Chart', $responseData[0]);
        $this->assertInstanceOf('\Tornado\Project\Chart', $responseData[1]);

        $this->assertEquals(0, $responseData[0]->getRank());
        $this->assertEquals(1, $responseData[1]->getRank());
    }

    /**
     * @covers \Controller\AnalyzerController::__construct
     * @covers \Controller\AnalyzerController::create
     * @covers \Controller\AnalyzerController::isUserAllowedToEditWorkbook
     */
    public function testReturnsAccessForbiddenUnlessWorkbookIsLockedBySessionUser()
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

        // do the test
        $controller = $this->getController($mocks);

        $result = $controller->create($mocks['request']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
        $this->assertInternalType('array', $result->getData());
        $this->assertEquals([], $result->getData());
        $this->assertEquals(403, $result->getHttpCode());

        $this->assertInternalType('array', $result->getMeta());
        $this->arrayHasKey('error', $result->getMeta());
    }

    /**
     * @covers \Controller\AnalyzerController::__construct
     * @covers \Controller\AnalyzerController::create
     * @covers \Controller\AnalyzerController::isUserAllowedToEditWorkbook
     */
    public function testReturnsSuccessUnlessWorkbookIsLockedByAnotherUser()
    {
        $mocks = $this->getMocks();
        $mocks['locker'] = Mockery::mock('\Tornado\Project\Workbook\Locker');

        $mocks['locker']->shouldReceive('isLocked')
            ->once()
            ->with($mocks['workbook'])
            ->andReturn(true);
        $mocks['locker']->shouldReceive('isGranted')
            ->once()
            ->with($mocks['workbook'], $mocks['sessionUser'])
            ->andReturn(true);

        // do the test
        $controller = $this->getController($mocks);

        $result = $controller->create($mocks['request']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
        $this->assertInternalType('array', $result->getData());

        $this->assertEquals(200, $result->getHttpCode());
        $this->assertEquals(['charts_count' => 2], $result->getMeta());

        $responseData = $result->getData();
        $this->assertEquals($mocks['charts'], $responseData);
        $this->assertCount(count($mocks['charts']), $responseData);
        $this->assertInstanceOf('\Tornado\Project\Chart', $responseData[0]);
        $this->assertInstanceOf('\Tornado\Project\Chart', $responseData[1]);

        $this->assertEquals(0, $responseData[0]->getRank());
        $this->assertEquals(1, $responseData[1]->getRank());
    }

    /**
     * @covers \Controller\AnalyzerController::__construct
     * @covers \Controller\AnalyzerController::create
     */
    public function testSuccessfullyCreateUnlessWorkbookLocked()
    {
        $mocks = $this->getMocks();

        // overwrites mocks
        $formattedErrors = [
            'dimension.property1' => 'errorMsg',
            'dimension.property2.nested' => 'errorMsg',
            'dimension.property2.nested.nested' => 'errorMsg'
        ];
        // --- form ---
        $mocks['createForm'] = $this->getMockObject('DataSift\Form\FormInterface');
        $mocks['createForm']->expects($this->once())
            ->method('submit')
            ->with($mocks['requestData']);
        $mocks['createForm']->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $mocks['createForm']->expects($this->once())
            ->method('getErrors')
            ->willReturn($formattedErrors);

        // do the test

        $controller = $this->getController($mocks);

        $result = $controller->create($mocks['request']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
        $this->assertInternalType('array', $result->getData());
        $this->assertEquals([], $result->getData());
        $this->assertEquals(400, $result->getHttpCode());

        $this->assertInternalType('array', $result->getMeta());
        $this->assertEquals($formattedErrors, $result->getMeta());
    }

    /**
     * @covers \Controller\AnalyzerController::__construct
     * @covers \Controller\AnalyzerController::create
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testThrowNotFoundHttpExceptionUnlessRecordingFound()
    {
        $mocks = $this->getMocks();

        // overwrites mocks
        $mocks['recordingRepo'] = $this->getMockObject('Tornado\DataMapper\DataMapperInterface');
        $mocks['recordingRepo']->expects($this->any())
            ->method('findOne')
            ->with(['id' => $mocks['recordingId']])
            ->willReturn(null);

        // do the test
        $controller = $this->getController($mocks);

        $controller->create($mocks['request']);
    }

    /**
     * @covers \Controller\AnalyzerController::__construct
     * @covers \Controller\AnalyzerController::create
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testThrowNotFoundHttpExceptionUnlessBaselineDataSetFound()
    {
        $mocks = $this->getMocks();

        // overwrites mocks
        // -- requestData --
        $mocks['worksheet']->setBaselineDataSetId(2);
        // --- form ---
        $mocks['createForm'] = $this->getMockObject('DataSift\Form\FormInterface');
        $mocks['createForm']->expects($this->any())
            ->method('submit')
            ->with($mocks['requestData']);
        $mocks['createForm']->expects($this->any())
            ->method('isValid')
            ->willReturn(true);
        $mocks['createForm']->expects($this->any())
            ->method('getData')
            ->willReturn($mocks['worksheet']);
        // --- dataset repo ---
        $mocks['dataSetRepo'] = $this->getMockObject('Tornado\DataMapper\DataMapperInterface');
        $mocks['dataSetRepo']->expects($this->any())
            ->method('findOne')
            ->with(['id' => 2])
            ->willReturn(null);

        // do the test
        $controller = $this->getController($mocks);

        $controller->create($mocks['request']);
    }

    /**
     * @covers \Controller\AnalyzerController::__construct
     * @covers \Controller\AnalyzerController::create
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\ConflictHttpException
     */
    public function testThrowConflictHttpExceptionUnlessBaselineDataSetFound()
    {
        $mocks = $this->getMocks();

        // overwrites mocks
        $mocks['worksheet']->setBaselineDataSetId(2);
        $mocks['worksheet']->setSecondaryRecordingId(2);
        // -- request --
        $mocks['request'] = $this->getMockObject('DataSift\Http\Request', true);
        $mocks['request']->expects($this->any())
            ->method('getPostParams')
            ->willReturn($mocks['requestData']);
        // --- form ---
        $mocks['createForm'] = $this->getMockObject('DataSift\Form\FormInterface');
        $mocks['createForm']->expects($this->any())
            ->method('isValid')
            ->willReturn(true);
        $mocks['createForm']->expects($this->any())
            ->method('getData')
            ->willReturn($mocks['worksheet']);

        // do the test
        $controller = $this->getController($mocks);

        $controller->create($mocks['request']);
    }

    /**
     * @return array
     */
    protected function getMocks(
        $target = 'fb.author.id',
        $type = Analysis::TYPE_FREQUENCY_DISTRIBUTION,
        $expectedTarget = false
    ) {
        // get mocks
        // --- request ---
        $brandId = 27;
        $projectId = 23;
        $workbookId = 15;
        $worksheetId = 1;
        $recordingId = 'abc123';
        $dataSetId = 2;

        $expectedTarget = $expectedTarget ?: $target;

        $requestData = [
            'worksheet_id' => $worksheetId,
            'recording_id' => $recordingId,
            'chart_type' => 'tornado',
            'dimensions' => [
                $target
            ],
            'type' => $type
        ];
        $worksheet = new Worksheet();
        $worksheet->setId($worksheetId);
        $worksheet->setWorkbookId($workbookId);
        $worksheet->setChartType($requestData['chart_type']);
        $worksheet->setAnalysisType($requestData['type']);

        $workbook = new Workbook();
        $workbook->setId($workbookId);
        $workbook->setRecordingId($recordingId);

        $project = new Project();
        $project->setId($projectId);
        $project->setBrandId($brandId);

        $recording = new Recording();
        $recording->setId($recordingId);

        $brand = new Brand();
        $brand->setId($brandId);

        $dimsCollection = new Collection();
        $dimsCollection->addDimension(new Dimension($expectedTarget, 1, 'Test Dimension'));
        $worksheet->setDimensions($dimsCollection);

        $chart = new Chart();
        $chart->setData(['18-24' => 1234, '25-36' => 5678]);
        $chart->setType(Chart::TYPE_TORNADO);
        $chart1 = new Chart();
        $chart1->setData(['37-50' => 1234, '50-60' => 5678]);
        $chart1->setType('graph');
        $charts = [$chart, $chart1];

        $request = $this->getMockObject('DataSift\Http\Request', true);
        $request->expects($this->any())
            ->method('getPostParams')
            ->willReturn($requestData);
        // --- form ---
        $createForm = $this->getMockObject('DataSift\Form\FormInterface');
        $createForm->expects($this->any())
            ->method('submit')
            ->with($requestData);
        $createForm->expects($this->any())
            ->method('isValid')
            ->willReturn(true);
        $createForm->expects($this->any())
            ->method('getData')
            ->willReturn($worksheet);

        $brandRepo = $this->getMockObject('Tornado\DataMapper\DataMapperInterface', true);
        $brandRepo->expects($this->any())
            ->method('findOne')
            ->with(['id' => $brandId])
            ->willReturn($brand);

        // --- worksheet repo ---
        $worksheetRepo = $this->getMockObject('Tornado\DataMapper\DataMapperInterface', true);
        $worksheetRepo->expects($this->any())
            ->method('findOne')
            ->with(['id' => $worksheetId])
            ->willReturn($worksheet);
        // --- recording repo ---
        $recordingRepo = $this->getMockObject('Tornado\DataMapper\DataMapperInterface', true);
        $recordingRepo->expects($this->any())
            ->method('findOne')
            ->with(['id' => $recordingId])
            ->willReturn($this->getMockObject('Tornado\Project\Recording'));
        // --- dataset repo ---
        $dataSetRepo = $this->getMockObject('Tornado\DataMapper\DataMapperInterface', true);
        $dataSetRepo->expects($this->any())
            ->method('findOne')
            ->with(['id' => $dataSetId])
            ->willReturn($this->getMockObject('Tornado\Analyze\DataSet\StoredDataSet'));
        // --- chart repo ---
        $chartRepo = $this->getMockObject('Tornado\Project\Chart\DataMapper', true);
        $chartRepo->expects($this->any())
            ->method('deleteByWorksheet')
            ->with($worksheet)
            ->willReturn(1);
        $chartRepo->expects($this->any())
            ->method('create')
            ->will($this->returnCallback(function ($obj) {
                $obj->setId(1);
            }));
        // --- analyzer ---
        $analyzer = $this->getMockObject('Tornado\Analyze\Analyzer', true);
        $analyzer->expects($this->any())
            ->method('perform')
            ->willReturn($this->getMockObject('Tornado\Analyze\Analysis\Collection'));
        // --- dataset generator ---
        $datasetGenerator = $this->getMockObject('Tornado\Analyze\DataSet\Generator');
        $datasetGenerator->expects($this->any())
            ->method('fromAnalyses')
            ->willReturn($this->getMockObject('\Tornado\Analyze\DataSet', true));
        // --- charts factory ---
        $chartFactory = $this->getMockBuilder('Tornado\Project\Chart\Factory')
            ->setConstructorArgs([new NameGenerator()])
            ->getMock();
        $chartFactory->expects($this->any())
            ->method('fromDataSet')
            ->willReturn($charts);

        $workbookLocker = Mockery::mock('\Tornado\Project\Workbook\Locker');
        $userId = 10;
        $sessionUser = new User();
        $sessionUser->setId($userId);

        $lockingUser = new User();
        $lockingUser->setId(100);
        $lockingUser->setEmail('test@test.com');

        $workbookLocker->shouldReceive('isLocked')
            ->with($workbook)
            ->andReturn(false);
        $workbookLocker->shouldReceive('isGranted')
            ->with($workbook, $sessionUser)
            ->andReturn(false);

        return [
            'locker' => $workbookLocker,
            'worksheetId' => $worksheetId,
            'workbookId' => $workbookId,
            'recordingId' => $recordingId,
            'requestData' => $requestData,
            'request' => $request,
            'createForm' => $createForm,
            'worksheetRepo' => $worksheetRepo,
            'recordingRepo' => $recordingRepo,
            'brandRepo' => $brandRepo,
            'chartRepo' => $chartRepo,
            'dataSetRepo' => $dataSetRepo,
            'analyzer' => $analyzer,
            'datasetGenerator' => $datasetGenerator,
            'chartFactory' => $chartFactory,
            'charts' => $charts,
            'worksheet' => $worksheet,
            'workbook' => $workbook,
            'projectId' => $projectId,
            'project' => $project,
            'brandId' => $brandId,
            'brand' => $brand,
            'sessionUser' => $sessionUser,
            'userId' => $userId,
            'lockingUser' => $lockingUser
        ];
    }

    protected function getController(array $mocks)
    {
        $controller = Mockery::mock(AnalyzerController::class, [
            $mocks['recordingRepo'],
            $mocks['chartRepo'],
            $mocks['dataSetRepo'],
            $mocks['createForm'],
            $mocks['analyzer'],
            $mocks['datasetGenerator'],
            $mocks['chartFactory'],
            $mocks['locker'],
            $mocks['sessionUser']
        ])->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // mock ProjectDataAwareTrait methods
        $controller->shouldReceive('getProject')
            ->with($mocks['projectId'])
            ->andReturn($mocks['project']);
        $controller->shouldReceive('getProjectDataForWorksheetId')
            ->with($mocks['worksheetId'])
            ->andReturn([$mocks['project'], $mocks['workbook'], $mocks['worksheet'], $mocks['brand']]);

        $controller->setWorksheetRepository($mocks['worksheetRepo']);

        return $controller;
    }

    /**
     * @param string $class
     * @param bool   $disableConstructor
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockObject($class, $disableConstructor = false)
    {
        $mockBuilder = $this->getMockBuilder($class);

        if ($disableConstructor) {
            $mockBuilder->disableOriginalConstructor();
        }

        return $mockBuilder->getMock();
    }
}
