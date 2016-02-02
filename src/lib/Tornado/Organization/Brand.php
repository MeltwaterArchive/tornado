<?php

namespace Tornado\Organization;

use Tornado\DataMapper\DataObjectInterface;

/**
 * Models a Tornado Brand
 *
 * @author Christopher Hoult <chris.hoult@datasift.com>
 */
class Brand implements DataObjectInterface
{

    /**
     * Target permission access levels.
     */
    const PERM_PREMIUM = 'premium';
    const PERM_INTERNAL = 'internal';
    const PERM_EVERYONE = 'everyone';

    /**
     * The id of this Brand
     *
     * @var integer
     */
    protected $id;

    /**
     * The id of the Agency this Brand belongs to
     *
     * @var integer
     */
    protected $agencyId;

    /**
     * The name of this Brand
     *
     * @var string
     */
    protected $name;

    /**
     * The id of this Brand's DataSift Identity
     *
     * @var string
     */
    protected $datasiftIdentityId;

    /**
     * This Brand's DataSift API Key
     *
     * @var string
     */
    protected $datasiftApiKey;

    /**
     * This Brand's permissions level for targets.
     *
     * @var array
     */
    protected $targetPermissions = [];

    /**
     * Gets this Brand's Id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets this Brand's Id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Gets the id of this Brand's Agency
     *
     * @return integer
     */
    public function getAgencyId()
    {
        return $this->agencyId;
    }

    /**
     * Sets the id of this Brand's Agency
     *
     * @param integer $agencyId
     */
    public function setAgencyId($agencyId)
    {
        $this->agencyId = $agencyId;
    }

    /**
     * Returns this Brand's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets this Brand's name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the id of this Brand's DataSift Identity
     *
     * @return string
     */
    public function getDatasiftIdentityId()
    {
        return $this->datasiftIdentityId;
    }

    /**
     * Sets the id of this Brand's DataSift Identity
     *
     * @param string $datasiftIdentityId
     */
    public function setDatasiftIdentityId($datasiftIdentityId)
    {
        $this->datasiftIdentityId = $datasiftIdentityId;
    }

    /**
     * Gets this Brand's DataSift API Key
     *
     * @return string
     */
    public function getDatasiftApiKey()
    {
        return $this->datasiftApiKey;
    }

    /**
     * Sets this Brand's DataSift API Key
     *
     * @param string $datasiftApiKey
     */
    public function setDatasiftApiKey($datasiftApiKey)
    {
        $this->datasiftApiKey = $datasiftApiKey;
    }

    /**
     * Sets this Brand's target permissions.
     *
     * @param array $permissions Array of permissions, must be values of self::PERM_* constants. Can be empty.
     *
     * @throws \InvalidArgumentException When an invalid permission given.
     */
    public function setTargetPermissions(array $permissions)
    {
        foreach ($permissions as $permission) {
            if (!in_array($permission, [self::PERM_EVERYONE, self::PERM_INTERNAL, self::PERM_PREMIUM])) {
                throw new \InvalidArgumentException(sprintf(
                    'Brand Target Permission must be one of Brand::PERM_* constants, %s given',
                    $permission
                ));
            }
        }

        $this->targetPermissions = $permissions;
    }

    /**
     * Gets this Brand's target permissions.
     *
     * @return array
     */
    public function getTargetPermissions()
    {
        return $this->targetPermissions;
    }

    /**
     * Sets this Brand's target permission from a CSV list.
     *
     * @param string $permissions The permissions as CSV list.
     */
    public function setRawTargetPermissions($permissions)
    {
        $permissions = empty($permissions) ? [] : explode(',', $permissions);
        $this->setTargetPermissions($permissions);
    }

    /**
     * Gets this Brand's target permissions as a CSV list.
     *
     * @return string
     */
    public function getRawTargetPermissions()
    {
        return implode(',', $this->targetPermissions);
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
            'agency_id' => 'setAgencyId',
            'agencyId' => 'setAgencyId',
            'name' => 'setName',
            'datasift_identity_id' => 'setDatasiftIdentityId',
            'datasiftIdentityId' => 'setDatasiftIdentityId',
            'datasift_apikey' => 'setDatasiftApiKey',
            'datasiftApikey' => 'setDatasiftApiKey',
            'target_permissions' => 'setRawTargetPermissions',
            'targetPermissions' => 'setRawTargetPermissions',
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
            'agency_id' => 'getAgencyId',
            'name' => 'getName',
            'datasift_identity_id' => 'getDatasiftIdentityId',
            'datasift_apikey' => 'getDatasiftApiKey',
            'target_permissions' => 'getRawTargetPermissions'
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
        $data = $this->toArray();
        // jsonize an array, not csv
        $data['target_permissions'] = $this->getTargetPermissions();
        return $data;
    }
}
