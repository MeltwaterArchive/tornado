<?php

namespace Tornado\Analyze;

use Tornado\Project\Recording;

/**
 * Models a basic Analysis
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
abstract class Analysis
{
    const TYPE_FREQUENCY_DISTRIBUTION = 'freqDist';
    const TYPE_TIME_SERIES = 'timeSeries';

    /**
     * The Recording on which this Analysis is executed
     *
     * @var Recording|null
     */
    protected $recording;

    /**
     * The target of this Analysis, i.e. fb.author.gender
     *
     * @var string
     */
    protected $target;

    /**
     * The start time of this Analysis represents as UNIX timestamp
     *
     * @var integer|null
     */
    protected $start;

    /**
     * The end time of this Analysis represents as UNIX timestamp
     *
     * @var integer|null
     */
    protected $end;

    /**
     * The secondary filter for this Analysis
     *
     * @var string
     */
    protected $filter;

    /**
     * The Analysis response from PYLON API
     *
     * @var \stdClass|null
     */
    protected $results;

    /**
     * The Analysis's child Analysis
     *
     * @var Analysis|null
     */
    protected $child;

    /**
     * Gets directly this Analysis type
     * The type of Analysis is strictly determine by the Analysis child class, i.e. TimeSeriesAnalysis.
     *
     * @return string
     */
    abstract public function getType();

    /**
     * Gets this Analysis Recording
     *
     * @return \Tornado\Project\Recording|null
     */
    public function getRecording()
    {
        return $this->recording;
    }

    /**
     * Gets this Analysis target
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Gets this Analysis start time
     *
     * @return int|null
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Gets this Analysis end time
     *
     * @return int|null
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Gets this Analysis object's secondary filter
     *
     * @return string
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Sets this Analysis object's secondary filter
     *
     * @param string $filter
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
    }

    /**
     * Sets this Analysis results
     *
     * @param \stdClass $results
     */
    public function setResults(\stdClass $results)
    {
        $this->results = $results;
    }

    /**
     * Gets this Analysis results
     *
     * @return \stdClass|null
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * Sets this Analysis a child Analysis
     *
     * @param \Tornado\Analyze\Analysis $child
     */
    public function setChild(Analysis $child)
    {
        $this->child = $child;
    }

    /**
     * Gets this Analysis a child Analysis
     *
     * @return \Tornado\Analyze\Analysis|null
     */
    public function getChild()
    {
        return $this->child;
    }
}
