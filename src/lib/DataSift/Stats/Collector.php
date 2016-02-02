<?php

namespace DataSift\Stats;

/**
 * An interface to model a Stats collector
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
 */
interface Collector
{
    /**
     * Start tracking a timer
     *
     * The timer only exists inside this object until the timer ends
     *
     * @param string $key Key
     */
    public function startTimer($key);

    /**
     * Check to see if a timer has been started
     *
     * @param string $key Key
     *
     * @return boolean
     */
    public function timerIsRunning($key);

    /**
     * End a timer, and record how long it took
     *
     * You should first start timers using startTimer().  If you forget to, then
     * we throw an exception.
     *
     * @param string $key Key
     */
    public function endTimer($key);

    /**
     * Handle an absolute value
     *
     * @param string  $key   Key
     * @param integer $value Value
     * @param boolean $multiple
     */
    public function absolute($key, $value, $multiple = false);

    /**
     * Increment a counter
     *
     * If the counter does not exist, we will automatically initialise it
     *
     * @param string  $key    Key
     * @param integer $amount Increment step
     */
    public function increment($key, $amount = 1);

    /**
     * Add a pre-calculated timing to the cache of stats
     *
     * @param string $key     Key
     * @param float  $elapsed Elapsed time (in microseconds)
     */
    public function addTiming($key, $elapsed);
}
