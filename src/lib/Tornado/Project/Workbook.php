<?php

namespace Tornado\Project;

use Tornado\DataMapper\DataObjectInterface;

use Tornado\Project\Worksheet;

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
 */
class Workbook implements DataObjectInterface
{
    /**
     * The ID of this Workbook.
     *
     * @var integer
     */
    protected $id;

    /**
     * Project ID to which this Workbook belongs.
     *
     * @var integer
     */
    protected $projectId;

    /**
     * Name of this Workbook.
     *
     * @var string
     */
    protected $name;

    /**
     * Main Recording ID used in this Workbook for analysis. Must be defined to perform analysis
     *
     * @var integer
     */
    protected $recordingId;

    /**
     * Ordering rank of this workbook.
     *
     * @var integer
     */
    protected $rank;

    /**
     * Worksheets in this Workbook.
     *
     * @var array
     */
    protected $worksheets = [];

    /**
     * Gets the id of this Workbook.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the id of this Workbook.
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Gets the Project ID to which this Workbook belongs.
     *
     * @return integer
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * Sets the Project ID to which this Workbook belongs.
     *
     * @param integer $projectId
     */
    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
    }

    /**
     * Returns this Workbook's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets this Workbook's name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Sets the recording ID for this Workbook
     *
     * @param integer $recordingId
     */
    public function setRecordingId($recordingId)
    {
        $this->recordingId = $recordingId;
    }

    /**
     * Gets the recording ID for this Workbook
     *
     * @return string
     */
    public function getRecordingId()
    {
        return $this->recordingId;
    }

    /**
     * Gets the rank of this Workbook.
     *
     * @return integer
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Sets the rank of this Workbook.
     *
     * @param integer $rank
     */
    public function setRank($rank)
    {
        $this->rank = $rank;
    }

    /**
     * Sets worksheets for this workbook.
     *
     * Note: this is not persisted and is simply a convenience method to link worksheets with workbook.
     *
     * @param array $worksheets Array of Worksheets
     */
    public function setWorksheets(array $worksheets)
    {
        foreach ($worksheets as $worksheet) {
            $this->addWorksheet($worksheet);
        }
    }

    /**
     * Adds a worksheet to this workbook's worksheet list.
     *
     * Note: this is not persisted and is simply a convenience method to link worksheets with workbook.
     *
     * @param Worksheet $worksheet Worksheet.
     *
     * @throws \RuntimeException When the worksheet does not belong to this workbook.
     */
    public function addWorksheet(Worksheet $worksheet)
    {
        if ($worksheet->getWorkbookId() !== $this->getId()) {
            throw new \RuntimeException(sprintf(
                'Trying to add worksheet %d to a workbook %d that is not its owner',
                $worksheet->getId(),
                $this->getId()
            ));
        }

        $this->worksheets[] = $worksheet;
    }

    /**
     * Returns worksheets of this workbook.
     *
     * Note: This doesn't fetch the worksheets from the database and is simply a convenience method to access
     * worksheets that were previously added to this workbook.
     *
     * @return array
     */
    public function getWorksheets()
    {
        return $this->worksheets;
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromArray(array $data)
    {
        $map = [
            'id' => 'setId',
            'project_id' => 'setProjectId',
            'name' => 'setName',
            'recording_id' => 'setRecordingId',
            'rank' => 'setRank'
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
    public function toArray()
    {
        $map = [
            'id' => 'getId',
            'project_id' => 'getProjectId',
            'name' => 'getName',
            'recording_id' => 'getRecordingId',
            'rank' => 'getRank'
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
        // also serialize worksheets array
        $data['worksheets'] = $this->worksheets;
        return $data;
    }
}
