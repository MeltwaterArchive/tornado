<?php

namespace Tornado\Controller;

/**
 * Wraps a Controller result.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Controller
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Result
{

    /**
     * Data returned from the controller.
     *
     * @var mixed
     */
    protected $data;

    /**
     * Meta data returned from the controller
     *
     * @var mixed
     */
    protected $meta;

    /**
     * HTTP Status Code the controller responded with.
     *
     * @var integer
     */
    protected $httpCode = 200;

    /**
     * Constructor.
     *
     * @param mixed   $data     Data returned from the controller.
     * @param mixed   $meta     Meta data returned from the controller.
     * @param integer $httpCode HTTP Status Code the controller responded with. Default: `200`.
     */
    public function __construct($data, $meta = [], $httpCode = 200)
    {
        $this->data = $data;
        $this->meta = $meta;
        $this->httpCode = $httpCode;
    }

    /**
     * Returns the data returned from the controller.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Sets the controller return data.
     *
     * @param mixed $data Controller return data.
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Returns the meta data returned from the controller.
     *
     * @return mixed
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Sets the controller return meta data.
     *
     * @param mixed $meta Controller return meta data.
     */
    public function setMeta($meta)
    {
        $this->meta = $meta;
    }

    /**
     * Returns the HTTP Status Code the controller responded with.
     *
     * @return integer
     */
    public function getHttpCode()
    {
        return $this->httpCode;
    }

    /**
     * Sets the HTTP Status Code the controller responded with.
     *
     * @param integer $httpCode HTTP Status Code.
     */
    public function setHttpCode($httpCode)
    {
        $this->httpCode = $httpCode;
    }
}
