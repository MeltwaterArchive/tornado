<?php

namespace Tornado\Analyze\DataSet;

use \Tornado\Analyze\DataSet;
use \Tornado\Analyze\Dimension\Collection as DimensionCollection;

/**
 * Models a TimeSeries DataSet
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Analyze
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class TimeSeries extends DataSet
{

    /**
     * The interval of this TimeSeries
     *
     * @var integer
     */
    private $interval;

    /**
     * The span for this TimeSeries
     *
     * @var string
     */
    private $span;

    /**
     * The start of this TimeSeries
     *
     * @var integer|null
     */
    private $start;

    /**
     * The end of this TimeSeries
     *
     * @var integer|null
     */
    private $end;

    /**
     * Constructs a new TimeSeries DataSet
     *
     * @param \Tornado\Analyze\Dimension\Collection $dimensions
     * @param array $data
     * @param string $interval
     * @param integer $span
     */
    public function __construct(
        DimensionCollection $dimensions,
        array $data,
        $interval,
        $span
    ) {
        parent::__construct($dimensions, $data);
        $this->interval = $interval;
        $this->span = $span;
    }

    /**
     * Gets the interval used to create this TimeSeries
     *
     * @return string
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * Sets the span used to create this TimeSeries
     *
     * @return integer
     */
    public function getSpan()
    {
        return $this->span;
    }

    /**
     * Gets the start of this TimeSeries, if applicable
     *
     * @return integer|null
     */
    public function getStart()
    {
        if ($this->start == null) {
            $data = $this->getSimple();
            $mins = [];
            foreach ($data as $measureData) {
                $keys = array_map(
                    function ($key) {
                        if (preg_match('/^' . self::KEY_DIMENSION_PREFIX . '[^:]+:(.+)$/', $key, $matches)) {
                            return $matches[1];
                        }
                        return $key;
                    },
                    array_keys($measureData)
                );
                $newMeasureData = array_combine($keys, $measureData);
                $mins[] = min(array_keys($newMeasureData));
            }
            $this->start = min($mins);
        }
        return $this->start;
    }

    /**
     * Gets the end of this TimeSeries, if applicable
     *
     * @return integer|null
     */
    public function getEnd()
    {
        if ($this->end == null) {
            $data = $this->getSimple();
            $max = [];
            foreach ($data as $measureData) {
                $keys = array_map(
                    function ($key) {
                        if (preg_match('/^' . self::KEY_DIMENSION_PREFIX . '[^:]+:(.+)$/', $key, $matches)) {
                            return $matches[1];
                        }
                        return $key;
                    },
                    array_keys($measureData)
                );
                $newMeasureData = array_combine($keys, $measureData);
                $max[] = max(array_keys($newMeasureData));
            }
            $this->end = max($max);
        }
        return $this->end;
    }

    /**
     * Shifts this TimeSeries DataSet by a supplied number of seconds
     *
     * @param integer $seconds
     *
     * @return \Tornado\Analyze\DataSet\TimeSeries
     */
    public function shift($seconds)
    {
        $data = $this->getData();
        $newData = [];
        foreach ($data as $measureKey => $measureData) {
            $newMeasureData = [];
            foreach ($measureData[self::KEY_DIMENSION_TIME] as $ts => $subData) {
                $newMeasureData[$ts + $seconds] = $subData;
            }
            $newData[$measureKey] = [self::KEY_DIMENSION_TIME => $newMeasureData];
        }

        return new TimeSeries(
            $this->getDimensions(),
            $newData,
            $this->getInterval(),
            $this->getSpan()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCompatible(DataSet $dataset, $permissive = true)
    {
        if (!($dataset instanceof self)) {
            return false;
        }
        if ($dataset->getInterval() !== $this->getInterval()) {
            return false;
        }

        if (!$permissive) {
            if ($dataset->getSpan() !== $this->getSpan()) {
                return false;
            }
        }

        return true;
    }
}
