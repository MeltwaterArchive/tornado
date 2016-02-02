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
class ChangePassword extends Create
{

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $data = [
            'password' => $this->inputData['password'],
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
                    'message' => 'The current password field is missing.'
                ]),
                new Callback($this->passwordCorrect())
            ]),
            'password' => new Required([
                new NotBlank([
                    'message' => 'The password field is missing.'
                ])
            ]),
            'confirm_password' => new Required([
                new NotBlank([
                    'message' => 'The confirm password field is missing.'
                ]),
                new Callback($this->confirmPassword())
            ])
        ]);
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
}
