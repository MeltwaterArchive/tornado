<?php

namespace Test\Tornado\Project\Recording\Form;

use \Mockery;

use Tornado\Project\Recording\Form\Update;
use Tornado\Project\Recording;

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
 * @package     \Test\Tornado\Project\Recording\Form
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass      \Tornado\Project\Recording\Form\Update
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
        $form = new Update($this->validator, $mocks['recordingRepo']);

        $this->assertEquals(
            ['name', 'brand_id'],
            $form->getFields()
        );
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

        $form = new Update($this->validator, $mocks['recordingRepo']);
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
        $recording = new Recording();

        $form = new Update($this->validator, $mocks['recordingRepo']);
        $form->submit($mocks['inputData'], $recording);
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
     */
    public function testSubmit()
    {
        $mocks = $this->getMocks();
        $form = new Update($this->validator, $mocks['recordingRepo']);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit($mocks['inputData'], $mocks['recording']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(true, $form->isValid());
        $this->assertEquals([], $form->getErrors());

        $this->assertEquals($mocks['inputData'], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($mocks['inputData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf('\Tornado\Project\Recording', $modelData);
        $this->assertEquals($mocks['id'], $modelData->getId());
        $this->assertEquals($mocks['updatedName'], $modelData->getName());
        $this->assertEquals($mocks['updatedBrandId'], $modelData->getBrandId());
        $this->assertEquals($mocks['csdl'], $modelData->getCsdl());
        $this->assertEquals(false, $modelData->isVqbGenerated());
        $this->assertNotNull($modelData->getCreatedAt());
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
     */
    public function testSubmitWithoutAnyUpdates()
    {
        $mocks = $this->getMocks();
        $form = new Update($this->validator, $mocks['recordingRepo']);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit([], $mocks['recording']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(true, $form->isValid());
        $this->assertEquals([], $form->getErrors());

        $this->assertEquals([], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals([], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf('\Tornado\Project\Recording', $modelData);
        $this->assertEquals($mocks['id'], $modelData->getId());
        $this->assertEquals($mocks['name'], $modelData->getName());
        $this->assertEquals($mocks['brandId'], $modelData->getBrandId());
        $this->assertEquals($mocks['csdl'], $modelData->getCsdl());
        $this->assertEquals(false, $modelData->isVqbGenerated());
        $this->assertNotNull($modelData->getCreatedAt());
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
     */
    public function testReturnsErrorsUnlessValidDataGiven()
    {
        $mocks = $this->getMocks();
        $inputData = [
            'name' => 123,
            'brand_id' => 'string'
        ];
        $form = new Update($this->validator, $mocks['recordingRepo']);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit($inputData, $mocks['recording']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals($inputData, $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($inputData, $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertNotInstanceOf('\Tornado\Project\Recording', $modelData);
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
        $csdl = 'JCSDL_START 41fffbe21e24fb396cf174d991bc9ce8 fb.type,equals,12-4 1 fb.type == "like"';
        $name = 'test';
        $updatedName = 'name edited';
        $brandId = 1;
        $id = 10;
        $updatedBrandId = 10;
        $inputData = [
            'brand_id' => $updatedBrandId,
            'name' => $updatedName
        ];
        $recordingRepo = Mockery::mock('\Tornado\Project\Recording\DataMapper');
        $recording = new Recording();
        $recording->setId($id);
        $recording->setName($name);
        $recording->setBrandId($brandId);
        $recording->setCsdl($csdl);
        $recording->setCreatedAt(time());

        return [
            'name' => $name,
            'id' => $id,
            'updatedName' => $updatedName,
            'brandId' => $brandId,
            'updatedBrandId' => $updatedBrandId,
            'csdl' => $csdl,
            'inputData' => $inputData,
            'recordingRepo' => $recordingRepo,
            'recording' => $recording
        ];
    }
}
