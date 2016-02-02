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
class Histogram extends Generator
{
    /**
     * Generates a list of Histogram charts from the passed DataSet(s)
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
        if ($dimensions->getCount() > 2) {
            throw new IncompatibleDimensionsException('Histograms can only support up to 2 dimensions');
        }

        $primary = $primary->pivot($dimensions);
        $data = $primary->getSimple();
        $secondaryData = [];
        if ($secondary) {
            $secondary = $secondary->pivot($dimensions);
            $secondaryData = $secondary->getSimple();
        }

        $measures = [
            DataSet::KEY_MEASURE_INTERACTIONS => self::MEASURE_INTERACTIONS,
            DataSet::KEY_MEASURE_UNIQUE_AUTHORS => self::MEASURE_UNIQUE_AUTHORS
        ];

        $chartsData = [];

        foreach ($data as $measureKey => $measureData) {
            $measure = $measures[$measureKey];
            $sdata = (isset($secondaryData[$measureKey])) ? $secondaryData[$measureKey] : [];
            ksort($measureData);

            if ($dimensions->getCount() === 1) {
                $chartsData = $this->getMeasureSingleChartData($measureData, $sdata, $mode, $measure, $chartsData);
            } else {
                $chartsData = $this->getMeasureChartData($measureData, $sdata, $mode, $measure, $chartsData);
            }
        }

        return $this->getChartsFromData($chartsData, $dimensions);
    }

    /**
     *
     * @param array $measureData
     * @param array $sdata
     * @param string $mode
     * @param string $measure
     * @param array $chartsData
     *
     * @return array
     */
    private function getMeasureChartData(array $measureData, array $sdata, $mode, $measure, array $chartsData)
    {
        foreach ($measureData as $key => $value) {
            $newKey = $this->stripDimension($key);
            if (!isset($chartsData[$newKey])) {
                $chartsData[$newKey] = [];
            }
            if (!isset($chartsData[$newKey][$measure])) {
                $chartsData[$newKey][$measure] = [$newKey => []];
            }

            ksort($value);
            $sData2 = $this->generateSecondarySeries($sdata, $key, $mode, $value);
            foreach ($value as $subkey => $subvalue) {
                $cValue = (isset($sData2[$subkey])) ? $sData2[$subkey] : 0;
                $chartsData[$newKey][$measure][$newKey][] = $this->getHistogramEntry(
                    $subkey,
                    $subvalue,
                    $cValue,
                    $mode,
                    [$key, $subkey]
                );
            }
        }
        return $chartsData;
    }

    /**
     * Generates measure data for single-dimension histogram.
     *
     * @param  array  $measureData
     * @param  array  $sdata
     * @param  string $mode
     * @param  string $measure
     * @param  array  $chartsData
     *
     * @return array
     */
    private function getMeasureSingleChartData(array $measureData, array $sdata, $mode, $measure, array $chartsData)
    {
        if (!isset($chartsData['single'])) {
            $chartsData['single'] = [];
        }

        $label = 'data';
        $chartData = [$label => []];

        $sData2 = $this->generateSecondarySeries(['data' => $sdata], 'data', $mode, $measureData);

        foreach ($measureData as $key => $value) {
            $cValue = (isset($sData2[$key])) ? $sData2[$key] : 0;
            $chartData[$label][] = $this->getHistogramEntry(
                $key,
                $value,
                $cValue,
                $mode,
                [$key]
            );
        }

        $chartsData['single'][$measure] = $chartData;
        return $chartsData;
    }

    /**
     * Gets an entry for a Histogram
     *
     * @param string $key
     * @param integer $value
     * @param integer $cValue
     * @param string $mode
     * @param array $keys
     *
     * @return array
     */
    private function getHistogramEntry($key, $value, $cValue, $mode, array $keys = [])
    {
        return [
            $this->stripDimension($key),
            $value,
            $cValue,
            $this->getMetadata($value, $cValue, $mode, $keys)
        ];
    }

    /**
     * Gets a list of charts from the passed data
     *
     * @param array $chartsData
     * @param \Tornado\Analyze\Dimension\Collection $dimensions
     *
     * @return \Tornado\Project\Chart
     */
    private function getChartsFromData(array $chartsData, DimensionCollection $dimensions)
    {
        $charts = [];
        foreach ($chartsData as $chartData) {
            $chart = new Chart();
            $chart->setData($chartData);
            $chart->setType(Chart::TYPE_HISTOGRAM);
            
            // build name
            $nameKey = $this->stripDimension(key(current($chartData)));
            $name = $this->nameGenerator->generate($chart, $dimensions, $nameKey);
            if ($dimensions->getCount() > 1) {
                $name .= ": {$nameKey}";
            }
            $chart->setName($name);

            $charts[] = $chart;
        }
        return $charts;
    }

    /**
     * Generates the secondary series depending on the passed mode
     *
     * @param array   $secondaryData
     * @param string  $key
     * @param string  $mode
     * @param array   $data
     *
     * @return array
     */
    protected function generateSecondarySeries(
        array $secondaryData,
        $key,
        $mode,
        $data
    ) {
        $sdata = (isset($secondaryData[$key])) ? $secondaryData[$key] : [];

        if ($mode == self::MODE_BASELINE) {
            $sdata = $this->baseline([$key => $data], $secondaryData);
            return $sdata[$key];
        }

        return $sdata;
    }
}
