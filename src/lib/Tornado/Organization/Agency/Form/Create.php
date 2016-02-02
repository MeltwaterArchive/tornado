<?php

namespace Tornado\Organization\Agency\Form;

use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use DataSift\Form\Form;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\DataObjectInterface;
use Tornado\Organization\Agency;

/**
 * Create Agency form
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Organization\User\Form
 * @author      Christopher Hoult <chris.hoult@datasift.com>
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
     * @var DataMapperInterface
     */
    protected $organizationRepo;

    /**
     * @var DataMapperInterface
     */
    protected $agencyRepository;

    /**
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \Tornado\DataMapper\DataMapperInterface                   $organizationRepo
     * @param \Tornado\DataMapper\DataMapperInterface                   $agencyRepository
     */
    public function __construct(
        ValidatorInterface $validator,
        DataMapperInterface $organizationRepo,
        DataMapperInterface $agencyRepository
    ) {
        $this->validator = $validator;
        $this->organizationRepo = $organizationRepo;
        $this->agencyRepository = $agencyRepository;
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
            'name' => '',
            'datasiftUsername' => '',
            'datasiftApikey' => '',
            'organizationId' => null
        ];

        $inputData = ($this->inputData) ? $this->inputData : [];
        $data = array_merge($data, $inputData);

        $this->modelData = new Agency();
        $this->modelData->loadFromArray($data);

        return $this->modelData;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConstraints()
    {
        return new Collection([
            'organizationId' => new Required([
                new NotBlank([
                    'message' => 'The organization id is missing.'
                ]),
                new Type([
                    'type' => 'numeric',
                    'message' => 'Organization ID must be a number.'
                ]),
                new Callback($this->organizationExists())
            ]),
            'name' => new Required([
                new NotBlank([
                    'message' => 'The name is missing.'
                ]),
                new Callback($this->agencyExists())
            ]),
            'datasiftUsername' => new Required([
                new NotBlank([
                    'message' => 'The DataSift username is missing.'
                ])
            ]),
            'datasiftApikey' => new Required([
                new NotBlank([
                    'message' => 'The DataSift API key is missing.'
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
     * Validates if an agency with the given name already exists
     *
     * @return callable
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function agencyExists()
    {
        return function ($object, ExecutionContextInterface $context) {
            $agency = $this->agencyRepository->findOne([
                'name' => $this->inputData['name'],
                'organization_id' => $this->inputData['organizationId']
            ]);

            if ($agency) {
                $context
                    ->buildViolation(sprintf(
                        'An agency with the name "%s" already exists in the system.',
                        $this->inputData['name']
                    ))
                    ->addViolation();
            }
        };
    }
}
