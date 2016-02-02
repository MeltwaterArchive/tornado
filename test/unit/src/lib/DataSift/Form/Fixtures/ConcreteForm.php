<?php

namespace Test\DataSift\Form\Fixtures;

use DataSift\Form\Form;
use Tornado\DataMapper\DataObjectInterface;

/**
 * ConcreteForm implementation of Abstract Form class
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\DataSift\Form
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class ConcreteForm extends Form
{
    public function submit(array $data, DataObjectInterface $object = null)
    {
        $this->inputData = $data;
        $this->normalizedData = $data;
        $this->modelData = $object;
    }

    public function getFields()
    {

    }
}
