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
class Update extends Create
{

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $data = [
            'email' => $this->inputData['email'],
            'username' => $this->inputData['username'],
        ];

        if (isset($this->inputData['password']) && $this->inputData['password']) {
            $data['password'] = $this->inputData['password'];
        }

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
        $permissionChoices = [
            static::PERMISSION_NONE,
            static::PERMISSION_SPAONLY,
            static::PERMISSION_ADMIN
        ];

        if ($allowSuperAdmin) {
            $permissionChoices[] = static::PERMISSION_SUPERADMIN;
        }

        return new Collection([
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
            'password' => new Optional(),
            'confirm_password' => new Optional([
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
}
