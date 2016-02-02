<?php

namespace Tornado\Project;

use stdClass;

use Tornado\DataMapper\DataObjectInterface;

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
class Chart implements DataObjectInterface
{
    /**
     * A tornado chart
     */
    const TYPE_TORNADO = 'tornado';
    const TYPE_HISTOGRAM = 'histogram';
    const TYPE_TIME_SERIES = 'timeseries';

    /**
     * The different measure types
     */
    const MEASURE_INTERACTIONS = 'interactions';
    const MEASURE_UNIQUE_AUTHORS = 'unique_authors';

    /**
     * The id of this Chart
     *
     * @var integer
     */
    protected $id;

    /**
     * Worksheet ID to which this Chart belongs.
     *
     * @var integer
     */
    protected $worksheetId;

    /**
     * Name of this Chart.
     *
     * @var string
     */
    protected $name;

    /**
     * Ordering rank of this project.
     *
     * @var integer
     */
    protected $rank;

    /**
     * Chart type.
     *
     * @var string
     */
    protected $type;

    /**
     * Chart DATA, stored as JSON.
     *
     * @var string
     */
    protected $data;

    /**
     * Chart data, decoded to object.
     *
     * @var stdClass
     */
    protected $decodedData = [];

    /**
     * Gets the id of this Chart.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the id of this Chart.
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Gets the Worksheet ID to which this Chart belongs.
     *
     * @return integer
     */
    public function getWorksheetId()
    {
        return $this->worksheetId;
    }

    /**
     * Sets the Worksheet ID to which this Chart belongs.
     *
     * @param integer $worksheetId
     */
    public function setWorksheetId($worksheetId)
    {
        $this->worksheetId = $worksheetId;
    }

    /**
     * Returns this Chart's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets this Chart's name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the rank of this Chart.
     *
     * @return integer
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Sets the rank of this Chart.
     *
     * @param integer $rank
     */
    public function setRank($rank)
    {
        $this->rank = $rank;
    }

    /**
     * Gets the type of this Chart.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the type of this Chart.
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Gets the data for this Chart.
     *
     * @param string $measure
     *
     * @return stdClass|null
     */
    public function getData($measure = false)
    {
        if (empty($this->decodedData)) {
            $this->decodedData = json_decode($this->data);
        }

        if ($measure) {
            return (isset($this->decodedData[$measure])) ? $this->decodedData[$measure] : null;
        }

        return $this->decodedData;
    }

    /**
     * Sets the data for this Chart.
     *
     * @param object $data
     */
    public function setData($data)
    {
        $this->data = json_encode($data);
        $this->decodedData = $data;
    }

    /**
     * Gets raw JSON encoded data.
     *
     * @return string
     */
    public function getRawData()
    {
        return $this->data;
    }

    /**
     * Sets raw JSON encoded data.
     *
     * @param string $rawData JSON encoded data.
     */
    public function setRawData($rawData)
    {
        $this->data = $rawData;
        $this->decodedData = array();
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
            'worksheet_id' => 'setWorksheetId',
            'name' => 'setName',
            'rank' => 'setRank',
            'type' => 'setType',
            'data' => 'setRawData'
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
            'worksheet_id' => 'getWorksheetId',
            'name' => 'getName',
            'rank' => 'getRank',
            'type' => 'getType',
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
        return $this->toArray();
    }
}
