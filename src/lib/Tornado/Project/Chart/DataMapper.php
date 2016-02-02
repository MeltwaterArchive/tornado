<?php

namespace Tornado\Project\Chart;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\DoctrineRepository;
use Tornado\Project\Worksheet;

/**
 * DataMapper class for Tornado Worksheet's Chart
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Project
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class DataMapper extends DoctrineRepository
{
    /**
     * {@inheritdoc}
     */
    public function find(array $filter = [], array $sortBy = [], $limit = 0, $offset = 0)
    {
        if (empty($sortBy)) {
            $sortBy['rank'] = DataMapperInterface::ORDER_ASCENDING;
        }

        return parent::find($filter, $sortBy, $limit, $offset);
    }

    /**
     * Finds a list of Charts for the given Worksheet
     *
     * @param \Tornado\Project\Worksheet $worksheet
     * @param array                      $filter
     * @param array                      $sortBy
     * @param integer                    $limit
     * @param integer                    $offset
     *
     * @return array
     */
    public function findByWorksheet(
        Worksheet $worksheet,
        array $filter = [],
        array $sortBy = [],
        $limit = 0,
        $offset = 0
    ) {
        $filter['worksheet_id'] = $worksheet->getPrimaryKey();

        return $this->find($filter, $sortBy, (int)$limit, (int)$offset);
    }

    /**
     * Deletes all charts which belongs to the given worksheet
     *
     * @param \Tornado\Project\Worksheet $worksheet
     *
     * @return \Doctrine\DBAL\Driver\Statement|int number of deleted charts or error
     */
    public function deleteByWorksheet(Worksheet $worksheet)
    {
        return $this->createQueryBuilder()
            ->delete($this->tableName)
            ->where('worksheet_id = :worksheetId')
            ->setParameter('worksheetId', $worksheet->getId())
            ->execute();
    }
}
