<?php

namespace Tornado\Organization\User\Form;

use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use DataSift\Form\Form;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\DataObjectInterface;
use Tornado\Organization\User;
use Tornado\Organization\User\Factory;

/**
 * Create User form
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Organization\User\Form
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Create extends Form
{

    /**
     * Permission levels
     */
    const PERMISSION_NONE = 'none';
    const PERMISSION_SPAONLY = 'spaonly';
    const PERMISSION_ADMIN = 'admin';
    const PERMISSION_SUPERADMIN = 'superadmin';

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var DataMapperInterface
     */
    protected $organizationRepo;

    /**
     * @var DataMapperInterface
     */
    protected $userRepository;

    /**
     * @var \Tornado\Organization\User\Factory
     */
    protected $userFactory;

    /**
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \Tornado\DataMapper\DataMapperInterface                   $organizationRepo
     * @param \Tornado\DataMapper\DataMapperInterface                   $userRepository
     * @param \Tornado\Organization\User\Factory                        $userFactory
     */
    public function __construct(
        ValidatorInterface $validator,
        DataMapperInterface $organizationRepo,
        DataMapperInterface $userRepository,
        Factory $userFactory
    ) {
        $this->validator = $validator;
        $this->organizationRepo = $organizationRepo;
        $this->userRepository = $userRepository;
        $this->userFactory = $userFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function submit(array $data, DataObjectInterface $object = null, $allowSuperUser = false)
    {
        $this->inputData = $data;
        $this->normalizedData = $data;
        $this->modelData = $object;

        $this->errors = $this->validator->validate($data, $this->getConstraints($allowSuperUser));
        $this->getData();
        $this->submitted = true;
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        return array_keys($this->getConstraints()->fields);
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {

        $data = [
            'email' => '',
            'password' => '',
            'username' => '',
            'organizationId' => null,
            'permissions' => ''
        ];

        $inputData = ($this->inputData) ? $this->inputData : [];

        $this->modelData = $this->userFactory->create(array_merge($data, $inputData));

        return $this->modelData;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConstraints($allowSuperAdmin = false)
    {

        $permissionChoices = [
            static::PERMISSION_NONE,
            static::PERMISSION_SPAONLY,
            static::PERMISSION_ADMIN,
        ];

        if ($allowSuperAdmin) {
            $permissionChoices[] = static::PERMISSION_SUPERADMIN;
        }

        return new Collection([
            'organizationId' => new Required([
                new NotBlank([
                    'message' => 'The username is missing.'
                ]),
                new Type([
                    'type' => 'numeric',
                    'message' => 'Organization ID must be a number.'
                ]),
                new Callback($this->organizationExists())
            ]),
            'username' => new Required([
                new NotBlank([
                    'message' => 'The username is missing.'
                ])
            ]),
            'email' => new Required([
                new NotBlank([
                    'message' => 'The email is missing.'
                ]),
                new Email(),
                new Callback($this->userExists())
            ]),
            'password' => new Required([
                new NotBlank([
                    'message' => 'The password is missing.'
                ])
            ]),
            'confirm_password' => new Required([
                new NotBlank([
                    'message' => 'The password confirmation is missing.'
                ]),
                new Callback($this->confirmPassword())
            ]),
            'permissions' => new Required([
                new NotBlank([
                    'message' => 'The access level for the user must not be empty'
                ]),
                new Choice([
                    'choices' => $permissionChoices,
                    'message' => 'Invalid permission given'
                ])
            ])
        ]);
    }

    /**
     * Validates if Organization of given ID exists
     *
     * @return callable
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function organizationExists()
    {
        return function ($object, ExecutionContextInterface $context) {
            $organization = $this->organizationRepo->findOne(
                ['id' => $this->inputData['organizationId']]
            );

            if (!$organization) {
                $context
                    ->buildViolation(sprintf(
                        'Organization with id "%d" does not exist in the system.',
                        $this->inputData['organizationId']
                    ))
                    ->addViolation();
            }
        };
    }

    /**
     * Confirms the password and confirm_password fields are identical
     *
     * @return callable
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function confirmPassword()
    {
        return function ($object, ExecutionContextInterface $context) {
            if ($this->inputData['confirm_password'] !== $this->inputData['password']) {
                $context->buildViolation('Passwords must match')->addViolation();
            }
        };
    }

    /**
     * Validates if user of given email already exists in the system
     *
     * @return callable
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function userExists()
    {
        return function ($object, ExecutionContextInterface $context) {
            $user = $this->userRepository->findOne(['email' => $this->inputData['email']]);

            if ($user) {
                $context
                    ->buildViolation(sprintf(
                        'User with email "%s" already exists in the system.',
                        $this->inputData['email']
                    ))
                    ->addViolation();
            }
        };
    }
}
