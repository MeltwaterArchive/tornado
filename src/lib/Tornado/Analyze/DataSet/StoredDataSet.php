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
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
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

    const SCHEDULE_UNITS_DAY = 'day';
    const SCHEDULE_UNITS_WEEK = 'week';
    const SCHEDULE_UNITS_FORTNIGHT = 'fortnight';
    const SCHEDULE_UNITS_MONTH = 'month';

    const TIMERANGE_DAY = 'day';
    const TIMERANGE_WEEK = 'week';
    const TIMERANGE_FORTNIGHT = 'fortnight';
    const TIMERANGE_MONTH = 'month';

    const STATUS_RUNNING = 'running';
    const STATUS_PAUSED = 'paused';

    /**
     * The id of this DataSet
     *
     * @var integer
     */
    protected $id;

    /**
     * The id of the Brand this DataSet belongs to
     *
     * @var integer
     */
    protected $brandId;

    /**
     * The id of the Recording this DataSet is generated from
     *
     * @var integer
     */
    protected $recordingId;

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
     * The analysis type that generates this DataSet
     *
     * @var string
     */
    protected $analysisType;

    /**
     * The secondary filter (CSDL)
     *
     * @var string
     */
    protected $filter;

    /**
     * The schedule on which to run
     *
     * @var integer
     */
    protected $schedule;

    /**
     * The schedule units on which to run
     *
     * @var string
     */
    protected $scheduleUnits;

    /**
     * The time range over which to run the analysis
     *
     * @var string
     */
    protected $timeRange;

    /**
     * The status of this DataSet's scheduling
     *
     * @var string
     */
    protected $status;

    /**
     * The date/time this DataSet was last refreshed
     *
     * @var integer
     */
    protected $lastRefreshed;

    /**
     * When this DataSet was created
     *
     * @var integer
     */
    protected $createdAt;

    /**
     * When this DataSet was last updated
     *
     * @var integer
     */
    protected $updatedAt;

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
     * Gets the id of the Brand this DataSet is for
     *
     * @return integer
     */
    public function getBrandId()
    {
        return $this->brandId;
    }

    /**
     * Sets the id of the Brand this DataSet is for
     *
     * @param integer $brandId
     */
    public function setBrandId($brandId)
    {
        $this->brandId = $brandId;
    }

    /**
     * Gets the id of the Recording this DataSet is for
     *
     * @return integer
     */
    public function getRecordingId()
    {
        return $this->recordingId;
    }

    /**
     * Sets the id of the Recording this DataSet is for
     *
     * @param integer $recordingId
     */
    public function setRecordingId($recordingId)
    {
        $this->recordingId = $recordingId;
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
        if (!$this->getDimensions()) {
            return '';
        }

        $targets = array_map(
            function ($dimension) {
                return $dimension->getTarget();
            },
            $this->getDimensions()->getDimensions()
        );

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
        if (is_string($dimensions)) {
            $dimensions = explode(',', $dimensions);
        }

        if (!$dimensions instanceof DimensionCollection) {
            if (!is_array($dimensions)) {
                throw new \InvalidArgumentException('Dimensions must be a collection of Dimensions, an array or csv');
            }
            $dimensions = $this->getDimensionCollection($dimensions);
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
     * Gets the analysis type of this dataset
     *
     * @return string
     */
    public function getAnalysisType()
    {
        return $this->analysisType;
    }

    /**
     * Sets the analysis type of this dataset
     *
     * @param string $analysisType
     */
    public function setAnalysisType($analysisType)
    {
        $this->analysisType = $analysisType;
    }

    /**
     * Gets the secondary filter for this DataSet
     *
     * @return string
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Sets the secondary filter for this DataSet
     *
     * @param string $filter
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
    }

    /**
     * Gets the number of units to schedule this DataSet at
     *
     * @return integer
     */
    public function getSchedule()
    {
        return $this->schedule;
    }

    /**
     * Gets the number of units to schedule this DataSet at
     *
     * @param integer $schedule
     */
    public function setSchedule($schedule)
    {
        $this->schedule = $schedule;
    }

    /**
     * Gets the units for the scheduled time period
     *
     * @return string
     */
    public function getScheduleUnits()
    {
        return $this->scheduleUnits;
    }

    /**
     * Sets the units for the scheduled time period
     *
     * @param string $scheduleUnits
     */
    public function setScheduleUnits($scheduleUnits)
    {
        $this->scheduleUnits = $scheduleUnits;
    }

    /**
     * Gets the time range for this DataSet
     *
     * @return string
     */
    public function getTimeRange()
    {
        return $this->timeRange;
    }

    /**
     * Sets the time range for this DataSet
     *
     * @param string $timeRange
     */
    public function setTimeRange($timeRange)
    {
        $this->timeRange = $timeRange;
    }

    /**
     * Gets this DataSet's status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets this DataSet's status
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Gets the timestamp for when this DataSet was last refreshed
     *
     * @return integer
     */
    public function getLastRefreshed()
    {
        return $this->lastRefreshed;
    }

    /**
     * Sets the timestamp for when this DataSet was last refreshed
     *
     * @param integer $lastRefreshed
     */
    public function setLastRefreshed($lastRefreshed)
    {
        $this->lastRefreshed = $lastRefreshed;
    }

    /**
     * Gets the timestamp for when this DataSet was created
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Sets the timestamp for when this DataSet was created
     *
     * @param integer $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Gets the timestamp for when this DataSet was last updated
     *
     * @return integer
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Sets the timestamp for when this DataSet was last updated
     *
     * @param integer $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
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
     * Returns the start of this time range
     *
     * @return integer
     */
    public function getStart()
    {
        $map = [
            static::TIMERANGE_DAY => 1,
            static::TIMERANGE_WEEK => 7,
            static::TIMERANGE_MONTH => 31
        ];

        return (isset($map[$this->timeRange])) ? strtotime('midnight') - $map[$this->timeRange] * 86400 : null;
    }

    /**
     * Returns the end of this time range
     *
     * @return integer
     */
    public function getEnd()
    {
        $map = [
            static::TIMERANGE_DAY => 1,
            static::TIMERANGE_WEEK => 7,
            static::TIMERANGE_MONTH => 31
        ];
        return (isset($map[$this->timeRange])) ? $this->getStart() + $map[$this->timeRange] * 86400 : null;
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromArray(array $data)
    {
        $map = [
            'id' => 'setId',
            'brand_id' => 'setBrandId',
            'brandId' => 'setBrandId',
            'recording_id' => 'setRecordingId',
            'recordingId' => 'setRecordingId',
            'name' => 'setName',
            'dimensions' => 'setDimensions',
            'visibility' => 'setVisibility',
            'data' => 'setData',
            'analysis_type' => 'setAnalysisType',
            'analysisType' => 'setAnalysisType',
            'filter' => 'setFilter',
            'schedule' => 'setSchedule',
            'schedule_units' => 'setScheduleUnits',
            'scheduleUnits' => 'setScheduleUnits',
            'time_range' => 'setTimeRange',
            'timeRange' => 'setTimeRange',
            'status' => 'setStatus',
            'last_refreshed' => 'setLastRefreshed',
            'created_at' => 'setCreatedAt',
            'updated_at' => 'setUpdatedAt',
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
            'brand_id' => 'getBrandId',
            'recording_id' => 'getRecordingId',
            'name' => 'getName',
            'dimensions' => 'getRawDimensions',
            'visibility' => 'getVisibility',
            // return json encoded data
            'data' => 'getRawData',
            'analysis_type' => 'getAnalysisType',
            'filter' => 'getFilter',
            'schedule' => 'getSchedule',
            'schedule_units' => 'getScheduleUnits',
            'time_range' => 'getTimeRange',
            'status' => 'getStatus',
            'last_refreshed' => 'getLastRefreshed',
            'created_at' => 'getCreatedAt',
            'updated_at' => 'getUpdatedAt',
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

    /**
     * Converts a list of targets into a Dimension Collection
     *
     * @param array $dimensions
     *
     * @return \Tornado\Analyze\Dimension\Collection
     */
    private function getDimensionCollection(array $dimensions)
    {
        $collection = new DimensionCollection();
        array_walk(
            $dimensions,
            function ($target) use ($collection) {
                if (trim($target)) {
                    $collection->addDimension(new Dimension($target));
                }
            }
        );

        return $collection;
    }
}
