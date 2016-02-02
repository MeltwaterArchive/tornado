<?php

namespace Tornado\Project;

use Tornado\DataMapper\DataObjectInterface;

/**
 * Models a Tornado Brand's Project
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
class Project implements DataObjectInterface
{
    /**
     * User type constants:
     */
    // normal user
    const TYPE_NORMAL = 0;
    // user created by using the tornado api
    const TYPE_API = 1;

    // limits the list of recording for the project to only those assigned by API
    const RECORDING_FILTER_API = 1;

    /**
     * The id of this Project
     *
     * @var integer
     */
    protected $id;

    /**
     * The id of the Brand this Project belongs to
     *
     * @var integer
     */
    protected $brandId;

    /**
     * The name of this Project
     *
     * @var string
     */
    protected $name;

    /**
     * The Project type
     *
     * @var int
     */
    protected $type = self::TYPE_NORMAL;

    /**
     * Defines recording filtering for the Project
     *
     * @var int
     */
    protected $recordingFilter;

    /**
     * Is this project fresh? Ie. empty and no default workbooks created.
     *
     * @var boolean
     */
    protected $fresh = 1;

    /**
     * Creation date of this Project
     *
     * @var integer
     */
    protected $createdAt;

    /**
     * Gets this Project's Id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets this Project's Id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Gets the id of this Project's Brand
     *
     * @return integer
     */
    public function getBrandId()
    {
        return $this->brandId;
    }

    /**
     * Sets the id of Project's Brand
     *
     * @param integer $brandId
     */
    public function setBrandId($brandId)
    {
        $this->brandId = $brandId;
    }

    /**
     * Returns this Project's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets this Project's name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Sets the Project's type
     *
     * @param integer $type
     *
     * @throws \InvalidArgumentException if not supported type given
     */
    public function setType($type)
    {
        if (!in_array($type, [self::TYPE_NORMAL, self::TYPE_API])) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported Project type: %s.',
                $type
            ));
        }

        $this->type = $type;
    }

    /**
     * Gets the Project's type.
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets type of recording filtering for this Project
     *
     * @param integer $recordingFilter
     */
    public function setRecordingFilter($recordingFilter)
    {
        $this->recordingFilter = $recordingFilter;
    }

    /**
     * Gets type of recording filtering for this Project
     *
     * @return integer
     */
    public function getRecordingFilter()
    {
        return $this->recordingFilter;
    }

    /**
     * Set freshness of this project.
     *
     * @param integer $fresh 0 or 1.
     */
    public function setFresh($fresh)
    {
        $this->fresh = intval($fresh);
    }

    /**
     * Get the freshness of this project.
     *
     * @return integer
     */
    public function getFresh()
    {
        return $this->fresh;
    }

    /**
     * Is this project fresh?
     *
     * @return boolean
     */
    public function isFresh()
    {
        return (bool)$this->fresh;
    }

    /**
     * Sets this Project creation date
     *
     * @param integer $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Gets this Project creation date
     *
     * @return int
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
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
     *
     * @codeCoverageIgnore
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
            'brand_id' => 'setBrandId',
            'name' => 'setName',
            'type' => 'setType',
            'recording_filter' => 'setRecordingFilter',
            'fresh' => 'setFresh',
            'created_at' => 'setCreatedAt'
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
            'name' => 'getName',
            'type' => 'getType',
            'recording_filter' => 'getRecordingFilter',
            'fresh' => 'getFresh',
            'created_at' => 'getCreatedAt'
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
