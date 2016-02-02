<?php

namespace Tornado\Project\Chart\Form;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;

use DataSift\Form\Form as BaseForm;

use Tornado\DataMapper\DataObjectInterface;

use Tornado\Project\Chart;

/**
 * Chart Update form.
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
class Update extends BaseForm
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * Constructor.
     *
     * @param ValidatorInterface  $validator    Validator.
     */
    public function __construct(
        ValidatorInterface $validator
    ) {
        $this->validator = $validator;
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
        if (!$this->modelData || !$this->isSubmitted() || !$this->isValid()) {
            return $this->modelData;
        }

        $this->modelData->setName($this->normalizedData['name']);

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
     * {@inheritdoc}
     */
    public function submit(array $data, DataObjectInterface $chart = null)
    {
        if (!$chart || !($chart instanceof Chart) || !$chart->getId()) {
            throw new \InvalidArgumentException(sprintf(
                '%s expects persisted Chart as the 2nd argument.',
                __METHOD__
            ));
        }

        $this->inputData = $data;
        $this->normalizedData = $data;
        $this->modelData = $chart;

        $this->errors = $this->validator->validate(
            $data,
            $this->getConstraints()
        );
        $this->submitted = true;
    }

    /**
     * Defines the Chart constraints.
     *
     * @return \Symfony\Component\Validator\Constraints\Collection
     */
    protected function getConstraints()
    {
        return new Collection([
            'name' => new Required([
                new NotBlank(),
                new Type([
                    'type' => 'string',
                    'message' => 'Chart name must be a string.'
                ])
            ])
        ]);
    }
}
