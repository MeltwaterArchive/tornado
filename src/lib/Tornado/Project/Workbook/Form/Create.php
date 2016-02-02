<?php

namespace Tornado\Project\Workbook\Form;

use Tornado\DataMapper\DataObjectInterface;

use Tornado\Project\Workbook\Form;
use Tornado\Project\Workbook;

/**
 * Workbook Create form.
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
class Create extends Form
{
    /**
     * {@inheritdoc}
     */
    public function submit(array $data, DataObjectInterface $workbook = null)
    {
        $this->inputData = $data;
        $this->normalizedData = $data;

        $this->errors = $this->validator->validate($data, $this->getConstraints());
        $this->submitted = true;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Tornado\Project\Workbook|null
     */
    public function getData()
    {
        if (!$this->isSubmitted() || !$this->isValid()) {
            return null;
        }

        $this->modelData = new Workbook();

        $this->modelData->setProjectId($this->normalizedData['project_id']);

        return parent::getData();
    }
}
