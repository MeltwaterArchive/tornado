<?php

namespace Tornado\Organization\Role;

use Tornado\DataMapper\DoctrineRepository;
use Tornado\Organization\Role;
use Tornado\Organization\Organization;
use Tornado\Organization\User;

/**
 * Role Repository
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Organization\Role
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class DataMapper extends DoctrineRepository
{
    /**
     * Finds all Roles assigned to the User
     *
     * @param \Tornado\Organization\User $user
     *
     * @return \Tornado\DataMapper\DataObjectInterface[]
     */
    public function findUserAssigned(User $user)
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select('r.*')
            ->from($this->tableName, 'r')
            ->leftJoin('r', User::RELATION_TABLE_ROLES, 'ur', 'ur.role_id = r.id')
            ->where('ur.user_id = :userId')
            ->setParameter('userId', $user->getId());

        return $this->mapResults(
            $queryBuilder->execute()
        );
    }

    /**
     * Sets a user's roles
     *
     * @param \Tornado\Organization\User $user
     * @param array $roleNames
     */
    public function setUserRoles(User $user, array $roleNames)
    {
        // clear user's existing roles
        $qb = $this->createQueryBuilder();
        $qb->delete(User::RELATION_TABLE_ROLES)
           ->add('where', 'user_id = :userId')
           ->setParameter('userId', $user->getId())
           ->execute();

        foreach ($roleNames as $roleName) {
            $role = $this->findOne(['name' => $roleName]);

            if ($role) {
                $qb->insert(User::RELATION_TABLE_ROLES)
                    ->setValue('user_id', ':' . 'userId')
                    ->setParameter('userId', $user->getId())
                    ->setValue('role_id', ':' . 'roleId')
                    ->setParameter('roleId', $role->getId())
                    ->execute();
            }
        }
    }
}
