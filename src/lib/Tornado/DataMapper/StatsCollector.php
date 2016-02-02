<?php

namespace Tornado\DataMapper;

use DataSift\Stats\Collector as BaseStatsCollector;

use Doctrine\DBAL\Logging\SQLLogger;

/**
 * Collects stats about made SQL queries.
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
class StatsCollector implements SQLLogger
{
    /**
     * Stats tracker.
     *
     * @var \DataSift\Stats\Collector
     */
    protected $stats;

    /**
     * Constructor.
     *
     * @param \DataSift\Stats\Collector $stats Stats tracker.
     */
    public function __construct(BaseStatsCollector $stats)
    {
        $this->stats = $stats;
    }

    /**
     * Tracks number and execution time of query.
     *
     * {@inheritdoc}
     *
     * @param string     $sql    The SQL to be executed.
     * @param array|null $params The SQL parameters.
     * @param array|null $types  The SQL parameter types.
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->stats->increment('db_query');
        $this->stats->startTimer('db_query.total_time');
    }

    /**
     * Tracks execution time of query.
     *
     * {@inheritdoc}
     */
    public function stopQuery()
    {
        $this->stats->endTimer('db_query.total_time');
    }
}
