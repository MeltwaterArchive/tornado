<?php

namespace Tornado\DataMapper;

/**
 * PaginatorProviderInterface
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\DataMapper
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
interface PaginatorProviderInterface
{
    /**
     * Finds objects in a persistent store.
     *
     * @param array $filter Filter to apply when finding objects.
     * @param array $sortBy Associative array to sort by field in key and order in value.
     * @param int $limit Limit results.
     * @param int $offset Offset results.
     *
     * @return DataObjectInterface[]
     */
    public function find(array $filter = [], array $sortBy = [], $limit = 0, $offset = 0);

    /**
     * Counts objects in a persistent store.
     *
     * @param array $filter Filter to apply when finding objects.
     * @return integer
     */
    public function count(array $filter = []);
}
