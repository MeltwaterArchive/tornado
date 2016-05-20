<?php

namespace Tornado\Project\Recording;

use Tornado\DataMapper\DataObjectInterface;

/**
 * Models a Sample element of a Recording
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
class Sample implements DataObjectInterface
{
    /**
     * Sets the default number of interaction to return
     */
    const RESULT_LIMIT = 10;
    
    /**
     * The ID of this Sample object
     *
     * @var integer
     */
    private $id;

    /**
     * The ID of the Recording this Sample is from
     *
     * @var integer
     */
    private $recordingId;

    /**
     * A hashed sample filter
     *
     * @var integer
     */
    private $filterHash;

    /**
     * The data that comes back as part of this Sample
     *
     * @var string
     */
    private $data;

    /**
     * The timestamp this Sample item was created
     *
     * @var integer
     */
    private $createdAt;

    /**
     * Gets this Sample's ID
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets this Sample's ID
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Gets the id of the Recording this Sample is from
     *
     * @return integer
     */
    public function getRecordingId()
    {
        return $this->recordingId;
    }

    /**
     * Sets the id of the Recording this Sample is from
     *
     * @param integer $recordingId
     */
    public function setRecordingId($recordingId)
    {
        $this->recordingId = $recordingId;
    }

    /**
     * @return int
     */
    public function getFilterHash()
    {
        return $this->filterHash;
    }

    /**
     * @param int $filterHash
     */
    public function setFilterHash($filterHash)
    {
        $this->filterHash = $filterHash;
    }

    /**
     * Gets the data for this Sample
     *
     * @return \StdClass
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Sets the data object for this Sample
     *
     * @param string $data
     */
    public function setData($data)
    {
        if (!$data) {
            $data = new \StdClass();
        }

        if (is_string($data)) {
            $data = json_decode($data);
        }

        if (is_array($data)) {
            $data = (object)$data;
        }

        $this->data = $data;
    }

    /**
     * Gets string representation of this Sample's data
     *
     * @return string
     */
    public function getRawData()
    {
        if (!$this->data) {
            return json_encode(new \StdClass());
        }

        return json_encode($this->getData());
    }

    /**
     * Sets the data object from the database
     *
     * @param string $data
     */
    public function setRawData($data)
    {
        $this->data = json_decode($data);
    }

    /**
     * Gets the created at timestamp for this Sample
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Sets the created at timestamp for this Sample
     *
     * @param integer $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
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
            'recording_id' => 'setRecordingId',
            'filter_hash' => 'setFilterHash',
            'data' => 'setRawData',
            'created_at' => 'setCreatedAt'
        ];

        foreach ($map as $key => $setter) {
            if (isset($data[$key])) {
                $this->{$setter}($data[$key]);
            }
        }
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
    public function toArray()
    {
        $map = [
            'id' => 'getId',
            'recording_id' => 'getRecordingId',
            'filter_hash' => 'getFilterHash',
            'data' => 'getRawData',
            'created_at' => 'getCreatedAt'
        ];

        $data = [];
        foreach ($map as $key => $getter) {
            $data[$key] = $this->{$getter}();
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = $this->toArray();
        $data['data'] = json_decode($data['data']);
        return $data;
    }
}
