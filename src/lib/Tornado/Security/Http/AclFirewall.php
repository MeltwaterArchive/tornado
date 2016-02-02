<?php

namespace Tornado\Security\Http;

use DataSift\Http\Request;

use Tornado\Organization\User;

/**
 * AclFirewall
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Security\Http
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class AclFirewall
{
    const ACL_PERMISSION_ATTR = '_permissions';
    const ACL_DISALLOW_ATTR = '_disallow';

    /**
     * @var \DataSift\Http\Request
     */
    protected $request;

    /**
     * @var \Tornado\Organization\User
     */
    protected $sessionUser;

    /**
     * @param \DataSift\Http\Request     $request
     * @param \Tornado\Organization\User $sessionUser
     */
    public function __construct(Request $request, User $sessionUser = null)
    {
        $this->request = $request;
        $this->sessionUser = $sessionUser;
    }

    /**
     * Checks if session User is granted to access resource behind ACL
     *
     * @return bool
     */
    public function isGranted()
    {
        if (!$this->sessionUser) {
            return;
        }

        $deniedRoles = $this->request->attributes->get(self::ACL_DISALLOW_ATTR);
        if ($deniedRoles) {
            foreach ($deniedRoles as $role) {
                if ($this->sessionUser->hasRole($role)) {
                    return false;
                }
            }
        }

        $expectedRoles = $this->request->attributes->get(self::ACL_PERMISSION_ATTR);

        // if no permissions defined for route access granted
        if (!$expectedRoles) {
            return true;
        }

        foreach ($expectedRoles as $role) {
            if ($this->sessionUser->hasRole($role)) {
                return true;
            }
        }

        return false;
    }
}
