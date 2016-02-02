<?php

namespace Tornado\Organization\Organization\Form;

use Doctrine\Common\Util\Debug;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Tornado\DataMapper\DataObjectInterface;

/**
 * Update
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Organization
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Update extends Create
{
    /**
     * {@inheritdoc}
     */
    public function submit(array $data, DataObjectInterface $object = null)
    {
        if (!$object || !$object->getId()) {
            throw new \LogicException(sprintf(
                '%s expects persisted DataObject as the 2nd argument.',
                __METHOD__
            ));
        }

        parent::submit($data, $object);
    }

    /**
     * {@inheritdoc}
     */
    protected function getConstraints()
    {
        return new Collection([
            'name' => new Optional([
                new NotBlank(),
                new Type([
                    'type' => 'string',
                    'message' => 'Organization name must be a string.'
                ]),
                new Callback($this->organizationExists())
            ]),
            'jwt_secret' => new Optional([
                new Type(['type' => 'string'])
            ]),
            'skin' => new Optional([
                new Type(['type' => 'string'])
            ])
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        if (!$this->isSubmitted() || !$this->isValid()) {
            return null;
        }

        if (isset($this->inputData['name'])) {
            $this->modelData->setName($this->inputData['name']);
        }

        if (isset($this->inputData['jwt_secret'])) {
            $this->modelData->setJwtSecret($this->inputData['jwt_secret']);
        }

        if (isset($this->inputData['skin'])) {
            $this->modelData->setSkin($this->inputData['skin']);
        }

        return $this->modelData;
    }

    /**
     * Validates if Organization with given name exists
     *
     * @return callable
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function organizationExists()
    {
        return function ($object, ExecutionContextInterface $context) {
            if (!isset($this->inputData['name'])) {
                return false;
            }

            $organization = $this->organizationRepo->findByName($this->inputData['name']);

            if ($organization && $organization->getId() !== $this->modelData->getId()) {
                $context
                    ->buildViolation(sprintf(
                        'Organization with name "%s" already exists.',
                        $this->inputData['name']
                    ))
                    ->addViolation();
            }
        };
    }
}
