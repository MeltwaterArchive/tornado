<?php
/**
 * Interface for a Data Mapper that links PHP objects with an external data source
 * (usually a database, but not necessarily).
 *
 * @package Tornado
 * @subpackage DataMapper
 */
namespace Tornado\DataMapper;

use Tornado\DataMapper\Exceptions\AlreadySavedObjectException;
use Tornado\DataMapper\Exceptions\UnsavedObjectException;
use Tornado\DataMapper\DataObjectInterface;

interface DataMapperInterface
{

    const ORDER_ASCENDING = 'asc';
    const ORDER_DESCENDING = 'desc';

    /**
     * Create / write the object to a persistent store.
     *
     * After successful creation it should update the `$object`'s primary key.
     *
     * Returns the passed `$object`.
     *
     * @param DataObjectInterface $object Object to be created.
     * @return DataObjectInterface
     *
     * @throws AlreadySavedObjectException When trying to create an object that has
     *                                     already been saved in persistent store.
     */
    public function create(DataObjectInterface $object);
    
    /**
     * Update an object in persistent store.
     *
     * @param DataObjectInterface $object Object to be updated.
     *
     * @throws UnsavedObjectException When trying to update an object that has not been
     *                                previously saved in persistent store.
     */
    public function update(DataObjectInterface $object);
    
    /**
     * Delete an object from persistent store.
     *
     * @param DataObjectInterface $object Object to be deleted.
     *
     * @throws UnsavedObjectException When trying to delete an object that has not been
     *                                previously saved in persistent store.
     */
    public function delete(DataObjectInterface $object);
    
    /**
     * Finds objects in a persistent store.
     *
     * @param array $filter Filter to apply when finding objects.
     * @param array $sortBy Associative array to sort by field in key and order in value.
     * @param int $limit Limit results.
     * @param int $offset Offset results.
     * @return DataObjectInterface[]
     */
    public function find(array $filter = [], array $sortBy = [], $limit = 0, $offset = 0);
    
    /**
     * Finds an object in persistent store.
     *
     * @param array $filter Filter to apply when searching for the object.
     *
     * @return DataObjectInterface|null
     */
    public function findOne(array $filter);
}
