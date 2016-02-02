<?php

namespace Tornado\Project\Worksheet\Form;

use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

use Tornado\DataMapper\DataObjectInterface;
use Tornado\Project\Worksheet;
use Tornado\Project\Worksheet\Form;
use Tornado\Project\Recording;

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

        // no, we cannot use validation groups as they don't apply
        // when you pass specific constraints to the validator...
        unset($constraints->fields['worksheet_id']);
        unset($constraints->fields['dimensions']);
        unset($constraints->fields['name']);

        $constraints->fields['name'] = new Required([
            new NotBlank(),
            new Type([
                'type' => 'string',
                'message' => 'Worksheet Name must be a string.'
            ]),
            new Callback($this->worksheetExists())
        ]);

        return $constraints;
    }
}
