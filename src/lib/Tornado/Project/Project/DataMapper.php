<?php

namespace Tornado\Project\Project;

use Tornado\DataMapper\DoctrineRepository;
use Tornado\Organization\Brand;

/**
 * DataMapper class for Tornado Brand's Project
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
     * Finds a list of Projects for the given Brand
     *
     * @param \Tornado\Organization\Brand $brand
     * @param array                       $filter
     * @param array                       $sortBy
     * @param integer                     $limit
     * @param integer                     $offset
     *
     * @return array|null
     */
    public function findByBrand(Brand $brand, array $filter = [], array $sortBy = [], $limit = 0, $offset = 0)
    {
        $filter['brand_id'] = $brand->getPrimaryKey();

        return $this->find($filter, $sortBy, (int)$limit, (int)$offset);
    }

    /**
     * Batch Projects delete by ids and Brand
     *
     * @param Brand $brand
     * @param array $ids
     *
     * @return int number of deleted items
     */
    public function deleteProjectsByBrand(Brand $brand, array $ids = [])
    {
        $qb = $this->createQueryBuilder();
        $qb
            ->delete($this->tableName)
            ->add('where', $qb->expr()->in('id', $ids))
            ->andWhere('brand_id = :brandId')
            ->setParameter('brandId', $brand->getId());

        return $qb->execute();
    }
}
