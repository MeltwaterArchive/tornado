<?php

namespace DataSift\Pylon\Schema;

/**
 * Schema
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift\Pylon\Schema
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Schema
{
    /**
     * This Schema objects
     *
     * @var array
     */
    protected $objects = [];

    public function __construct(array $objects = [])
    {
        $this->objects = $objects;
    }

    /**
     * Returns this Schema all existing objects as they are or reduced to expected keys.
     *
     * @param array $keysToReturn   A list of schema object keys to return
     * @param array $filter         A list of values to match in each schema object
     * @param array $permissions    A list of permissions for which to get the objects.
     *
     * @return array
     */
    public function getObjects(array $keysToReturn = [], array $filter = [], array $permissions = [])
    {

        $objects = [];
        foreach ($this->objects as $key => $object) {
            if ($this->filterObject($object, $filter) || !$this->hasPermissions($object, $permissions)) {
                continue;
            }

            if ($keysToReturn) {
                $reducedObj = array_intersect_key($object, array_flip($keysToReturn));

                // if no key exists in main array, do not add its to the return array
                if ($reducedObj) {
                    $objects[$key] = $reducedObj;
                }
            } else {
                $objects[$key] = $object;
            }
        }

        return $objects;
    }

    /**
     * Should the given object be filtered out?
     *
     * @param  array  $object      Object to be checked.
     * @param  array  $filter      Filters to apply.
     * @return boolean
     */
    private function filterObject(array $object, array $filter = [])
    {
        if (empty($filter)) {
            return false;
        }

        foreach ($filter as $key => $value) {
            if (!isset($object[$key]) || $object[$key] !== $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is the given object visible for the given set of permissions?
     *
     * @param  array   $object      Object to be checked.
     * @param  array   $permissions Permissions.
     * @return boolean
     */
    private function hasPermissions(array $object, array $permissions = [])
    {
        // prepare the default permissions for the object (as they are optional)
        $perms = isset($object['perms']) && is_array($object['perms']) ? $object['perms'] : [];
        $perms = empty($perms) ? ['everyone'] : $perms;

        // if 'everyone' permission on this object then its visible always
        // otherwise there must be at least one "shared" permission to allow access
        if (!in_array('everyone', $perms) && !array_intersect($permissions, $perms)) {
            return false;
        }

        return true;
    }

    /**
     * Returns this Schema all existing targets - UNIQUE
     *
     * @return array
     */
    public function getTargets(array $filter = [], array $permissions = [])
    {
        return array_unique(array_keys($this->getObjects([], $filter, $permissions)));
    }

    /**
     * Finds this Schema object by target
     *
     * @param string $target
     * @param array $permissions Allowed permissions.
     *
     * @return array|null
     */
    public function findObjectByTarget($target, array $permissions = [])
    {
        $objects = $this->getObjects([], ['target' => $target], $permissions);
        return empty($objects) ? null : current($objects);
    }
}
