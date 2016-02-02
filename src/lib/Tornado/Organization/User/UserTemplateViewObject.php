<?php

namespace Tornado\Organization\User;

use Tornado\Organization\User;

/**
 * UserTemplateViewObject class
 *
 * User View object for Twig
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
class UserTemplateViewObject extends User
{
    /**
     * @param \Tornado\Organization\User $user
     */
    public function __construct(User $user)
    {
        $this->id = $user->getId();
        $this->organizationId = $user->getOrganizationId();
        $this->email = $user->getEmail();
        $this->username = $user->getUsername();
        $this->type = $user->getType();
        $this->roles = $user->getRoles();
    }
}
