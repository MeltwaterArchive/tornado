<?php

namespace Tornado\Organization\User;

use Tornado\DataMapper\DoctrineRepository;
use Tornado\Organization\Agency;
use Tornado\Organization\Brand;
use Tornado\Organization\Organization;
use Tornado\Organization\User;

/**
 * User Repository
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Organization\User
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class DataMapper extends DoctrineRepository
{
    /**
     * Finds a list of Users for the given Organization
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
     * Finds a list of Users for the given Organization excluding given User (in most cases authenticated User)
     *
     * @param \Tornado\Organization\Organization $organization
     * @param \Tornado\Organization\User         $user
     *
     * @return \Tornado\DataMapper\DataObjectInterface[]
     */
    public function findByOrganizationExcludingUser(Organization $organization, User $user)
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('*')
            ->from($this->tableName, 'u')
            ->where('u.organization_id = :organizationId')
            ->andWhere('u.id <> :userId')
            ->setParameters([
                'organizationId' => $organization->getId(),
                'userId' => $user->getId()
            ]);

        return $this->mapResults(
            $queryBuilder->execute()
        );
    }

    /**
     * Finds single User but its username or email
     *
     * @param string $name
     *
     * @return null|\Tornado\DataMapper\DataObjectInterface
     */
    public function findOneByUsernameOrEmail($name)
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('*')
            ->from($this->tableName, 'u')
            ->where('u.username = :username')
            ->orWhere('u.email = :email')
            ->setParameters([
                'username' => $name,
                'email' => $name
            ]);

        $result = $queryBuilder->execute()
            ->fetch();

        if (!$result) {
            return null;
        }

        // execute the query
        return $this->mapResult($result);
    }

    /**
     * Assign Brands to the User
     *
     * @param \Tornado\Organization\User    $user
     * @param \Tornado\Organization\Brand[] $brands
     *
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function addBrands(User $user, array $brands = [])
    {
        $qb = $this->createQueryBuilder()
            ->insert(User::RELATION_TABLE_BRAND);

        $userId = $user->getId();
        foreach ($brands as $brand) {
            $qb->values(['user_id' => $userId, 'brand_id' => $brand->getId()]);
            $qb->execute();
        }
    }

    /**
     * Removes all Brands to which User belongs to
     *
     * @param \Tornado\Organization\User $user
     * @param array $brands
     *
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function removeBrands(User $user, array $brands = [])
    {
        $qb = $this->createQueryBuilder();
        if (count($brands)) {
            $brandIds = [];
            foreach ($brands as $brand) {
                $brandIds[] = $brand->getId();
            }

            return $qb->delete(User::RELATION_TABLE_BRAND)
               ->add('where', $qb->expr()->in('brand_id', $brandIds))
               ->andWhere('user_id = :userId')
               ->setParameter('userId', $user->getId())
               ->execute();
        }
        return $qb->delete(User::RELATION_TABLE_BRAND)
            ->where('user_id = :userId')
            ->setParameter('userId', $user->getId())
            ->execute();
    }

    /**
     * Assign Brands to the User
     *
     * @param \Tornado\Organization\User     $user
     * @param \Tornado\Organization\Agency[] $agencies
     *
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function addAgencies(User $user, array $agencies = [])
    {
        $qb = $this->createQueryBuilder()
            ->insert(User::RELATION_TABLE_AGENCY);

        $userId = $user->getId();
        foreach ($agencies as $agency) {
            $qb->values(['user_id' => $userId, 'agency_id' => $agency->getId()]);
            $qb->execute();
        }
    }

    /**
     * Removes all Agencies to which User belongs to
     *
     * @param \Tornado\Organization\User $user
     * @param array $agencies
     *
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function removeAgencies(User $user, array $agencies = [])
    {
        $qb = $this->createQueryBuilder();
        if (count($agencies)) {
            $agencyIds = [];
            foreach ($agencies as $agency) {
                $agencyIds[] = $agency->getId();
            }

            return $qb->delete(User::RELATION_TABLE_AGENCY)
               ->add('where', $qb->expr()->in('agency_id', $agencyIds))
               ->andWhere('user_id = :userId')
               ->setParameter('userId', $user->getId())
               ->execute();
        }
        return $qb
            ->delete(User::RELATION_TABLE_AGENCY)
            ->where('user_id = :userId')
            ->setParameter('userId', $user->getId())
            ->execute();
    }

    /**
     * Removes Users by ids
     *
     * @param array $ids
     *
     * @return int number of deleted items
     */
    public function deleteByIds(array $ids)
    {
        $qb = $this->createQueryBuilder();
        $qb
            ->delete($this->tableName)
            ->add('where', $qb->expr()->in('id', $ids));

        return $qb->execute();
    }
}
