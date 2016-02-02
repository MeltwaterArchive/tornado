<?php

namespace Tornado\Organization\Agency\Form;

use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use DataSift\Form\Form;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\DataObjectInterface;
use Tornado\Organization\User;
use Tornado\Organization\User\Factory;

/**
 * Update User form
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Organization\User\Form
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Update extends Create
{

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $data = [
            'name' => '',
            'datasiftUsername' => '',
            'datasiftApikey' => '',
            'organizationId' => null
        ];

        $inputData = ($this->inputData) ? $this->inputData : [];
        $data = array_merge($data, $inputData);

        if ($this->modelData) {
            $this->modelData->loadFromArray($data);
        }

        return $this->modelData;
    }

    /**
     * Validates if an agency with the given name already exists
     *
     * @return callable
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function agencyExists()
    {
        return function ($object, ExecutionContextInterface $context) {
            $agency = $this->agencyRepository->findOne(
                [
                    'name' => $this->inputData['name'],
                    'organization_id' => $this->modelData->getOrganizationId()
                ]
            );

            if ($agency && $agency->getId() !== $this->modelData->getId()) {
                $context
                    ->buildViolation(sprintf(
                        'An agency with the name "%s" already exists in the system.',
                        $this->inputData['name']
                    ))
                    ->addViolation();
            }
        };
    }
}
