<?php

namespace Tornado\Analyze\Analysis;

use Tornado\Analyze\Analysis;
use Tornado\Project\Recording;

/**
 * Models a FrequencyDistribution analysis built on top of a basic Analysis
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
class FrequencyDistribution extends Analysis
{
    /**
     * Sets threshold for this FrequencyDistribution analysis
     *
     * @var integer|null
     */
    protected $threshold;

    /**
     * Determines and sets this FrequencyDistribution required values.
     * Recording is not required in case of using the FrequencyDistribution as an Analysis child.
     *
     * @param string                          $target
     * @param integer|null                    $threshold
     * @param integer|null                    $start
     * @param integer|null                    $end
     * @param \Tornado\Project\Recording|null $recording
     * @param string|null                     $filter
     */
    public function __construct(
        $target,
        $threshold = null,
        $start = null,
        $end = null,
        Recording $recording = null,
        $filter = null
    ) {
        $this->target = $target;
        $this->threshold = $threshold;
        $this->start = $start;
        $this->end = $end;
        $this->recording = $recording;
        $this->filter = $filter;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE_FREQUENCY_DISTRIBUTION;
    }

    /**
     * Gets this FrequencyDistribution analysis threshold
     *
     * @return integer|null
     */
    public function getThreshold()
    {
        return $this->threshold;
    }
}
