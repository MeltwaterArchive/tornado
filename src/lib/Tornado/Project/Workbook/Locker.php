<?php

namespace Tornado\Project\Workbook;

use Doctrine\Common\Cache\Cache;

use Tornado\Organization\User;
use Tornado\Project\Workbook;

/**
 * Locker
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
class Locker
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
     * @var \Doctrine\Common\Cache\MemcachedCache
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

    public function __construct(Cache $cache, $ttl = self::LOCKING_TTL, $ttlResetLimit = self::TTL_RESET_LIMIT)
    {
        $this->cache = $cache;
        $this->ttl = $ttl;
        $this->ttlResetLimit = $ttlResetLimit;
        $this->remainingLimit = $ttlResetLimit;
    }

    /**
     * Retrieves Workbook's locking User
     *
     * @return \Tornado\Organization\User
     */
    public function getLockingUser()
    {
        return $this->lockingUser;
    }

    /**
     * Gets this Workbook TTL
     *
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * Gets this Workbook TTL reset limit
     *
     * @return int
     */
    public function getTtlResetLimit()
    {
        return $this->ttlResetLimit;
    }

    /**
     * Gets this Workbook remaining TTL reset limit
     *
     * @return int
     */
    public function getRemainingLimit()
    {
        return $this->remainingLimit;
    }

    /**
     * Checks if given Workbook is locked
     *
     * @param \Tornado\Project\Workbook $workbook
     *
     * @return bool
     */
    public function isLocked(Workbook $workbook)
    {
        return $this->cache->contains($this->generateLockKey($workbook));
    }

    /**
     * Fetches Workbook's lock object
     *
     * @param \Tornado\Project\Workbook $workbook
     *
     * @return false|mixed
     */
    public function fetch(Workbook $workbook)
    {
        return $this->cache->fetch($this->generateLockKey($workbook));
    }

    /**
     * Locks given Workbook for given user by using lock storage.
     *
     * @param \Tornado\Project\Workbook  $workbook
     * @param \Tornado\Organization\User $user
     *
     * @return bool
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
     * Checks if given User is granted to access the given Workbook
     *
     * @param \Tornado\Project\Workbook  $workbook
     * @param \Tornado\Organization\User $user
     *
     * @return bool
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
     * Retrieves Workbook's locking User
     *
     * @param \Tornado\Project\Workbook $workbook
     *
     * @return mixed
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
     * Resets lock entry TTL for given workbook.
     *
     * That's used for periodical Workbook's TTL resetting to ensure, that workbook isn't blocked
     * without any reasons for too long time, which could happen by setting initial TTL on i.e. 30 mins.
     *
     * @param \Tornado\Project\Workbook  $workbook
     * @param \Tornado\Organization\User $user
     *
     * @return int|false if TTL limit was reached, returns false and does not reset it.
     * Otherwise, returns remaining counter
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
     * Unlocks given Workbook
     *
     * @param \Tornado\Project\Workbook $workbook
     *
     * @return bool
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
