<?php

namespace Tornado\Project\Workbook\Locker;

use Tornado\Project\Workbook\Locker;
use Tornado\Organization\User;
use Tornado\Project\Workbook;

/**
 * A null Workbook Locker
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Project\Worbook
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Dummy implements Locker
{

    /**
     * {@inheritdoc}
     */
    public function getLockingUser()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTtl()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getTtlResetLimit()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getRemainingLimit()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isLocked(Workbook $workbook)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(Workbook $workbook)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function lock(Workbook $workbook, User $user)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted(Workbook $workbook, User $user)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchLockingUser(Workbook $workbook)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function resetTtl(Workbook $workbook, User $user)
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function unlock(Workbook $workbook)
    {
        return true;
    }
}
