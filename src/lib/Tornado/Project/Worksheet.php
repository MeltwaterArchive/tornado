<?php

namespace Tornado\Project;

use Tornado\Analyze\Analysis;
use Tornado\Analyze\Dimension;
use Tornado\Analyze\Dimension\Collection as DimensionCollection;
use Tornado\DataMapper\DataObjectInterface;
use Tornado\Project\Chart\Generator as ChartGenerator;

/**
 * Models a Tornado Project's Worksheet
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
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity,PHPMD.TooManyFields,PHPMD.ExcessivePublicCount,PHPMD.LongVariable,
 *     PHPMD.ExcessiveClassLength)
 */
class Worksheet implements DataObjectInterface
{
    /**
     * The id of this Worksheet
     *
     * @var integer
     */
    protected $id;

    /**
     * Workbook ID to which this Worksheet belongs
     *
     * @var integer
     */
    protected $workbookId;

    /**
     * Name of this Worksheet.
     *
     * @var string
     */
    protected $name;

    /**
     * Ordering rank of this worksheet.
     *
     * @var integer
     */
    protected $rank;

    /**
     * Comparison mode used in this Worksheet
     *
     * @var string
     */
    protected $comparison = ChartGenerator::MODE_COMPARE;

    /**
     * Measurement mode used in this Worksheet
     *
     * @var string
     */
    protected $measurement = ChartGenerator::MEASURE_UNIQUE_AUTHORS;

    /**
     * Chart visualization type used in this Worksheet
     *
     * @var string
     */
    protected $chartType = Chart::TYPE_TORNADO;

    /**
     * The analysis type used in this Worksheet
     *
     * @var string
     */
    protected $analysisType = Analysis::TYPE_FREQUENCY_DISTRIBUTION;

    /**
     * Secondary Recording ID used in this Worksheet for comparisons
     *
     * @var integer
     */
    protected $secondaryRecordingId;

    /**
     * Secondary Recording filters, analogous to $filters.
     *
     * @var \StdClass
     */
    protected $secondaryRecordingFilters;

    /**
     * DataSet ID used in this Worksheet, if any applicable
     *
     * @var integer
     */
    protected $baselineDataSetId;

    /**
     * Filters used in this Worksheet, stored as object with csdlQuery property
     *
     * @var \StdClass
     */
    protected $filters;

    /**
     * Dimensions targets used in this Worksheet
     *
     * @var \Tornado\Analyze\Dimension\Collection
     */
    protected $dimensions;

    /**
     * Start time of the analysis for this Worksheet to limit whole recording data processing
     *
     * @var integer
     */
    protected $start;

    /**
     * End time of the analysis for this Worksheet to limit whole recording data processing
     *
     * @var integer
     */
    protected $end;

    /**
     * The parent worksheet ID for this Worksheet
     *
     * @var integer|null
     */
    protected $parentWorksheetId;

    /**
     * Saves a json object with the defined display options for this worksheet
     *
     * @var \StdClass
     */
    protected $displayOptions;

    /**
     * Creation date of this Worksheet
     *
     * @var integer
     */
    protected $createdAt;

    /**
     * Updated date of this Worksheet
     *
     * @var integer
     */
    protected $updatedAt;

    /**
     * Gets the id of this worksheet.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the id of this worksheet.
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Gets the Workbook ID to which this Worksheet belongs.
     *
     * @return integer
     */
    public function getWorkbookId()
    {
        return $this->workbookId;
    }

    /**
     * Sets the Workbook ID to which this Worksheet belongs.
     *
     * @param integer $workbookId
     */
    public function setWorkbookId($workbookId)
    {
        $this->workbookId = $workbookId;
    }

