<?php

namespace Tornado\DataMapper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Query\QueryBuilder;

use Tornado\DataMapper\Exceptions\AlreadySavedObjectException;
use Tornado\DataMapper\Exceptions\InvalidPrimaryKeyException;
use Tornado\DataMapper\Exceptions\UnsavedObjectException;
use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\DataObjectInterface;

/**
 * PaginatorInterface
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\DataMapper
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class DoctrineRepository implements DataMapperInterface, PaginatorProviderInterface
{

    const FILTER_PARAMETER_PREFIX = 'dbal_filter___';

    /**
     * Doctrine DBAL connection.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Class name of the object that this repository manages.
     *
     * @var string
     */
    protected $objectClass;

    /**
     * Name of the database table in which objects are stored.
     *
     * @var string
     */
    protected $tableName;

    /**
     * Constructor.
     *
     * @param Connection $connection  Doctrine DBAL connection.
     * @param string     $objectClass Class name of the object that this repository manages.
     * @param string     $tableName   Name of the database table in which objects are stored.
     */
    public function __construct(Connection $connection, $objectClass, $tableName)
    {
        $this->connection = $connection;
        $this->objectClass = $objectClass;
        $this->tableName = $tableName;
    }

    /**
     * Create / write the object to the database.
     *
     * Returns the primary key (if any) of the object.
     *
     * @param DataObjectInterface $object Object to be created.
     *
     * @return DataObjectInterface
     */
    public function create(DataObjectInterface $object)
    {
        $primaryKeyName = $object->getPrimaryKeyName();
        $primaryKey = $object->getPrimaryKey();

        // compound keys can be already set and any conflict should be detected on INSERT time
        if (is_string($primaryKeyName) && !empty($primaryKey)) {
            throw new AlreadySavedObjectException(
                'DataObject of class "' . get_class($object) . '" already has a primary key set to '
                . strval($primaryKey) . ', so it cannot be created in the database. Did you want to update it instead?'
            );
        }

        $data = $object->toArray();

        // we are using the query builder because it sanitizes user input,
        // opposed to $this->connection->insert()
        $queryBuilder = $this->createQueryBuilder()
            ->insert($this->tableName);

        // set values using parameters
        foreach ($data as $column => $value) {
            // don't include NULL values
            if ($value === null) {
                continue;
            }

            $queryBuilder->setValue($column, ':' . $column);
            $queryBuilder->setParameter($column, $value);
        }

        $result = $queryBuilder->execute();

        // $result should contain number of affected rows, so if its 0
        // then it means that insert failed
        if ($result === 0) {
            throw new \RuntimeException(
                'Could not create DataObject of class "' . get_class($object) . '" in the database.'
            );
        }

        // updating the primary key only works for simple (single column) keys
        // when using compound keys they should already be defined before insert time
        if (is_string($primaryKeyName)) {
            $insertId = $this->connection->lastInsertId();
            $object->setPrimaryKey($insertId);
        }

        return $object;
    }

    /**
     * Update an object in persistent store.
     *
     * @param DataObjectInterface $object Object to be updated.
     */
    public function update(DataObjectInterface $object)
    {
        // make sure that the primary key of this object is valid
        $this->verifyPrimaryKey($object);

        $primaryKey = $object->getPrimaryKey();
        if (empty($primaryKey)) {
            throw new UnsavedObjectException(
                'DataObject of class "' . get_class($object) . '" cannot be updated because it has not been saved '
                . 'in the database yet. Did you want to create it instead?'
            );
        }

        $data = $object->toArray();

        // we are using the query builder because it sanitizes user input,
        // opposed to $this->connection->insert()
        $queryBuilder = $this->createQueryBuilder()
            ->update($this->tableName);

        // set values using parameters
        foreach ($data as $column => $value) {
            $queryBuilder->set($column, ':' . $column);
            $queryBuilder->setParameter($column, $value);
        }

        // set WHERE clause
        $primaryKeyName = $object->getPrimaryKeyName();
        $primaryKey = $object->getPrimaryKey();
        // build a filter array and add it to the query builder
        $filter = is_array($primaryKeyName)
            ? array_combine($primaryKeyName, $primaryKey)
            : [$primaryKeyName => $primaryKey];

        $this->addFilterToQueryBuilder($queryBuilder, $filter);

        $queryBuilder->execute();

        return $object;
    }

    /**
     * Creates or updates an object
     *
     * @param \Tornado\DataMapper\DataObjectInterface $object
     *
     * @return DataObjectInterface
     */
    public function upsert(DataObjectInterface $object)
    {
        return ($object->getPrimaryKey())
                ? $this->update($object)
                : $this->create($object);
    }

    /**
     * Delete an object from persistent store.
     *
     * @param DataObjectInterface $object Object to be deleted.
     */
    public function delete(DataObjectInterface $object)
    {
        // make sure that the primary key of this object is valid
        $this->verifyPrimaryKey($object);

        $primaryKey = $object->getPrimaryKey();
        if (empty($primaryKey)) {
            throw new UnsavedObjectException(
                'DataObject of class "' . get_class($object) . '" cannot be updated because it has not been saved '
                . 'in the database yet. Did you want to create it instead?'
            );
        }

        $queryBuilder = $this->createQueryBuilder()
            ->delete($this->tableName);

        // set WHERE clause
        $primaryKeyName = $object->getPrimaryKeyName();
        $primaryKey = $object->getPrimaryKey();
        // build a filter array and add it to the query builder
        $filter = is_array($primaryKeyName)
            ? array_combine($primaryKeyName, $primaryKey)
            : [$primaryKeyName => $primaryKey];

        $this->addFilterToQueryBuilder($queryBuilder, $filter);

        $queryBuilder->execute();
    }

    /**
     * {@inheritdoc}
     *
     */
    public function deleteByIds(array $ids)
    {
        $qb = $this->createQueryBuilder();
        $qb
            ->delete($this->tableName)
            ->add('where', $qb->expr()->in('id', $ids));

        return $qb->execute();
    }

    /**
     * Finds objects in a persistent store.
     *
     * @param array $filter Filter to apply when finding objects.
     * @param array $sortBy Associative array to sort by field in key and order in value.
     * @param int   $limit  Limit results.
     * @param int   $offset Offset results.
     *
     * @return DataObjectInterface[]
     */
    public function find(array $filter = [], array $sortBy = [], $limit = 0, $offset = 0)
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('*')
            ->from($this->tableName);

        // reuse filter builder to add filters
        $this->addFilterToQueryBuilder($queryBuilder, $filter);
        $this->addRangeStatements($queryBuilder, $sortBy, $limit, $offset);

        // execute the query
        $results = $queryBuilder->execute();
        return $this->mapResults($results);
    }

    /**
     * {@inheritdoc}
     */
    public function count(array $filter = [])
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('COUNT(id)')
            ->from($this->tableName);

        // reuse filter builder to add filters
        $this->addFilterToQueryBuilder($queryBuilder, $filter);

        // execute the query which returns associative array with ::select arg as a key
        $results = $queryBuilder->execute()
            ->fetch();

        return array_values($results)[0];
    }

    /**
     * @param \Doctrine\DBAL\Query\QueryBuilder $queryBuilder
     * @param array                             $sortBy
     * @param int                               $limit
     * @param int                               $offset
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function addRangeStatements(QueryBuilder $queryBuilder, array $sortBy = [], $limit = 0, $offset = 0)
    {
        // add ordering
        foreach ($sortBy as $column => $order) {
            $order = strtolower($order) === DataMapperInterface::ORDER_DESCENDING ? 'DESC' : 'ASC';
            $queryBuilder->addOrderBy($column, $order);
        }

        // add limit
        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        // add offset
        if ($offset) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder;
    }

    /**
     * Finds an object in persistent store.
     *
     * @param array $filter Filter to apply when searching for the object.
     *
     * @return DataObjectInterface|null
     */
    public function findOne(array $filter)
    {
        // just call find with enforced limit
        $results = $this->find($filter, [], 1);
        return isset($results[0]) ? $results[0] : null;
    }

    /**
     * Maps data to the managed object.
     *
     * @param array $data Data to be set on the object.
     *
     * @return DataObjectInterface
     */
    protected function mapResult(array $data)
    {
        $object = new $this->objectClass();
        $object->loadFromArray($data);
        return $object;
    }

    /**
     * Maps data from the result statements to objects of this repository.
     *
     * @param ResultStatement $results Statement containing the results.
     *
     * @return DataObjectInterface[]
     */
    protected function mapResults(ResultStatement $result)
    {
        $objects = array();
        while ($row = $result->fetch()) {
            $objects[] = $this->mapResult($row);
        }
        return $objects;
    }

    /**
     * Creates a query builder.
     *
     * @return QueryBuilder
     */
    protected function createQueryBuilder()
    {
        return $this->connection->createQueryBuilder();
    }

    /**
     * Verifies definition and value of primary key of the given object.
     *
     * @param  DataObjectInterface $object Object that should have its primary key verified.
     *
     * @return boolean
     *
     * @throws InvalidPrimaryKeyException When verification fails.
     */
    protected function verifyPrimaryKey(DataObjectInterface $object)
    {
        $primaryKeyName = $object->getPrimaryKeyName();
        $primaryKey = $object->getPrimaryKey();

        if (empty($primaryKeyName)) {
            throw new InvalidPrimaryKeyException(
                'DataObject of class "' . get_class($object) . '" has an empty primary key name.'
            );
        }

        if (is_array($primaryKeyName)) {
            if (!is_array($primaryKey)) {
                throw new InvalidPrimaryKeyException(
                    'DataObject of class "' . get_class($object) . '" has a compound primary key,'
                    . ' but does not return an array for its value.'
                );
            }

            if (count($primaryKeyName) !== count($primaryKey)) {
                throw new InvalidPrimaryKeyException(
                    'DataObject of class "' . get_class($object) . '" has a compound primary key,'
                    . ' but its column count differs between name and value.'
                );
            }

            return true;
        }

        if (!is_string($primaryKeyName)) {
            throw new InvalidPrimaryKeyException(
                'DataObject of class "' . get_class($object) . '" has an invalid primary key name definition.'
            );
        }

        if (is_array($primaryKey)) {
            throw new InvalidPrimaryKeyException(
                'DataObject of class "' . get_class($object) . '" has a simple primary key,'
                . ' but returns an array for its value.'
            );
        }

        return true;
    }

    /**
     * Adds a `AND WHERE` filter to the given query builder based on the passed
     * filter array.
     *
     * Keys from that filter array will be used as column names which should be
     * equal to the appropriate values.
     *
     * @param QueryBuilder $queryBuilder QueryBuilder to append the WHERE clause to.
     * @param array        $filter       Dictionary of columns and their values.
     *
     * @return QueryBuilder
     */
    protected function addFilterToQueryBuilder(QueryBuilder $queryBuilder, array $filter)
    {
        if (empty($filter)) {
            return $queryBuilder;
        }

        $constraints = [];
        foreach ($filter as $column => $value) {
            if (is_array($value)) {
                $constraints[] = $queryBuilder->expr()->in($column, $value);
                continue;
            }

            $constraints[] = $queryBuilder->expr()
                ->eq($column, ':' . self::FILTER_PARAMETER_PREFIX . $column);
            $queryBuilder->setParameter(self::FILTER_PARAMETER_PREFIX . $column, $value);
        }

        // if only one constraint then don't bother with wrapping AND
        if (count($constraints) === 1) {
            $queryBuilder->andWhere($constraints[0]);
        } else {
            $queryBuilder->andWhere(
                call_user_func_array([$queryBuilder->expr(), 'andX'], $constraints)
            );
        }

        return $queryBuilder;
    }
}
