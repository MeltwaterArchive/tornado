<?php

namespace Tornado\Project\Workbook\Locker;

use Doctrine\Common\Cache\Cache as CacheInterface;
use Tornado\Project\Workbook\Locker;
use Tornado\Organization\User;
use Tornado\Project\Workbook;

/**
 * Cache-based Locker
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Project\Worbook
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Cache implements Locker
{
    /**
     * TTL Default locking time, represented in seconds
     */
    const LOCKING_TTL = 120;

    /**
     * Default top limit value for resetting the TTL for particular key
     */
    const TTL_RESET_LIMIT = 30;

    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    protected $cache;

    /**
     * TTL locking time, represented in seconds
     *
     * @var int
     */
    protected $ttl;

    /**
     * Top limit value for resetting the TTL for single key
     *
     * @var int
     */
    protected $ttlResetLimit;

    /**
     * Remaining TTL reset limit
     *
     * @var int
     */
    protected $remainingLimit;

    /**
     * Workbook's locking User
     *
     * @var User
     */
    protected $lockingUser = null;

    /**
     * Constructs a new cached Workbook locker
     *
     * @param \Doctrine\Common\Cache\Cache $cache
     * @param integer $ttl
     * @param integer $ttlResetLimit
     */
    public function __construct(CacheInterface $cache, $ttl = self::LOCKING_TTL, $ttlResetLimit = self::TTL_RESET_LIMIT)
    {
        $this->cache = $cache;
        $this->ttl = $ttl;
        $this->ttlResetLimit = $ttlResetLimit;
        $this->remainingLimit = $ttlResetLimit;
    }

    /**
     * {@inheritdoc}
     */
    public function getLockingUser()
    {
        return $this->lockingUser;
    }

    /**
     * {@inheritdoc}
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * {@inheritdoc}
     */
    public function getTtlResetLimit()
    {
        return $this->ttlResetLimit;
    }

    /**
     * {@inheritdoc}
     */
    public function getRemainingLimit()
    {
        return $this->remainingLimit;
    }

    /**
     * {@inheritdoc}
     */
    public function isLocked(Workbook $workbook)
    {
        return $this->cache->contains($this->generateLockKey($workbook));
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(Workbook $workbook)
    {
        return $this->cache->fetch($this->generateLockKey($workbook));
    }

    /**
     * {@inheritdoc}
     */
    public function lock(Workbook $workbook, User $user)
    {
        return $this->cache->save(
            $this->generateLockKey($workbook),
            [
                'workbook' => $workbook,
                'user' => $user,
                // ttlResetLimit may be 0, prevent that by opposite clause stmt
                'reset_limit' => $this->remainingLimit
            ],
            $this->ttl
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted(Workbook $workbook, User $user)
    {
        $lockingUser = $this->fetchLockingUser($workbook);
        if (!$lockingUser) {
            return false;
        }

        return $lockingUser->getId() === $user->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function fetchLockingUser(Workbook $workbook)
    {
        $lockData = $this->fetch($workbook);
        if (!$lockData || !isset($lockData['user']) || !$lockData['user'] instanceof User) {
            return null;
        }

        $this->lockingUser = $lockData['user'];
        return $this->lockingUser;
    }

    /**
     * {@inheritdoc}
     */
    public function resetTtl(Workbook $workbook, User $user)
    {
        $lockData = $this->fetch($workbook);
        $resetLimit = $lockData['reset_limit'];

        if (!($resetLimit > 0)) {
            return false;
        }

        $this->remainingLimit = $resetLimit - 1;
        $this->lock($workbook, $user);

        return $this->remainingLimit;
    }

    /**
     * {@inheritdoc}
     */
    public function unlock(Workbook $workbook)
    {
        return $this->cache->delete($this->generateLockKey($workbook));
    }

    /**
     * Generates a lock key for given workbook
     *
     * @param \Tornado\Project\Workbook $workbook
     *
     * @return string
     */
    protected function generateLockKey(Workbook $workbook)
    {
        return sprintf('workbook:lock:%d', $workbook->getId());
    }
}