    /**
     * Returns this Worksheet's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets this Worksheet's name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the rank of this Worksheet.
     *
     * @return integer
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Sets the rank of this Worksheet.
     *
     * @param integer $rank
     */
    public function setRank($rank)
    {
        $this->rank = $rank;
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
     * Sets the comparison mode for this Worksheet
     *
     * @param string $comparison
     *
     * @throws \InvalidArgumentException if comparison mode does not exist
     */
    public function setComparison($comparison)
    {
        $comparisonModes = [ChartGenerator::MODE_BASELINE, ChartGenerator::MODE_COMPARE];

        if (!in_array($comparison, $comparisonModes)) {
            throw new \InvalidArgumentException(sprintf(
                '%s expects one of the following comparison mode: %s',
                __METHOD__,
                join($comparisonModes, ', ')
            ));
        }

        $this->comparison = $comparison;
    }

    /**
     * Gets the comparison mode for this Worksheet
     *
     * @return string
     */
    public function getComparison()
    {
        return $this->comparison;
    }

    /**
     * Sets the measurement mode for this Worksheet
     *
     * @param string $measurement
     *
     * @throws \InvalidArgumentException if measurement mode does not exist
     */
    public function setMeasurement($measurement)
    {
        $measurementModes = [ChartGenerator::MEASURE_INTERACTIONS, ChartGenerator::MEASURE_UNIQUE_AUTHORS];

        if (!in_array($measurement, $measurementModes)) {
            throw new \InvalidArgumentException(sprintf(
                '%s expects one of the following measurement mode: %s',
                __METHOD__,
                join($measurementModes, ', ')
            ));
        }

        $this->measurement = $measurement;
    }

    /**
     * Gets the measurement mode for this Worksheet
     *
     * @return string
     */
    public function getMeasurement()
    {
        return $this->measurement;
    }

    /**
     * Sets the chart type for this Worksheet
     *
     * @param string $chartType
     *
     * @throws \InvalidArgumentException if chart type does not exist
     */
    public function setChartType($chartType)
    {
        $chartTypes = [
            Chart::TYPE_TORNADO,
            Chart::TYPE_HISTOGRAM,
            Chart::TYPE_TIME_SERIES,
            Chart::TYPE_SAMPLE,
        ];

        if (!in_array($chartType, $chartTypes)) {
            throw new \InvalidArgumentException(sprintf(
                '%s expects one of the following chart type: %s',
                __METHOD__,
                join($chartTypes, ', ')
            ));
        }

        $this->chartType = $chartType;
    }

    /**
     * Gets the chart type for this Worksheet
     *
     * @return string
     */
    public function getChartType()
    {
        return $this->chartType;
    }

    /**
     * Sets the analysis type for this Worksheet
     *
     * @param string $analysisType
     *
     * @throws \InvalidArgumentException if analysis type does not exist
     */
    public function setAnalysisType($analysisType)
    {
        $analysisTypes = [Analysis::TYPE_FREQUENCY_DISTRIBUTION, Analysis::TYPE_TIME_SERIES, Analysis::TYPE_SAMPLE];

        if (!in_array($analysisType, $analysisTypes)) {
            throw new \InvalidArgumentException(sprintf(
                '%s expects one of the following chart type: %s',
                __METHOD__,
                join($analysisTypes, ', ')
            ));
        }

        $this->analysisType = $analysisType;
    }

    /**
     * Gets the analysis type for this Worksheet
     *
     * @return string
     */
    public function getAnalysisType()
    {
        return $this->analysisType;
    }

    /**
     * Sets the secondary recording ID for this Worksheet
     *
     * @param integer $secondaryRecordingId
     */
    public function setSecondaryRecordingId($secondaryRecordingId)
    {
        $this->secondaryRecordingId = $secondaryRecordingId;
    }

    /**
     * Gets the secondary recording ID for this Worksheet
     *
     * @return string
     */
    public function getSecondaryRecordingId()
    {
        return $this->secondaryRecordingId;
    }

    /**
     * Sets the filters object for the secondary recording.
     *
     * @param mixed $filters
     */
    public function setSecondaryRecordingFilters($filters)
    {
        if (!$filters) {
            $filters = new \StdClass();
        }

        if (is_string($filters)) {
            $filters = json_decode($filters);
        }

        if (is_array($filters)) {
            $filters = (object)$filters;
        }

        if (!$filters instanceof \StdClass) {
            throw new \InvalidArgumentException(sprintf(
                '%s expect secondary recording filters to be json string or array. "%s" given.',
                __METHOD__,
                gettype($filters)
            ));
        }

        foreach ([
                     'keywords',
                     'links',
                     'country',
                     'region',
                     'gender',
                     'age',
                     'csdl',
                     'generated_csdl',
                     'start',
                     'end'
                 ] as $key) {
            if (!property_exists($filters, $key)) {
                $filters->{$key} = null;
            }
        }

        $this->secondaryRecordingFilters = $filters;
    }

    /**
     * Sets the secondary recording filters object from DB
     *
     * @param string $filters
     */
    protected function setRawSecondaryRecordingFilters($filters)
    {
        $this->secondaryRecordingFilters = json_decode($filters);
    }

    /**
     * Gets filters for the secondary recording.
     *
     * @return \StdClass
     */
    public function getSecondaryRecordingFilters()
    {
        return $this->secondaryRecordingFilters;
    }

    /**
     * Gets string representation of filters for the secondary recording.
     *
     * @return string
     */
    public function getRawSecondaryRecordingFilters()
    {
        if (!$this->secondaryRecordingFilters) {
            return json_encode(new \StdClass());
        }

        return json_encode($this->getSecondaryRecordingFilters());
    }

    /**
     * @param string $filterKey
     *
     * @return null
     */
    public function getSecondaryRecordingFilter($filterKey)
    {
        if (!$this->secondaryRecordingFilters instanceof \StdClass ||
            !property_exists($this->secondaryRecordingFilters, $filterKey)
        ) {
            return null;
        }

        return $this->secondaryRecordingFilters->{$filterKey};
    }

    /**
     * Sets the baseline DataSet ID for this Worksheet
     *
     * @param integer $baselineDataSetId
     */
    public function setBaselineDataSetId($baselineDataSetId)
    {
        $this->baselineDataSetId = $baselineDataSetId;
    }

    /**
     * Gets the baseline DataSet ID for this Worksheet
     *
     * @return string
     */
    public function getBaselineDataSetId()
    {
        return $this->baselineDataSetId;
    }

    /**
     * Sets the filters object for this Worksheet.
     *
     * @param mixed $filters
     */
    public function setFilters($filters)
    {
        if (!$filters) {
            $filters = new \StdClass();
        }

        if (is_string($filters)) {
            $filters = json_decode($filters);
        }

        if (is_array($filters)) {
            $filters = (object)$filters;
        }

        if (!$filters instanceof \StdClass) {
            throw new \InvalidArgumentException(sprintf(
                '%s expect filters to be json string or array. "%s" given.',
                __METHOD__,
                gettype($filters)
            ));
        }

        foreach ([
            'keywords',
            'links',
            'country',
            'region',
            'gender',
            'age',
            'csdl',
            'generated_csdl',
            'span',
            'interval'
        ] as $key) {
            if (!property_exists($filters, $key)) {
                $filters->{$key} = null;
            }
        }

        $this->filters = $filters;
    }

    /**
     * Sets the filters object from DB
     *
     * @param string $filters
     */
    protected function setRawFilters($filters)
    {
        $this->filters = json_decode($filters);
    }

    /**
     * Gets filter used in this Worksheet
     *
     * @return \StdClass
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Gets string representation of filters used in this Worksheet
     *
     * @return string
     */
    public function getRawFilters()
    {
        if (!$this->filters) {
            return json_encode(new \StdClass());
        }

        return json_encode($this->getFilters());
    }

    /**
     * @param string $filterKey
     *
     * @return null
     */
    public function getFilter($filterKey)
    {
        if (!$this->filters instanceof \StdClass || !property_exists($this->filters, $filterKey)) {
            return null;
        }

        return $this->filters->$filterKey;
    }

    /**
     * Set a single filter.
     *
     * @param string $filterKey Filter key.
     * @param mixed $value Filter value.
     */
    public function setFilter($filterKey, $value)
    {
        $filters = $this->getFilters();
        if (!$filters) {
            $filters = new \stdClass();
        }

        $filters->{$filterKey} = $value;
        $this->setFilters($filters);
    }

    /**
     * Get span.
     *
     * @return integer
     */
    public function getSpan()
    {
        $span = $this->getFilter('span');
        return $span ? $span : 1;
    }

    /**
     * Set span.
     *
     * @param integer $span Span.
     */
    public function setSpan($span)
    {
        $this->setFilter('span', $span);
    }

    /**
     * Get interval.
     *
     * @return string
     */
    public function getInterval()
    {
        $interval = $this->getFilter('interval');
        return $interval ? $interval : 'day';
    }

    /**
     * Set interval.
     *
     * @param string $interval Interval.
     */
    public function setInterval($interval)
    {
        $this->setFilter('interval', $interval);
    }

    /**
     * Sets dimensions of this Worksheet
     *
     * @param DimensionCollection|array $dimensions Can be an instance of Dimension\Collection or an array
     *                          of targets with optional threshold param
     */
    public function setDimensions($dimensions)
    {
        if (!$dimensions instanceof DimensionCollection) {
            if (!$dimensions) {
                return;
            }

            // only in case for fetching data from db
            if (is_string($dimensions)) {
                $dimensions = json_decode($dimensions, true);
            }

            // if $dimensions is not an array at this point then break
            if (!is_array($dimensions)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        '%s expects the argument to be a Dimension\Collection or '
                        . 'array of targets with optional threshold param.',
                        __METHOD__
                    )
                );
            }

            $collection = new DimensionCollection();
            foreach ($dimensions as $dimensionData) {
                $dimension = new Dimension($dimensionData['target']);

                if (isset($dimensionData['threshold'])) {
                    $dimension->setThreshold($dimensionData['threshold']);
                }

                $collection->addDimension($dimension);
            }

            $dimensions = $collection;
        }

