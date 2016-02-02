<?php

namespace DataSift\Api;

/**
 * DataSift_NullUser child class that provides not functional,operational user instance only to avoid
 * symfony DIC RuntimeException error:
 *
 * "You have requested a synthetic service ("datasift.user"). The DIC does not know how to construct this service."
 *
 * which occurs in situation when DataSift_User cannot be resolved properly in request cycle. Situation when it may
 * happen are as follow:
 *  - requesting not found brand (404)
 *  - requesting not found project (404)
 *  - anytime when real DataSift_User can be resolved based on request data
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift\Api
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class NullUser extends \DataSift_User
{
    // @codingStandardsIgnoreStart
    /**
     * @var string The DataSift username.
     */
    protected $_username = 'tornado-null-user';

    /**
     * @var string The DataSift API Key.
     */
    protected $_api_key = 'tornado-null-user-no-api-key';
    // @codingStandardsIgnoreEnd
    public function __construct()
    {
    }
}
