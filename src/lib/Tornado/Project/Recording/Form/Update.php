<?php

namespace Tornado\Project\Recording\Form;

use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;

use Tornado\DataMapper\DataObjectInterface;

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
 */
class Update extends Create
{
    /**
     * {@inheritdoc}
     */
    public function submit(array $data, DataObjectInterface $object = null)
    {
        if (!$object || !$object->getId()) {
            throw new \LogicException(sprintf(
                '%s expects persisted DataObject as the 2nd argument.',
                __METHOD__
            ));
        }

        parent::submit($data, $object);
    }

    /**
     * {@inheritdoc}
     */
    protected function getConstraints()
    {
        return new Collection([
            'name' => new Optional([
                new NotBlank(),
                new Type([
                    'type' => 'string',
                    'message' => 'Project Name must be a string.'
                ])
            ]),
            'brand_id' => new Optional([
                new NotBlank([
                    'message' => 'The Brand ID is missing.'
                ]),
                new Type([
                    'type' => 'numeric',
                    'message' => 'Brand ID must be a number.'
                ])
            ])
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        if (!$this->isSubmitted() || !$this->isValid()) {
            return null;
        }

        if (isset($this->inputData['brand_id'])) {
            $this->modelData->setBrandId($this->inputData['brand_id']);
        }

        if (isset($this->inputData['name'])) {
            $this->modelData->setName($this->inputData['name']);
        }

        return $this->modelData;
    }
}
