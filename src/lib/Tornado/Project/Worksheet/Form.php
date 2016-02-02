<?php

namespace Tornado\Project\Worksheet;

use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotEqualTo;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use DataSift\Pylon\Schema\Provider;
use DataSift\Pylon\Regions;
use DataSift\Form\Form as BaseForm;

use Tornado\Analyze\Analysis;
use Tornado\Analyze\Dimension;
use Tornado\Analyze\Dimension\Factory;
use Tornado\DataMapper\DataMapperInterface;
use Tornado\Project\Chart;
use Tornado\Project\Chart\Generator as ChartGenerator;
use Tornado\Project\Recording;
use Tornado\Project\Worksheet;

/**
 * Worksheet Create Form
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Analyze
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @SuppressWarnings(PHPMD)
 */
abstract class Form extends BaseForm
{
    /**
     * @var \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    protected $validator;

    /**
     * @var \DataSift\Pylon\Schema\Provider
     */
    protected $schemaProvider;

    /**
     * @var \Tornado\Analyze\Dimension\Factory
     */
    protected $dimensionFactory;

    /**
     * @var \Tornado\Project\Worksheet\FilterCsdlGenerator
     */
    protected $filterCsdlGenerator;

    /**
     * @var \DataSift\Pylon\Regions
     */
    protected $regions;

    /**
     * @var \Tornado\DataMapper\DataMapperInterface
     */
    protected $workbookRepo;

    /**
     * @var \Tornado\DataMapper\DataMapperInterface
     */
    protected $worksheetRepo;

    /**
     * An array of target permissions
     *
     * @var array
     */
    protected $targetPermissions = [];

    /**
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \DataSift\Pylon\Schema\Provider                           $schemaProvider
     * @param \Tornado\Analyze\Dimension\Factory                        $dimensionFactory
     * @param \Tornado\Project\Worksheet\FilterCsdlGenerator            $filterCsdlGenerator
     * @param \DataSift\Pylon\Regions                                   $regions
     * @param \Tornado\DataMapper\DataMapperInterface                   $workbookRepo
     * @param \Tornado\DataMapper\DataMapperInterface                   $worksheetRepo
     */
    public function __construct(
        ValidatorInterface $validator,
        Provider $schemaProvider,
        Factory $dimensionFactory,
        FilterCsdlGenerator $filterCsdlGenerator,
        Regions $regions,
        DataMapperInterface $workbookRepo,
        DataMapperInterface $worksheetRepo,
        array $targetPermissions = []
    ) {
        $this->validator = $validator;
        $this->schemaProvider = $schemaProvider;
        $this->dimensionFactory = $dimensionFactory;
        $this->filterCsdlGenerator = $filterCsdlGenerator;
        $this->regions = $regions;
        $this->workbookRepo = $workbookRepo;
        $this->worksheetRepo = $worksheetRepo;
        $this->targetPermissions = $targetPermissions;
    }

    /**
     * Formats this form inputData into modelData
     *
     * {@inheritdoc}
     *
     * @return \Tornado\Project\Worksheet|null
     */
    public function getData(Recording $recording = null)
    {
        if (!$this->modelData || !$this->isSubmitted() || !$this->isValid()) {
            return $this->modelData;
        }

        // required data
        $this->modelData->setChartType($this->normalizedData['chart_type']);
        $this->modelData->setAnalysisType($this->normalizedData['type']);

        // optional data

        if (isset($this->normalizedData['dimensions'])) {
            $this->modelData->setDimensions($this->dimensionFactory->getDimensionCollection(
                $this->normalizedData['dimensions'],
                $recording,
                $this->targetPermissions
            ));
        }

        $this->modelData->setSecondaryRecordingId($this->normalizedData['secondary_recording_id']);
        $this->modelData->setBaselineDataSetId($this->normalizedData['baseline_dataset_id']);
        $this->modelData->setComparison($this->normalizedData['comparison']);
        $this->modelData->setMeasurement($this->normalizedData['measurement']);

        // augment the passed filters with a CSDL code generated from them
        $filters = $this->normalizedData['filters'];
        $filters['generated_csdl'] = $this->filterCsdlGenerator->generate($filters);
        $this->modelData->setFilters($filters);

        $secondaryRecordingFilters = $this->normalizedData['secondary_recording_filters'];
        $secondaryRecordingFilters['generated_csdl'] = $this->filterCsdlGenerator->generate($secondaryRecordingFilters);
        $this->modelData->setSecondaryRecordingFilters($secondaryRecordingFilters);

        $this->modelData->setSpan($this->normalizedData['span']);
        $this->modelData->setInterval($this->normalizedData['interval']);
        $this->modelData->setStart($this->normalizedData['start']);
        $this->modelData->setEnd($this->normalizedData['end']);

        return $this->modelData;
    }

