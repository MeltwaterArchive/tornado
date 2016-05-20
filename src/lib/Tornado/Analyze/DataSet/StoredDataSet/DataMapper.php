<?php

namespace Tornado\Analyze\DataSet\StoredDataSet;

use Tornado\DataMapper\DoctrineRepository;
use Tornado\Analyze\DataSet\StoredDataSet;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Connection;

use Tornado\Analyze\Dimension\Factory as DimensionFactory;

/**
 * DataMapper class for Tornado StoredDataSets
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Analyze
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class DataMapper extends DoctrineRepository
{

    /**
     * The Dimension Factory to add cardinality information with
     *
     * @var \Tornado\Analyze\Dimension\Factory
     */
    private $dimensionFactory;

    /**
     * Constructs a new StoredDataSet mapper
     *
     * {@inheritdoc}
     * @param \Tornado\Analyze\Dimension\Factory|null $dimensionFactory
     */
    public function __construct(
        Connection $connection,
        $objectClass,
        $tableName,
        DimensionFactory $dimensionFactory
    ) {
        parent::__construct($connection, $objectClass, $tableName);
        $this->dimensionFactory = $dimensionFactory;
    }

    /**
     * Gets a list of DataSets to update from the command line
     *
     * @param integer $now
     *
     * @return array
     */
    public function findDataSetsToSchedule($now)
    {
        $now = (int)$now;
        $results = $this->connection->query(
            "SELECT * FROM {$this->tableName}"
            . " WHERE last_refreshed <= {$now}"
            . " - (86400 * CASE WHEN schedule_units = 'day' THEN 1 WHEN schedule_units = 'week' THEN 7 ELSE 31 END)"
            . " AND status = 'running'"
        );
        return $this->mapResults($results);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapResults(ResultStatement $results)
    {
        $results = parent::mapResults($results);
        foreach ($results as $result) {
            $this->dimensionFactory->decorateDimensionCollection($result->getDimensions());
        }
        return $results;
    }
}
