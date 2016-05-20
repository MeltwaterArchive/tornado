<?php

namespace Test\Tornado\Project\Project\Form;

use \Mockery;

use Tornado\Project\Project;
use Tornado\Project\Project\Form\Update;

use Test\DataSift\ApplicationBuilder;
use Test\DataSift\ReflectionAccess;

use Symfony\Component\Validator\ValidatorBuilder;

/**
 * UpdateTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Project\Project\Form
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass      \Tornado\Project\Project\Form\Update
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
        $form = new Update($this->validator, $mocks['projectRepo']);

        $this->assertEquals(['brand_id', 'name'], $form->getFields());
    }

    /**
     * @covers  ::__construct
     * @covers  ::submit
     *
     * @expectedException \LogicException
     */
    public function testThrowExceptionUnlessObjectGiven()
    {
        $mocks = $this->getMocks();

        $form = new Update($this->validator, $mocks['projectRepo']);
        $form->submit($mocks['inputData']);
    }

    /**
     * @covers  ::__construct
     * @covers  ::submit
     *
     * @expectedException \LogicException
     */
    public function testSubmitUnlessNotPersistedObjectGiven()
    {
        $mocks = $this->getMocks();
        $project = new Project();

        $form = new Update($this->validator, $mocks['projectRepo']);
        $form->submit($mocks['inputData'], $project);
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
     * @covers  ::projectExists
     */
    public function testSubmit()
    {
        $mocks = $this->getMocks();

        $mocks['projectRepo']->shouldReceive('findOne')
            ->once()
            ->with(['name' => $mocks['updatedName'], 'brand_id' => $mocks['updatedBrandId']])
            ->andReturnNull();

        $form = new Update($this->validator, $mocks['projectRepo']);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $inputData = ['brand_id' => $mocks['updatedBrandId'], 'name' => $mocks['updatedName']];
        $form->submit($inputData, $mocks['project']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(true, $form->isValid());
        $this->assertEquals([], $form->getErrors());

        $this->assertEquals($inputData, $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($inputData, $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf('\Tornado\Project\Project', $modelData);
        $this->assertEquals($mocks['updatedName'], $modelData->getName());
        $this->assertEquals($mocks['updatedBrandId'], $modelData->getBrandId());
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
     * @covers  ::projectExists
     */
    public function testSubmitWithoutAnyUpdates()
    {
        $mocks = $this->getMocks();
        $form = new Update($this->validator, $mocks['projectRepo']);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit([], $mocks['project']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(true, $form->isValid());
        $this->assertEquals([], $form->getErrors());

        $this->assertEquals([], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals([], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf('\Tornado\Project\Project', $modelData);
        $this->assertEquals($mocks['name'], $modelData->getName());
        $this->assertEquals($mocks['brandId'], $modelData->getBrandId());
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
     * @covers  ::projectExists
     */
    public function testReturnsErrorUnlessNameIsAvailable()
    {
        $mocks = $this->getMocks();

        $mocks['projectRepo']->shouldReceive('findOne')
            ->once()
            ->with(['name' => $mocks['name'], 'brand_id' => $mocks['brandId']])
            ->andReturn($mocks['project']);

        $form = new Update($this->validator, $mocks['projectRepo']);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $project = new Project();
        $project->setId(10);
        $project->setName($mocks['name']);
        $project->setBrandId($mocks['brandId']);

        $form->submit($mocks['inputData'], $project);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals($mocks['inputData'], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($mocks['inputData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertNotInstanceOf('\Tornado\Project\Project', $modelData);
        $this->assertNull($modelData);

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
     * @covers  ::projectExists
     */
    public function testReturnsErrorsUnlessValidData()
    {
        $mocks = $this->getMocks();
        $inputData = ['brand_id' => 'string', 'name' => 123];
        $mocks['projectRepo']->shouldReceive('findOne')
            ->once()
            ->with($inputData)
            ->andReturnNull();

        $form = new Update($this->validator, $mocks['projectRepo']);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit($inputData, $mocks['project']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals($inputData, $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($inputData, $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertNotInstanceOf('\Tornado\Project\Project', $modelData);
        $this->assertNull($modelData);

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('brand_id', $errors);
    }

    /**
     * Creates test mocks
     *
     * @return array
     */
    protected function getMocks()
    {
        $name = 'test';
        $updatedName = 'new name';
        $brandId = 2;
        $updatedBrandId = 5;
        $id = 1;
        $inputData = [
            'brand_id' => $brandId,
            'name' => $name
        ];
        $projectRepo = Mockery::mock('\Tornado\Project\Project\DataMapper');

        $project = new Project();
        $project->setId($id);
        $project->setBrandId($brandId);
        $project->setName($name);

        return [
            'id' => $id,
            'name' => $name,
            'updatedName' => $updatedName,
            'brandId' => $brandId,
            'updatedBrandId' => $updatedBrandId,
            'inputData' => $inputData,
            'projectRepo' => $projectRepo,
            'project' => $project
        ];
    }
}