    /**
     * {@inheritdoc}
     */
    public function getFields(Recording $recording = null, $targetPermissions = [])
    {
        return array_keys($this->getConstraints($recording)->fields);
    }

    /**
     * Normalizes the optional input data
     *
     * @param array $inputData
     *
     * @return array
     */
    protected function normalizeData(array $inputData)
    {
        // set baseline comparison if a baseline dataset id is set and no comparison mode sent
        if (isset($inputData['baseline_dataset_id'])
            && $inputData['baseline_dataset_id']
            && !isset($inputData['comparison'])
        ) {
            $inputData['comparison'] = ChartGenerator::MODE_BASELINE;
        }

        $map = [
            'secondary_recording_id' => null,
            'secondary_recording_filters' => null,
            'baseline_dataset_id' => null,
            'comparison' => ChartGenerator::MODE_COMPARE,
            'measurement' => ChartGenerator::MEASURE_UNIQUE_AUTHORS,
            'filters' => null,
            'span' => null,
            'interval' => null,
            'start' => null,
            'end' => null,
            'chart_type' => null,
            'type' => null
        ];

        foreach ($map as $key => $defaultVal) {
            if (!isset($inputData[$key])) {
                $inputData[$key] = $defaultVal;
            }
        }

        if (isset($inputData['chart_type']) && $inputData['chart_type'] == Chart::TYPE_TIME_SERIES) {
            $inputData['dimensions'] = [['target' => Dimension::TIME]];
        }

        return $inputData;
    }

