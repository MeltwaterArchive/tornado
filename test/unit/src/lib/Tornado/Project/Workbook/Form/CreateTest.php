<?php

namespace Test\Tornado\Project\Workbook\Form;

use Mockery;

use Tornado\Project\Workbook\Form\Create;
use Tornado\Project\Workbook;

use Test\DataSift\ApplicationBuilder;
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
 * @category    Applications
 * @package     \Test\Tornado\Project\Workbook\Form
 * @author      Michał Pałys-Dudek
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Project\Workbook\Form\Create
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
     * @covers  ::__construct
     * @covers  ::getFields
     */
    public function testGetFields()
    {
        $mocks = $this->getMocks();
        $form = new Create($this->validator, $mocks['workbookRepo'], $mocks['templatedAnalyzer']);

        $this->assertEquals(
            ['project_id', 'name', 'recording_id', 'template'],
            $form->getFields()
        );
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
     * @covers  ::workbookExists
     */
    public function testSubmit()
    {
        $mocks = $this->getMocks();
        $mocks['workbookRepo']->shouldReceive('findOne')
            ->once()
            ->with(['project_id' => $mocks['projectId'], 'name' => $mocks['name']])
            ->andReturnNull();

        $form = new Create($this->validator, $mocks['workbookRepo'], $mocks['templatedAnalyzer']);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit($mocks['inputData']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(true, $form->isValid());
        $this->assertEquals([], $form->getErrors());

        $this->assertEquals($mocks['inputData'], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($mocks['inputData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf('\Tornado\Project\Workbook', $modelData);
        $this->assertEquals($mocks['name'], $modelData->getName());
        $this->assertEquals($mocks['projectId'], $modelData->getProjectId());
        $this->assertEquals($mocks['recordingId'], $modelData->getRecordingId());
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
     * @covers  ::workbookExists
     */
    public function testReturnsErrorsUnlessRequiredDataGiven()
    {
        $mocks = $this->getMocks();
        $mocks['workbookRepo']->shouldReceive('findOne')
            ->never();

        $form = new Create($this->validator, $mocks['workbookRepo'], $mocks['templatedAnalyzer']);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit([]);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals([], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals([], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertNotInstanceOf('\Tornado\Project\Workbook', $modelData);
        $this->assertNull($modelData);

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('project_id', $errors);
        $this->assertArrayHasKey('recording_id', $errors);
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
     * @covers  ::workbookExists
     */
    public function testReturnsErrorsUnlessValidDataGiven()
    {
        $mocks = $this->getMocks();
        $inputData = ['project_id' => 'string', 'name' => 123, 'recording_id' => 'string', 'template' => ''];
        $mocks['workbookRepo']->shouldReceive('findOne')
            ->once()
            ->with(['project_id' => 'string', 'name' => 123])
            ->andReturnNull();

        $form = new Create($this->validator, $mocks['workbookRepo'], $mocks['templatedAnalyzer']);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit($inputData);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals($inputData, $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($inputData, $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertNotInstanceOf('\Tornado\Project\Workbook', $modelData);
        $this->assertNull($modelData);

        $errors = $form->getErrors();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('project_id', $errors);
        $this->assertArrayHasKey('recording_id', $errors);
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
     * @covers  ::workbookExists
     */
    public function testReturnsErrorUnlessWorkbookNotExists()
    {
        $mocks = $this->getMocks();

        $mocks['workbookRepo']->shouldReceive('findOne')
            ->with(['project_id' => $mocks['projectId'], 'name' => $mocks['name']])
            ->andReturn($mocks['workbook']);

        $form = new Create($this->validator, $mocks['workbookRepo'], $mocks['templatedAnalyzer']);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit($mocks['inputData']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals($mocks['inputData'], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($mocks['inputData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertNotInstanceOf('\Tornado\Project\Workbook', $modelData);
        $this->assertNull($modelData);

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('name', $errors);
    }

    /**
     * Creates test mocks
     *
     * @return array
     */
    protected function getMocks()
    {
        $projectId = 1;
        $name = 'test';
        $recordingId = 33;
        $workbook = new Workbook();
        $template = 'test1';

        $templates = [
            'test1' => ['title' => 'Test 1'],
            'test2' => ['title' => 'Test 2'],
        ];

        $templatedAnalyzer = Mockery::mock('\Tornado\Analyze\TemplatedAnalyzer');

        $templatedAnalyzer->shouldReceive('getTemplates')
            ->andReturn($templates);

        $inputData = [
            'project_id' => $projectId,
            'name' => $name,
            'recording_id' => $recordingId,
            'template' => $template
        ];
        $workbookRepo = Mockery::mock('Tornado\Project\Workbook\DataMapper');

        return [
            'workbookRepo' => $workbookRepo,
            'workbook' => $workbook,
            'inputData' => $inputData,
            'name' => $name,
            'projectId' => $projectId,
            'recordingId' => $recordingId,
            'template' => $template,
            'templates' => $templates,
            'templatedAnalyzer' => $templatedAnalyzer
        ];
    }
}
