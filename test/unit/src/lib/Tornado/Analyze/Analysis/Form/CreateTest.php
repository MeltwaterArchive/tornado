<?php

namespace Test\Tornado\Analyze\Analysis\Form;

use Mockery;

use DataSift\Pylon\Schema\Schema;

use Tornado\Analyze\Analysis;
use Tornado\Analyze\Analysis\Form\Create;
use Tornado\Analyze\Dimension;
use Tornado\Analyze\Dimension\Factory as DimensionFactory;
use Tornado\Project\Chart;
use Tornado\Project\Chart\Generator as ChartGenerator;
use Tornado\Project\Recording;
use Tornado\Project\Worksheet;
use Tornado\Analyze\Dimension\Collection as DimensionCollection;

use Test\DataSift\ApplicationBuilder;
use Test\DataSift\ReflectionAccess;

/**
 * CreateTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category     Applications
 * @package      \Test\Tornado\Analyze
 * @copyright    2015-2016 MediaSift Ltd.
 * @license      http://mediasift.com/licenses/internal MediaSift Internal License
 * @link         https://github.com/datasift/tornado
 *
 * @covers       \Tornado\Analyze\Analysis\Form\Create
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
        $this->buildApplication();
        $this->validator = $this->container->get('validator');
    }

    /**
     * @covers  \Tornado\Analyze\Analysis\Form\Create::__construct
     * @covers  \Tornado\Project\Worksheet\Form::getFields
     */
    public function testGetFields()
    {
        $mocks = $this->getMocks();
        $form = $this->getForm($mocks);

        $this->assertEquals(
            [
                'worksheet_id',
                'dimensions',
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
                'end'
            ],
            $form->getFields($mocks['recording'])
        );
    }

    /**
     * @covers \Tornado\Analyze\Analysis\Form\Create::__construct
     * @covers \Tornado\Analyze\Analysis\Form\Create::submit
     * @covers \Tornado\Project\Worksheet\Form::isSubmitted
     * @covers \Tornado\Project\Worksheet\Form::getInputData
     * @covers \Tornado\Project\Worksheet\Form::getNormalizedData
     * @covers \Tornado\Project\Worksheet\Form::getData
     * @covers \Tornado\Project\Worksheet\Form::normalizeData
     * @covers \DataSift\Form\Form::isValid
     * @covers \DataSift\Form\Form::getErrors
     */
    public function testSubmit()
    {
        $mocks = $this->getMocks();

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
        $this->assertEquals($mocks['worksheetId'], $modelData->getId());
        $this->assertEquals($mocks['chartType'], $modelData->getChartType());
        $this->assertEquals($mocks['analysisType'], $modelData->getAnalysisType());
        $this->assertEquals($mocks['dimensionsCollection'], $modelData->getDimensions());
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
     * @covers \Tornado\Analyze\Analysis\Form\Create::__construct
     * @covers \Tornado\Analyze\Analysis\Form\Create::submit
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

        $this->assertEquals(null, $form->getData());
    }

    /**
     * @covers \Tornado\Analyze\Analysis\Form\Create::__construct
     * @covers \Tornado\Analyze\Analysis\Form\Create::submit
     * @covers \Tornado\Project\Worksheet\Form::isSubmitted
     * @covers \Tornado\Project\Worksheet\Form::getInputData
     * @covers \Tornado\Project\Worksheet\Form::getNormalizedData
     * @covers \Tornado\Project\Worksheet\Form::getData
     * @covers \Tornado\Project\Worksheet\Form::normalizeData
     * @covers \DataSift\Form\Form::isValid
     * @covers \DataSift\Form\Form::getErrors
     *
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testSuccessfullySubmitUnlessInvalidDimensionDataGiven()
    {
        $mocks = $this->getMocks();
        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $mocks['inputData']['dimensions'] = 'string';
        $form->submit($mocks['inputData'], $mocks['worksheet'], $mocks['recording']);
    }

    /**
     * @covers \Tornado\Analyze\Analysis\Form\Create::__construct
     * @covers \Tornado\Analyze\Analysis\Form\Create::submit
     * @covers \Tornado\Project\Worksheet\Form::isSubmitted
     * @covers \Tornado\Project\Worksheet\Form::getInputData
     * @covers \Tornado\Project\Worksheet\Form::getNormalizedData
     * @covers \Tornado\Project\Worksheet\Form::getData
     * @covers \Tornado\Project\Worksheet\Form::normalizeData
     * @covers \DataSift\Form\Form::isValid
     * @covers \DataSift\Form\Form::getErrors
     */
    public function testEnableTimeDimension()
    {
        $mocks = $this->getMocks();

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $mocks['inputData']['chart_type'] = Chart::TYPE_TIME_SERIES;
        $mocks['inputData']['dimensions'] = [['target' => $mocks['dimensionTargetTime']]];

        $mocks['normalizedData']['chart_type'] = Chart::TYPE_TIME_SERIES;
        $mocks['normalizedData']['dimensions'] = [['target' => Dimension::TIME]];

        $form->submit($mocks['inputData'], $mocks['worksheet'], $mocks['recording']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(true, $form->isValid());
        $this->assertEquals([], $form->getErrors());

        $expectedData = $mocks['inputData'];
        $expectedData['dimensions'] = [['target' => Dimension::TIME]];
        $this->assertEquals($expectedData, $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($mocks['normalizedData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        // check if any update happens
        $modelData = $form->getData($mocks['recording']);
        $this->assertInstanceOf('\Tornado\Project\Worksheet', $modelData);
        $this->assertEquals(Chart::TYPE_TIME_SERIES, $modelData->getChartType());
    }

    /**
     * @covers \Tornado\Analyze\Analysis\Form\Create::__construct
     * @covers \Tornado\Analyze\Analysis\Form\Create::submit
     * @covers \Tornado\Project\Worksheet\Form::isSubmitted
     * @covers \Tornado\Project\Worksheet\Form::getInputData
     * @covers \Tornado\Project\Worksheet\Form::getNormalizedData
     * @covers \Tornado\Project\Worksheet\Form::getData
     * @covers \Tornado\Project\Worksheet\Form::normalizeData
     * @covers \DataSift\Form\Form::isValid
     * @covers \DataSift\Form\Form::getErrors
     */
    public function testEnableTimeDimensionAndReturnErrorUnlessTimeAwareDimensionGiven()
    {
        $mocks = $this->getMocks();

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $mocks['inputData']['chart_type'] = Chart::TYPE_TIME_SERIES;
        $mocks['normalizedData']['chart_type'] = Chart::TYPE_TIME_SERIES;
        $mocks['normalizedData']['dimensions'] = [['target' => Dimension::TIME]];
        $form->submit($mocks['inputData'], $mocks['worksheet'], $mocks['recording']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $expectedData = $mocks['inputData'];
        $expectedData['dimensions'] = [['target' => Dimension::TIME]];
        $this->assertEquals($expectedData, $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($mocks['normalizedData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        // check if any update happens
        $modelData = $form->getData($mocks['recording']);
        $this->assertInstanceOf('\Tornado\Project\Worksheet', $modelData);
        $this->assertEquals($mocks['worksheetId'], $modelData->getId());
        $this->assertNotEquals('time', $modelData->getChartType());
        $this->assertEquals('tornado', $modelData->getChartType());

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('dimensions.0.target', $errors);
        $this->assertArrayHasKey('dimensions.1.target', $errors);
    }

    /**
     * @covers \Tornado\Analyze\Analysis\Form\Create::__construct
     * @covers \Tornado\Analyze\Analysis\Form\Create::submit
     * @covers \Tornado\Project\Worksheet\Form::isSubmitted
     * @covers \Tornado\Project\Worksheet\Form::getInputData
     * @covers \Tornado\Project\Worksheet\Form::getNormalizedData
     * @covers \Tornado\Project\Worksheet\Form::getData
     * @covers \Tornado\Project\Worksheet\Form::normalizeData
     * @covers \DataSift\Form\Form::isValid
     * @covers \DataSift\Form\Form::getErrors
     */
    public function testReturnErrorUnlessValidComparisonSetForBaseline()
    {
        $mocks = $this->getMocks();

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $mocks['inputData']['baseline_dataset_id'] = 10;
        $mocks['inputData']['comparison'] = ChartGenerator::MODE_COMPARE;
        $mocks['normalizedData']['baseline_dataset_id'] = 10;
        $mocks['normalizedData']['comparison'] = ChartGenerator::MODE_COMPARE;

        $form->submit($mocks['inputData'], $mocks['worksheet'], $mocks['recording']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals($mocks['inputData'], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($mocks['normalizedData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('comparison', $errors);
    }

    /**
     * @covers \Tornado\Analyze\Analysis\Form\Create::__construct
     * @covers \Tornado\Analyze\Analysis\Form\Create::submit
     * @covers \Tornado\Project\Worksheet\Form::isSubmitted
     * @covers \Tornado\Project\Worksheet\Form::getInputData
     * @covers \Tornado\Project\Worksheet\Form::getNormalizedData
     * @covers \Tornado\Project\Worksheet\Form::getData
     * @covers \Tornado\Project\Worksheet\Form::normalizeData
     * @covers \DataSift\Form\Form::isValid
     * @covers \DataSift\Form\Form::getErrors
     */
    public function testSubmitWithBaselineComparison()
    {
        $mocks = $this->getMocks();

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $mocks['inputData']['baseline_dataset_id'] = 10;
        $mocks['normalizedData']['baseline_dataset_id'] = 10;
        $mocks['normalizedData']['comparison'] = ChartGenerator::MODE_BASELINE;

        $form->submit($mocks['inputData'], $mocks['worksheet'], $mocks['recording']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(true, $form->isValid());
        $this->assertEquals([], $form->getErrors());

        $this->assertEquals($mocks['inputData'], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($mocks['normalizedData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData($mocks['recording']);
        $this->assertEquals(ChartGenerator::MODE_BASELINE, $modelData->getComparison());
        $this->assertEquals(10, $modelData->getBaselineDataSetId());
    }

    /**
     * @covers \Tornado\Analyze\Analysis\Form\Create::__construct
     * @covers \Tornado\Analyze\Analysis\Form\Create::submit
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
        $this->assertArrayHasKey('worksheet_id', $errors);
        $this->assertArrayHasKey('dimensions', $errors);
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
     * @covers \Tornado\Analyze\Analysis\Form\Create::__construct
     * @covers \Tornado\Analyze\Analysis\Form\Create::submit
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

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $inputData = [
            'worksheet_id' => 'string',
            'dimensions' => [['notsupported']],
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
        $this->assertArrayHasKey('worksheet_id', $errors);
        $this->assertArrayHasKey('dimensions.0.target', $errors);
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
        $recording = new Recording();
        // mock only required data
        $target = 'fb.author.id';
        $target2 = 'fb.author.gender';
        $targetTime = 'fb.timeline';

        $chartType = Chart::TYPE_TORNADO;
        $analysisType = Analysis::TYPE_TIME_SERIES;

        $worksheetId = 1;
        $worksheet = new Worksheet();
        $worksheet->setId($worksheetId);
        $worksheet->setChartType($chartType);

        // mock only required data
        $inputData = [
            'worksheet_id' => $worksheetId,
            'chart_type' => $chartType,
            'dimensions' => [['target' => $target], ['target' => $target2]],
            'type' => $analysisType
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
            'chartType' => $chartType,
            'analysisType' => $analysisType,
            'dimensions' => $target,
            'schema' => $schema,
            'worksheetModelFilters' => $worksheetModelFilters,
            'secondaryRecordingFilters' => $secondaryRecordingFilters,
            'objects' => $objects,
            'recording' => $recording,
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
     * @return \Tornado\Analyze\Analysis\Form\Create
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
