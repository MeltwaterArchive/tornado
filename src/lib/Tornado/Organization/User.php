<?php

namespace Tornado\Organization;

use Tornado\DataMapper\DataObjectInterface;

/**
 * User
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     Tornado\Organization
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class User implements DataObjectInterface
{
    const RELATION_TABLE_BRAND = 'users_brands';
    const RELATION_TABLE_AGENCY = 'users_agencies';
    const RELATION_TABLE_ROLES = 'users_roles';

    /**
     * User type constants.
     */
    // normal user
    const TYPE_NORMAL = 0;
    // user created by using the identity api, shouldn't be able to login in Tornado
    const TYPE_IDENTITY_API = 1;

    /**
     * The id of this User
     *
     * @var integer
     */
    protected $id;

    /**
     * The id of the Organization this User belongs to
     *
     * @var integer
     */
    protected $organizationId;

    /**
     * The User's email
     *
     * @var string
     */
    protected $email;

    /**
     * The User's hashed password
     *
     * @var string
     */
    protected $password;

    /**
     * The User's username; nullable
     *
     * @var string
     */
    protected $username;

    /**
     * User type.
     *
     * @var integer
     */
    protected $type;

    /**
     * User's roles.
     *
     * @var Role[]
     */
    protected $roles = [];

    /**
     * Sets the User's ID
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
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
     * Gets the User's ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the id of the Organization to which User belongs to
     *
     * @param integer $organizationId
     */
    public function setOrganizationId($organizationId)
    {
        $this->organizationId = $organizationId;
    }

    /**
     * Gets the id of the Organization to which User belongs to
     *
     * @return integer
     */
    public function getOrganizationId()
    {
        return $this->organizationId;
    }

    /**
     * Sets User's email
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Gets the User's email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Sets User's password
     *
     * It is set as 255 in db just in case we would like to change the BCRYPT hashing algorithm to different one
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Gets the User's password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Sets User's username
     *
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Gets the User's username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Sets the User's type.
     *
     * @param integer $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Gets the User's type.
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets User's Roles
     *
     * @param array $roles
     */
    public function setRoles(array $roles = [])
    {
        foreach ($roles as $role) {
            $this->addRole($role);
        }
    }

    /**
     * Gets User's Roles
     *
     * @return \Tornado\Organization\Role[]
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Adds User's Role
     *
     * @param \Tornado\Organization\Role $role
     */
    public function addRole(Role $role)
    {
        $this->roles[] = $role;
    }

    /**
     * Checks is User has Role
     *
     * Roles arent fetched on user loading. Please ensure you set them before executing this method
     *
     * @param string|Role $role
     *
     * @return bool
     */
    public function hasRole($role)
    {
        if (!$role instanceof Role) {
            $roleName = $role;
            $role = new Role();
            $role->setName(strtolower($roleName));
        }

        /**
         */
        if ($role->getName() == Role::ROLE_SPAONLY && $this->getType() == static::TYPE_IDENTITY_API) {
            return true;
        }

        foreach ($this->roles as $existingRole) {
            if ($existingRole->getName() === $role->getName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if this User has the admin role
     *
     * @return boolean
     */
    public function isAdmin()
    {
        return $this->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * Returns true if this User has the super-admin role
     *
     * @return boolean
     */
    public function isSuperAdmin()
    {
        return $this->hasRole(Role::ROLE_SUPERADMIN);
    }

    /**
     * Returns true if this User has the SPA-only role
     *
     * @return boolean
     */
    public function isSpaOnly()
    {
        return $this->hasRole(Role::ROLE_SPAONLY);
    }

    /**
     * Gets the Gravatar image for this User
     *
     * @return string
     */
    public function getImage()
    {
        return '//www.gravatar.com/avatar/' . md5($this->getEmail());
    }

    /**
     * {@inheritdoc}
     */
    public function loadFromArray(array $data)
    {
        $map = [
            'id' => 'setId',
            'organization_id' => 'setOrganizationId',
            'email' => 'setEmail',
            'password' => 'setPassword',
            'username' => 'setUsername',
            'type' => 'setType'
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
            'email' => 'getEmail',
            'password' => 'getPassword',
            'username' => 'getUsername',
            'type' => 'getType'
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
        unset($data['password']);
        $data['image'] = $this->getImage();

        return $data;
    }
}
