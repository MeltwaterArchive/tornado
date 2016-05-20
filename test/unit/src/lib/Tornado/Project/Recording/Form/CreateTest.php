<?php

namespace Test\Tornado\Project\Recording\Form;

use \Mockery;

use Tornado\Project\Recording;
use Tornado\Project\Recording\Form\Create;

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
 * @package     \Test\Tornado\Project\Recording\Form
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass      \Tornado\Project\Recording\Form\Create
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
        $form = new Create($this->validator, $mocks['recordingRepo']);

        $this->assertEquals(
            ['name', 'hash', 'csdl', 'brand_id', 'vqb_generated'],
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
     */
    public function testSubmit()
    {
        $mocks = $this->getMocks();
        $form = new Create($this->validator, $mocks['recordingRepo']);

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
        $this->assertInstanceOf('\Tornado\Project\Recording', $modelData);
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
    public function testReturnsErrorsRequiredDataGiven()
    {
        $mocks = $this->getMocks();
        $inputData = [];
        $form = new Create($this->validator, $mocks['recordingRepo']);

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
        $this->assertNotInstanceOf('\Tornado\Project\Recording', $modelData);
        $this->assertNull($modelData);

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('brand_id', $errors);
        $this->assertArrayHasKey('csdl', $errors);
        $this->assertArrayNotHasKey('vqb_generated', $errors);
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
            'csdl' => 123,
            'brand_id' => 'string',
            'vqb_generated' => 23
        ];
        $form = new Create($this->validator, $mocks['recordingRepo']);

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
        $this->assertNotInstanceOf('\Tornado\Project\Recording', $modelData);
        $this->assertNull($modelData);

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('brand_id', $errors);
        $this->assertArrayHasKey('csdl', $errors);
        $this->assertArrayHasKey('vqb_generated', $errors);
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
        $brandId = 1;
        $inputData = [
            'brand_id' => $brandId,
            'name' => $name,
            'csdl' => $csdl
        ];
        $recordingRepo = Mockery::mock('\Tornado\Project\Recording\DataMapper');
        $recording = new Recording();
        $recording->setName($name);
        $recording->setBrandId($brandId);
        $recording->setCsdl($csdl);

        return [
            'name' => $name,
            'brandId' => $brandId,
            'csdl' => $csdl,
            'inputData' => $inputData,
            'recordingRepo' => $recordingRepo,
            'recording' => $recording
        ];
    }
}
