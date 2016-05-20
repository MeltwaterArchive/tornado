<?php

namespace Tornado\Organization\User;

use Tornado\Organization\User as UserModel;
use Tornado\Organization\User;

/**
 * Factory class for creating an User object
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
class Factory
{
    /**
     * Creates User object with given data
     *
     * @param array $data
     *
     * @return \Tornado\Organization\User
     */
    public function create(array $data)
    {
        return $this->setData(new UserModel(), $data);
    }

    /**
     * Updates a User data
     *
     * @param \Tornado\Organization\User $user
     * @param array                      $data
     *
     * @return \Tornado\Organization\User
     */
    public function update(User $user, $data = [])
    {
        return $this->setData($user, $data);
    }

    /**
     * Sets User object data
     *
     * @param \Tornado\Organization\User $user
     * @param array                      $data
     *
     * @return \Tornado\Organization\User
     */
    protected function setData(User $user, array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $user->loadFromArray($data);

        return $user;
    }
}
