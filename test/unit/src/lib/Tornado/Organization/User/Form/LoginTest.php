<?php

namespace Test\Tornado\Organization\User\Form;

use \Mockery;

use Tornado\Organization\User;
use Tornado\Organization\User\Form\Login;

use Test\DataSift\ApplicationBuilder;
use Test\DataSift\ReflectionAccess;

use Symfony\Component\Validator\ValidatorBuilder;

/**
 * LoginTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Organization\User\Form
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass      \Tornado\Organization\User\Form\Login
 */
class LoginTest extends \PHPUnit_Framework_TestCase
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
     * @covers  ::getConstraints
     */
    public function testGetFields()
    {
        $mocks = $this->getMocks();
        $form = $this->getForm($mocks);

        $this->assertEquals(
            ['login', 'password', 'redirect'],
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
     * @covers  ::passwordCorrect
     * @covers  ::userExists
     */
    public function testSubmit()
    {
        $mocks = $this->getMocks();
        $mocks['userRepo']->shouldReceive('findOne')
            ->once()
            ->with(['email' => $mocks['login']])
            ->andReturn($mocks['user']);

        $form = $this->getForm($mocks);

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
        $this->assertInstanceOf('\Tornado\Organization\User', $modelData);
        $this->assertEquals($mocks['username'], $modelData->getUsername());
        $this->assertEquals($mocks['login'], $modelData->getEmail());
        $this->assertTrue(password_verify($mocks['password'], $modelData->getPassword()));
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
     * @covers  ::passwordCorrect
     * @covers  ::userExists
     */
    public function testReturnsErrorUnlessUserExists()
    {
        $mocks = $this->getMocks();
        $mocks['userRepo']->shouldReceive('findOne')
            ->once()
            ->with(['email' => $mocks['login']])
            ->andReturnNull();

        $form = $this->getForm($mocks);

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
        $this->assertNotInstanceOf('\Tornado\Organization\User', $modelData);
        $this->assertNull($modelData);

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('login', $errors);
        $this->assertArrayNotHasKey('password', $errors);
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
     * @covers  ::passwordCorrect
     * @covers  ::userExists
     */
    public function testReturnsErrorUnlessPasswordMatch()
    {
        $mocks = $this->getMocks();
        $mocks['user']->setPassword(password_hash('notmatch', PASSWORD_BCRYPT));

        $mocks['userRepo']->shouldReceive('findOne')
            ->once()
            ->with(['email' => $mocks['login']])
            ->andReturn($mocks['user']);

        $form = $this->getForm($mocks);

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
        $this->assertInstanceOf('\Tornado\Organization\User', $modelData);
        $this->assertEquals($mocks['username'], $modelData->getUsername());
        $this->assertEquals($mocks['login'], $modelData->getEmail());
        $this->assertTrue(password_verify('notmatch', $modelData->getPassword()));

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('password', $errors);
        $this->assertArrayNotHasKey('login', $errors);
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
     * @covers  ::passwordCorrect
     * @covers  ::userExists
     */
    public function testReturnsErrorUnlessRequiredDataGiven()
    {
        $mocks = $this->getMocks();
        $mocks['userRepo']->shouldReceive('findOne')
            ->never();

        $form = $this->getForm($mocks);

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
        $this->assertNotInstanceOf('\Tornado\Organization\User', $modelData);
        $this->assertNull($modelData);

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('login', $errors);
        $this->assertArrayHasKey('password', $errors);
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
     * @covers  ::passwordCorrect
     * @covers  ::userExists
     */
    public function testReturnsErrorUnlessValidDataGiven()
    {
        $mocks = $this->getMocks();
        $mocks['userRepo']->shouldReceive('findOne')
            ->once();
        $inputData = ['login' => '', 'password' => ''];

        $form = $this->getForm($mocks);

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
        $this->assertNotInstanceOf('\Tornado\Organization\User', $modelData);
        $this->assertNull($modelData);

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('login', $errors);
        $this->assertArrayHasKey('password', $errors);
    }

    /**
     * Creates test mocks
     *
     * @return array
     */
    protected function getMocks()
    {
        $userRepo = Mockery::mock('\Tornado\Organization\User\DataMapper');

        $login = 'test@unit.com';
        $password = '123qwe';
        $username = 'test';

        $inputData = [
            'login' => $login,
            'password' => $password
        ];

        $userId = 20;
        $user = new User();
        $user->setId($userId);
        $user->setUsername($username);
        $user->setPassword(password_hash($password, PASSWORD_BCRYPT));
        $user->setEmail($login);

        return [
            'userRepo' => $userRepo,
            'user' => $user,
            'userId' => $userId,
            'inputData' => $inputData,
            'username' => $username,
            'login' => $login,
            'password' => $password
        ];
    }

    /**
     * @param array $mocks
     *
     * @return \Tornado\Organization\User\Form\Create
     */
    protected function getForm(array $mocks)
    {
        return new Login(
            $this->validator,
            $mocks['userRepo']
        );
    }
}
