<?php

namespace Tornado\Project\Project\Form;

use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use DataSift\Form\Form;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\DataObjectInterface;
use Tornado\Project\Project;

/**
 * Create Project form
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Project\Project\Form
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Create extends Form
{
    /**
     * @var string
     */
    const CONFLICT_MESSAGE_SUFFIX = 'already in use.';

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var \Tornado\Project\Project\DataMapper
     */
    protected $projectRepo;

    /**
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \Tornado\DataMapper\DataMapperInterface $projectRepo
     */
    public function __construct(
        ValidatorInterface $validator,
        DataMapperInterface $projectRepo
    ) {
        $this->validator = $validator;
        $this->projectRepo = $projectRepo;
    }

    /**
     * {@inheritdoc}
     */
    public function submit(array $data, DataObjectInterface $object = null)
    {
        $this->inputData = $data;
        $this->normalizedData = $data;
        $this->modelData = $object;

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
                $data[$key] = $value;
            }
        }

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

        $this->modelData = new Project();
        $this->modelData->setBrandId($this->inputData['brand_id']);
        $this->modelData->setName($this->inputData['name']);
        $this->modelData->setCreatedAt(time());
        $this->modelData->setFresh(1);

        return $this->modelData;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConstraints()
    {

        return new Collection([
            'brand_id' => new Required([
                new NotBlank([
                    'message' => 'The Brand ID is missing.'
                ]),
                new Type([
                    'type' => 'numeric',
                    'message' => 'Brand ID must be a number.'
                ])
            ]),
            'name' => new Required([
                new NotBlank(),
                new Type([
                    'type' => 'string',
                    'message' => 'Project Name must be a string.'
                ]),
                new Callback($this->projectExists())
            ])
        ]);
    }

    /**
     * Validates if Project with given name exists for given Brand
     *
     * @return callable
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function projectExists()
    {
        return function ($object, ExecutionContextInterface $context) {
            if (!isset($this->inputData['brand_id']) || !isset($this->inputData['name'])) {
                return false;
            }

            $project = $this->projectRepo->findOne([
                'name' => $this->inputData['name'],
                'brand_id' => $this->inputData['brand_id']
            ]);

            if ($project) {
                $context
                    ->buildViolation(sprintf(
                        'Project with name "%s" %s',
                        $this->inputData['name'],
                        static::CONFLICT_MESSAGE_SUFFIX
                    ))
                    ->addViolation();
            }
        };
    }
}
