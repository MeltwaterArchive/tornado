<?php

namespace Tornado\Security\Authorization\JWT;

/**
 * KeyDataMapper
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Security\Authorization
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
interface KeyDataMapper
{
    /**
     * Gets the JWT key for the passed key ID
     *
     * @param string $kid
     *
     * @return mixed
     */
    public function getJwtKey($keyId);
}
