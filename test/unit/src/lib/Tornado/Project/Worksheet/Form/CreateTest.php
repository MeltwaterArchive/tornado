<?php

namespace Test\Tornado\Project\Worksheet\Form;

use Mockery;

use DataSift\Pylon\Schema\Schema;

use Test\DataSift\ApplicationBuilder;
use Tornado\Analyze\Analysis;
use Tornado\Analyze\Dimension;
use Tornado\Analyze\Dimension\Factory as DimensionFactory;
use Tornado\Project\Chart;
use Tornado\Project\Chart\Generator as ChartGenerator;
use Tornado\Project\Recording;
use Tornado\Project\Workbook;
use Tornado\Project\Worksheet;
use Tornado\Project\Worksheet\Form\Create;
use Tornado\Analyze\Dimension\Collection as DimensionCollection;

use Test\DataSift\ReflectionAccess;

use Symfony\Component\Validator\ValidatorBuilder;

/**
 * CreateTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category     Applications
 * @package      \Test\Tornado\Project\Worksheet\Form
 * @copyright    2015-2016 MediaSift Ltd.
 * @license      http://mediasift.com/licenses/internal MediaSift Internal License
 * @link         https://github.com/datasift/tornado
 *
 * @covers       \Tornado\Project\Worksheet\Form\Create
 * @covers       \Tornado\Project\Worksheet\Form
 * @covers       \DataSift\Form\Form
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreateTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess,
        ApplicationBuilder;

    /**
     * @var \Symfony\Component\Validator\Validator\RecursiveValidator
     */
    protected $validator;

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $validatorBuilder = new ValidatorBuilder();
        $this->validator = $validatorBuilder->getValidator();
    }

    /**
     * @covers  \Tornado\Project\Worksheet\Form\Create::__construct
     * @covers  \Tornado\Project\Worksheet\Form::getFields
     */
    public function testGetFields()
    {
        $mocks = $this->getMocks();
        $form = $this->getForm($mocks);

        $this->assertEquals(
            [
                'workbook_id',
                'chart_type',
                'type',
                'comparison',
                'measurement',
                'secondary_recording_id',
                'secondary_recording_filters',
                'baseline_dataset_id',
                'filters',
                'span',
                'interval',
                'start',
                'end',
                'dimensions',
                'name'
            ],
            $form->getFields($mocks['recording'])
        );
    }

    /**
     * @covers \Tornado\Project\Worksheet\Form\Create::__construct
     * @covers \Tornado\Project\Worksheet\Form\Create::submit
     * @covers \Tornado\Project\Worksheet\Form::isSubmitted
     * @covers \Tornado\Project\Worksheet\Form::getInputData
     * @covers \Tornado\Project\Worksheet\Form::getNormalizedData
     * @covers \Tornado\Project\Worksheet\Form::getData
     * @covers \Tornado\Project\Worksheet\Form::normalizeData
     * @covers \Tornado\Project\Worksheet\Form::worksheetExists
     * @covers \DataSift\Form\Form::isValid
     * @covers \DataSift\Form\Form::getErrors
     */
    public function testSubmit()
    {
        $mocks = $this->getMocks();
        $mocks['workbookRepo']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['workbookId']])
            ->andReturn($mocks['workbook']);
        $mocks['worksheetRepo']->shouldReceive('findOne')
            ->once()
            ->with(['name' => $mocks['updatedName'], 'workbook_id' => $mocks['workbookId']])
            ->andReturnNull();

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit($mocks['inputData'], $mocks['worksheet'], $mocks['recording']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(true, $form->isValid());
        $this->assertEquals([], $form->getErrors());

        $this->assertEquals($mocks['inputData'], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($mocks['normalizedData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData($mocks['recording']);

        $this->assertInstanceOf('\Tornado\Project\Worksheet', $modelData);
        $this->assertNull($modelData->getId());
        $this->assertEquals($mocks['workbookId'], $modelData->getWorkbookId());
        $this->assertEquals($mocks['updatedName'], $modelData->getName());
        $this->assertEquals($mocks['chartType'], $modelData->getChartType());
        $this->assertEquals($mocks['analysisType'], $modelData->getAnalysisType());
        $this->assertNull($modelData->getDimensions());
        $this->assertNull($modelData->getSecondaryRecordingId());
        $this->assertNull($modelData->getBaselineDataSetId());
        $this->assertEquals(ChartGenerator::MODE_COMPARE, $modelData->getComparison());
        $this->assertEquals(ChartGenerator::MEASURE_UNIQUE_AUTHORS, $modelData->getMeasurement());
        $this->assertInstanceOf('\StdClass', $modelData->getFilters());
        $this->assertEquals($mocks['worksheetModelFilters'], $modelData->getFilters());
        $this->assertInstanceOf('\StdClass', $modelData->getSecondaryRecordingFilters());
        $this->assertEquals($mocks['secondaryRecordingFilters'], $modelData->getSecondaryRecordingFilters());
        $this->assertNull($modelData->getStart());
        $this->assertNull($modelData->getEnd());
    }

    /**
     * @covers \Tornado\Project\Worksheet\Form\Create::__construct
     * @covers \Tornado\Project\Worksheet\Form\Create::submit
     * @covers \Tornado\Project\Worksheet\Form::isSubmitted
     * @covers \Tornado\Project\Worksheet\Form::getInputData
     * @covers \Tornado\Project\Worksheet\Form::getNormalizedData
     * @covers \Tornado\Project\Worksheet\Form::getData
     * @covers \Tornado\Project\Worksheet\Form::normalizeData
     * @covers \Tornado\Project\Worksheet\Form::worksheetExists
     * @covers \DataSift\Form\Form::isValid
     * @covers \DataSift\Form\Form::getErrors
     *
     * @expectedException \RuntimeException
     */
    public function testThrowExceptionUnlessWorkbookNotFound()
    {
        $mocks = $this->getMocks();
        $mocks['workbookRepo']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['workbookId']])
            ->andReturnNull();
        $mocks['worksheetRepo']->shouldReceive('findOne')
            ->never();

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit($mocks['inputData'], $mocks['worksheet'], $mocks['recording']);
    }

    /**
     * @covers \Tornado\Project\Worksheet\Form\Create::__construct
     * @covers \Tornado\Project\Worksheet\Form\Create::submit
     * @covers \Tornado\Project\Worksheet\Form::isSubmitted
     * @covers \Tornado\Project\Worksheet\Form::getInputData
     * @covers \Tornado\Project\Worksheet\Form::getNormalizedData
     * @covers \Tornado\Project\Worksheet\Form::getData
     * @covers \Tornado\Project\Worksheet\Form::normalizeData
     * @covers \Tornado\Project\Worksheet\Form::worksheetExists
     * @covers \DataSift\Form\Form::isValid
     * @covers \DataSift\Form\Form::getErrors
     */
    public function testSuccessfullySubmitUnlessWorksheetFound()
    {
        $mocks = $this->getMocks();
        $mocks['workbookRepo']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['workbookId']])
            ->andReturn($mocks['workbook']);

        $worksheet = new Worksheet();
        $worksheet->setWorkbookId($mocks['workbookId']);
        $worksheet->setId(45);

        $mocks['worksheetRepo']->shouldReceive('findOne')
            ->once()
            ->with(['name' => $mocks['updatedName'], 'workbook_id' => $mocks['workbookId']])
            ->andReturn($worksheet);

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit($mocks['inputData'], $mocks['worksheet'], $mocks['recording']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals($mocks['inputData'], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($mocks['normalizedData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData($mocks['recording']);
        $this->assertNull($modelData);

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('name', $errors);
    }

    /**
     * @covers \Tornado\Project\Worksheet\Form\Create::__construct
     * @covers \Tornado\Project\Worksheet\Form\Create::submit
     * @covers \Tornado\Project\Worksheet\Form::isSubmitted
     * @covers \Tornado\Project\Worksheet\Form::getInputData
     * @covers \Tornado\Project\Worksheet\Form::getNormalizedData
     * @covers \Tornado\Project\Worksheet\Form::getData
     * @covers \Tornado\Project\Worksheet\Form::normalizeData
     * @covers \DataSift\Form\Form::isValid
     * @covers \DataSift\Form\Form::getErrors
     */
    public function testSubmitWithoutModelData()
    {
        $mocks = $this->getMocks();
        $mocks['workbookRepo']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['workbookId']])
            ->andReturn($mocks['workbook']);
        $mocks['worksheetRepo']->shouldReceive('findOne')
            ->once()
            ->with(['name' => $mocks['updatedName'], 'workbook_id' => $mocks['workbookId']])
            ->andReturnNull();

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit($mocks['inputData'], null, $mocks['recording']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(true, $form->isValid());
        $this->assertEquals([], $form->getErrors());

        $this->assertEquals($mocks['inputData'], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($mocks['normalizedData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf('\Tornado\Project\Worksheet', $modelData);
        $this->assertNotSame($mocks['worksheet'], $modelData);
        $this->assertNull($modelData->getId());
        $this->assertEquals($mocks['workbookId'], $modelData->getWorkbookId());
        $this->assertEquals($mocks['updatedName'], $modelData->getName());
        $this->assertEquals($mocks['chartType'], $modelData->getChartType());
        $this->assertEquals($mocks['analysisType'], $modelData->getAnalysisType());
        $this->assertNull($modelData->getDimensions());
        $this->assertNull($modelData->getSecondaryRecordingId());
        $this->assertNull($modelData->getBaselineDataSetId());
        $this->assertEquals(ChartGenerator::MODE_COMPARE, $modelData->getComparison());
        $this->assertEquals(ChartGenerator::MEASURE_UNIQUE_AUTHORS, $modelData->getMeasurement());
        $this->assertInstanceOf('\StdClass', $modelData->getFilters());
        $this->assertEquals($mocks['worksheetModelFilters'], $modelData->getFilters());
        $this->assertInstanceOf('\StdClass', $modelData->getSecondaryRecordingFilters());
        $this->assertEquals($mocks['secondaryRecordingFilters'], $modelData->getSecondaryRecordingFilters());
        $this->assertNull($modelData->getStart());
        $this->assertNull($modelData->getEnd());
    }

    /**
     * @covers \Tornado\Project\Worksheet\Form\Create::__construct
     * @covers \Tornado\Project\Worksheet\Form\Create::submit
     * @covers \Tornado\Project\Worksheet\Form::isSubmitted
     * @covers \Tornado\Project\Worksheet\Form::getInputData
     * @covers \Tornado\Project\Worksheet\Form::getNormalizedData
     * @covers \Tornado\Project\Worksheet\Form::getData
     * @covers \Tornado\Project\Worksheet\Form::normalizeData
     * @covers \DataSift\Form\Form::isValid
     * @covers \DataSift\Form\Form::getErrors
     */
    public function testSuccessfullySubmitUnlessRequiredDataMissed()
    {
        $mocks = $this->getMocks();

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit([], $mocks['worksheet'], $mocks['recording']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals([], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $normalizedData = [
            'secondary_recording_id' => null,
            'secondary_recording_filters' => null,
            'baseline_dataset_id' => null,
            'comparison' => ChartGenerator::MODE_COMPARE,
            'measurement' => ChartGenerator::MEASURE_UNIQUE_AUTHORS,
            'filters' => null,
            'span' => null,
            'interval' => null,
            'start' => null,
            'end' => null,
            'chart_type' => null,
            'type' => null
        ];
        $this->assertEquals($normalizedData, $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('chart_type', $errors);
        $this->assertArrayHasKey('type', $errors);
        $this->assertArrayNotHasKey('comparison', $errors);
        $this->assertArrayNotHasKey('measurement', $errors);
        $this->assertArrayNotHasKey('secondary_recording_id', $errors);
        $this->assertArrayNotHasKey('secondary_recording_filters', $errors);
        $this->assertArrayNotHasKey('baseline_dataset_id', $errors);
        $this->assertArrayNotHasKey('filters', $errors);
        $this->assertArrayNotHasKey('start', $errors);
        $this->assertArrayNotHasKey('end', $errors);
    }

    /**
     * @covers \Tornado\Project\Worksheet\Form\Create::__construct
     * @covers \Tornado\Project\Worksheet\Form\Create::submit
     * @covers \Tornado\Project\Worksheet\Form::isSubmitted
     * @covers \Tornado\Project\Worksheet\Form::getInputData
     * @covers \Tornado\Project\Worksheet\Form::getNormalizedData
     * @covers \Tornado\Project\Worksheet\Form::getData
     * @covers \Tornado\Project\Worksheet\Form::normalizeData
     * @covers \DataSift\Form\Form::isValid
     * @covers \DataSift\Form\Form::getErrors
     */
    public function testSuccessfullySubmitUnlessInvalidDataGiven()
    {
        $mocks = $this->getMocks();
        $mocks['workbookRepo']->shouldReceive('findOne')
            ->once()
            ->with(['id' => 'string'])
            ->andReturn($mocks['workbook']);
        $mocks['worksheetRepo']->shouldReceive('findOne')
            ->once()
            ->with(['name' => 123, 'workbook_id' => 'string'])
            ->andReturnNull();

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $inputData = [
            'workbook_id' => 'string',
            'name' => 123,
            'chart_type' => 'notsupported',
            'type' => 'notsupported',
            'comparison' => 'notsupported',
            'measurement' => 'notsupported',
            'secondary_recording_id' => 'string',
            'baseline_dataset_id' => 'string',
            'start' => 'string',
            'end' => 'string'
        ];
        $form->submit($inputData, $mocks['worksheet'], $mocks['recording']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals($inputData, $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $normalizedData = array_merge(
            $inputData,
            [
                'secondary_recording_filters' => null,
                'filters' => null,
                'span' => null,
                'interval' => null
            ]
        );

        $this->assertEquals($normalizedData, $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('workbook_id', $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('chart_type', $errors);
        $this->assertArrayHasKey('type', $errors);
        $this->assertArrayHasKey('comparison', $errors);
        $this->assertArrayHasKey('measurement', $errors);
        $this->assertArrayHasKey('secondary_recording_id', $errors);
        $this->assertArrayHasKey('baseline_dataset_id', $errors);
        $this->assertArrayHasKey('start', $errors);
        $this->assertArrayHasKey('end', $errors);
    }

    /**
     * Creates test mocks
     *
     * @return array
     */
    protected function getMocks()
    {
        $recordingId = 900;
        $recording = new Recording();
        $recording->setId($recordingId);

        // mock only required data
        $target = 'fb.author.id';
        $target2 = 'fb.author.gender';
        $targetTime = 'fb.timeline';

        $chartType = Chart::TYPE_TORNADO;
        $analysisType = Analysis::TYPE_TIME_SERIES;

        $worksheetId = 1;
        $name = 'test';
        $updatedName = 'test edited';

        $workbookId = 10;
        $workbook = new Workbook();
        $workbook->setId($workbookId);
        $workbook->setRecordingId($recordingId);

        // mock only required data
        $inputData = [
            'workbook_id' => $workbookId,
            'chart_type' => $chartType,
            'type' => $analysisType,
            'name' => $updatedName,
        ];
        $filters = null;
        $normalizedData = array_merge($inputData, [
            'secondary_recording_id' => null,
            'secondary_recording_filters' => null,
            'baseline_dataset_id' => null,
            'comparison' => ChartGenerator::MODE_COMPARE,
            'measurement' => ChartGenerator::MEASURE_UNIQUE_AUTHORS,
            'filters' => $filters,
            'span' => null,
            'interval' => null,
            'start' => null,
            'end' => null
        ]);
        $worksheetModelFilters = new \StdClass();
        $worksheetModelFilters->generated_csdl = '';
        $worksheetModelFilters->country = null;
        $worksheetModelFilters->region = null;
        $worksheetModelFilters->gender = null;
        $worksheetModelFilters->age = null;
        $worksheetModelFilters->csdl = null;
        $worksheetModelFilters->keywords = null;
        $worksheetModelFilters->links = null;

        $secondaryRecordingFilters = clone $worksheetModelFilters;
        $secondaryRecordingFilters->start = null;
        $secondaryRecordingFilters->end = null;

        $worksheetModelFilters->span = null;
        $worksheetModelFilters->interval = null;

        $dimsCollection = new DimensionCollection();
        $dimsCollection->addDimension(new Dimension($target));
        $dimsCollection->addDimension(new Dimension($target2));

        $worksheet = new Worksheet();
        $worksheet->setId($worksheetId);
        $worksheet->setName($name);
        $worksheet->setChartType($chartType);
        $worksheet->setDimensions($dimsCollection);

        $objects = [
            $target => ['target' => $target],
            $target2 => ['target' => $target2],
            'time' => ['target' => 'time', 'is_time' => true],
            $targetTime => ['target' => $targetTime, 'is_time' => true]
        ];

        $schema = new Schema($objects);
        $schemaProvider = Mockery::mock('\DataSift\Pylon\Schema\Provider');
        $schemaProvider->shouldReceive('getSchema')
            ->with($recording)
            ->andReturn($schema);
        $dimensionsFactory = new DimensionFactory($schemaProvider);

        $workbookRepo = Mockery::mock('\Tornado\Project\Workbook\DataMapper');
        $worksheetRepo = Mockery::mock('\Tornado\Project\Worksheet\DataMapper');

        $filterCsdlGenerator = Mockery::mock('\Tornado\Project\Worksheet\FilterCsdlGenerator');
        $filterCsdlGenerator->shouldReceive('generate')
            ->with($filters)
            ->andReturn('');

        $regions = Mockery::mock('\DataSift\Pylon\Regions', [
            'getCountries' => [],
            'getRegions' => [],
            'getCountriesWithRegions' => []
        ]);

        return [
            'inputData' => $inputData,
            'dimensionTarget1' => $target,
            'dimensionTarget2' => $target2,
            'dimensionTargetTime' => $targetTime,
            'normalizedData' => $normalizedData,
            'worksheetId' => $worksheetId,
            'worksheet' => $worksheet,
            'workbookId' => $workbookId,
            'workbook' => $workbook,
            'name' => $name,
            'updatedName' => $updatedName,
            'chartType' => $chartType,
            'analysisType' => $analysisType,
            'dimensions' => $target,
            'schema' => $schema,
            'worksheetModelFilters' => $worksheetModelFilters,
            'secondaryRecordingFilters' => $secondaryRecordingFilters,
            'objects' => $objects,
            'recording' => $recording,
            'recordingId' => $recordingId,
            'schemaProvider' => $schemaProvider,
            'dimensionsCollection' => $dimsCollection,
            'dimensionsFactory' => $dimensionsFactory,
            'filterCsdlGenerator' => $filterCsdlGenerator,
            'regions' => $regions,
            'workbookRepo' => $workbookRepo,
            'worksheetRepo' => $worksheetRepo,
        ];
    }

    /**
     * @param array $mocks
     *
     * @return \Tornado\Project\Worksheet\Form\Create
     */
    protected function getForm(array $mocks)
    {
        return new Create(
            $this->validator,
            $mocks['schemaProvider'],
            $mocks['dimensionsFactory'],
            $mocks['filterCsdlGenerator'],
            $mocks['regions'],
            $mocks['workbookRepo'],
            $mocks['worksheetRepo']
        );
    }
}
