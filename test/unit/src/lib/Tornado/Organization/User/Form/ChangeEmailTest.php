<?php

namespace Test\Tornado\Organization\User\Form;

use \Mockery;

use Symfony\Component\Validator\ValidatorBuilder;
use Tornado\Organization\Organization;
use Tornado\Organization\User;
use Tornado\Organization\User\Factory;
use Tornado\Organization\User\Form\ChangeEmail;

use Test\DataSift\ApplicationBuilder;
use Test\DataSift\ReflectionAccess;

/**
 * ChangeEmailTest
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
 * @coversDefaultClass      \Tornado\Organization\User\Form\ChangeEmail
 */
class ChangeEmailTest extends \PHPUnit_Framework_TestCase
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

        $form = new ChangeEmail($validator, $organizationRepo, $userRepo, $userFactory);

        $this->assertEquals(
            ['currentPassword', 'email', 'confirmEmail'],
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
                    'email' => 'test@test.com',
                    'confirmEmail' => 'test@test.com'
                ],
                'user' => Mockery::mock(
                    '\Tornado\Organization\User',
                    ['getPassword' => \password_hash('test', PASSWORD_DEFAULT), 'getId' => 1]
                ),
                'newEmail' => 'test@test.com',
                'userExists' => 0,
                'isValid' => true
            ],
            'Happy path, user exists, same id' => [
                'data' => [
                    'currentPassword' => 'test',
                    'email' => 'test@test.com',
                    'confirmEmail' => 'test@test.com'
                ],
                'user' => Mockery::mock(
                    '\Tornado\Organization\User',
                    ['getPassword' => \password_hash('test', PASSWORD_DEFAULT), 'getId' => 1]
                ),
                'newEmail' => 'test@test.com',
                'userExists' => 1,
                'isValid' => true
            ],
            'Fail path, user exists, different id' => [
                'data' => [
                    'currentPassword' => 'test',
                    'email' => 'test@test.com',
                    'confirmEmail' => 'test@test.com'
                ],
                'user' => Mockery::mock(
                    '\Tornado\Organization\User',
                    ['getPassword' => \password_hash('test', PASSWORD_DEFAULT), 'getId' => 1]
                ),
                'newEmail' => 'test@test.com',
                'userExists' => 2,
                'isValid' => false,
                'expectedErrors' => ['email']
            ],
            'Fail path invalid email address' => [
                'data' => [
                    'currentPassword' => 'test',
                    'email' => 'test',
                    'confirmEmail' => 'test'
                ],
                'user' => Mockery::mock(
                    '\Tornado\Organization\User',
                    ['getPassword' => \password_hash('test', PASSWORD_DEFAULT), 'getId' => 1]
                ),
                'newEmail' => 'test',
                'userExists' => 1,
                'isValid' => false,
                'expectedErrors' => ['email', 'confirmEmail']
            ],
            'Fail path email addresses do not match' => [
                'data' => [
                    'currentPassword' => 'test',
                    'email' => 'test@test.com',
                    'confirmEmail' => 'test2@test.com'
                ],
                'user' => Mockery::mock(
                    '\Tornado\Organization\User',
                    ['getPassword' => \password_hash('test', PASSWORD_DEFAULT), 'getId' => 1]
                ),
                'newEmail' => 'test@test.com',
                'userExists' => 1,
                'isValid' => false,
                'expectedErrors' => ['confirmEmail']
            ],
            'Fail path bad password' => [
                'data' => [
                    'currentPassword' => 'tested',
                    'email' => 'test@test.com',
                    'confirmEmail' => 'test@test.com'
                ],
                'user' => Mockery::mock(
                    '\Tornado\Organization\User',
                    ['getPassword' => \password_hash('test', PASSWORD_DEFAULT), 'getId' => 1]
                ),
                'newEmail' => 'test@test.com',
                'userExists' => 1,
                'isValid' => false,
                'expectedErrors' => ['currentPassword']
            ]
        ];
    }

    /**
     * @dataProvider submitProvider
     *
     * @covers ::submit
     * @covers ::getData
     * @covers ::userExists
     * @covers ::passwordCorrect
     * @covers ::confirmEmail
     *
     * @param array $data
     * @param \Tornado\Organization\User $user
     * @param string $newEmail
     * @param integer $userExists
     * @param boolean $isValid
     * @param array $expectedErrors
     */
    public function testSubmit(array $data, User $user, $newEmail, $userExists, $isValid, array $expectedErrors = [])
    {

        $validatorBuilder = new ValidatorBuilder();
        $validator = $validatorBuilder->getValidator();

        $organizationRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface');
        $userRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface');

        $foundUser = null;
        if ($userExists) {
            $foundUser = Mockery::mock('\Tornado\Organization\User', ['getId' => $userExists]);
        }

        $userRepo->shouldReceive('findOne')
            ->with(['email' => $newEmail])
            ->andReturn($foundUser);

        $userFactory = Mockery::mock('\Tornado\Organization\User\Factory');
        $newData = Mockery::mock('\Tornado\Organization\User');
        $userFactory->shouldReceive('update')
            ->once()
            ->with($user, ['email' => $newEmail])
            ->andReturn($newData);

        $userFactory->shouldReceive('update')
            ->once()
            ->with($newData, ['email' => $newEmail])
            ->andReturn($newData);

        $form = new ChangeEmail(
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
