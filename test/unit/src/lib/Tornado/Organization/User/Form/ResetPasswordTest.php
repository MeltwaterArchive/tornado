<?php

namespace Test\Tornado\Organization\User\Form;

use \Mockery;

use Symfony\Component\Validator\ValidatorBuilder;
use Tornado\Organization\Organization;
use Tornado\Organization\User;
use Tornado\Organization\User\Factory;
use Tornado\Organization\User\Form\ResetPassword;

use Test\DataSift\ApplicationBuilder;
use Test\DataSift\ReflectionAccess;

/**
 * ResetPasswordTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Organization\User\Form
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass      \Tornado\Organization\User\Form\ResetPassword
 */
class ResetPasswordTest extends \PHPUnit_Framework_TestCase
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
     * @covers  ::__construct
     * @covers  ::getFields
     */
    public function testGetFields()
    {
        $validatorBuilder = new ValidatorBuilder();
        $validator = $validatorBuilder->getValidator();
        $organizationRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface');
        $userRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface');
        $userFactory = Mockery::mock('\Tornado\Organization\User\Factory');

        $form = new ResetPassword($validator, $organizationRepo, $userRepo, $userFactory);

        $this->assertEquals(
            ['password', 'confirm_password'],
            $form->getFields()
        );
    }

    /**
     * DataProvider for testSubmit
     *
     * @return array
     */
    public function submitProvider()
    {
        return [
            'Happy path' => [
                'data' => [
                    'password' => 'test2',
                    'confirm_password' => 'test2'
                ],
                'isValid' => true
            ],
            'Mismatched password' => [
                'data' => [
                    'password' => 'test2',
                    'confirm_password' => 'test3'
                ],
                'isValid' => false,
                'expectedErrors' => ['confirm_password']
            ],
            'Blank passwords' => [
                'data' => [
                    'password' => '',
                    'confirm_password' => ''
                ],
                'isValid' => false,
                'expectedErrors' => ['password', 'confirm_password']
            ]
        ];
    }

    /**
     * @dataProvider submitProvider
     *
     * @covers ::submit
     * @covers ::getData
     * @covers ::confirmPassword
     *
     * @param array $data
     * @param boolean $isValid
     * @param array $expectedErrors
     */
    public function testSubmit(array $data, $isValid, array $expectedErrors = [])
    {

        $validatorBuilder = new ValidatorBuilder();
        $validator = $validatorBuilder->getValidator();

        $organizationRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface');
        $userRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface');
        $userFactory = Mockery::mock('\Tornado\Organization\User\Factory');

        $form = new ResetPassword(
            $validator,
            $organizationRepo,
            $userRepo,
            $userFactory
        );

        $form->submit($data);

        $errors = $form->getErrors();
        $this->assertEquals(count($expectedErrors), count($errors));
        foreach ($expectedErrors as $field) {
            $this->assertTrue(isset($errors[$field]));
        }

        $this->assertEquals($isValid, $form->isValid());
    }
}
