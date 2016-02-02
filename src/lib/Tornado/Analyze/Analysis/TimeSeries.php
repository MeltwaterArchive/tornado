<?php

namespace Tornado\Analyze\Analysis;

use Tornado\Analyze\Analysis;
use Tornado\Project\Recording;

/**
 * Models a TimeSeries analysis built on top of a basic Analysis
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Analyze
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class TimeSeries extends Analysis
{
    /**
     * The interval of this TimeSeries analysis. It represents the interval unit i.e. "hour"
     *
     * @var string
     */
    protected $interval;

    /**
     * The span of this TimeSeries analysis interval
     *
     * @var integer|null
     */
    protected $span;

    /**
     * Determines and sets this TimeSeries analysis required values.
     * Recording is not required in case of using the TimeSeries as an Analysis child.
     *
     * @param string                          $target
     * @param string                          $interval
     * @param integer|null                    $start
     * @param integer|null                    $end
     * @param integer|null                    $span
     * @param \Tornado\Project\Recording|null $recording
     * @param string|null                     $filter
     */
    public function __construct(
        $target,
        $interval,
        $start = null,
        $end = null,
        $span = null,
        Recording $recording = null,
        $filter = null
    ) {
        $this->target = $target;
        $this->interval = $interval;
        $this->start = $start;
        $this->end = $end;
        $this->span = $span;
        $this->recording = $recording;
        $this->filter = $filter;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE_TIME_SERIES;
    }

    /**
     * Gets this Analysis interval
     *
     * @return string
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * Gets this Analysis interval's span
     *
     * @return int|null
     */
    public function getSpan()
    {
        return $this->span;
    }
}
