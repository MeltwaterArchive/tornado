<?php

namespace DataSift\Cache;

use Doctrine\Common\Cache\Cache;

/**
 * NullCache
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift\Cache
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class NullCache implements Cache
{
    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getStats()
    {
        return null;
    }
}
