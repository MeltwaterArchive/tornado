<?php

namespace Tornado\Analyze\Analysis\Form;

use Tornado\DataMapper\DataObjectInterface;
use Tornado\Project\Worksheet;
use Tornado\Project\Worksheet\Form;
use Tornado\Project\Recording;

/**
 * Create Form to validate data for Analyzer
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
 */
class Create extends Form
{
    /**
     * @var Recording
     */
    protected $recording;

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
        $this->modelData = $worksheet;
        $this->recording = $recording;
        $this->targetPermissions = $targetPermissions;
        $this->errors = $this->validator->validate($data, $this->getConstraints($recording));
        $this->submitted = true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConstraints(Recording $recording = null)
    {
        $constraints = parent::getConstraints($recording);

        // no, we cannot use validation groups as they don't apply
        // when you pass specific constraints to the validator...
        unset($constraints->fields['workbook_id']);
        unset($constraints->fields['name']);

        return $constraints;
    }
}
