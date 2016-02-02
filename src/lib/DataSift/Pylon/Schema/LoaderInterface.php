<?php

namespace DataSift\Pylon\Schema;

use \DataSift\Pylon\SubscriptionInterface;
use DataSift\Loader\LoaderInterface as BaseLoaderInterface;

/**
 * License
 *
 * PHP Version 5.3
 *
 * This software is the intellectual property of DataSift Ltd., and is covered by retained intellectual property rights,
 * including copyright. Distribution of this software is strictly forbidden under the terms of this license.
 *
 * @category  Fido
 * @author    Christopher Hoult <chris.hoult@datasift.com>
 * @copyright 2015-2016 MediaSift Ltd.
 * @license   http://datasift.com DataSift Internal License
 * @link      http://www.datasift.com
 */
interface LoaderInterface
{
    /**
     *
     * @param \DataSift\Pylon\SubscriptionInterface $subscription
     */
    public function load(SubscriptionInterface $subscription = null);
}
