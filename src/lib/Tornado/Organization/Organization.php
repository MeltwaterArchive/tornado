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
     * @param string $skin
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
            'jwt_secret' => 'setJwtSecret'
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
            'jwt_secret' => 'getJwtSecret'
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
