<?php

namespace Tornado\Analyze;

use \Tornado\Analyze\Dimension\Collection as DimensionCollection;
use \Tornado\Analyze\DataSet\IncompatibleDimensionsException;

/**
 * Models a Tornado DataSet
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
class DataSet
{

    const MEASURE_INTERACTIONS = 'interactions';
    const MEASURE_UNIQUE_AUTHORS = 'unique_authors';

    const KEY_VALUE = '%VALUE%';
    const KEY_REDACTED = '%REDACTED%';

    const KEY_DIMENSION_PREFIX = 'dimension:';
    const KEY_DIMENSION_TIME = 'dimension:time';
    const KEY_MEASURE_PREFIX = 'measure:';

    const KEY_MEASURE_INTERACTIONS = 'measure:interactions';
    const KEY_MEASURE_UNIQUE_AUTHORS = 'measure:unique_authors';

    /**
     * The Dimensions used in this DataSet
     *
     * @var \Tornado\Analyze\Dimension\Collection
     */
    protected $dimensions;

    /**
     * The data contained by this DataSet
     *
     * @var array
     */
    protected $data;

    /**
     * Constructs a new DataSet
     *
     * @param \Tornado\Analyze\Dimension\Collection $dimensions
     * @param array $data
     */
    public function __construct(DimensionCollection $dimensions, array $data)
    {
        $this->dimensions = $dimensions;
        $this->data = $data;
    }

    /**
     * Gets the list of Dimensions for this DataSet
     *
     * @return \Tornado\Analyze\Dimension\Collection
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * Gets the data contained in this DataSet
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Pivots the data stored in this DataSet into another order
     *
     *       ie. $dimensions matches $data
     *
     * @param \Tornado\Analyze\Dimension\Collection $dimensions
     * @param boolean $allowSubset  Set to true, if the passed dimensions are
     *                               an ordered subset of the DataSet's
     *                               dimensions, the dimensions will be reduced
     *                               and pivoting will occur thusly
     *
     * @return \Tornado\Analyze\DataSet
     *
     * @throws \Tornado\Analyze\DataSet\IncompatibleDimensionsException
     */
    public function pivot(DimensionCollection $dimensions, $allowSubset = false)
    {
        if ($allowSubset) {
            if (!$this->getDimensions()->isSubset($dimensions)) {
                throw new IncompatibleDimensionsException();
            }
            $dimensions = $this->getDimensions()->getOrderedSubset($dimensions);
        } elseif (!$this->getDimensions()->isSame($dimensions)) {
            throw new IncompatibleDimensionsException();
        }

        $result = array();
        $this->flattenData($this->getData(), $result);
        $newData = $this->reorderFlattenedData($result, $dimensions);
        $newData = $this->unFlattenData($newData);

        return new DataSet($dimensions, $newData);
    }

    /**
     * Compares this DataSet to another to determine whether it's compatible or
     * not
     *
     * @param \Tornado\Analyze\DataSet $dataset
     * @param boolean $permissive               Set to true to allow a subset
     *
     * @return boolean
     */
    public function isCompatible(DataSet $dataset, $permissive = true)
    {
        if ($permissive) {
            if (!$this->getDimensions()->isSubset($dataset->getDimensions())) {
                return false;
            }
        } elseif (!$this->getDimensions()->isSame($dataset->getDimensions())) {
            return false;
        }
        return true;
    }

    /**
     * Gets a list of Dimensions and their values
     *
     * @param array $data
     * @param mixed $key
     *
     * @return array
     */
    private function getDimensionKeys(array $data, $key = false)
    {
        $ret = ($key) ? [$key => []] : [];
        foreach ($data as $currentKey => $value) {
            if (preg_match('/^' . self::KEY_DIMENSION_PREFIX . '(.*)$/', $currentKey, $matches)) {
                $ret = array_merge($ret, $this->getDimensionKeys($value, $matches[1]));
            } elseif (!in_array($currentKey, [self::KEY_REDACTED, self::KEY_VALUE])) {
                $ret[$key][] = $currentKey;
                $ret = array_merge($ret, $this->getDimensionKeys($value));
            }
        }

        return $ret;
    }

    /**
     * Gets the values for a given path in the main data array
     *
     * @param array $key
     * @param array $data
     *
     * @return array
     */
    private function getValues(array $key, array &$data)
    {
        if (count($key)) {
            $val = array_shift($key);
            return $this->getValues($key, $data[$val]);
        } else {
            return [
                self::KEY_VALUE => $data[self::KEY_VALUE],
                self::KEY_REDACTED => $data[self::KEY_REDACTED],
            ];
        }
    }

    /**
     * Flattens the data structure
     *
     * @param array $data
     * @param array &$result
     *
     * @param string|false $path
     */
    private function flattenData(array $data, array &$result, $path = '')
    {
        foreach ($data as $key => $value) {
            if (preg_match('/^' . self::KEY_MEASURE_PREFIX . '/', $key)) {
                $this->flattenData($data[$key], $result, $key);
                $result[$key . '||' . self::KEY_VALUE] = $data[$key][self::KEY_VALUE];
                $result[$key . '||' . self::KEY_REDACTED] = $data[$key][self::KEY_REDACTED];
            } elseif (preg_match('/^' . self::KEY_DIMENSION_PREFIX . '/', $key)) {
                foreach ($value as $subkey => $subvalue) {
                    $this->flattenData($subvalue, $result, "{$path}||{$key}:{$subkey}");
                }
            } elseif (count($data) == 2) {
                $result["{$path}||{$key}"] = $value;
            }
        }
    }

    /**
     * Reorders a set of data flattened with flattenData
     *
     * @param array $data
     * @param \Tornado\Analyze\Dimension\Collection $dimensionCollection
     * @param string $order The order in which to pivot the data
     *
     * @return array
     */
    private function reorderFlattenedData(
        array $data,
        DimensionCollection $dimensionCollection,
        $order = DimensionCollection::ORDER_NATURAL
    ) {
        $targets = [];
        foreach ($dimensionCollection->getDimensions($order) as $dimension) {
            $targets[] = $dimension->getTarget();
        }

        $results = [];

        foreach ($data as $key => $value) {
            $parts = explode('||', $key);
            $measure = array_shift($parts);
            $last = array_pop($parts);
            $newKey = [];
            foreach ($parts as $part) {
                preg_match('/^' . self::KEY_DIMENSION_PREFIX . '([^:]+):/', $part, $matches);
                $pos = array_search($matches[1], $targets);
                $newKey[(int)$pos] = $part;
            }
            ksort($newKey);
            $newKey = implode('||', $newKey);
            if ($newKey) {
                $newKey .= '||';
            }

            $newKey = "{$measure}||{$newKey}{$last}";

            $results[$newKey] = $value;
        }

        return $results;
    }

    /**
     * Unflattens a flattened tree
     *
     * @param array $data
     *
     * @return array
     */
    private function unFlattenData(array $data)
    {
        $ret = [];
        foreach ($data as $key => $value) {
            $parts = explode('||', $key);
            $pos = &$ret;
            foreach ($parts as $part) {
                if (preg_match('/^' . self::KEY_DIMENSION_PREFIX . '([^:]+):(.+)$/', $part, $matches)) {
                    $part = self::KEY_DIMENSION_PREFIX . $matches[1];
                    if (!isset($pos[$part])) {
                        $pos[$part] = [];
                    }
                    $pos = &$pos[$part];
                    $part = $matches[2];
                }
                if (!isset($pos[$part])) {
                    $pos[$part] = [];
                }
                $pos = &$pos[$part];
            }
            $pos = $value;
        }
        return $ret;
    }

    /**
     * Gets a slice of this dataset, removing dimension and measure information
     *
     * @param string $measure
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function getSimple($measure = false)
    {
        if (!in_array($measure, [false, self::MEASURE_INTERACTIONS, self::MEASURE_UNIQUE_AUTHORS])) {
            throw new \InvalidArgumentException("Unknown measure '{$measure}'");
        }

        $data = $this->data;

        if ($measure) {
            $key = ($measure == self::MEASURE_INTERACTIONS)
                        ? self::KEY_MEASURE_INTERACTIONS
                        : self::KEY_MEASURE_UNIQUE_AUTHORS;

            $data = $data[$key];
        }

        return $this->simplifyData($data);
    }

    /**
     * Simplifies the passed data by stripping out Dimension information
     *
     * @param array $toSimplify
     *
     * @return array
     */
    private function simplifyData(array $toSimplify, $prefix = false)
    {
        $ret = [];

        foreach ($toSimplify as $key => $data) {
            $prefixKey = ($prefix) ? "{$prefix}:{$key}" : $key;
            if (count($data) == 2 && isset($data[self::KEY_VALUE])) {
                $ret[$prefixKey] = $data[self::KEY_VALUE];
            } elseif (!in_array($key, [self::KEY_VALUE, self::KEY_REDACTED])) {
                if (preg_match('/^' . self::KEY_DIMENSION_PREFIX . '/', $key)) {
                    return $this->simplifyData($data, $key);
                }
                $ret[$prefixKey] = $this->simplifyData($data);
            }
        }
        return $ret;
    }
}
