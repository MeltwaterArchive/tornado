<?php

namespace DataSift\Pylon;

/**
 * An interface to represent a PYLON subscription
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift\Pylon
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
interface SubscriptionInterface
{

    /**
     * Gets the ID for this Subscription
     *
     * @return string
     */
    public function getSubscriptionId();

    /**
     * Gets the CSDL hash for this Subscription
     *
     * @return string
     */
    public function getSubscriptionHash();
}
