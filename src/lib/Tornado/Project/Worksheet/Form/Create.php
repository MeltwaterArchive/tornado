<?php

namespace Tornado\Project\Worksheet\Form;

use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\IsNull;
use Symfony\Component\Validator\Constraints\Null;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Tornado\DataMapper\DataObjectInterface;
use Tornado\Project\Worksheet;
use Tornado\Project\Worksheet\Form;
use Tornado\Project\Recording;
use Tornado\Analyze\Analysis;
use Tornado\Project\Chart;

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
class Create extends Form
{
    /**
     * {@inheritdoc}
     */
    public function submit(
        array $data,
        DataObjectInterface $worksheet = null,
        Recording $recording = null,
        array $targetPermissions = []
    ) {
        $this->inputData = $data;
        $this->normalizedData = $this->normalizeData($data);

        $this->errors = $this->validator->validate($data, $this->getConstraints($recording));
        $this->submitted = true;
        $this->targetPermissions = $targetPermissions;
    }

    /**
     * Formats this form inputData into modelData
     *
     * {@inheritdoc}
     *
     * @return \Tornado\Project\Worksheet|null
     */
    public function getData()
    {
        if (!$this->isSubmitted() || !$this->isValid()) {
            return null;
        }

        if (!$this->modelData) {
            $this->modelData = new Worksheet();
        }

        $this->modelData->setWorkbookId($this->normalizedData['workbook_id']);
        $this->modelData->setName($this->normalizedData['name']);

        return parent::getData();
    }

    /**
     * {@inheritdoc}
     */
    protected function getConstraints(Recording $recording = null)
    {
        $constraints = parent::getConstraints($recording);
        $schema = $this->schemaProvider->getSchema($recording);
        $targetFilter = [];

        if (isset($this->inputData['chart_type']) && $this->inputData['chart_type'] == Chart::TYPE_TIME_SERIES) {
            $targetFilter = ['is_time' => true];
        }

        // no, we cannot use validation groups as they don't apply
        // when you pass specific constraints to the validator...
        unset($constraints->fields['worksheet_id']);
        unset($constraints->fields['dimensions']);
        unset($constraints->fields['name']);

        // redifine dimensions as optional
        // I'm not sure about redefining the validation on the dimensions field
        $constraints->fields['dimensions'] = new Optional(new Count([
            'min' => 1,
            'max' => 3,
            'minMessage' => 'You must specify at least one dimension.',
            'maxMessage' => 'You cannot specify more than {{ limit }} dimensions.',
        ]), new All([
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
        ]));

        $constraints->fields['name'] = new Required([
            new NotBlank(),
            new Type([
                'type' => 'string',
                'message' => 'Worksheet Name must be a string.'
            ]),
            new Callback($this->worksheetExists())
        ]);

        if (isset($this->inputData['chart_type']) && $this->inputData['chart_type'] == Chart::TYPE_SAMPLE) {
            unset($constraints->fields['dimensions']);
        }

        return $constraints;
    }
}
