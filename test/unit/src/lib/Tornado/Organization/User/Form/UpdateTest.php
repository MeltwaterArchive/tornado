<?php

namespace Test\Tornado\Organization\User\Form;

use \Mockery;

use Tornado\Organization\Organization;
use Tornado\Organization\User;
use Tornado\Organization\User\Factory;
use Tornado\Organization\User\Form\Update;

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
 * @package     \Test\Tornado\Organization\User\Form
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass      \Tornado\Organization\User\Form\Update
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
     * @covers  ::submit
     *
     * @expectedException \LogicException
     */
    public function testThrowExceptionUnlessObjectGiven()
    {
        $mocks = $this->getMocks();

        $form = $this->getForm($mocks);
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
        $user = new User();

        $form = $this->getForm($mocks);
        $form->submit($mocks['inputData'], $user);
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
            ['username', 'email', 'password', 'confirm_password', 'permissions', 'disabled'],
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
     * @covers  ::userExists
     */
    public function testSubmit()
    {
        $mocks = $this->getMocks();
        $mocks['userRepo']->shouldReceive('findOne')
            ->once()
            ->with(['email' => $mocks['updatedEmail']])
            ->andReturnNull();

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit($mocks['inputData'], $mocks['user']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(true, $form->isValid());
        $this->assertEquals([], $form->getErrors());

        $this->assertEquals($mocks['inputData'], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($mocks['inputData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf('\Tornado\Organization\User', $modelData);
        $this->assertEquals($mocks['updatedUsername'], $modelData->getUsername());
        $this->assertEquals($mocks['updatedEmail'], $modelData->getEmail());
        $this->assertTrue(password_verify($mocks['updatedPassword'], $modelData->getPassword()));
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
     * @covers  ::userExists
     */
    public function testReturnsErrorsUnlessUserNotExists()
    {
        $mocks = $this->getMocks();

        $user = new User();
        $user->setId(200);
        $mocks['userRepo']->shouldReceive('findOne')
            ->once()
            //->with(['email' => $mocks['updatedEmail']])
            ->andReturn($user);

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit($mocks['inputData'], $mocks['user']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals($mocks['inputData'], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($mocks['inputData'], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf('\Tornado\Organization\User', $modelData);

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayNotHasKey('organization_id', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayNotHasKey('username', $errors);
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
     * @covers  ::userExists
     */
    public function testPartialUpdate()
    {
        $mocks = $this->getMocks();
        $inputData = [
            'username' => $mocks['username'],
            'email' => $mocks['email'],
            'permissions' => $mocks['permissions'],
            'disabled' => 1
        ];

        $mocks['userRepo']->shouldReceive('findOne')
            ->once()
            ->with(['email' => $mocks['email']])
            ->andReturnNull();

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit($inputData, $mocks['user']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(true, $form->isValid());
        $this->assertEquals([], $form->getErrors());

        $this->assertEquals($inputData, $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($inputData, $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf('\Tornado\Organization\User', $modelData);
        $this->assertEquals($mocks['username'], $modelData->getUsername());
        $this->assertEquals($mocks['organizationId'], $modelData->getOrganizationId());
        $this->assertEquals($mocks['email'], $modelData->getEmail());
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
     * @covers  ::organizationExists
     * @covers  ::userExists
     */
    public function testReturnsErrorsUnlessValidDataGiven()
    {
        $mocks = $this->getMocks();
        $inputData = [
            'organization_id' => 'string',
            'username' => '',
            'email' => 'test@unit',
            'password' => '',
            'disabled' => 0
        ];

        $mocks['userRepo']->shouldReceive('findOne')
            ->once()
            ->with(['email' => 'test@unit'])
            ->andReturnNull();

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertEquals(null, $form->getData());

        $form->submit($inputData, $mocks['user']);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals($inputData, $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals($inputData, $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf('\Tornado\Organization\User', $modelData);

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('username', $errors);
    }

    /**
     * Creates test mocks
     *
     * @return array
     */
    protected function getMocks()
    {
        $organizationRepo = Mockery::mock('\Tornado\DataMapper\DataMapperInterface');
        $userRepo = Mockery::mock('\Tornado\Organization\User\DataMapper');
        $userFactory = new Factory();

        $organizationId = 1;
        $updatedOrganizationId = 100;
        $username = 'unit';
        $updatedUsername = 'username edited';
        $email = 'test@unit.com';
        $updatedEmail = 'unit@test.com';
        $password = '123qwe';
        $updatedPassword = 'qwe123';
        $permissions = 'none';

        $organization = new Organization();
        $organization->setId($organizationId);

        $inputData = [
            'username' => $updatedUsername,
            'email' => $updatedEmail,
            'password' => $updatedPassword,
            'confirm_password' => $updatedPassword,
            'permissions' => $permissions,
            'disabled' => 0
        ];

        $userId = 20;
        $user = new User();
        $user->setOrganizationId($organizationId);
        $user->setId($userId);
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword(password_hash($password, PASSWORD_BCRYPT));

        return [
            'organizationRepo' => $organizationRepo,
            'organization' => $organization,
            'userRepo' => $userRepo,
            'user' => $user,
            'userId' => $userId,
            'userFactory' => $userFactory,
            'inputData' => $inputData,
            'organizationId' => $organizationId,
            'updatedOrganizationId' => $updatedOrganizationId,
            'username' => $username,
            'updatedUsername' => $updatedUsername,
            'email' => $email,
            'updatedEmail' => $updatedEmail,
            'password' => $password,
            'updatedPassword' => $updatedPassword,
            'permissions' => $permissions
        ];
    }

    /**
     * @param array $mocks
     *
     * @return \Tornado\Organization\User\Form\Create
     */
    protected function getForm(array $mocks)
    {
        return new Update(
            $this->validator,
            $mocks['organizationRepo'],
            $mocks['userRepo'],
            $mocks['userFactory']
        );
    }
}
