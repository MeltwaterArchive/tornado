<?php

namespace Tornado\Security\Authorization;

/**
 * VoterInterface
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
interface VoterInterface
{
    /**
     * Checks if a Voter supports given object class
     *
     * @param string $class
     *
     * @return boolean
     */
    public function supportsClass($class);

    /**
     * Checks if user can access a given object under a given action
     *
     * @param mixed $object
     * @param string|null $action
     *
     * @return boolean
     */
    public function vote($object, $action);
}
