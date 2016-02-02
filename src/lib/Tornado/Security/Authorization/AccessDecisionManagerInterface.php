<?php

namespace Tornado\Security\Authorization;

/**
 * AccessDecisionManagerInterface
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Security\Authorization
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
interface AccessDecisionManagerInterface
{
    /**
     * Checks user authorization rights on the given object under given action.
     *
     * @param mixed $object
     * @param string|null $action not used at this stage of the project. Added for future support if necessary
     *
     * @return boolean true is access granted, false otherwise
     */
    public function isGranted($object, $action = null);
}
