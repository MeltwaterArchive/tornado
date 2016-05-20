<?php

namespace Tornado\Project\Workbook;

use Tornado\Organization\User;
use Tornado\Project\Workbook;

/**
 * An interface to model a worksheet locker
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
interface Locker
{

    /**
     * Retrieves Workbook's locking User
     *
     * @return \Tornado\Organization\User
     */
    public function getLockingUser();

    /**
     * Gets this Workbook TTL
     *
     * @return int
     */
    public function getTtl();

    /**
     * Gets this Workbook TTL reset limit
     *
     * @return int
     */
    public function getTtlResetLimit();

    /**
     * Gets this Workbook remaining TTL reset limit
     *
     * @return int
     */
    public function getRemainingLimit();

    /**
     * Checks if given Workbook is locked
     *
     * @param \Tornado\Project\Workbook $workbook
     *
     * @return bool
     */
    public function isLocked(Workbook $workbook);

    /**
     * Fetches Workbook's lock object
     *
     * @param \Tornado\Project\Workbook $workbook
     *
     * @return false|mixed
     */
    public function fetch(Workbook $workbook);

    /**
     * Locks given Workbook for given user by using lock storage.
     *
     * @param \Tornado\Project\Workbook  $workbook
     * @param \Tornado\Organization\User $user
     *
     * @return bool
     */
    public function lock(Workbook $workbook, User $user);

    /**
     * Checks if given User is granted to access the given Workbook
     *
     * @param \Tornado\Project\Workbook  $workbook
     * @param \Tornado\Organization\User $user
     *
     * @return bool
     */
    public function isGranted(Workbook $workbook, User $user);

    /**
     * Retrieves Workbook's locking User
     *
     * @param \Tornado\Project\Workbook $workbook
     *
     * @return mixed
     */
    public function fetchLockingUser(Workbook $workbook);

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
    public function resetTtl(Workbook $workbook, User $user);

    /**
     * Unlocks given Workbook
     *
     * @param \Tornado\Project\Workbook $workbook
     *
     * @return bool
     */
    public function unlock(Workbook $workbook);
}
