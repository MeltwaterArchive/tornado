<?php

namespace Tornado\Organization\Organization;

use Tornado\DataMapper\DoctrineRepository;
use Tornado\Organization\User;
use Tornado\Security\Authorization\JWT\KeyDataMapper;

/**
 * Organization Repository
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
class DataMapper extends DoctrineRepository implements KeyDataMapper
{
    /**
     * Finds an Organization by User
     *
     * @return \Tornado\Organization\Organization
     */
    public function findOneByUser(User $user)
    {
        return $this->findOne([
            'id' => $user->getOrganizationId()
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getJwtKey($keyId)
    {
        $organization = $this->findOne(['id' => $keyId]);
        if ($organization) {
            return $organization->getJwtSecret();
        }
        return null;
    }

    /**
     * Finds Organization by its name
     *
     * @param string $name
     *
     * @return null|\Tornado\DataMapper\DataObjectInterface
     */
    public function findByName($name)
    {
        $qb = $this->createQueryBuilder();
        $qb
            ->select('*')
            ->from($this->tableName)
            ->where('LOWER(name) = :name')
            ->setParameter('name', strtolower($name));

        $result = $qb->execute()
            ->fetch();

        if (!$result) {
            return null;
        }

        // execute the query
        return $this->mapResult($result);
    }
}
