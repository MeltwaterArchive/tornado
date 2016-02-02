<?php

namespace Test\Tornado\Organization\User\Form;

use \Mockery;

use Symfony\Component\Validator\ValidatorBuilder;
use Tornado\Organization\Organization;
use Tornado\Organization\User;
use Tornado\Organization\User\Factory;
use Tornado\Organization\User\Form\ChangePassword;

use Test\DataSift\ApplicationBuilder;
use Test\DataSift\ReflectionAccess;

/**
 * ChangePasswordTest
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
 * @coversDefaultClass      \Tornado\Organization\User\Form\ChangePassword
 */
class ChangePasswordTest extends \PHPUnit_Framework_TestCase
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

        $form = new ChangePassword($validator, $organizationRepo, $userRepo, $userFactory);

        $this->assertEquals(
            ['currentPassword', 'password', 'confirm_password'],
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
                    'currentPassword' => 'test',
                    'password' => 'test2',
                    'confirm_password' => 'test2'
                ],
                'user' => Mockery::mock(
                    '\Tornado\Organization\User',
                    ['getPassword' => \password_hash('test', PASSWORD_DEFAULT)]
                ),
                'newPassword' => 'test2',
                'isValid' => true
            ],
            'Invalid current password' => [
                'data' => [
                    'currentPassword' => 'test2',
                    'password' => 'test2',
                    'confirm_password' => 'test2'
                ],
                'user' => Mockery::mock(
                    '\Tornado\Organization\User',
                    ['getPassword' => \password_hash('test', PASSWORD_DEFAULT)]
                ),
                'newPassword' => 'test2',
                'isValid' => false,
                'expectedErrors' => ['currentPassword']
            ],
            'Mismatched password' => [
                'data' => [
                    'currentPassword' => 'test',
                    'password' => 'test2',
                    'confirm_password' => 'test3'
                ],
                'user' => Mockery::mock(
                    '\Tornado\Organization\User',
                    ['getPassword' => \password_hash('test', PASSWORD_DEFAULT)]
                ),
                'newPassword' => 'test2',
                'isValid' => false,
                'expectedErrors' => ['confirm_password']
            ]
        ];
    }

    /**
     * @dataProvider submitProvider
     *
     * @covers ::submit
     * @covers ::getData
     * @covers ::passwordCorrect
     * @covers ::confirmPassword
     *
     * @param array $data
     * @param \Tornado\Organization\User $user
     * @param string $newPassword
     * @param boolean $isValid
     * @param array $expectedErrors
     */
    public function testSubmit(array $data, User $user, $newPassword, $isValid, array $expectedErrors = [])
    {

        $validatorBuilder = new ValidatorBuilder();
        $validator = $validatorBuilder->getValidator();

        $organizationRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface');
        $userRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface');
        $userFactory = Mockery::mock('\Tornado\Organization\User\Factory');

        $newData = Mockery::mock('\Tornado\Organization\User');
        $userFactory->shouldReceive('update')
            ->once()
            ->with($user, ['password' => $newPassword])
            ->andReturn($newData);

        $userFactory->shouldReceive('update')
            ->once()
            ->with($newData, ['password' => $newPassword])
            ->andReturn($newData);

        $form = new ChangePassword(
            $validator,
            $organizationRepo,
            $userRepo,
            $userFactory
        );

        $form->submit($data, $user);

        $errors = $form->getErrors();
        $this->assertEquals(count($expectedErrors), count($errors));
        foreach ($expectedErrors as $field) {
            $this->assertTrue(isset($errors[$field]));
        }

        $this->assertEquals($isValid, $form->isValid());
        $this->assertEquals($newData, $form->getData());
    }
}
