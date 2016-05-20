<?php

namespace Tornado\Project\Workbook\Form;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\DataObjectInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Choice;

use Tornado\Project\Workbook\Form;
use Tornado\Project\Workbook;

use Tornado\Analyze\TemplatedAnalyzer;

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
     * The templated analyzer for this form
     *
     * @var \Tornado\Analyze\TemplatedAnalyzer
     */
    private $templatedAnalyzer;

    /**
     * {@inheritdoc}
     * @param \Tornado\Analyze\TemplatedAnalyzer $templatedAnalyzer
     */
    public function __construct(
        ValidatorInterface $validator,
        DataMapperInterface $workbookRepo,
        TemplatedAnalyzer $templatedAnalyzer
    ) {
        parent::__construct($validator, $workbookRepo);
        $this->templatedAnalyzer = $templatedAnalyzer;
    }

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

    /**
     * {@inheritdoc}
     */
    protected function getConstraints()
    {
        $constraints = parent::getConstraints();

        $constraints->fields['template'] = new Required([
            new Type(['type' => 'string']),
            new Choice([
                'choices' => array_merge([''], array_keys($this->templatedAnalyzer->getTemplates())),
                'message' => 'Invalid template selected'
            ])
        ]);

        return $constraints;
    }
}
