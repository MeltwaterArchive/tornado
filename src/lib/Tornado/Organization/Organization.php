<?php

namespace Tornado\Organization;

use Tornado\DataMapper\DataObjectInterface;

/**
 * Models a Tornado Organization, or "Parent Agency"
 *
 * @author Christopher Hoult <chris.hoult@datasift.com>
 */
class Organization implements DataObjectInterface
{

    /**
     * The id of this Organization
     *
     * @var integer
     */
    protected $id;

    /**
     * The name of this Organization
     *
     * @var string
     */
    protected $name;

    /**
     * The skin for this Organization to use; the name of this will have .css
     * appended as appropriate
     *
     * @var string|null
     */
    protected $skin;

    /**
     * The JWT secret for this Organization
     *
     * @var string|null
     */
    protected $jwtSecret;

    /**
     * The user account limit for this Organization
     *
     * @var int|null
     */
    protected $accountLimit;

    /**
     * A CSV of permissions for this Organization
     *
     * @var string|null
     */
    protected $permissions;

    /**
     * Gets this Organization's Id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets this Organization's Id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Returns this Organization's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets this Organization's name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Gets the skin for this Organization
     *
     * @return string
     */
    public function getSkin()
    {
        return $this->skin;
    }

    /**
     * Sets the skin for this Organization
     *
     * @param string $skin
     */
    public function setSkin($skin)
    {
        $this->skin = $skin;
    }

    /**
     * Gets the JWT shared secret for this Organization
     *
     * @return string
     */
    public function getJwtSecret()
    {
        return $this->jwtSecret;
    }

    /**
     * Sets the JWT shared secret for this Organization
     *
     * @param string $jwtSecret
     */
    public function setJwtSecret($jwtSecret)
    {
        $this->jwtSecret = $jwtSecret;
    }

    /**
     * Gets the user account limit or this Organization
     *
     * @return int
     */
    public function getAccountLimit()
    {
        return $this->accountLimit;
    }

    /**
     * Sets the user account limit or this Organization
     *
     * @param int $accountLimit
     */
    public function setAccountLimit($accountLimit)
    {
        $this->accountLimit = $accountLimit;
    }

    /**
     * Verifies if the user limit has been reached
     *
     * @param $currentUserCount
     * @return bool
     */
    public function hasReachedAccountLimit($currentUserCount)
    {
        return (int)$this->accountLimit !== 0 && $currentUserCount >= $this->getAccountLimit();
    }

    /**
     * Gets the permissions for this Organization
     *
     * @return string
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Sets the permissions for this Organization
     *
     * @param string $permissions
     */
    public function setPermissions($permissions)
    {
        if (is_array($permissions)) {
            $permissions = implode(',', $permissions);
        }
        $this->permissions = $permissions;
    }

    /**
     * Returns true if the passed permission is set for the Organization
     *
     * @param string $permission
     *
     * @return boolean
     */
    public function hasPermission($permission)
    {
        if ($this->permissions == null) {
            return false;
        }

        $permissions = explode(',', $this->permissions);
        return (in_array($permission, $permissions));
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
            'name' => 'setName',
            'skin' => 'setSkin',
            'jwt_secret' => 'setJwtSecret',
            'account_limit' => 'setAccountLimit',
            'permissions' => 'setPermissions'
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
            'name' => 'getName',
            'skin' => 'getSkin',
            'jwt_secret' => 'getJwtSecret',
            'account_limit' => 'getAccountLimit',
            'permissions' => 'getPermissions'
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
