<?php

namespace Tornado\Project\Worksheet\Form;

use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\GreaterThan;

use Tornado\Analyze\Analysis;
use Tornado\Project\Worksheet;
use Tornado\Project\Chart;
use Tornado\Project\Recording;

/**
 * Worksheet Explore Form
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
class Explore extends Create
{

    /**
     * Formats this form inputData into modelData
     *
     * {@inheritdoc}
     *
     * @return array|null
     */
    public function getData()
    {
        if (!$this->isSubmitted() || !$this->isValid()) {
            return null;
        }

        return $this->normalizedData;
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeData(array $inputData)
    {
        // set baseline comparison if a baseline dataset id is set and no comparison mode sent
        $data = parent::normalizeData($inputData);

        return [
            'name' => (isset($data['name'])) ? $data['name'] : null,
            'chart_type' => (isset($data['chart_type'])) ? $data['chart_type'] : null,
            'type' => (isset($data['type'])) ? $data['type'] : null,
            'explore' => (isset($data['explore'])) ? $data['explore'] : null,
            'start' => (isset($data['start'])) ? $data['start'] : null,
            'end' => (isset($data['end'])) ? $data['end'] : null
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getConstraints(Recording $recording = null)
    {
        $targetFilter = [];
        $typeChoices = [Analysis::TYPE_TIME_SERIES, Analysis::TYPE_FREQUENCY_DISTRIBUTION];
        if (isset($this->inputData['chart_type']) && $this->inputData['chart_type'] == Chart::TYPE_TIME_SERIES) {
            $targetFilter = ['is_time' => true];
            $typeChoices = [Analysis::TYPE_TIME_SERIES];
        }

        $constraints = [
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
                ]),
                new Callback($this->worksheetExists())
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
            'explore' => new Required([
                new NotBlank(),
                new Type([
                    'type' => 'array',
                    'message' => 'Explore must be an object.'
                ]),
                new Collection([
                    'fields' => $this->getExploreFields($recording, $this->targetPermissions),
                    'allowMissingFields' => true
                ])
            ]),
            'start' => new Optional([]),
            'end' => new Optional([])
        ];

        if (isset($this->inputData['start']) && $this->inputData['start']) {
            $constraints['start'] = new Optional([
                new NotBlank(),
                new Type([
                    'type' => 'numeric',
                    'message' => 'Analysis start time must represent a UNIX timestamp.'
                ]),
                new LessThan([
                    'value' => isset($this->inputData['end']) ? $this->inputData['end'] : 99999999
                ])
            ]);
        }

        if (isset($this->inputData['end']) && $this->inputData['end']) {
            $constraints['end'] = new Required([
                new NotBlank(),
                new Type([
                    'type' => 'numeric',
                    'message' => 'Analysis end time must represent a UNIX timestamp.'
                ]),
                new GreaterThan([
                    'value' => isset($this->inputData['start']) ? $this->inputData['start'] : 0
                ])
            ]);
        }

        return new Collection($constraints);
    }

    /**
     * Gets a list of acceptable targets for explore
     *
     * @param \DataSift\Project\Recording
     *
     * @return array
     */
    private function getExploreFields(Recording $recording = null)
    {
        $fields = [];

        foreach ($this->schemaProvider->getSchema($recording)->getTargets([], $this->targetPermissions) as $target) {
            $fields[$target] = new NotBlank();
        }

        return $fields;
    }
}
