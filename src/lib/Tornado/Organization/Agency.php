<?php

namespace Tornado\Organization;

use Tornado\DataMapper\DataObjectInterface;

/**
 * Models a Tornado Agency
 *
 * @author Christopher Hoult <chris.hoult@datasift.com>
 */
class Agency implements DataObjectInterface
{

    /**
     * The id of this Agency
     *
     * @var integer
     */
    protected $id;

    /**
     * The id of the Organization this Agency belongs to
     *
     * @var integer
     */
    protected $organizationId;

    /**
     * The name of this Agency
     *
     * @var string
     */
    protected $name;

    /**
     * This Agency's DataSift API Username
     *
     * @var string
     */
    protected $datasiftUsername;

    /**
     * This Agency's DataSift API Key
     *
     * @var string
     */
    protected $datasiftApiKey;

    /**
     * The skin for this Agency to use; the name of this will have .css
     * appended as appropriate. This overrides the parent Organization's skin
     *
     * @var string|null
     */
    protected $skin;

    /**
     * Gets this Agency's Id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets this Agency's Id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Gets the id of this Agency's Organization
     *
     * @return integer
     */
    public function getOrganizationId()
    {
        return $this->organizationId;
    }

    /**
     * Sets the id of this Agency's Organization
     *
     * @param integer $organizationId
     */
    public function setOrganizationId($organizationId)
    {
        $this->organizationId = $organizationId;
    }

    /**
     * Returns this Agency's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets this Agency's name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets this Agency's DataSift API username
     *
     * @return string
     */
    public function getDatasiftUsername()
    {
        return $this->datasiftUsername;
    }

    /**
     * Sets this Agency's DataSift API username
     *
     * @param string $datasiftUsername
     */
    public function setDatasiftUsername($datasiftUsername)
    {
        $this->datasiftUsername = $datasiftUsername;
    }

    /**
     * Gets this Agency's DataSift API Key
     *
     * @return string
     */
    public function getDatasiftApiKey()
    {
        return $this->datasiftApiKey;
    }

    /**
     * Sets this Agency's DataSift API Key
     *
     * @param string $datasiftApiKey
     */
    public function setDatasiftApiKey($datasiftApiKey)
    {
        $this->datasiftApiKey = $datasiftApiKey;
    }

    /**
     * Gets the skin for this Agency
     *
     * @param string $skin
     */
    public function getSkin()
    {
        return $this->skin;
    }

    /**
     * Sets the skin for this Agency
     *
     * @param string $skin
     */
    public function setSkin($skin)
    {
        $this->skin = $skin;
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
            'organization_id' => 'setOrganizationId',
            'organizationId' => 'setOrganizationId',
            'name' => 'setName',
            'datasift_username' => 'setDatasiftUsername',
            'datasiftUsername' => 'setDatasiftUsername',
            'datasift_apikey' => 'setDatasiftApiKey',
            'datasiftApikey' => 'setDatasiftApiKey',
            'skin' => 'setSkin'
        ];

        foreach ($map as $key => $setter) {
            if (array_key_exists($key, $data)) {
                $this->{$setter}($data[$key]);
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
            'organization_id' => 'getOrganizationId',
            'name' => 'getName',
            'datasift_username' => 'getDatasiftUsername',
            'datasift_apikey' => 'getDatasiftApiKey',
            'skin' => 'getSkin'
        ];

        $ret = [];
        foreach ($map as $key => $getter) {
            $ret[$key] = $this->{$getter}();
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