    /**
     * Defines this Worksheet Create constraints
     *
     * @param Recording $recording
     *
     * @return \Symfony\Component\Validator\Constraints\Collection
     */
    protected function getConstraints(Recording $recording = null)
    {
        $schema = $this->schemaProvider->getSchema($recording);

        $secondaryRecordingIdConstraints = [
            new NotBlank(),
            new Type([
                'type' => 'numeric',
                'message' => 'Secondary Recording ID must be a number.'
            ])
        ];

        // get parent workbook to fetch some constraints from it
        $workbookId = null;
        if (isset($this->inputData['workbook_id'])) {
            $workbookId = $this->inputData['workbook_id'];
        } elseif (isset($this->modelData)) {
            $workbookId = $this->modelData->getWorkbookId();
        }

        if ($workbookId) {
            $workbook = $this->workbookRepo->findOne(['id' => $workbookId]);

            if (!$workbook) {
                throw new \RuntimeException(sprintf('Could not find parent workbook with ID %s', $workbookId));
            }
        }

        $comparisonChoices = [ChartGenerator::MODE_COMPARE, ChartGenerator::MODE_BASELINE];
        $comparisonMessage = sprintf(
            'Comparison must be one of the following value: %s, %s.',
            ChartGenerator::MODE_COMPARE,
            ChartGenerator::MODE_BASELINE
        );

        if (isset($this->inputData['baseline_dataset_id']) && $this->inputData['baseline_dataset_id']) {
            $comparisonChoices = [ChartGenerator::MODE_BASELINE];
            $comparisonMessage = sprintf(
                'Comparison against a baseline dataset must be %s only',
                ChartGenerator::MODE_BASELINE
            );
        }

        $targetFilter = [];
        $typeChoices = [Analysis::TYPE_TIME_SERIES, Analysis::TYPE_FREQUENCY_DISTRIBUTION];
        if (isset($this->inputData['chart_type']) && $this->inputData['chart_type'] == Chart::TYPE_TIME_SERIES) {
            $targetFilter = ['is_time' => true];
            $typeChoices = [Analysis::TYPE_TIME_SERIES];
            $this->inputData['dimensions'] = [['target' => Dimension::TIME]];
        }

        return new Collection([
            'workbook_id' => new Required([
                new NotBlank(),
                new Type([
                    'type' => 'numeric',
                    'message' => 'Workbook ID must be a number.'
                ])
            ]),
            'name' => new Required([
                new NotBlank(),
                new Type([
                    'type' => 'string',
                    'message' => 'Worksheet Name must be a string.'
                ])
            ]),
            'worksheet_id' => new Required([
                new NotBlank(),
                new Type([
                    'type' => 'numeric',
                    'message' => 'Worksheet ID must be a number.'
                ]),
            ]),
            'dimensions' => new Required([
                new Count([
                    'min' => 1,
                    'max' => 3,
                    'minMessage' => 'You must specify at least one dimension.',
                    'maxMessage' => 'You cannot specify more than {{ limit }} dimensions.',
                ]),
                new All([
                    'constraints' => [
                        new Collection([
                            'allowExtraFields' => true,
                            'fields' => [
                                'target' => new Required([
                                    new Type(['type' => 'string']),
                                    new Choice([
                                        'choices' => $schema->getTargets($targetFilter, $this->targetPermissions),
                                        'message' => 'Invalid target given.'
                                    ])
                                ]),
                                'threshold' => new Optional([
                                    new Type(['type' => 'numeric'])
                                ]),
                            ]
                        ])
                    ]
                ])
            ]),
            'chart_type' => new Required([
                new NotBlank(),
                new Choice([
                    'choices' => [Chart::TYPE_TORNADO, Chart::TYPE_HISTOGRAM, Chart::TYPE_TIME_SERIES],
                    'message' => 'Invalid Chart type given. Available types: tornado, histogram or timeSeries'
                ])
            ]),
            'type' => new Required([
                new NotBlank(),
                new Choice([
                    'choices' => $typeChoices,
                    'message' =>
                        'Type must be one of the following values: '
                        . implode(', ', $typeChoices)
                ])
            ]),
            'comparison' => new Optional([
                new NotBlank(),
                new Choice([
                    'choices' => $comparisonChoices,
                    'message' => $comparisonMessage
                ])
            ]),
            'measurement' => new Optional([
                new NotBlank(),
                new Choice([
                    'choices' => [ChartGenerator::MEASURE_INTERACTIONS, ChartGenerator::MEASURE_UNIQUE_AUTHORS],
                    'message' => sprintf(
                        'Measurement must be one of the following value: %s, %s.',
                        ChartGenerator::MEASURE_INTERACTIONS,
                        ChartGenerator::MEASURE_UNIQUE_AUTHORS
                    )
                ])
            ]),
            'secondary_recording_id' => new Optional($secondaryRecordingIdConstraints),
            'secondary_recording_filters' => new Optional([
                new Collection([
                    'allowExtraFields' => true,
                    'fields' => [
                        'keywords' => new Optional([
                            new All([
                                new Type(['type' => 'string'])
                            ])
                        ]),
                        'links' => new Optional([
                            new All([
                                new Type(['type' => 'string'])
                            ])
                        ]),
                        'country' => new Optional([
                            new All([
                                new Type(['type' => 'string']),
                                new Choice([
                                    'choices' => $this->regions->getCountries(),
                                    'message' => 'Invalid country given'
                                ])
                            ])
                        ]),
                        'region' => new Optional([
                            new All([
                                new Type(['type' => 'string']),
                                new Choice([
                                    'choices' => $this->regions->getRegions(),
                                    'message' => 'Invalid region given'
                                ])
                            ])
                        ]),
                        'gender' => new Optional([
                            new All([
                                new Type(['type' => 'string'])
                            ])
                        ]),
                        'age' => new Optional([
                            new All([
                                new Type(['type' => 'string'])
                            ])
                        ]),
                        'csdl' => new Optional([
                            new Type([
                                'type' => 'string'
                            ])
                        ]),
                        'start' => new Optional([
                            new Type([
                                'type' => 'numeric'
                            ])
                        ]),
                        'end' => new Optional([
                            new Type([
                                'type' => 'numeric'
                            ])
                        ])
                    ]
                ])
            ]),
            'baseline_dataset_id' => new Optional([
                new NotBlank(),
                new Type([
                    'type' => 'numeric',
                    'message' => 'Baseline DataSet ID must be a number.'
                ])
            ]),
            'filters' => new Optional([
                new Collection([
                    'allowExtraFields' => true,
                    'fields' => [
                        'keywords' => new Optional([
                            new All([
                                new Type(['type' => 'string'])
                            ])
                        ]),
                        'links' => new Optional([
                            new All([
                                new Type(['type' => 'string'])
                            ])
                        ]),
                        'country' => new Optional([
                            new All([
                                new Type(['type' => 'string']),
                                new Choice([
                                    'choices' => $this->regions->getCountries(),
                                    'message' => 'Invalid country given'
                                ])
                            ])
                        ]),
                        'region' => new Optional([
                            new All([
                                new Type(['type' => 'string']),
                                new Choice([
                                    'choices' => $this->regions->getRegions(),
                                    'message' => 'Invalid region given'
                                ])
                            ])
                        ]),
                        'gender' => new Optional([
                            new All([
                                new Type(['type' => 'string'])
                            ])
                        ]),
                        'age' => new Optional([
                            new All([
                                new Type(['type' => 'string'])
                            ])
                        ]),
                        'csdl' => new Optional([
                            new Type([
                                'type' => 'string'
                            ])
                        ])
                    ]
                ])
            ]),
            'span' => new Optional([
                new NotBlank(),
                new Type([
                    'type' => 'numeric'
                ]),
                new GreaterThan([
                    'value' => 0
                ])
            ]),
            'interval' => new Optional([
                new NotBlank(),
                new Type([
                    'type' => 'string'
                ]),
                new Choice([
                    'choices' => ['minute', 'hour', 'day', 'week'],
                    'message' => 'Interval must be one of "minute", "hour", "day" or "week".'
                ])
            ]),
            'start' => new Optional([
                new NotBlank(),
                new Type([
                    'type' => 'numeric',
                    'message' => 'Analysis start time must represent a UNIX timestamp.'
                ]),
                new LessThan([
                    'value' => isset($this->inputData['end']) ? $this->inputData['end'] : time()
                ])
            ]),
            'end' => new Optional([
                new NotBlank(),
                new Type([
                    'type' => 'numeric',
                    'message' => 'Analysis end time must represent a UNIX timestamp.'
                ]),
                new GreaterThan([
                    'value' => isset($this->inputData['start']) ? $this->inputData['start'] : 0
                ]),
                new LessThan([
                    'value' => time()
                ])
            ]),
        ]);
    }

    /**
     * Validates if worksheet with given name already exists for the parent workbook
     *
     * @return callable
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function worksheetExists()
    {
        return function ($object, ExecutionContextInterface $context) {
            if (!isset($this->inputData['workbook_id']) || !isset($this->inputData['name'])) {
                return false;
            }

            $worksheet = $this->worksheetRepo->findOne([
                'name' => $this->inputData['name'],
                'workbook_id' => $this->inputData['workbook_id']
            ]);

            if ($worksheet && (!isset($this->modelData) || $worksheet->getId() !== $this->modelData->getId())) {
                $context
                    ->buildViolation('Title already in use.')
                    ->addViolation();
            }
        };
    }
}
