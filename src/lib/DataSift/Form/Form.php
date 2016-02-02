<?php

namespace DataSift\Form;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

use Tornado\Application\Flash\Message;

/**
 * Form
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
abstract class Form implements FormInterface
{
    /**
     * Determines if Form has been already submitted
     *
     * @var bool
     */
    protected $submitted = false;

    /**
     * Stores all Form processing errors
     *
     * @var array|ConstraintViolationList
     */
    protected $errors = [];

    /**
     * The form data in input format before any modification/transformations
     *
     * @var mixed
     */
    protected $inputData;

    /**
     * The form data in normalized format
     *
     * @var mixed
     */
    protected $normalizedData;

    /**
     * The form data in model format
     *
     * @var array
     */
    protected $modelData;

    /**
     * {@inheritdoc}
     */
    public function getInputData()
    {
        return $this->inputData;
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizedData()
    {
        return $this->normalizedData;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->modelData;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid()
    {
        if (!$this->submitted) {
            return false;
        }

        if (count($this->getErrors())) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isSubmitted()
    {
        return $this->submitted;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors($notification = '')
    {
        $errors = [];

        foreach ($this->errors as $key => $error) {
            if ($error instanceof ConstraintViolation) {
                $errors[$this->getErrorPath($error)] = $error->getMessage();
            } else {
                $errors[$key] = $error;
            }
        }

        if (count($errors) && $notification) {
            $errors['__notification'] = ['message' => $notification, 'level' => Message::LEVEL_ERROR];
        }

        return $errors;
    }

    /**
     * Gets the error related field path
     *
     * @param \Symfony\Component\Validator\ConstraintViolation $error
     *
     * @return string
     */
    protected function getErrorPath(ConstraintViolation $error)
    {
        return preg_replace(
            '/(\[|\])/',
            '',
            preg_replace('/(\]\[)/', '.', $error->getPropertyPath())
        );
    }
}
