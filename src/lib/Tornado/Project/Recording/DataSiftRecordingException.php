<?php

namespace Tornado\Project\Recording;

/**
 * DataSiftRecordingException
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
class DataSiftRecordingException extends \Exception
{
    /**
     * HTTP representation of Exception
     *
     * @var int
     */
    protected $statusCode = 500;

    /**
     * @param string     $message
     * @param int        $code
     * @param \Exception $previous
     * @param int        $statusCode
     */
    public function __construct($message = '', $code = 0, \Exception $previous = null, $statusCode = 500)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Gets a HTTP representation of Exception code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