        $this->dimensions = $dimensions;
    }

    /**
     * Gets the list of Dimensions
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * Gets dimensions of this Worksheet, comma separated
     *
     * @return string
     */
    public function getRawDimensions()
    {
        if (!$this->getDimensions()) {
            return null;
        }

        return json_encode((array)$this->getDimensions()->getDimensions());
    }

    /**
     * Sets the start time of the analysis for this Worksheet
     *
     * @param integer $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    /**
     * Gets the start time of the analysis for this Worksheet
     *
     * @return integer
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Sets the end time of the analysis for this Worksheet
     *
     * @param integer $end
     */
    public function setEnd($end)
    {
        $this->end = $end;
    }

    /**
     * Gets the end time of the analysis for this Worksheet
     *
     * @return int
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Gets the parent Worksheet ID
     *
     * @return integer|null
     */
    public function getParentWorksheetId()
    {
        return $this->parentWorksheetId;
    }

    /**
     * Sets the parent Worksheet ID
     *
     * @param integer|null $parentWorksheetId
     */
    public function setParentWorksheetId($parentWorksheetId)
    {
        $this->parentWorksheetId = $parentWorksheetId;
    }

    /**
     * Gets the display options
     *
     * @return null|array
     */
    public function getDisplayOptions()
    {
        return $this->displayOptions;
    }

    /**
     * Gets the raw display options
     *
     * @return null|string
     */
    public function getRawDisplayOptions()
    {
        if (!$this->displayOptions) {
            return json_encode(new \StdClass());
        }

        return json_encode($this->getDisplayOptions());
    }

    /**
     * Sets the display options
     *
     * @param null|string $displayOptions
     */
    public function setDisplayOptions($displayOptions)
    {
        if (!$displayOptions) {
            $displayOptions = new \StdClass();
        }

        if (is_string($displayOptions)) {
            $displayOptions = json_decode($displayOptions);
        }

        if (is_array($displayOptions)) {
            $displayOptions = (object)$displayOptions;
        }

        if (!$displayOptions instanceof \StdClass) {
            throw new \InvalidArgumentException(sprintf(
                '%s expect display options to be json string or array. "%s" given.',
                __METHOD__,
                gettype($displayOptions)
            ));
        }

        $this->displayOptions = $displayOptions;
    }

    /**
     * Sets raw display options
     *
     * @param string $displayOptions
     */
    public function setRawDisplayOptions($displayOptions)
    {
        if (is_string($displayOptions)) {
            $displayOptions = json_decode($displayOptions);
        }
        $this->displayOptions = $displayOptions;
    }

    /**
     * Gets the created at timestamp
     *
     * @return int
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Sets the created at timestamp
     *
     * @param int $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Gets the updated at timestamp
     *
     * @return int
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Sets the updated at timestamp
     *
     * @param int $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromArray(array $data)
    {
        $map = [
            'id' => 'setId',
            'workbook_id' => 'setWorkbookId',
            'name' => 'setName',
            'rank' => 'setRank',
            'comparison' => 'setComparison',
            'measurement' => 'setMeasurement',
            'chart_type' => 'setChartType',
            'analysis_type' => 'setAnalysisType',
            'secondary_recording_id' => 'setSecondaryRecordingId',
            'secondary_recording_filters' => 'setRawSecondaryRecordingFilters',
            'baseline_dataset_id' => 'setBaselineDataSetId',
            'filters' => 'setRawFilters',
            'dimensions' => 'setDimensions',
            'start' => 'setStart',
            'end' => 'setEnd',
            'parent_worksheet_id' => 'setParentWorksheetId',
            'display_options' => 'setRawDisplayOptions',
            'created_at' => 'setCreatedAt',
            'updated_at' => 'setUpdatedAt'
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
            'workbook_id' => 'getWorkbookId',
            'name' => 'getName',
            'rank' => 'getRank',
            'comparison' => 'getComparison',
            'measurement' => 'getMeasurement',
            'chart_type' => 'getChartType',
            'analysis_type' => 'getAnalysisType',
            'secondary_recording_id' => 'getSecondaryRecordingId',
            'secondary_recording_filters' => 'getRawSecondaryRecordingFilters',
            'baseline_dataset_id' => 'getBaselineDataSetId',
            'filters' => 'getRawFilters',
            'dimensions' => 'getRawDimensions',
            'start' => 'getStart',
            'end' => 'getEnd',
            'parent_worksheet_id' => 'getParentWorksheetId',
            'display_options' => 'getRawDisplayOptions',
            'created_at' => 'getCreatedAt',
            'updated_at' => 'getUpdatedAt'
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

        // in JSON use an array of dimensions
        $data['dimensions'] = json_decode($data['dimensions'], true);
        // in JSON use an array filtering info
        $data['secondary_recording_filters'] = json_decode($data['secondary_recording_filters']);
        $data['filters'] = json_decode($data['filters']);

        // extract few more items
        $data['span'] = $this->getSpan();
        $data['interval'] = $this->getInterval();

        // in JSON use an array of display options
        $data['display_options'] = json_decode($data['display_options']);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        $this->id = null;
        $this->createdAt = null;
        $this->updatedAt = null;
        $this->rank = null;
    }
}
