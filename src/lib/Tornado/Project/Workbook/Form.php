<?php

namespace Tornado\Project\Workbook;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use DataSift\Form\Form as BaseForm;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\Project\Workbook;

/**
 * Workbook form.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Project
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
abstract class Form extends BaseForm
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var \Tornado\DataMapper\DataMapperInterface
     */
    protected $workbookRepo;

    /**
     * Constructor.
     *
     * @param ValidatorInterface  $validator    Validator.
     * @param DataMapperInterface $workbookRepo Workbook repository.
     */
    public function __construct(
        ValidatorInterface $validator,
        DataMapperInterface $workbookRepo
    ) {
        $this->validator = $validator;
        $this->workbookRepo = $workbookRepo;
    }

    /**
     * Formats this form inputData into modelData
     *
     * {@inheritdoc}
     *
     * @return \Tornado\Project\Workbook|null
     */
    public function getData()
    {
        if (!$this->modelData || !$this->isSubmitted() || !$this->isValid()) {
            return $this->modelData;
        }

        $this->modelData->setName($this->normalizedData['name']);
        $this->modelData->setRecordingId($this->normalizedData['recording_id']);

        return $this->modelData;
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        return array_keys($this->getConstraints()->fields);
    }

    /**
     * Defines the Workbook constraints.
     *
     * @return \Symfony\Component\Validator\Constraints\Collection
     */
    protected function getConstraints()
    {
        return new Collection([
            'project_id' => new Required([
                new NotBlank(),
                new Type([
                    'type' => 'numeric',
                    'message' => 'Project ID must be a number.'
                ])
            ]),
            'name' => new Required([
                new NotBlank(),
                new Type([
                    'type' => 'string',
                    'message' => 'Workbook name must be a string.'
                ]),
                new Callback($this->workbookExists())
            ]),
            'recording_id' => new Required([
                new NotBlank(),
                new Type([
                    'type' => 'numeric',
                    'message' => 'Recording ID must be a number.'
                ]),
            ])
        ]);
    }

    /**
     * Validates if workbook with given name exists for given project.
     *
     * @return callable
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function workbookExists()
    {
        return function ($object, ExecutionContextInterface $context) {
            if (!isset($this->inputData['project_id']) || !isset($this->inputData['name'])) {
                return false;
            }

            $workbook = $this->workbookRepo->findOne([
                'project_id' => $this->inputData['project_id'],
                'name' => $this->inputData['name']
            ]);

            if ($workbook && (!isset($this->modelData) || $workbook->getId() !== $this->modelData->getId())) {
                $context
                    ->buildViolation('This workbook name is already in use')
                    ->addViolation();
            }
        };
    }
}
