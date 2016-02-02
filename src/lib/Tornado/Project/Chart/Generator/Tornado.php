<?php

namespace Tornado\Project\Chart\Generator;

use \Tornado\Project\Chart\Generator;
use Tornado\Project\Chart;
use Tornado\Analyze\DataSet;
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
class Tornado extends Generator
{
/**
     * Generates a list of Tornado charts from the passed DataSet(s)
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
        $newDimensions = $dimensions;
        if ($dimensions->getCount() == 3) {
            $newDimensions = new DimensionCollection(
                $dimensions->getDimensions(DimensionCollection::ORDER_LAST_FIRST)
            );
        }
        $primary = $primary->pivot($newDimensions);

        $data = $primary->getSimple();
        $secondaryData = [];
        $secondaryHasThree = false;
        if ($secondary) {
            $secondaryHasThree = ($secondary->getDimensions()->getCount() == 3);
            $dims = ($secondaryHasThree) ? $newDimensions : $dimensions;
            $secondary = $secondary->pivot($dims, true);
            $secondaryData = $secondary->getSimple();
        }

        $charts = [];

        if ($dimensions->getCount() == 3) {
            $keys = [];
            foreach ([DataSet::KEY_MEASURE_INTERACTIONS, DataSet::KEY_MEASURE_UNIQUE_AUTHORS] as $key) {
                if (isset($data[$key]) && is_array($data[$key])) {
                    $keys = array_merge($keys, array_keys($data[$key]));
                }
            }

            foreach (array_unique($keys) as $key) {
                $chart = $this->generateTornado(
                    $key,
                    $data,
                    $secondaryData,
                    $secondaryHasThree,
                    $mode
                );
                $chart->setType(Chart::TYPE_TORNADO);
                $chart->setName($this->nameGenerator->generate($chart, $dimensions, $this->stripDimension($key)));
                $charts[] = $chart;
            }
            return $charts;
        }

        $chart = $this->generateSingleTornado($data, $secondaryData, $mode);
        $chart->setType(Chart::TYPE_TORNADO);
        $chart->setName($this->nameGenerator->generate($chart, $dimensions));

        return [$chart];
    }

    /**
     * Generates a single Tornado chart for the given data
     *
     * @param array  $data
     * @param array  $secondaryData
     * @param string $mode
     *
     * @return \Tornado\Project\Chart
     */
    private function generateSingleTornado(array $data, array $secondaryData, $mode)
    {
        $chartData = [];
        $measurementsMap = [
            DataSet::KEY_MEASURE_INTERACTIONS => self::MEASURE_INTERACTIONS,
            DataSet::KEY_MEASURE_UNIQUE_AUTHORS => self::MEASURE_UNIQUE_AUTHORS
        ];

        foreach ($measurementsMap as $key => $measureKey) {
            $sData = (isset($secondaryData[$key])) ? $secondaryData[$key] : [];
            if ($mode == self::MODE_BASELINE) {
                $sData = $this->baseline($data[$key], $sData);
            }
            $chartData[$measureKey] = $this->generateMeasureTornado($data[$key], $sData, $mode);
        }

        $chart = new Chart();
        $chart->setData($chartData);

        return $chart;
    }

    /**
     * Generates a Tornado chart from the passed data
     *
     * @param string  $key
     * @param array   $data
     * @param array   $secondary
     * @param boolean $secondaryHasThree
     * @param string  $mode
     * @param string  $parentKey
     *
     * @return \Tornado\Project\Chart
     */
    private function generateTornado(
        $key,
        array $data,
        array $secondary,
        $secondaryHasThree = false,
        $mode = self::MODE_COMPARE
    ) {
        $chart = new Chart();
        $chart->setType(Chart::TYPE_TORNADO);

        // Generate list of keys
        $measures = [
            DataSet::KEY_MEASURE_INTERACTIONS => self::MEASURE_INTERACTIONS,
            DataSet::KEY_MEASURE_UNIQUE_AUTHORS => self::MEASURE_UNIQUE_AUTHORS
        ];
        foreach ($measures as $measureKey => $measure) {
            if (isset($data[$measureKey], $data[$measureKey][$key])) {
                $compare = [];
                if ((
                        $secondaryHasThree
                        && isset($secondary[$measureKey], $secondary[$measureKey][$key])
                    )
                    || (!$secondaryHasThree && isset($secondary[$measureKey]))
                ) {
                    $compare = $this->generateSecondarySeries(
                        $secondaryHasThree,
                        $secondary[$measureKey],
                        $key,
                        $mode,
                        $data[$measureKey][$key]
                    );
                }
                $chartData[$measure] = $this->generateMeasureTornado($data[$measureKey][$key], $compare, $mode, $key);
            }
        }

        $chart->setData($chartData);
        return $chart;
    }

    /**
     * Generates the secondary series depending on the passed mode
     *
     * @param boolean $secondaryHasThree
     * @param array   $secondaryData
     * @param string  $key
     * @param string  $mode
     * @param array   $data
     *
     * @return array
     */
    protected function generateSecondarySeries(
        $secondaryHasThree,
        array $secondaryData,
        $key,
        $mode,
        $data
    ) {
        $sdata = ($secondaryHasThree && isset($secondaryData[$key])) ? $secondaryData[$key] : $secondaryData;

        if ($mode == self::MODE_BASELINE) {
            $sdata = $this->baseline($data, $sdata);
        }
        return $sdata;
    }

    /**
     * Generates Tornado data for a single measure
     *
     * @param array $data
     * @param array $compare
     * @param string $mode
     * @param string $parentKey
     *
     * @return array
     */
    private function generateMeasureTornado(array $data, array $compare, $mode, $parentKey = false)
    {
        $data = array_slice($data, 0, 2);
        $chartData = [];
        $keys = $this->getSeriesKeys($data);

        foreach ($data as $key => $value) {
            $newKey = $this->stripDimension($key);
            $cValue = (isset($compare[$key])) ? $compare[$key] : 0;
            if (!isset($chartData[$key])) {
                $chartData[$newKey] = [];
            }
            if (is_scalar($value)) {
                $chartData[$newKey] = [$this->getTornadoEntry(
                    '',
                    ['' => $value],
                    ['' => (is_scalar($cValue)) ? $cValue : 0],
                    $mode,
                    [$key]
                )];
            } else {
                foreach ($keys as $subKey) {
                    $keyList = [$key, $subKey];
                    if ($parentKey) {
                        array_unshift($keyList, $parentKey);
                    }
                    $chartData[$newKey][] = $this->getTornadoEntry($subKey, $value, $cValue, $mode, $keyList);
                }
                usort($chartData[$newKey], function ($a, $b) {
                    return $a[0] > $b[0];
                });
            }
        }

        ksort($chartData);
        return $chartData;
    }

    /**
     * Gets a single series entry for the passed data
     *
     * @param string $subKey
     * @param mixed  $value
     * @param mixed  $cValue
     * @param string $mode
     * @param array $keys
     *
     * @return array
     */
    private function getTornadoEntry($subKey, $value, $cValue, $mode, $keys = [])
    {
        $subValue = (isset($value[$subKey])) ? $value[$subKey] : 0;
        $nValue = (isset($cValue[$subKey])) ? $cValue[$subKey] : 0;
        return [
            $this->stripDimension($subKey),
            $subValue,
            $nValue,
            $this->getMetadata($subValue, $nValue, $mode, $keys)
        ];
    }
}
