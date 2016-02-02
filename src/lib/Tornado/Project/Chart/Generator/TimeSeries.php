<?php

namespace Tornado\Project\Chart\Generator;

use \Tornado\Project\Chart\Generator;
use Tornado\Project\Chart;
use Tornado\Analyze\DataSet;
use Tornado\Analyze\DataSet\IncompatibleDimensionsException;
use Tornado\Analyze\Dimension;
use Tornado\Analyze\Dimension\Collection as DimensionCollection;

/**
 * Models a Tornado Worksheet's Chart
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
class TimeSeries extends Generator
{
    /**
     * Keys for the different series
     */
    const KEY_MAIN_SERIES = 'main';
    const KEY_COMPARISON_SERIES = 'comparison';

    /**
     * Generates a TimeSeries charts from the passed DataSet(s)
     *
     * @param \Tornado\Analyze\Dimension\Collection $dimensions
     * @param \Tornado\Analyze\DataSet              $primary
     * @param \Tornado\Analyze\DataSet              $secondary
     * @param string                                $mode
     *
     * @return array
     */
    public function fromDataSet(
        DimensionCollection $dimensions,
        DataSet $primary,
        DataSet $secondary = null,
        $mode = self::MODE_COMPARE
    ) {
        if ($dimensions->getCount() !== 1) {
            throw new IncompatibleDimensionsException('TimeSeries can only support 1 dimension');
        }

        $data = $primary->getSimple();
        $secondaryData = [];
        if ($secondary) {
            $secondaryData = $secondary->getSimple();
        }

        $measures = [
            DataSet::KEY_MEASURE_INTERACTIONS => self::MEASURE_INTERACTIONS,
            DataSet::KEY_MEASURE_UNIQUE_AUTHORS => self::MEASURE_UNIQUE_AUTHORS
        ];

        $chartData = [];

        foreach ($data as $measureKey => $measureData) {
            $measure = $measures[$measureKey];
            if (!isset($chartData[$measure])) {
                $chartData[$measure] = [static::KEY_MAIN_SERIES => []];
            }

            if ($secondary) {
                $chartData[$measure] = [static::KEY_COMPARISON_SERIES => [], static::KEY_MAIN_SERIES => []];
            }

            ksort($measureData);

            $sdata = (isset($secondaryData[$measureKey])) ? $secondaryData[$measureKey] : [];

            $chartData[$measure] = $this->generateMeasureChartData(
                $measureData,
                $sdata,
                $mode
            );
        }

        $chart = new Chart();
        $chart->setData($chartData);
        $chart->setType(Chart::TYPE_TIME_SERIES);
        $chart->setName('Time Series');

        return [$chart];
    }

    /**
     * Generates chart data for a measure
     *
     * @param array $data
     * @param array $sdata
     * @param string $mode
     *
     * @return array
     */
    protected function generateMeasureChartData(array $data, array $sdata, $mode)
    {
        $sdata = $this->generateSecondarySeries($sdata, $mode, $data);

        $chartData = [
            static::KEY_MAIN_SERIES => []
        ];

        if ($sdata) {
            $chartData[static::KEY_COMPARISON_SERIES] = [];
        }

        foreach ($data as $key => $value) {
            $cValue = (isset($sdata[$key])) ? $sdata[$key] : 0;
            $newKey = $this->stripDimension($key);
            $chartData[static::KEY_MAIN_SERIES][] = [$newKey, $value];
            if ($sdata) {
                $chartData[static::KEY_COMPARISON_SERIES][] = [$newKey, $cValue];
            }
        }

        return $chartData;
    }

    /**
     * Returns a baseline of $data against $baseline
     *
     * @param array $data
     * @param array $baseline
     *
     *
     * @return array
     */
    protected function baseline(array $data, array $baseline)
    {
        $dataPopulation = \array_sum($data);
        $baselinePopulation = \array_sum($baseline);
        $ratio = ($baselinePopulation > 0) ? $dataPopulation / $baselinePopulation : 0;

        $ret = [];
        foreach (array_keys($data) as $key) {
            if (!isset($baseline[$key])) {
                continue;
            }
            $ret[$key] = (int)round($baseline[$key] * $ratio);
        }

        return $ret;
    }

    /**
     * Generates the secondary series depending on the passed mode
     *
     * @param array   $secondaryData
     * @param string  $mode
     * @param array   $data
     *
     * @return array
     */
    protected function generateSecondarySeries(
        array $secondaryData,
        $mode,
        $data
    ) {

        $sdata = (isset($secondaryData)) ? $secondaryData : [];

        if ($mode == self::MODE_BASELINE) {
            $sdata = $this->baseline($data, $secondaryData);
            return $sdata;
        }

        return $sdata;
    }
}
