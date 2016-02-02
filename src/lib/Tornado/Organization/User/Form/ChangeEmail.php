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
 * Update User form
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
class ChangeEmail extends Create
{

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $data = [
            'email' => $this->inputData['email'],
        ];

        if ($this->modelData) {
            $this->modelData = $this->userFactory->update(
                $this->modelData,
                $data
            );
        }

        return $this->modelData;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConstraints($allowSuperAdmin = false)
    {
        return new Collection([
            'currentPassword' => new Required([
                new NotBlank([
                    'message' => 'The password is missing.'
                ]),
                new Callback($this->passwordCorrect())
            ]),
            'email' => new Required([
                new NotBlank([
                    'message' => 'The email field is missing.'
                ]),
                new Email(),
                new Callback($this->userExists())
            ]),
            'confirmEmail' => new Required([
                new NotBlank([
                    'message' => 'The confirm email field is missing.'
                ]),
                new Email(),
                new Callback($this->confirmEmail())
            ])
        ]);
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
            if ($user && $user->getId() !== $this->modelData->getId()) {
                $context
                    ->buildViolation(sprintf(
                        'User with email "%s" already exists in the system.',
                        $this->inputData['email']
                    ))
                    ->addViolation();
            }
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
            if (!password_verify($this->inputData['currentPassword'], $this->modelData->getPassword())) {
                $context
                    ->buildViolation(sprintf(
                        'Invalid password.',
                        $this->inputData['currentPassword']
                    ))
                    ->addViolation();
            }
        };
    }

    /**
     * Confirms the email and confirmEmail fields are identical
     *
     * @return callable
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function confirmEmail()
    {
        return function ($object, ExecutionContextInterface $context) {
            if ($this->inputData['confirmEmail'] !== $this->inputData['email']) {
                $context->buildViolation('Email addresses must match')->addViolation();
            }
        };
    }
}
