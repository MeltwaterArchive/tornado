<?php

namespace Tornado\Project\Recording\DataSiftRecording;

use Tornado\DataMapper\PaginatorProviderInterface;

use \DataSift_User;
use \DataSift_Pylon as Pylon;

/**
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Project
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Collection implements PaginatorProviderInterface
{

    /**
     * The user to fetch recordings for
     *
     * @var DataSift_User
     */
    private $user;

    /**
     * The total number of PYLON recordings
     *
     * @var integer
     */
    private $count;

    /**
     * An array of cached results, mainly to cope with the timing of the ::count() call
     *
     * @var array
     */
    private $cache = [];

    /**
     * Constructs a new Collection
     *
     * @param DataSift_User $user
     */
    public function __construct(DataSift_User $user)
    {
        $this->user = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function count(array $filter = array())
    {
        if ($this->count == null) {
            $this->find($filter);
        }

        return $this->count;
    }

    /**
     * {@inheritdoc}
     */
    public function find(array $filter = array(), array $sortBy = array(), $limit = 0, $offset = 0)
    {
        /**
         */
        $key = "{$limit}-{$offset}";
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        if ($limit == 0) {
            $limit = 25;
        }

        $page = ceil(($offset + 1) / $limit);

        $perPage = $limit;

        $results = Pylon::getAll($this->user, $page, $perPage, 'name', Pylon::ORDERDIR_ASC);
        $this->count = $results['count'];
        $this->cache[$key] = $results['subscriptions'];

        return $results['subscriptions'];
    }
}
