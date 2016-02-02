<?php

namespace Test\Tornado\Project\Worksheet\Form;

use Mockery;

use DataSift\Pylon\Schema\Schema;

use Tornado\Analyze\Analysis;
use Tornado\Analyze\Dimension;
use Tornado\Project\Chart;
use Tornado\Project\Chart\Generator as ChartGenerator;
use Tornado\Project\Recording;
use Tornado\Project\Worksheet;
use Tornado\Project\Worksheet\Form\Update;
use Tornado\Analyze\Dimension\Factory as DimensionFactory;

use Test\DataSift\ApplicationBuilder;
use Test\DataSift\ReflectionAccess;

/**
 * UpdateTest
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
 * @coversDefaultClass       \Tornado\Project\Worksheet\Form\Update
 */
class UpdateTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess,
        ApplicationBuilder;

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @var \Symfony\Component\Validator\Validator\RecursiveValidator
     */
    protected $validator;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->buildApplication();
        $this->validator = $this->container->get('validator');
    }

    /**
     * @covers  ::__construct
     * @covers  ::getFields
     */
    public function testGetFields()
    {
        $mocks = $this->getMocks();
        $form = $this->getForm($mocks);

        $this->assertEquals(['workbook_id', 'name'], $form->getFields());
    }

    /**
     * @covers  ::__construct
     * @covers  ::submit
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionUnlessObjectGiven()
    {
        $mocks = $this->getMocks();

        $form = $this->getForm($mocks);
        $form->submit($mocks['inputData']);
    }

    /**
     * @covers  ::__construct
     * @covers  ::submit
     *
     * @expectedException \InvalidArgumentException
     */
    public function testSubmitUnlessNotPersistedObjectGiven()
    {
        $mocks = $this->getMocks();
        $form = $this->getForm($mocks);

        $worksheet = new Worksheet();
        $form->submit($mocks['inputData'], $worksheet);
    }

    /**
     * @covers  ::__construct
     * @covers  ::submit
     * @covers  ::isSubmitted
     * @covers  ::isValid
     * @covers  ::getErrors
     * @covers  ::getData
     * @covers  ::getConstraints
     * @covers  ::getNormalizedData
     * @covers  ::getInputData
     * @covers  ::worksheetExists
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

        $this->assertEquals($mocks['inputData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf('\Tornado\Project\Worksheet', $modelData);
        $this->assertEquals($mocks['updatedName'], $modelData->getName());
        $this->assertEquals($mocks['workbookId'], $modelData->getWorkbookId());
    }

    /**
     * @covers  ::__construct
     * @covers  ::submit
     * @covers  ::isSubmitted
     * @covers  ::isValid
     * @covers  ::getErrors
     * @covers  ::getData
     * @covers  ::getConstraints
     * @covers  ::getNormalizedData
     * @covers  ::getInputData
     * @covers  ::worksheetExists
     */
    public function testReturnsErrorUnlessNameIsAvailable()
    {
        $mocks = $this->getMocks();
        $worksheet = new Worksheet();
        $worksheet->setId(999);
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

        $this->assertEquals($mocks['inputData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('name', $errors);
    }

    /**
     * @covers  ::__construct
     * @covers  ::submit
     * @covers  ::isSubmitted
     * @covers  ::isValid
     * @covers  ::getErrors
     * @covers  ::getData
     * @covers  ::getConstraints
     * @covers  ::getNormalizedData
     * @covers  ::getInputData
     * @covers  ::worksheetExists
     */
    public function testReturnsErrorsUnlessValidData()
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

        $input = ['workbook_id' => 'string', 'name' => 123];
        $form->submit($input, $mocks['worksheet'], $mocks['recording']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals($input, $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($input, $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->arrayHasKey('workbook_id', $errors);
        $this->arrayHasKey('name', $errors);
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
        $name = 'test';
        $updatedName = 'name edited';

        $chartType = Chart::TYPE_TORNADO;

        $workbookId = 100;
        $worksheetId = 1;
        $worksheet = new Worksheet();
        $worksheet->setId($worksheetId);
        $worksheet->setWorkbookId($workbookId);
        $worksheet->setName($name);
        $worksheet->setChartType($chartType);

        // mock only required data
        $inputData = [
            'workbook_id' => $workbookId,
            'name' => $updatedName
        ];

        // form construct
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
        $filters = null;
        $filterCsdlGenerator = Mockery::mock('\Tornado\Project\Worksheet\FilterCsdlGenerator');
        $filterCsdlGenerator->shouldReceive('generate')
            ->with($filters)
            ->andReturn('');
        $regions = Mockery::mock('\DataSift\Pylon\Regions', [
            'getCountries' => [],
            'getRegions' => [],
            'getCountriesWithRegions' => []
        ]);
        $workbookRepo = Mockery::mock('\Tornado\Project\Workbook\DataMapper');
        $worksheetRepo = Mockery::mock('\Tornado\Project\Worksheet\DataMapper');

        return [
            'inputData' => $inputData,
            'recording' => $recording,
            'name' => $name,
            'updatedName' => $updatedName,
            'dimensionTarget1' => $target,
            'dimensionTarget2' => $target2,
            'dimensionTargetTime' => $targetTime,
            'normalizedData' => $inputData,
            'worksheetId' => $worksheetId,
            'worksheet' => $worksheet,
            'schemaProvider' => $schemaProvider,
            'dimensionsFactory' => $dimensionsFactory,
            'filterCsdlGenerator' => $filterCsdlGenerator,
            'regions' => $regions,
            'workbookRepo' => $workbookRepo,
            'workbookId' => $workbookId,
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
        return new Update(
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
