<?php

namespace Test\Tornado\Organization\User\Form;

use \Mockery;

use Tornado\Organization\Organization;
use Tornado\Organization\User;
use Tornado\Organization\User\Factory;
use Tornado\Organization\User\Form\Create;

use Test\DataSift\ApplicationBuilder;
use Test\DataSift\ReflectionAccess;

/**
 * CreateTest
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
 * @coversDefaultClass      \Tornado\Organization\User\Form\Create
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
        $this->buildApplication();
        $this->validator = $this->container->get('validator');
    }

    /**
     * @covers  ::__construct
     * @covers  ::getFields
     */
    public function testGetFields()
    {
        $mocks = $this->getMocks();
        $form = $this->getForm($mocks);

        $this->assertEquals(
            ['organizationId', 'username', 'email', 'password', 'confirm_password', 'permissions'],
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
     * @covers  ::organizationExists
     * @covers  ::userExists
     */
    public function testSubmit()
    {
        $mocks = $this->getMocks();
        $mocks['organizationRepo']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['organizationId']])
            ->andReturn($mocks['organization']);
        $mocks['userRepo']->shouldReceive('findOne')
            ->once()
            ->with(['email' => $mocks['email']])
            ->andReturnNull();

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertInstanceOf('\Tornado\Organization\User', $form->getData());

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
        $this->assertEquals($mocks['organizationId'], $modelData->getOrganizationId());
        $this->assertEquals($mocks['email'], $modelData->getEmail());
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
     * @covers  ::organizationExists
     * @covers  ::userExists
     */
    public function testReturnsErrorsUnlessOrganizationExists()
    {
        $mocks = $this->getMocks();
        $mocks['organizationRepo']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['organizationId']])
            ->andReturnNull();
        $mocks['userRepo']->shouldReceive('findOne')
            ->once()
            ->with(['email' => $mocks['email']])
            ->andReturnNull();

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertInstanceOf('\Tornado\Organization\User', $form->getData());

        $form->submit($mocks['inputData']);

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
        $this->assertArrayHasKey('organizationId', $errors);
        $this->assertArrayNotHasKey('username', $errors);
        $this->assertArrayNotHasKey('email', $errors);
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
     * @covers  ::organizationExists
     * @covers  ::userExists
     */
    public function testReturnsErrorsUnlessUserNotExists()
    {
        $mocks = $this->getMocks();
        $mocks['organizationRepo']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['organizationId']])
            ->andReturn($mocks['organization']);
        $mocks['userRepo']->shouldReceive('findOne')
            ->once()
            ->with(['email' => $mocks['email']])
            ->andReturn($mocks['user']);

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertInstanceOf('\Tornado\Organization\User', $form->getData());

        $form->submit($mocks['inputData']);

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
        $this->assertArrayNotHasKey('organizationId', $errors);
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
     * @covers  ::organizationExists
     * @covers  ::userExists
     */
    public function testReturnsErrorsUnlessRequiredDataGiven()
    {
        $mocks = $this->getMocks();
        $mocks['organizationRepo']->shouldReceive('findOne')
            ->never();
        $mocks['userRepo']->shouldReceive('findOne')
            ->never();

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertInstanceOf('\Tornado\Organization\User', $form->getData());

        $form->submit([]);

        $this->assertEquals(true, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());

        $this->assertEquals([], $form->getInputData());
        $this->assertInternalType('array', $form->getInputData());

        $this->assertEquals([], $form->getNormalizedData());
        $this->assertInternalType('array', $form->getNormalizedData());

        $modelData = $form->getData();
        $this->assertInstanceOf('\Tornado\Organization\User', $modelData);

        $errors = $form->getErrors();
        $this->assertInternalType('array', $errors);
        $this->assertArrayHasKey('organizationId', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('username', $errors);
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
     * @covers  ::organizationExists
     * @covers  ::userExists
     */
    public function testReturnsErrorsUnlessValidDataGiven()
    {
        $mocks = $this->getMocks();
        $inputData = [
            'username' => '',
            'email' => 'test',
            'organizationId' => 'string',
            'password' => ''
        ];

        $mocks['organizationRepo']->shouldReceive('findOne')
            ->once()
            ->with(['id' => 'string'])
            ->andReturnNull();
        $mocks['userRepo']->shouldReceive('findOne')
            ->once()
            ->with(['email' => 'test'])
            ->andReturnNull();

        $form = $this->getForm($mocks);

        $this->assertEquals(false, $form->isSubmitted());
        $this->assertEquals(false, $form->isValid());
        $this->assertEquals([], $form->getErrors());
        $this->assertInstanceOf('\Tornado\Organization\User', $form->getData());

        $form->submit($inputData);

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
        $this->assertArrayHasKey('organizationId', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('username', $errors);
        $this->assertArrayHasKey('password', $errors);
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
        $username = 'unit';
        $email = 'test@unit.com';
        $password = '123qwe';

        $organization = new Organization();
        $organization->setId($organizationId);

        $inputData = [
            'organizationId' => $organizationId,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'confirm_password' => $password,
            'permissions' => 'none'
        ];

        $userId = 20;
        $user = new User();
        $user->setId($userId);
        $user->setEmail($email);

        return [
            'organizationRepo' => $organizationRepo,
            'organization' => $organization,
            'userRepo' => $userRepo,
            'user' => $user,
            'userId' => $userId,
            'userFactory' => $userFactory,
            'inputData' => $inputData,
            'organizationId' => $organizationId,
            'username' => $username,
            'email' => $email,
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
        return new Create(
            $this->validator,
            $mocks['organizationRepo'],
            $mocks['userRepo'],
            $mocks['userFactory']
        );
    }
}
