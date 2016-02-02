<?php

namespace Tornado\Project\Chart;

use Tornado\Project\Chart;
use Tornado\Analyze\DataSet;
use Tornado\Analyze\Dimension;
use Tornado\Analyze\Dimension\Collection as DimensionCollection;

/**
 * A Factory for Charts
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Project
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Factory
{

    const MODE_COMPARE = 'compare';
    const MODE_BASELINE = 'baseline';

    /**
     * @var NameGenerator
     */
    protected $nameGenerator;

    public function __construct(NameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * Generates a list of Charts of $type based on the $measure of primary
     * DataSet passed to it. If $secondary is passed, the data is combined to
     * form a Chart providing a comparison.
     *
     * $mode may also be ChartGenerator::MODE_BASELINE which then generates a
     * baseline instead of a direct comparison.
     *
     * @param string                                $chartType
     * @param \Tornado\Analyze\Dimension\Collection $dimensions
     * @param \Tornado\Analyze\DataSet              $primary
     * @param \Tornado\Analyze\DataSet|null         $secondary
     * @param string                                $mode
     *
     * @return array
     */
    public function fromDataSet(
        $chartType,
        DimensionCollection $dimensions,
        DataSet $primary,
        DataSet $secondary = null,
        $mode = self::MODE_COMPARE
    ) {
        switch ($chartType) {
            case Chart::TYPE_TORNADO:
                $generator = new Generator\Tornado($this->nameGenerator);
                break;
            case Chart::TYPE_HISTOGRAM:
                $generator = new Generator\Histogram($this->nameGenerator);
                break;
            case Chart::TYPE_TIME_SERIES:
                $generator = new Generator\TimeSeries($this->nameGenerator);
                break;
            default:
                throw new \InvalidArgumentException("Unknown chart type '{$chartType}'");
        }

        return $generator->fromDataSet(
            $dimensions,
            $primary,
            $secondary,
            $mode
        );
    }
}
