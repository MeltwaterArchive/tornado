<?php

namespace Tornado\Organization\Organization\Form;

use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use DataSift\Form\Form;

use Tornado\DataMapper\DataObjectInterface;
use Tornado\Organization\Organization;
use Tornado\Organization\Organization\DataMapper as OrganizationRepo;

/**
 * Create
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
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Create extends Form
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var OrganizationRepo
     */
    protected $organizationRepo;

    /**
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \Tornado\Organization\Organization\DataMapper             $organizationRepo
     */
    public function __construct(
        ValidatorInterface $validator,
        OrganizationRepo $organizationRepo
    ) {
        $this->validator = $validator;
        $this->organizationRepo = $organizationRepo;
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
    public function getData()
    {
        if (!$this->isSubmitted() || !$this->isValid()) {
            return null;
        }

        $this->modelData = new Organization();
        $this->modelData->setName($this->inputData['name']);

        if (isset($this->inputData['jwt_secret'])) {
            $this->modelData->setJwtSecret($this->inputData['jwt_secret']);
        }

        if (isset($this->inputData['skin'])) {
            $this->modelData->setSkin($this->inputData['skin']);
        }

        return $this->modelData;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConstraints()
    {
        return new Collection([
            'name' => new Required([
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

            if ($organization) {
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
