<?php

namespace Tornado\Organization\User\Form;

use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use DataSift\Form\Form;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\DataObjectInterface;
use Tornado\Organization\User;

/**
 * Login
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     Tornado
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Login extends Form
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var DataMapperInterface
     */
    protected $userRepository;

    /**
     * @param ValidatorInterface  $validator
     * @param DataMapperInterface $userRepository
     */
    public function __construct(ValidatorInterface $validator, DataMapperInterface $userRepository)
    {
        $this->validator = $validator;
        $this->userRepository = $userRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function submit(array $data, DataObjectInterface $object = null)
    {
        $this->inputData = $data;
        $this->normalizedData = $data;
        $this->modelData = $object;

        $this->errors = $this->validator->validate($data, $this->getConstraints());
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
    protected function getConstraints()
    {
        return new Collection([
            'login' => new Required([
                new NotBlank([
                    'message' => 'The username or email is missing.'
                ]),
                new Callback($this->userExists())
            ]),
            'password' => new Required([
                new NotBlank([
                    'message' => 'The password is missing.'
                ]),
                new Callback($this->passwordCorrect())
            ]),
            'redirect' => new Optional()
        ]);
    }

    /**
     * Validates if user of given email or username exists in the system
     *
     * @return callable
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function userExists()
    {
        return function ($object, ExecutionContextInterface $context) {
            if (!isset($this->inputData['login'])) {
                return;
            }

            $user = $this->userRepository->findOne(['email' => $this->inputData['login']]);

            if (!$user) {
                $context
                    ->buildViolation(sprintf(
                        'Invalid login.',
                        $this->inputData['login']
                    ))
                    ->addViolation();
            }

            $this->modelData = $user;
        };
    }

    /**
     * Validates stored user password with the given one.
     *
     * This constraint callback MUST be called after the user was found in the database
     * (by the input email or username) and set to the modelData
     *
     * @return callable
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function passwordCorrect()
    {
        return function ($object, ExecutionContextInterface $context) {
            if (!$this->modelData instanceof User) {
                return;
            }

            if (!password_verify($this->inputData['password'], $this->modelData->getPassword())) {
                $context
                    ->buildViolation(sprintf(
                        'Invalid login.',
                        $this->inputData['password']
                    ))
                    ->addViolation();
            }
        };
    }
}
