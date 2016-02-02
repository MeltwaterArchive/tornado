<?php

namespace Tornado\Analyze\DataSet;

use Tornado\Analyze\Dimension\Collection as DimensionCollection;
use Tornado\Analyze\Dimension;
use Tornado\Analyze\DataSet;
use Tornado\DataMapper\DataObjectInterface;

/**
 * Models a Tornado DataSet that is stored in a database
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
class StoredDataSet extends DataSet implements DataObjectInterface
{

    /**
     * Private dataset.
     */
    const VISIBILITY_PRIVATE = 'private';

    /**
     * Public dataset.
     */
    const VISIBILITY_PUBLIC = 'public';

    /**
     * The id of this DataSet
     *
     * @var integer
     */
    protected $id;

    /**
     * Name of this DataSet
     *
     * @var string
     */
    protected $name;

    /**
     * Visibility of this DataSet
     *
     * @var string
     */
    protected $visibility;

    /**
     * Constructs a new DataSet
     *
     * @param \Tornado\Analyze\Dimension\Collection $dimensions
     * @param array                                 $data
     */
    public function __construct(DimensionCollection $dimensions = null, array $data = [])
    {
        $this->dimensions = $dimensions;
        $this->data = $data;
    }

    /**
     * Gets the id of this DataSet
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the id of this DataSet
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Gets the name of this DataSet
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name of this DataSet
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets visibility of this DataSet, one of self::VISIBILITY_* constants.
     *
     * @return string
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Sets visibility of this DataSet.
     *
     * @param string $visibility One of self::VISIBILITY_* constants.
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
    }

    /**
     * Gets dimensions of this DataSet as a CSV list.
     *
     * @return string
     */
    public function getRawDimensions()
    {
        $targets = [];
        foreach ($this->getDimensions()->getDimensions() as $dimension) {
            $targets[] = $dimension->getTarget();
        }
        return implode(',', $targets);
    }

    /**
     * Sets dimensions of this DataSet.
     *
     * @param mixed $dimensions Can be an instance of Dimension\Collection, an array
     *                          of targets or a CSV list.
     */
    public function setDimensions($dimensions)
    {
        if (!$dimensions instanceof DimensionCollection) {
            if (is_string($dimensions)) {
                $dimensions = explode(',', $dimensions);
            }

            // if $dimensions is not an array at this point then break
            if (!is_array($dimensions)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        '%s expects the argument to be a Dimension\Collection, '
                        . 'array of targets or a CSV list of targets',
                        __METHOD__
                    )
                );
            }

            $collection = new DimensionCollection();
            foreach ($dimensions as $target) {
                $collection->addDimension(new Dimension($target));
            }
            $dimensions = $collection;
        }

        $this->dimensions = $dimensions;
    }

    /**
     * Gets JSON encoded data.
     *
     * @return string
     */
    public function getRawData()
    {
        return json_encode($this->data);
    }

    /**
     * Sets the data.
     *
     * @param array|string $data Either an array of data or JSON encoded data.
     */
    public function setData($data)
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryKey()
    {
        return $this->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function setPrimaryKey($key)
    {
        $this->setId($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryKeyName()
    {
        return 'id';
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromArray(array $data)
    {
        $map = [
            'id' => 'setId',
            'name' => 'setName',
            'dimensions' => 'setDimensions',
            'visibility' => 'setVisibility',
            'data' => 'setData'
        ];

        foreach ($map as $key => $setter) {
            if (array_key_exists($key, $data)) {
                $this->$setter($data[$key]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $map = [
            'id' => 'getId',
            'name' => 'getName',
            'dimensions' => 'getRawDimensions',
            'visibility' => 'getVisibility',
            // return json encoded data
            'data' => 'getRawData'
        ];

        $ret = [];
        foreach ($map as $key => $getter) {
            $ret[$key] = $this->$getter();
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = $this->toArray();
        // make the list of dimensions an array in JSON
        $data['dimensions'] = explode(',', $data['dimensions']);
        unset($data['data']);
        
        return $data;
    }
}
