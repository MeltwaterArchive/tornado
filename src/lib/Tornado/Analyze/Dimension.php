<?php

namespace Tornado\Analyze;

/**
 * Models a Tornado Dimension
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
class Dimension implements \JsonSerializable
{

    /**
     * The dimension that represents time...
     *
     * ... the FOURTH dimension!
     *
     *
     * @const string
     */
    const TIME = 'time';

    /**
     * The target the Dimension applies to; this member should never change for
     * a given object
     *
     * @var string
     */
    private $target;

    /**
     * The cardinality of this Dimension - the maximum number of potential values
     * it contains. Null for unknown.
     *
     * @var integer|null
     */
    private $cardinality;

    /**
     * The label of this Dimension
     *
     * @var string
     */
    private $label;

    /**
     * The threshold of this Dimension - the max number of results which it should contain.
     *
     * @var integer|null
     */
    private $threshold = null;

    /**
     * Constructs a new Dimension
     *
     * @param string $target
     * @param integer|null $cardinality
     * @param string|null $label
     */
    public function __construct($target, $cardinality = null, $label = null, $threshold = null)
    {
        $this->target = $target;
        $this->setCardinality($cardinality);
        $this->setLabel($label);
        $this->setThreshold($threshold);
    }

    /**
     * Gets the target this Dimension applies to
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Gets the cardinality of this Dimension
     *
     * @return integer|null
     */
    public function getCardinality()
    {
        return $this->cardinality;
    }

    /**
     * Sets the cardinality of this Dimension
     *
     * @param integer|null $cardinality
     */
    public function setCardinality($cardinality)
    {
        $this->cardinality = $cardinality;
    }

    /**
     * Sets the label of this Dimension
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Gets the label of this Dimension
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Sets this Dimension threshold
     *
     * @param int $threshold
     */
    public function setThreshold($threshold)
    {
        $this->threshold = $threshold;
    }

    /**
     * Gets this Dimension threshold
     *
     * @return int
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'target' => $this->getTarget(),
            'cardinality' => $this->getCardinality(),
            'label' => $this->getLabel(),
            'threshold' => $this->getThreshold()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getTarget();
    }
}
