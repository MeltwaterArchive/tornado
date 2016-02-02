<?php

namespace Tornado\Project\Chart;

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
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
abstract class Generator
{
    const MEASURE_INTERACTIONS = 'interactions';
    const MEASURE_UNIQUE_AUTHORS = 'unique_authors';

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
     * Generates a list of Tornado charts from the passed DataSet(s)
     *
     * @param \Tornado\Analyze\Dimension\Collection $dimensions
     * @param \Tornado\Analyze\DataSet              $primary
     * @param \Tornado\Analyze\DataSet              $secondary
     * @param string                                $mode
     *
     * @return array
     */
    abstract public function fromDataSet(
        DimensionCollection $dimensions,
        DataSet $primary,
        DataSet $secondary = null,
        $mode = self::MODE_COMPARE
    );

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
        foreach ($data as $key => $values) {
            if (!isset($baseline[$key])) {
                continue;
            }
            $dataPopulation = \array_sum($values);
            $baselinePopulation = \array_sum($baseline[$key]);

            if ($baselinePopulation > 0) {
                $ratio = $dataPopulation / $baselinePopulation;
                $baseline[$key] = \array_map(
                    function ($value) use ($ratio) {
                        $ret = (int)round($value * $ratio);
                        return $ret;
                    },
                    $baseline[$key]
                );
            } else {
                $baseline[$key] = \array_fill_keys(array_keys($baseline[$key]), 0);
            }
        }

        return $baseline;
    }

    /**
     * Gets a list of keys for the passed data
     *
     * @param array $data
     *
     * @return array
     */
    protected function getSeriesKeys(array $data)
    {

        $keys = [];
        foreach ($data as $series) {
            if (is_array($series)) {
                $keys = array_merge($keys, array_keys($series));
            }
        }

        return array_unique($keys);
    }

    /**
     * Strips dimension metadata from the passed key
     *
     * @param string $key
     *
     * @return string
     */
    protected function stripDimension($key)
    {
        if (preg_match('/^' . DataSet::KEY_DIMENSION_PREFIX . '[^:]+:(.+)$/', $key, $matches)) {
            return $matches[1];
        }
        return $key;
    }

    /**
     * Gets the target from a given dimension
     *
     * @param string $key
     *
     * @return string
     */
    protected function getTargetFromDimension($key)
    {
        if (preg_match('/^' . DataSet::KEY_DIMENSION_PREFIX . '([^:]+):.+$/', $key, $matches)) {
            return $matches[1];
        }
        return $key;
    }

    /**
     * Gets the metadata for a data point
     *
     * @param integer $value
     * @param integer $cValue
     * @param string $mode
     * @param array $keys
     *
     * @return type
     */
    protected function getMetadata($value, $cValue, $mode, array $keys = [])
    {
        $tooltip = $cValue;
        if ($mode == self::MODE_BASELINE) {
            $tooltip = ($cValue) ? ($value / $cValue) - 1 : 0;
            $tooltip = round($tooltip * 100) . '%';
        }

        return [
            'tooltip' => $tooltip,
            'explore' => $this->getExplore($keys)
        ];
    }

    /**
     * Gets a list of explore options for the given list of keys
     *
     * @param array $keys
     *
     * @return array
     */
    private function getExplore(array $keys)
    {
        $targets = [];
        $label = [];
        foreach ($keys as $key) {
            $targets[$this->getTargetFromDimension($key)] = $this->stripDimension($key);
            $label[] = ucwords($this->stripDimension($key));
        }
        return [
            implode(', ', $label) => $targets
        ];
    }
}
