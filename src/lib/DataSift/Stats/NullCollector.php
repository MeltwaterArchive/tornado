<?php

namespace DataSift\Stats;

use \DataSift\Stats\Collector;

/**
 * A class to null-route stats
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @package     \DataSift\Stats
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @codeCoverageIgnore
 * @SuppressWarnings
 */
class NullCollector implements Collector
{
    /**
     * {@inheritdoc}
     */
    public function absolute($key, $value, $multiple = false)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function addTiming($key, $elapsed)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function endTimer($key)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function increment($key, $amount = 1)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function startTimer($key)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function timerIsRunning($key)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function forceFlush()
    {
        return true;
    }
}
