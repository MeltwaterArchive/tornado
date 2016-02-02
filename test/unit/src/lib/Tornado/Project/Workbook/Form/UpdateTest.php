<?php

namespace Test\Tornado\Project\Workbook\Form;

use Mockery;

use Tornado\Project\Workbook\Form\Update;
use Tornado\Project\Workbook;

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
 * @category    Applications
 * @package     \Test\Tornado\Project\Workbook\Form
 * @author      Michał Pałys-Dudek
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Project\Workbook\Form\Update
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateTest extends \PHPUnit_Framework_TestCase
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
     * @covers  ::__construct
     * @covers  ::getFields
     */
    public function testGetFields()
    {
        $mocks = $this->getMocks();
        $form = new Update($this->validator, $mocks['workbookRepo']);

        $this->assertEquals(
            ['project_id', 'name', 'recording_id'],
            $form->getFields()
        );
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

        $form = new Update($this->validator, $mocks['workbookRepo']);
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
        $workbook = new Workbook();

        $form = new Update($this->validator, $mocks['workbookRepo']);
        $form->submit($mocks['inputData'], $workbook);
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
            ->with(['project_id' => $mocks['projectId'], 'name' => $mocks['updatedName']])
            ->andReturnNull();

        $form = new Update($this->validator, $mocks['workbookRepo']);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit($mocks['inputData'], $mocks['workbook']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(true, $form->isValid());
        $this->assertEquals([], $form->getErrors());

        $this->assertEquals($mocks['inputData'], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($mocks['inputData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf('\Tornado\Project\Workbook', $modelData);
        $this->assertEquals($mocks['id'], $modelData->getId());
        $this->assertEquals($mocks['updatedName'], $modelData->getName());
        $this->assertEquals($mocks['projectId'], $modelData->getProjectId());
        $this->assertEquals($mocks['updatedRecordingId'], $modelData->getRecordingId());
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
    public function testNotOverwriteProjectId()
    {
        $mocks = $this->getMocks();
        $projectId = 100;
        $inputData = $mocks['inputData'];
        $inputData['project_id'] = $projectId;

        $mocks['workbookRepo']->shouldReceive('findOne')
            ->once()
            ->with(['project_id' => $mocks['projectId'], 'name' => $mocks['updatedName']])
            ->andReturnNull();

        $form = new Update($this->validator, $mocks['workbookRepo']);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit($inputData, $mocks['workbook']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(true, $form->isValid());
        $this->assertEquals([], $form->getErrors());

        $this->assertEquals($mocks['inputData'], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($mocks['inputData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf('\Tornado\Project\Workbook', $modelData);
        $this->assertEquals($mocks['id'], $modelData->getId());
        $this->assertEquals($mocks['updatedName'], $modelData->getName());
        $this->assertEquals($mocks['projectId'], $modelData->getProjectId());
        $this->assertEquals($mocks['updatedRecordingId'], $modelData->getRecordingId());
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
        $data = ['project_id' => 'string', 'name' => 123, 'recording_id' => 'string'];
        $inputData = $data;
        $inputData['project_id'] = $mocks['projectId'];

        $mocks['workbookRepo']->shouldReceive('findOne')
            ->once()
            ->with(['project_id' => $mocks['projectId'], 'name' => 123])
            ->andReturnNull();

        $form = new Update($this->validator, $mocks['workbookRepo']);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit($data, $mocks['workbook']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals($inputData, $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($inputData, $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf('\Tornado\Project\Workbook', $modelData);

        $errors = $form->getErrors();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('recording_id', $errors);
        $this->assertArrayNotHasKey('project_id', $errors);
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

        $form = new Update($this->validator, $mocks['workbookRepo']);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit([], $mocks['workbook']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals(['project_id' => $mocks['projectId']], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals(['project_id' => $mocks['projectId']], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf('\Tornado\Project\Workbook', $modelData);

        $errors = $form->getErrors();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('recording_id', $errors);
        $this->assertArrayNotHasKey('project_id', $errors);
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
            ->once()
            ->with(['project_id' => $mocks['projectId'], 'name' => $mocks['updatedName']])
            ->andReturn($mocks['workbook']);

        $form = new Update($this->validator, $mocks['workbookRepo']);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $workbook = new Workbook();
        $workbook->setId(20);
        $workbook->setName($mocks['name']);
        $workbook->setProjectId($mocks['projectId']);
        $workbook->setRecordingId($mocks['recordingId']);

        $form->submit($mocks['inputData'], $workbook);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals($mocks['inputData'], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($mocks['inputData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf('\Tornado\Project\Workbook', $modelData);

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayNotHasKey('project_id', $errors);
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
        $updatedName = 'name edited';
        $id = 10;
        $recordingId = 33;
        $updatedRecordingId = 66;
        $workbook = new Workbook();
        $workbook->setId($id);
        $workbook->setName($name);
        $workbook->setProjectId($projectId);
        $workbook->setRecordingId($recordingId);

        $inputData = [
            'project_id' => $projectId,
            'name' => $updatedName,
            'recording_id' => $updatedRecordingId
        ];
        $workbookRepo = Mockery::mock('Tornado\Project\Workbook\DataMapper');

        return [
            'id' => $id,
            'workbookRepo' => $workbookRepo,
            'workbook' => $workbook,
            'inputData' => $inputData,
            'name' => $name,
            'updatedName' => $updatedName,
            'projectId' => $projectId,
            'recordingId' => $recordingId,
            'updatedRecordingId' => $updatedRecordingId
        ];
    }
}
