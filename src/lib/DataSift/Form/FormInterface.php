<?php

namespace DataSift\Form;

use Tornado\DataMapper\DataObjectInterface;

/**
 * FormInterface
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift\Form
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
interface FormInterface
{
    const NOTIFICATION_KEY = '__notification';

    /**
     * Submits data for this Form to validate them against the constraints
     *
     *
     * @param array $data
     * @param \Tornado\DataMapper\DataObjectInterface
     *
     * @return void
     */
    public function submit(array $data, DataObjectInterface $object = null);

    /**
     * Determines if this Form has been already submitted
     *
     * @return boolean
     */
    public function isSubmitted();

    /**
     * Checks if this Form data has been processed successfully and are valid
     *
     * @return boolean
     */
    public function isValid();

    /**
     * Returns this Form data in input format, before any modification/transformations
     *
     * @return mixed
     */
    public function getInputData();

    /**
     * Returns this Form data in normalized format
     *
     * @return mixed
     */
    public function getNormalizedData();

    /**
     * Returns this Form data after any modification.
     * For instance, Form instance can hydrate the handed data to DataObjectInterface
     *
     * @return mixed
     */
    public function getData();

    /**
     * Returns this Form processing errors
     *
     * @return array
     */
    public function getErrors();

    /**
     * Returns this Form defined fields
     *
     * @return array
     */
    public function getFields();
}
