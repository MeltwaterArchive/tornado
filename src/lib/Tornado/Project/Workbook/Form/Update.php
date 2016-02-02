<?php

namespace Tornado\Project\Workbook\Form;

use Tornado\DataMapper\DataObjectInterface;

use Tornado\Project\Workbook\Form;
use Tornado\Project\Workbook;

/**
 * Workbook Update form.
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
class Update extends Form
{
    /**
     * {@inheritdoc}
     */
    public function submit(array $data, DataObjectInterface $workbook = null)
    {
        if (!$workbook || !($workbook instanceof Workbook) || !$workbook->getId()) {
            throw new \InvalidArgumentException(sprintf(
                '%s expects persisted Workbook as the 2nd argument.',
                __METHOD__
            ));
        }

        // project id is not editable, but is required for unique name check
        $data['project_id'] = $workbook->getProjectId();

        $this->inputData = $data;
        $this->normalizedData = $data;
        $this->modelData = $workbook;

        $this->errors = $this->validator->validate(
            $data,
            $this->getConstraints()
        );
        $this->submitted = true;
    }
}
