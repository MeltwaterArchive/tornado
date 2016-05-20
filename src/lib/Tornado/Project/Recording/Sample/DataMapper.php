<?php

namespace Tornado\Project\Recording\Sample;

use Tornado\Project\Recording\Sample;
use Tornado\DataMapper\DoctrineRepository;
use Doctrine\DBAL\Connection;
use Tornado\Project\Recording;
use DataSift\Pylon\Pylon;

/**
 * DataMapper class for Recording Samples
 *
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
class DataMapper extends DoctrineRepository
{
    /**
     * The PYLON client to use
     *
     * @var \DataSift\Pylon\Pylon
     */
    private $pylon;

    /**
     * Constructs a new Sample DataMapper
     *
     * @param \Doctrine\DBAL\Connection $connection
     * @param string $objectClass
     * @param string $tableName
     * @param \DataSift\Pylon\Pylon $pylon
     */
    public function __construct(Connection $connection, $objectClass, $tableName, Pylon $pylon)
    {
        parent::__construct($connection, $objectClass, $tableName);
        $this->pylon = $pylon;
    }

    /**
     * Retrieves sample interactions for the passed Recording
     *
     * @param \Tornado\Project\Recording $recording
     * @param bool|array $filter
     * @param bool|array $start
     * @param bool|array $end
     * @param integer $count
     *
     * @return array
     */
    public function retrieve(Recording $recording, $filter = false, $start = false, $end = false, $count = 10)
    {
        $samples = $this->pylon->sample($filter, $start, $end, $count, $recording->getDatasiftRecordingId());
        $filterHash =  empty($filter) ? null : md5($filter);
        foreach ($samples['interactions'] as $interaction) {
            $sample = new Sample();
            $sample->setRecordingId($recording->getId());
            $sample->setFilterHash($filterHash);
            $sample->setData($interaction);
            $sample->setCreatedAt(time());
            $this->create($sample);
        }

        return [
            'remaining' => $samples['remaining'],
            'reset_at' => $samples['reset_at']
        ];
    }
}
