<?php

namespace Tornado\Organization\Brand;

use MD\Foundation\Utils\ArrayUtils;

use Tornado\DataMapper\DoctrineRepository;
use Tornado\Organization\Agency;
use Tornado\Organization\User;
use Tornado\Organization\Brand;
use Tornado\Project\Project;

/**
 * Brand Repository
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Organization\Brand
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class DataMapper extends DoctrineRepository
{
    /**
     * Finds a list of Brands for the given Agency
     *
     * @param \Tornado\Organization\Agency $agency
     * @param array                        $filter
     * @param array                        $sortBy
     * @param integer                      $limit
     * @param integer                      $offset
     *
     * @return array|null
     */
    public function findByAgency(
        Agency $agency,
        array $filter = [],
        array $sortBy = [],
        $limit = 0,
        $offset = 0
    ) {
        $filter['agency_id'] = $agency->getPrimaryKey();
        return $this->find($filter, $sortBy, $limit, $offset);
    }

    /**
     * Finds all Brands to which given User belongs to
     *
     * @param \Tornado\Organization\User $user
     * @param array                      $sortBy
     * @param int                        $limit
     * @param int                        $offset
     *
     * @return \Tornado\DataMapper\DataObjectInterface[]
     */
    public function findUserAssigned(User $user, array $sortBy = ['name' => 'ASC'], $limit = 0, $offset = 0)
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('b.*')
            ->from($this->tableName, 'b')
            ->leftJoin('b', User::RELATION_TABLE_BRAND, 'ub', 'ub.brand_id = b.id')
            ->where('ub.user_id = :userId')
            ->setParameter('userId', $user->getId());

        $this->addRangeStatements($queryBuilder, $sortBy, $limit, $offset);

        return $this->mapResults(
            $queryBuilder->execute()
        );
    }

    /**
     * Finds all Brands to which given User can be assigned to.
     *
     * @param \Tornado\Organization\User $user
     *
     * @return \Tornado\DataMapper\DataObjectInterface[]
     */
    public function findUserAllowed(User $user)
    {
        $userAgenciesIds = $this->createQueryBuilder()
            ->select('ua.agency_id')
            ->from(User::RELATION_TABLE_AGENCY, 'ua')
            ->where('ua.user_id = :userId')
            ->setParameter('userId', $user->getId())
            ->execute()
            ->fetchAll();

        if (!$userAgenciesIds) {
            return null;
        }

        $qb = $this->createQueryBuilder();
        $qb
            ->select('*')
            ->from($this->tableName, 'b')
            ->add(
                'where',
                $qb
                    ->expr()
                    ->in('agency_id', ArrayUtils::pluck($userAgenciesIds, 'agency_id'))
            );

        return $this->mapResults(
            $qb->execute()
        );
    }

    /**
     * Checks if User is assigned to the particular Brand
     *
     * @param \Tornado\Organization\User  $user
     * @param \Tornado\Organization\Brand $brand
     *
     * @return bool
     */
    public function isUserAllowed(User $user, Brand $brand)
    {
        $result = $this->createQueryBuilder()
            ->select('*')
            ->from(User::RELATION_TABLE_BRAND)
            ->where('user_id = :userId')
            ->andWhere('brand_id = :brandId')
            ->setParameters([
                'userId' => $user->getId(),
                'brandId' => $brand->getId()
            ])
            ->execute()
            ->fetch();

        return $result ? true : false;
    }

    /**
     * Finds Brands by ids
     *
     * @param array $ids
     *
     * @return \Tornado\DataMapper\DataObjectInterface[]
     */
    public function findByIds(array $ids = [])
    {
        $qb = $this->createQueryBuilder();
        $qb
            ->select('*')
            ->from($this->tableName)
            ->add('where', $qb->expr()->in('id', $ids));

        return $this->mapResults($qb->execute());
    }

    /**
     * Find a Brand for the given Project.
     *
     * @param  Project $project
     * @return Brand|null
     */
    public function findOneByProject(Project $project)
    {
        return $this->findOne([
            'id' => $project->getBrandId()
        ]);
    }

    /**
     * Returns a count of Brands for the given Agency
     *
     * @param \Tornado\Organization\Agency $agency
     *
     * @return integer
     */
    public function countAgencyBrands(Agency $agency)
    {
        $qb = $this->createQueryBuilder();
        $qb->select('COUNT(id) AS cnt')
           ->from($this->tableName)
           ->where('agency_id = :agencyId')
           ->setParameter('agencyId', $agency->getId());

        $res = $qb->execute()->fetch();
        return $res['cnt'];
    }
}
