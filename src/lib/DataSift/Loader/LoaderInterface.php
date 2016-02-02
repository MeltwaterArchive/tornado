<?php

namespace DataSift\Loader;

/**
 * LoaderInterface
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift\Loader
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
interface LoaderInterface
{
    /**
     * Loads data from the given source type.
     *
     * Possible types might be:
     *   - json file
     *   - memcache
     *   - redis
     *   - etc
     *
     * @return array
     */
    public function load();

    /**
     * Checks if given resource if supported by this loader
     *
     * @param string      $resource
     * @param string|null $type
     *
     * @return boolean
     */
    public function supports($resource, $type = null);
}
