<?php

namespace Test\DataSift\Form;

use Test\DataSift\Form\Fixtures\ConcreteForm;
use Test\DataSift\ReflectionAccess;

/**
 * FormTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\DataSift\Form
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass      \DataSift\Form\Form
 */
class FormTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess;

    /**
     * @covers  ::getInputData
     * @covers  ::getNormalizedData
     * @covers  ::getData
     */
    public function testGetData()
    {
        $modelData = $this->getMockBuilder('\Tornado\DataMapper\DataObjectInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $modelData->expects($this->any())
            ->method('getPrimaryKey')
            ->willReturn('id');

        $form = new ConcreteForm();
        $form->submit(['prop1' => 123], $modelData);

        $this->assertEquals(['prop1' => 123], $form->getInputData());
        $this->assertEquals(['prop1' => 123], $form->getNormalizedData());
        $this->assertEquals($modelData, $form->getData());
        $this->assertEquals('id', $form->getData()->getPrimaryKey());
    }

    /**
     * @covers ::isValid
     */
    public function testIsValid()
    {
        $form = new ConcreteForm();
        $this->assertEquals(false, $form->isValid());

        $this->setPropertyValue($form, 'submitted', true);
        $this->assertEquals(true, $form->isValid());

        $this->setPropertyValue(
            $form,
            'errors',
            $this->getConstraintViolationMocks([
                '[dimension][property1]',
                '[dimension][property2][nested]',
                '[dimension][property2][nested][nested]'
            ])
        );

        $this->assertEquals(false, $form->isValid());
    }

    /**
     * @covers ::isSubmitted
     */
    public function testIsSubmitted()
    {
        $form = new ConcreteForm();
        $this->assertEquals(false, $form->isSubmitted());

        $this->setPropertyValue($form, 'submitted', true);
        $this->assertEquals(true, $form->isSubmitted());
    }

    /**
     * @covers ::getErrors
     */
    public function testGetConstraintViolationErrors()
    {
        $form = new ConcreteForm();
        $this->setPropertyValue(
            $form,
            'errors',
            $this->getConstraintViolationMocks([
                '[dimension][property1]',
                '[dimension][property2][nested]',
                '[dimension][property2][nested][nested]'
            ])
        );

        $this->assertEquals(
            [
                'dimension.property1' => 'errorMsg',
                'dimension.property2.nested' => 'errorMsg',
                'dimension.property2.nested.nested' => 'errorMsg'
            ],
            $form->getErrors()
        );
    }

    /**
     * @covers ::getErrors
     */
    public function testGetArrayErrors()
    {
        $form = new ConcreteForm();
        $this->setPropertyValue(
            $form,
            'errors',
            [
                '[dimension][property1]',
                '[dimension][property2][nested]',
                '[dimension][property2][nested][nested]'
            ]
        );

        $this->assertEquals(
            [
                '[dimension][property1]',
                '[dimension][property2][nested]',
                '[dimension][property2][nested][nested]'
            ],
            $form->getErrors()
        );
    }

    /**
     * @covers ::getErrors
     */
    public function testGetMixedTypeErrors()
    {
        $form = new ConcreteForm();
        $this->setPropertyValue(
            $form,
            'errors',
            array_merge(
                $this->getConstraintViolationMocks([
                    '[dimension][property1]',
                ]),
                [
                    '[dimension][property2]' => 'errorMsg',
                ]
            )
        );

        $this->assertEquals(
            [
                'dimension.property1' => 'errorMsg',
                '[dimension][property2]' => 'errorMsg'
            ],
            $form->getErrors()
        );
    }

    /**
     * Prepares ConstraintViolation error stubs
     *
     * @param array $properties
     *
     * @return \PHPUnit_Framework_MockObject_MockObject[]
     */
    protected function getConstraintViolationMocks(array $properties)
    {
        $errors = [];

        foreach ($properties as $property) {
            $stub = $this->getMockBuilder('\Symfony\Component\Validator\ConstraintViolation')
                ->disableOriginalConstructor()
                ->getMock();
            $stub->method('getPropertyPath')
                ->willReturn($property);
            $stub->method('getMessage')
                ->willReturn('errorMsg');

            $errors[] = $stub;
        }

        return $errors;
    }
}
