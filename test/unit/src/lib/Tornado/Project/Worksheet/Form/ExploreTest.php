<?php

namespace Test\Tornado\Project\Worksheet\Form;

use Mockery;

use DataSift\Pylon\Schema\Schema;

use Test\DataSift\ApplicationBuilder;
use Tornado\Analyze\Analysis;
use Tornado\Analyze\Dimension;
use Tornado\Project\Chart;
use Tornado\Project\Chart\Generator as ChartGenerator;
use Tornado\Project\Recording;
use Tornado\Project\Workbook;
use Tornado\Project\Worksheet;
use Tornado\Project\Worksheet\Form\Explore;
use Tornado\Analyze\Dimension\Factory as DimensionFactory;

use Test\DataSift\ReflectionAccess;

use Symfony\Component\Validator\ValidatorBuilder;

/**
 * ExploreTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category     Applications
 * @package      \Test\Tornado\Project\Worksheet\Form
 * @author       Christopher Hoult <chris.hoult@datasift.com>
 * @copyright    2015-2016 MediaSift Ltd.
 * @license      http://mediasift.com/licenses/internal MediaSift Internal License
 * @link         https://github.com/datasift/tornado
 *
 * @covers       \Tornado\Project\Worksheet\Form\Explore
 */
class ExploreTest extends \PHPUnit_Framework_TestCase
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
     * @covers  \Tornado\Project\Worksheet\Form\Explore::__construct
     * @covers  \Tornado\Project\Worksheet\Form::getFields
     */
    public function testGetFields()
    {
        $mocks = $this->getMocks();
        $form = $this->getForm($mocks);

        $this->assertEquals(
            [
                'workbook_id',
                'name',
                'chart_type',
                'type',
                'explore',
                'start',
                'end'
            ],
            $form->getFields($mocks['recording'])
        );
    }

    /**
     * @covers \Tornado\Project\Worksheet\Form\Explore::__construct
     * @covers \Tornado\Project\Worksheet\Form\Explore::submit
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

        $this->assertInternalType('array', $modelData);
        $results = $mocks['inputData'];

        unset($results['workbook_id']);
        $results['start'] = null;
        $results['end'] = null;
        $this->assertSame($results, $modelData);
    }

    /**
     * @covers \Tornado\Project\Worksheet\Form\Explore::__construct
     * @covers \Tornado\Project\Worksheet\Form\Explore::submit
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
     * @covers \Tornado\Project\Worksheet\Form\Explore::__construct
     * @covers \Tornado\Project\Worksheet\Form\Explore::submit
     * @covers \Tornado\Project\Worksheet\Form::isSubmitted
     * @covers \Tornado\Project\Worksheet\Form::getInputData
     * @covers \Tornado\Project\Worksheet\Form::getNormalizedData
     * @covers \Tornado\Project\Worksheet\Form::getData
     * @covers \Tornado\Project\Worksheet\Form::normalizeData
     * @covers \DataSift\Form\Form::isValid
     * @covers \DataSift\Form\Form::getErrors
     */
    public function testNormalizeData()
    {
        $mocks = $this->getMocks();

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $input = ['name' => 'test', 'start' => 10];
        $form->submit($input, $mocks['worksheet'], $mocks['recording']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals($input, $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals([
            'name' => 'test',
            'chart_type' => null,
            'type' => null,
            'explore' => [],
            'start' => 10,
            'end' => null
        ], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());
    }

    /**
     * @covers \Tornado\Project\Worksheet\Form\Explore::__construct
     * @covers \Tornado\Project\Worksheet\Form\Explore::submit
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

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('workbook_id', $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('chart_type', $errors);
        $this->assertArrayHasKey('type', $errors);
        $this->assertArrayHasKey('explore', $errors);
        $this->assertArrayNotHasKey('start', $errors);
        $this->assertArrayNotHasKey('end', $errors);
    }

    /**
     * @covers \Tornado\Project\Worksheet\Form\Explore::__construct
     * @covers \Tornado\Project\Worksheet\Form\Explore::submit
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
            'explore' => [],
            'start' => 'string',
            'end' => 'string'
        ];
        $form->submit($inputData, $mocks['worksheet'], $mocks['recording']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals($inputData, $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $normalized = $inputData;
        unset($normalized['workbook_id']);
        $this->assertEquals($normalized, $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('workbook_id', $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('chart_type', $errors);
        $this->assertArrayHasKey('type', $errors);
        $this->assertArrayHasKey('start', $errors);
        $this->assertArrayHasKey('end', $errors);
    }

    /**
     * @covers \Tornado\Project\Worksheet\Form\Explore::__construct
     * @covers \Tornado\Project\Worksheet\Form\Explore::submit
     * @covers \Tornado\Project\Worksheet\Form::isSubmitted
     * @covers \Tornado\Project\Worksheet\Form::getInputData
     * @covers \Tornado\Project\Worksheet\Form::getNormalizedData
     * @covers \Tornado\Project\Worksheet\Form::getData
     * @covers \Tornado\Project\Worksheet\Form::normalizeData
     * @covers \Tornado\Project\Worksheet\Form::worksheetExists
     * @covers \DataSift\Form\Form::isValid
     * @covers \DataSift\Form\Form::getErrors
     */
    public function testSuccessfullySubmitUnlessTimeFrameConflict()
    {
        $mocks = $this->getMocks();
        $mocks['worksheetRepo']->shouldReceive('findOne')
            ->once()
            ->with(['name' => $mocks['updatedName'], 'workbook_id' => $mocks['workbookId']])
            ->andReturnNull();

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $mocks['inputData']['start'] = 100;
        $mocks['inputData']['end'] = 10;
        $mocks['normalizedData']['start'] = 100;
        $mocks['normalizedData']['end'] = 10;
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
        $this->assertArrayHasKey('start', $errors);
        $this->assertArrayHasKey('end', $errors);
    }

    /**
     * @covers \Tornado\Project\Worksheet\Form\Explore::__construct
     * @covers \Tornado\Project\Worksheet\Form\Explore::submit
     * @covers \Tornado\Project\Worksheet\Form::isSubmitted
     * @covers \Tornado\Project\Worksheet\Form::getInputData
     * @covers \Tornado\Project\Worksheet\Form::getNormalizedData
     * @covers \Tornado\Project\Worksheet\Form::getData
     * @covers \Tornado\Project\Worksheet\Form::normalizeData
     * @covers \Tornado\Project\Worksheet\Form::worksheetExists
     * @covers \DataSift\Form\Form::isValid
     * @covers \DataSift\Form\Form::getErrors
     */
    public function testSuccessfullySubmitWithTimeFrames()
    {
        $mocks = $this->getMocks();
        $mocks['worksheetRepo']->shouldReceive('findOne')
            ->once()
            ->with(['name' => $mocks['updatedName'], 'workbook_id' => $mocks['workbookId']])
            ->andReturnNull();

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $mocks['inputData']['start'] = 10;
        $mocks['inputData']['end'] = 100;
        $mocks['normalizedData']['start'] = 10;
        $mocks['normalizedData']['end'] = 100;
        $form->submit($mocks['inputData'], $mocks['worksheet'], $mocks['recording']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(true, $form->isValid());

        $this->assertEquals($mocks['inputData'], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($mocks['normalizedData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData($mocks['recording']);
        $this->assertSame($mocks['normalizedData'], $modelData);

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayNotHasKey('start', $errors);
        $this->assertArrayNotHasKey('end', $errors);
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
        $target2 = 'fb.author.age';
        $targetTime = 'fb.timeline';

        $chartType = Chart::TYPE_TORNADO;
        $updatedChartType = Chart::TYPE_HISTOGRAM;
        $analysisType = Analysis::TYPE_TIME_SERIES;

        $worksheetId = 1;
        $name = 'test';
        $updatedName = 'test edited';

        $workbookId = 10;
        $workbook = new Workbook();
        $workbook->setId($workbookId);
        $workbook->setRecordingId($recordingId);

        $explore = ['fb.author.age' => ['fb.author.age == "18-24"']];
        // mock only required data
        $inputData = [
            'workbook_id' => $workbookId,
            'name' => $updatedName,
            'chart_type' => $updatedChartType,
            'type' => $analysisType,
            'explore' => $explore
        ];
        $filters = null;
        $normalizedData = array_merge($inputData, [
            'start' => null,
            'end' => null
        ]);
        unset($normalizedData['workbook_id']);

        $worksheet = new Worksheet();
        $worksheet->setId($worksheetId);
        $worksheet->setName($name);
        $worksheet->setChartType($chartType);

        $objects = [
            $target => ['target' => $target],
            $target2 => ['target' => $target2],
            'time' => ['target' => 'time', 'is_time' => true],
            $targetTime => ['target' => $targetTime, 'is_time' => true]
        ];

        $targetPermissions = ['everyone'];

        //$schema = new Schema($objects);

        $schema = Mockery::mock('\DataSift\Pylon\Schema');
        $schema->shouldReceive('getTargets')
            ->with([], $targetPermissions)
            ->andReturn([$target, $target2, 'time', $targetTime]);

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
            'objects' => $objects,
            'explore' => $explore,
            'recording' => $recording,
            'recordingId' => $recordingId,
            'schemaProvider' => $schemaProvider,
            'dimensionsFactory' => $dimensionsFactory,
            'filterCsdlGenerator' => $filterCsdlGenerator,
            'regions' => $regions,
            'workbookRepo' => $workbookRepo,
            'worksheetRepo' => $worksheetRepo,
            'targetPermissions' => $targetPermissions
        ];
    }

    /**
     * @param array $mocks
     *
     * @return \Tornado\Project\Worksheet\Form\Explore
     */
    protected function getForm(array $mocks)
    {
        return new Explore(
            $this->validator,
            $mocks['schemaProvider'],
            $mocks['dimensionsFactory'],
            $mocks['filterCsdlGenerator'],
            $mocks['regions'],
            $mocks['workbookRepo'],
            $mocks['worksheetRepo'],
            $mocks['targetPermissions']
        );
    }
}
