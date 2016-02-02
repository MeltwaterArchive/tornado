<?php

namespace Tornado\Analyze\Dimension;

use Tornado\Analyze\Dimension;
use Tornado\Analyze\DataSet\IncompatibleDimensionsException;

/**
 * Models a collection of Tornado Dimensions
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
class Collection
{
    /**
     * The order in which the Dimensions were added
     */
    const ORDER_NATURAL = 'natural';

    /**
     * The order of ascending cardinality; null last
     */
    const ORDER_CARDINALITY_ASC = 'cardinality-asc';

    /**
     * The order of descending cardinality; null first
     */
    const ORDER_CARDINALITY_DESC = 'cardinality-desc';

    /**
     * The order of target name ascending
     */
    const ORDER_TARGET_ASC = 'target-asc';

    /**
     * The order of target name descending cardinality
     */
    const ORDER_TARGET_DESC = 'target-desc';

    /**
     * Natural order, but with the third dimension first
     */
    const ORDER_LAST_FIRST = 'last-first';

    /**
     * A list of Dimension objects
     *
     * @var array
     */
    private $dimensions = [];

    /**
     * Constructs a new Collection of Dimensions
     *
     * @param \Tornado\Analyze\Dimension[] array $dimensions
     */
    public function __construct(array $dimensions = [])
    {
        $this->setDimensions($dimensions);
    }

    /**
     * Adds a single Dimension to this Collection
     *
     * @param \Tornado\Analyze\Dimension $dimension
     */
    public function addDimension(Dimension $dimension)
    {
        $this->dimensions[] = $dimension;
    }

    /**
     * Resets the Dimensions this Collection contains and adds them, in order
     *
     * @param \Tornado\Analyze\Dimension[] array $dimensions
     */
    public function setDimensions(array $dimensions)
    {
        $this->dimensions = [];
        foreach ($dimensions as $dimension) {
            $this->addDimension($dimension);
        }
    }

    /**
     * Gets a list of Dimensions
     *
     * @param string $mode The sort order; defaults to natural ordering
     *
     * @return \Tornado\Analyze\Dimension[] array
     */
    public function getDimensions($mode = self::ORDER_NATURAL)
    {
        if ($mode === self::ORDER_NATURAL) {
            return $this->dimensions;
        }

        if ($mode === self::ORDER_LAST_FIRST) {
            $dims = $this->dimensions;
            $item = array_pop($dims);
            array_unshift($dims, $item);
            return $dims;
        }

        $ret = $this->dimensions; // We don't want to sort the original array

        usort($ret, function (Dimension $a, Dimension $b) use ($mode) {
            if (in_array($mode, [self::ORDER_TARGET_ASC, self::ORDER_TARGET_DESC])) {
                return static::compareDimensionsByTarget($a, $b, $mode);
            }
            return static::compareDimensions($a, $b, $mode);
        });

        return $ret;
    }

    /**
     * Compares two Dimensions based on the sort mode passed in
     *
     * @param \Tornado\Analyze\Dimension $a
     * @param \Tornado\Analyze\Dimension $b
     * @param string $mode
     *
     * @return int
     */
    protected static function compareDimensions(Dimension $a, Dimension $b, $mode)
    {

        $cardA = ($a->getCardinality()) ? $a->getCardinality() : PHP_INT_MAX;
        $cardB = ($b->getCardinality()) ? $b->getCardinality() : PHP_INT_MAX;

        if ($mode === self::ORDER_CARDINALITY_DESC) {
            list($cardA, $cardB) = [$cardB, $cardA];
        }

        // Preserves the original order when two values are the same
        if ($cardA === $cardB) {
            return 1;
        }

        return $cardA - $cardB;
    }

    /**
     * Compares two Dimensions based on their targets
     *
     * @param \Tornado\Analyze\Dimension $a
     * @param \Tornado\Analyze\Dimension $b
     * @param string $mode
     *
     * @return int
     */
    protected static function compareDimensionsByTarget(Dimension $a, Dimension $b, $mode)
    {
        if ($mode == self::ORDER_TARGET_ASC) {
            return $a->getTarget() > $b->getTarget();
        }
        return $a->getTarget() < $b->getTarget();
    }

    /**
     * Compares this Dimension\Collection with another, returning true if the
     * Dimensions are identical in both, regardless of order
     *
     * @param \Tornado\Analyze\Dimension $compareTo
     *
     * @return boolean
     */
    public function isSame(self $compareTo)
    {
        $from = $this->getDimensions(self::ORDER_TARGET_ASC);
        $to = $compareTo->getDimensions(self::ORDER_TARGET_ASC);

        if (count($from) !== count($to)) {
            return false;
        }

        foreach ($from as $idx => $dim) {
            if ($dim->getTarget() !== $to[$idx]->getTarget()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compares this Dimension\Collection with another, returning true if the
     * Dimensions of this Collection are a subset of those in $compareTo,
     * regardless of order
     *
     * @param \Tornado\Analyze\Dimension\Collection $compareTo
     *
     * @return boolean
     */
    public function isSubset(self $compareTo)
    {
        $from = $this->getDimensions(self::ORDER_TARGET_ASC);
        $to = $compareTo->getDimensions(self::ORDER_TARGET_ASC);

        if (count($from) > count($to)) {
            return false;
        }

        $toTargets = [];
        foreach ($to as $dim) {
            $toTargets[] = $dim->getTarget();
        }

        foreach ($from as $dim) {
            if (!in_array($dim->getTarget(), $toTargets)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Gets a new Collection of the passed Collection in the order of this
     * current Collection
     *
     * @param \Tornado\Analyze\Dimension\Collection $subset
     *
     * @return \Tornado\Analyze\Dimension\Collection
     *
     * @throws IncompatibleDimensionsException
     */
    public function getOrderedSubset(self $subset)
    {
        if (!$this->isSubset($subset)) {
            throw new IncompatibleDimensionsException();
        }

        $newCollection = new self();
        $from = $this->getDimensions();
        $to = $subset->getDimensions();

        if (count($from) > count($to)) {
            return false;
        }

        $fromTargets = [];
        foreach ($from as $dim) {
            $fromTargets[] = $dim->getTarget();
        }

        foreach ($to as $dimension) {
            if (in_array($dimension->getTarget(), $fromTargets)) {
                $newCollection->addDimension($dimension);
            }
        }

        return $newCollection;
    }

    /**
     * Removes element of given index from this Collection.
     * Index min value is 0 according to the array index.
     *
     * @param integer $index
     *
     * @return boolean
     */
    public function removeElement($index)
    {
        if (!isset($this->dimensions[$index])) {
            return false;
        }

        array_splice($this->dimensions, $index, 1);

        return true;
    }

    /**
     * Gets a count of this Collection's dimensions
     *
     * @return integer
     */
    public function getCount()
    {
        return count($this->getDimensions());
    }
}
