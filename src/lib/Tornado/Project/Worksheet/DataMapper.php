<?php

namespace Tornado\Project\Worksheet;

use MD\Foundation\Utils\ObjectUtils;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\DataObjectInterface;
use Tornado\DataMapper\DoctrineRepository;
use Tornado\Project\Workbook;
use Tornado\Project\Worksheet;

/**
 * DataMapper class for Tornado Project's Worksheet
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
     * Finds a list of Worksheets from all of the given workbooks.
     *
     * @param  array   $workbooks Array of Workbook objects.
     * @param  array   $filter
     * @param  array   $sortBy
     * @param  integer $limit
     * @param  integer $offset
     * @return array
     */
    public function findByWorkbooks(array $workbooks, array $filter = [], array $sortBy = [], $limit = 0, $offset = 0)
    {
        if (empty($workbooks)) {
            return [];
        }

        $ids = ObjectUtils::pluck($workbooks, 'id');

        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->select('*')
            ->from($this->tableName)
            ->add('where', $queryBuilder->expr()->in('workbook_id', $ids));

        // reuse filter builder to add filters
        $this->addFilterToQueryBuilder($queryBuilder, $filter);
        $this->addRangeStatements($queryBuilder, $sortBy, $limit, $offset);

        $worksheets = $this->mapResults($queryBuilder->execute());

        // also merge in those worksheets into the workbooks if we already have them
        $grouped = ObjectUtils::groupBy($worksheets, 'workbook_id');
        foreach ($workbooks as $workbook) {
            if (isset($grouped[$workbook->getId()])) {
                $workbook->setWorksheets($grouped[$workbook->getId()]);
            }
        }

        return $worksheets;
    }

    /**
     * Finds a list of Worksheets for the given Workbook
     *
     * @param \Tornado\Project\Workbook $workbook
     * @param array                     $filter
     * @param array                     $sortBy
     * @param integer                   $limit
     * @param integer                   $offset
     *
     * @return array
     */
    public function findByWorkbook(Workbook $workbook, array $filter = [], array $sortBy = [], $limit = 0, $offset = 0)
    {
        $filter['workbook_id'] = $workbook->getPrimaryKey();

        $worksheets = $this->find($filter, $sortBy, (int)$limit, (int)$offset);
        $workbook->setWorksheets($worksheets);

        return $worksheets;
    }

    /**
     * Finds a worksheet by ID assigned to the given workbook.
     *
     * @param  integer  $worksheetId
     * @param  Workbook $workbook
     * @return Worksheet|null
     */
    public function findOneByWorkbook($worksheetId, Workbook $workbook)
    {
        return $this->findOne([
            'id' => $worksheetId,
            'workbook_id' => $workbook->getPrimaryKey()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function create(DataObjectInterface $object)
    {
        $object->setCreatedAt(time());
        $object->setUpdatedAt(time());
        return parent::create($object);
    }

    /**
     * {@inheritdoc}
     */
    public function update(DataObjectInterface $object)
    {
        $object->setUpdatedAt(time());
        return parent::update($object);
    }
}
