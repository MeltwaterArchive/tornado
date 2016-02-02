<?php
/**
 * Interface that MUST be implemented by all objects managed by the DataMapper.
 *
 * @package Tornado
 * @subpackage DataMapper
 */
namespace Tornado\DataMapper;

use JsonSerializable;

interface DataObjectInterface extends JsonSerializable
{
    
    /**
     * Get value of primary key.
     *
     * @return mixed
     */
    public function getPrimaryKey();

    /**
     * Set the value of primary key.
     *
     * @param mixed $key The primary key value.
     */
    public function setPrimaryKey($key);
    
    /**
     * Returns name of the primary key.
     *
     * If the primary key is compound, then it should return an array with names.
     *
     * @return string|array
     */
    public function getPrimaryKeyName();
    
    /**
     * Convert the object to array.
     *
     * @return array
     */
    public function toArray();
    
    /**
     * Load data from the `$array` to this object.
     *
     * @param array $data Data to apply on the object.
     */
    public function loadFromArray(array $data);
}
