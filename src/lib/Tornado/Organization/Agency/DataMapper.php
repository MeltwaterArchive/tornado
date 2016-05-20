<?php

namespace Tornado\Organization\Agency;

use Tornado\DataMapper\DoctrineRepository;
use Tornado\Organization\Agency;
use Tornado\Organization\Organization;
use Tornado\Organization\User;

/**
 * Agency Repository
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Organization\Agency
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class DataMapper extends DoctrineRepository
{
    /**
     * Finds a list of Agencies for the given Organization
     *
     * @param \Tornado\Organization\Organization $organization
     * @param array                              $filter
     * @param array                              $sortBy
     * @param integer                            $limit
     * @param integer                            $offset
     *
     * @return array|null
     */
    public function findByOrganization(
        Organization $organization,
        array $filter = array(),
        array $sortBy = array(),
        $limit = 0,
        $offset = 0
    ) {
        $filter['organization_id'] = $organization->getPrimaryKey();
        return $this->find($filter, $sortBy, $limit, $offset);
    }

    /**
     * Finds all Agencies assigned to the User
     *
     * @param \Tornado\Organization\User $user
     *
     * @return \Tornado\DataMapper\DataObjectInterface[]
     */
    public function findUserAssigned(User $user)
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('a.*')
            ->from($this->tableName, 'a')
            ->leftJoin('a', User::RELATION_TABLE_AGENCY, 'ua', 'ua.agency_id = a.id')
            ->where('ua.user_id = :userId')
            ->setParameter('userId', $user->getId());

        return $this->mapResults(
            $queryBuilder->execute()
        );
    }

    /**
     * Finds all Agencies to which User can be assigned to.
     *
     * @param \Tornado\Organization\User $user
     *
     * @return \Tornado\DataMapper\DataObjectInterface[]
     */
    public function findUserAllowed(User $user)
    {
        $qb = $this->createQueryBuilder()
            ->select('*')
            ->from($this->tableName, 'a')
            ->where('a.organization_id = :organizationId')
            ->setParameter('organizationId', $user->getOrganizationId());

        return $this->mapResults(
            $qb->execute()
        );
    }

    /**
     * Finds Agencies by ids
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
}
