<?php

namespace Tornado\Project\Recording\Form;

use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use DataSift\Form\Form;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\DataObjectInterface;
use Tornado\Project\Recording;

/**
 * Create
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Project\Recording
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Create extends Form
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     */
    public function __construct(
        ValidatorInterface $validator
    ) {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function submit(array $data, DataObjectInterface $object = null)
    {
        $this->inputData = $data;
        $this->normalizedData = $data;
        $this->modelData = $object;

        $this->errors = $this->validator->validate($data, $this->getConstraints());
        $this->submitted = true;
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
    public function getData()
    {
        if (!$this->isSubmitted() || !$this->isValid()) {
            return null;
        }

        $this->modelData = new Recording();
        $this->modelData->setBrandId($this->inputData['brand_id']);
        $this->modelData->setName($this->inputData['name']);
        $this->modelData->setCsdl($this->inputData['csdl']);
        $this->modelData->setCreatedAt(time());

        if (isset($this->inputData['vqb_generated'])) {
            $this->modelData->setVqbGenerated($this->inputData['vqb_generated']);
        }

        if (isset($this->inputData['hash'])) {
            $this->modelData->setDatasiftRecordingId($this->inputData['hash']);
            $this->modelData->setHash($this->inputData['hash']);
        }

        return $this->modelData;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConstraints()
    {
        return new Collection([
            'name' => new Required([
                new NotBlank(),
                new Type([
                    'type' => 'string',
                    'message' => 'Recording Name must be a string.'
                ])
            ]),
            'hash' => new Optional([
                new NotBlank(),
                new Type(['type' => 'string'])
            ]),
            'csdl' => new Required([
                new NotBlank([
                    'message' => 'The CSDL query is missing.'
                ]),
                new Type([
                    'type' => 'string'
                ])
            ]),
            'brand_id' => new Required([
                new NotBlank([
                    'message' => 'The Brand ID is missing.'
                ]),
                new Type([
                    'type' => 'numeric',
                    'message' => 'Brand ID must be a number.'
                ])
            ]),
            'vqb_generated' => new Optional([
                new Type([
                    'type' => 'bool'
                ])
            ])
        ]);
    }
}
