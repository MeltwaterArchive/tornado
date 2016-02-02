<?php

namespace Test\Tornado\Project\Chart\Form;

use Mockery;

use Tornado\Project\Chart\Form\Update;
use Tornado\Project\Chart;

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
 * @category    Applications
 * @package     \Test\Tornado\Project\Chart\Form
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Project\Chart\Form\Update
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
    public function setUp()
    {
        $this->buildApplication();
        $this->validator = $this->container->get('validator');
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @covers  ::__construct
     * @covers  ::getFields
     * @covers  ::getConstraints
     */
    public function testGetFields()
    {
        $form = new Update($this->validator);

        $this->assertEquals(['name'], $form->getFields());
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
     *
     * @expectedException \InvalidArgumentException
     */
    public function testSubmitWithoutChart()
    {
        $mocks = $this->getMocks();

        $form = new Update($this->validator);
        $form->submit($mocks['inputData']);
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

        $form = new Update($this->validator);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit($mocks['inputData'], $mocks['chart']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(true, $form->isValid());
        $this->assertEquals([], $form->getErrors());

        $this->assertEquals($mocks['inputData'], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($mocks['inputData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf(Chart::class, $modelData);
        $this->assertSame($mocks['chart'], $modelData);
        $this->assertEquals($mocks['updatedName'], $modelData->getName());
        $this->assertEquals($mocks['id'], $modelData->getId());
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
    public function testReturnsErrorsUnlessRequiredDataGiven()
    {
        $mocks = $this->getMocks();

        $form = new Update($this->validator);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit([], $mocks['chart']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals([], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals([], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf(Chart::class, $modelData);
        $this->assertSame($mocks['chart'], $modelData);
        $this->assertEquals($mocks['name'], $modelData->getName());
        $this->assertEquals($mocks['id'], $modelData->getId());

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
     */
    public function testReturnsErrorsUnlessValidDataGiven()
    {
        $mocks = $this->getMocks();

        $form = new Update($this->validator);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit(['name' => 123], $mocks['chart']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals(['name' => 123], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals(['name' => 123], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf(Chart::class, $modelData);
        $this->assertSame($mocks['chart'], $modelData);
        $this->assertEquals($mocks['name'], $modelData->getName());
        $this->assertEquals($mocks['id'], $modelData->getId());

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
        $name = 'test';
        $updatedName = 'test edited';
        $id = 24;

        $chart = new Chart();
        $chart->setId($id);
        $chart->setName($name);

        $inputData = [
            'name' => $updatedName,
        ];

        return [
            'name' => $name,
            'updatedName' => $updatedName,
            'inputData' => $inputData,
            'chart' => $chart,
            'id' => $id
        ];
    }
}
