<?php

namespace Tornado\Organization\Brand\Form;

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
use Tornado\Organization\Brand;

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
     * @var DataMapperInterface
     */
    protected $brandRepository;

    /**
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \Tornado\DataMapper\DataMapperInterface                   $agencyRepository
     * @param \Tornado\DataMapper\DataMapperInterface                   $brandRepository
     */
    public function __construct(
        ValidatorInterface $validator,
        DataMapperInterface $agencyRepository,
        DataMapperInterface $brandRepository
    ) {
        $this->validator = $validator;
        $this->agencyRepository = $agencyRepository;
        $this->brandRepository = $brandRepository;
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
            'datasiftIdentityId' => '',
            'datasiftApikey' => '',
            'agencyId' => null,
        ];

        $inputData = ($this->inputData) ? $this->inputData : [];
        $data = array_merge($data, $inputData);

        $this->modelData = new Brand();
        $this->modelData->loadFromArray($data);

        return $this->modelData;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConstraints()
    {
        return new Collection([
            'agencyId' => new Required([
                new NotBlank([
                    'message' => 'The agency id is missing.'
                ]),
                new Type([
                    'type' => 'numeric',
                    'message' => 'Agency ID must be a number.'
                ]),
                new Callback($this->agencyExists())
            ]),
            'name' => new Required([
                new NotBlank([
                    'message' => 'The name is missing.'
                ]),
                new Callback($this->brandExists())
            ]),
            'datasiftUsername' => new Required([
                new Optional()
            ]),
            'datasiftIdentityId' => new Required([
                new NotBlank([
                    'message' => 'The DataSift Identity ID is missing.'
                ])
            ]),
            'datasiftApikey' => new Required([
                new NotBlank([
                    'message' => 'The Identity API key is missing.'
                ])
            ])
        ]);
    }

    /**
     * Validates if the Agency of given ID exists
     *
     * @return callable
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function agencyExists()
    {
        return function ($object, ExecutionContextInterface $context) {
            $agency = $this->agencyRepository->findOne(
                ['id' => $this->inputData['agencyId']]
            );

            if (!$agency) {
                $context
                    ->buildViolation(sprintf(
                        'Agency with id "%d" does not exist in the system.',
                        $this->inputData['agencyId']
                    ))
                    ->addViolation();
            }
        };
    }

    /**
     * Validates if a brand with the given name already exists
     *
     * @return callable
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function brandExists()
    {
        return function ($object, ExecutionContextInterface $context) {
            $brand = $this->brandRepository->findOne([
                'name' => $this->inputData['name'],
                'agency_id' => $this->inputData['agencyId']
            ]);

            if ($brand) {
                $context
                    ->buildViolation(sprintf(
                        'A brand with the name "%s" already exists in the system.',
                        $this->inputData['name']
                    ))
                    ->addViolation();
            }
        };
    }

    /**
     * Validates if a brand with the given identity id already exists
     *
     * @return callable
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function identityExists()
    {
        return function ($object, ExecutionContextInterface $context) {
            $brand = $this->brandRepository->findOne([
                'datasift_identity_id' => $this->inputData['datasiftIdentityId']
            ]);

            if ($brand) {
                $context
                    ->buildViolation(sprintf(
                        'A brand with the identity id "%s" already exists in the system.',
                        $this->inputData['datasiftIdentityId']
                    ))
                    ->addViolation();
            }
        };
    }
}
