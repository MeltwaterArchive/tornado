<?php

namespace Tornado\Project\Worksheet\Form;

use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use DataSift\Pylon\Regions;
use DataSift\Pylon\Schema\Provider;

use Tornado\Analyze\Dimension\Factory;
use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\DataObjectInterface;
use Tornado\Project\Worksheet;
use Tornado\Project\Worksheet\Form;
use Tornado\Project\Worksheet\FilterCsdlGenerator;
use Tornado\Project\Recording;

/**
 * Worksheet Update Form
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
class Update extends Form
{
    /**
     * {@inheritdoc}
     */
    public function submit(array $data, DataObjectInterface $worksheet = null, Recording $recording = null)
    {
        if (!$worksheet || !($worksheet instanceof Worksheet) || !$worksheet->getId()) {
            throw new \InvalidArgumentException(sprintf(
                '%s expects persisted Worksheet as the 2nd argument.',
                __METHOD__
            ));
        }

        $this->inputData = $data;
        $this->modelData = $worksheet;
        $this->normalizedData = $data; // nothing to normalize here

        $this->errors = $this->validator->validate(
            $data,
            $this->getConstraints()
        );
        $this->submitted = true;
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

        $this->modelData->setName($this->normalizedData['name']);
        if (isset($this->normalizedData['display_options'])) {
            $this->modelData->setDisplayOptions($this->normalizedData['display_options']);
        }

        return $this->modelData;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConstraints(Recording $recording = null)
    {
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
                ]),
                new Callback($this->worksheetExists())
            ]),
            'display_options' => new Optional()
        ]);
    }
}
