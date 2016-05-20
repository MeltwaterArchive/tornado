<?php

namespace Tornado\Analyze\DataSet\StoredDataSet;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use DataSift\Form\Form as BaseForm;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\DataObjectInterface;
use Tornado\Analyze\DataSet\StoredDataSet;
use Tornado\Analyze\Analysis;

use DataSift\Pylon\Schema\Provider as SchemaProvider;
use \Tornado\Analyze\Dimension\Factory as DimensionFactory;

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
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Form extends BaseForm
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var \Tornado\DataMapper\DataMapperInterface
     */
    protected $datasetRepo;

    /**
     * @var \Tornado\DataMapper\DataMapperInterface
     */
    protected $recordingRepo;

    /**
     * @var \DataSift\Pylon\Schema\Provider
     */
    protected $schemaProvider;

    /**
     * @var \Tornado\Analyze\Dimension\Factory
     */
    protected $dimensionFactory;

    /**
     * Constructor.
     *
     * @param ValidatorInterface  $validator     Validator.
     * @param DataMapperInterface $datasetRepo   DataSet repository.
     * @param DataMapperInterface $recordingRepo Recording repository.
     * @param \DataSift\Pylon\Schema\Provider    $schemaProvider
     * @param \Tornado\Analyze\Dimension\Factory $dimensionFactory
     */
    public function __construct(
        ValidatorInterface $validator,
        DataMapperInterface $datasetRepo,
        DataMapperInterface $recordingRepo,
        SchemaProvider $schemaProvider,
        DimensionFactory $dimensionFactory
    ) {
        $this->validator = $validator;
        $this->datasetRepo = $datasetRepo;
        $this->recordingRepo = $recordingRepo;
        $this->schemaProvider = $schemaProvider;
        $this->dimensionFactory = $dimensionFactory;
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
        if (!$this->modelData || !$this->isSubmitted()) {
            return $this->modelData;
        }

        $this->modelData->loadFromArray($this->normalizedData);
        if (isset($this->normalizedData['dimensions'])) {
            $dimensions = $this->normalizedData['dimensions'];
            $dimensions = array_filter($dimensions);
            $dimensions = array_map(
                function ($item) {
                    return ['target' => $item];
                },
                $dimensions
            );
            $this->modelData->setDimensions($this->dimensionFactory->getDimensionCollection(
                $dimensions
            ));
        }

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
        $schema = $this->schemaProvider->getSchema();

        return new Collection([
            'name' => new Required([
                new NotBlank(),
                new Type([
                    'type' => 'string',
                    'message' => 'DataSet name must be a string.'
                ]),
                new Callback($this->datasetExists())
            ]),
            'recordingId' => new Required([
                new NotBlank(),
                new Type([
                    'type' => 'numeric',
                    'message' => 'Recording ID must be a number.'
                ]),
            ]),
            'filter' => new Required([]),
            'dimensions' => new Collection([
                new Required([
                    new NotBlank(),
                    new Choice([
                        'choices' => $schema->getTargets(['is_analysable' => true]),
                        'message' => 'Invalid target given.'
                    ])
                ]),
                new Required([
                    new Choice([
                        'choices' => array_merge([''], $schema->getTargets(['is_analysable' => true])),
                        'message' => 'Invalid target given.'
                    ])
                ]),
                new Required([
                    new Choice([
                        'choices' => array_merge([''], $schema->getTargets(['is_analysable' => true])),
                        'message' => 'Invalid target given.'
                    ])
                ])
            ]),
            'visibility' => new Required([
                new Choice([
                    'choices' => [StoredDataSet::VISIBILITY_PRIVATE, StoredDataSet::VISIBILITY_PUBLIC],
                    'message' => 'Invalid visibility'
                ])
            ]),
            'analysisType' => new Required([
                new Choice([
                    'choices' => [Analysis::TYPE_TIME_SERIES, Analysis::TYPE_FREQUENCY_DISTRIBUTION],
                    'message' => 'Invalid analysis type'
                ])
            ]),
            'schedule' => new Required([
                new NotBlank(),
                new Type([
                    'type' => 'numeric',
                    'message' => 'Please enter a number greater than 0'
                ]),
            ]),
            'scheduleUnits' => new Required([
                new NotBlank(),
                new Choice([
                    'choices' => [
                        StoredDataSet::SCHEDULE_UNITS_DAY,
                        StoredDataSet::SCHEDULE_UNITS_WEEK,
                        StoredDataSet::SCHEDULE_UNITS_MONTH
                    ],
                    'message' => 'Invalid schedule units'
                ])
            ]),
            'timeRange' => new Required([
                new NotBlank(),
                new Choice([
                    'choices' => [
                        StoredDataSet::SCHEDULE_UNITS_DAY,
                        StoredDataSet::SCHEDULE_UNITS_WEEK,
                        StoredDataSet::SCHEDULE_UNITS_FORTNIGHT,
                        StoredDataSet::SCHEDULE_UNITS_MONTH
                    ],
                    'message' => 'Invalid time range units'
                ])
            ]),
        ]);
    }

    /**
     * Validates if workbook with given name exists for given project.
     *
     * @return callable
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function datasetExists()
    {
        return function ($object, ExecutionContextInterface $context) {
            if (!isset($this->inputData['name'])) {
                return false;
            }

            $dataset = $this->datasetRepo->findOne([
                'name' => $this->inputData['name']
            ]);

            if ($dataset && (!isset($this->modelData) || $dataset->getId() !== $this->modelData->getId())) {
                $context
                    ->buildViolation('This DataSet name is already in use')
                    ->addViolation();
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function submit(array $data, DataObjectInterface $dataset = null)
    {
        $this->inputData = $data;
        $this->normalizedData = $data;
        $this->modelData = $dataset;
        $this->errors = $this->validator->validate($data, $this->getConstraints());
        $this->submitted = true;
    }
}
