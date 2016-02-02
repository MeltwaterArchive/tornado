<?php

namespace Tornado\Project\Recording;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\Project\Recording\DataSiftRecording\Collection;
use Tornado\Project\Recording as RecordingModel;
use Tornado\Project\Recording;

/**
 * DataSiftRecording class represents a DataSift recording model and actions which can be make on it
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Project\Recording
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class DataSiftRecording implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var \DataSift_Pylon
     */
    protected $pylon;

    /**
     * @var \Tornado\DataMapper\DataMapperInterface
     */
    protected $recordingRepository;

    /**
     * A cache of DataSift_Pylon recordings
     *
     * @var array
     */
    protected $pylonRecordings = null;

    /**
     * @param \DataSift_Pylon                         $pylon
     * @param \Tornado\DataMapper\DataMapperInterface $recordingRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \DataSift_Pylon $pylon,
        DataMapperInterface $recordingRepository,
        LoggerInterface $logger
    ) {
        $this->pylon = $pylon;
        $this->recordingRepository = $recordingRepository;
        $this->setLogger($logger);
    }

    /**
     * Gets a list of PYLON recordings, indexed by hash
     *
     * @param boolean $clearCache
     *
     * @return array
     */
    public function getPylonRecordings($clearCache = false)
    {
        if ($clearCache || !$this->pylonRecordings) {
            try {
                $recordings = $this->pylon->findAll(1, 1000); // 'orrible, I know!
            } catch (\Exception $ex) {
                $this->mapException($ex);
            }
            $this->pylonRecordings = [];
            foreach ($recordings as $recording) {
                $this->pylonRecordings[$recording->getHash()] = $recording;
            }
        }
        return $this->pylonRecordings;
    }

    /**
     * Gets a paginated collection of Pylon recordings
     *
     * @return \Tornado\Project\Recording\DataSiftRecording\Collection
     */
    public function getPaginatedCollection()
    {
        return new DataSiftRecording\Collection($this->pylon->getUser());
    }

    /**
     * Gets a paginated collection of Pylon recordings
     *
     * @return \Tornado\Project\Recording\DataSiftRecording\Collection
     */
    public function decoratePylonCollection(array $recordings)
    {
        $ids = [];

        foreach ($recordings as $recording) {
            $ids[$recording['hash']] = $recording;
        }

        $tornadoRecordings = $this->recordingRepository->findByDataSiftRecordingIds(array_keys($ids));

        foreach ($tornadoRecordings as $recording) {
            $ids[$recording->getDataSiftRecordingId()]['imported'] = false;
            if (isset($ids[$recording->getDataSiftRecordingId()])) {
                $ids[$recording->getDataSiftRecordingId()]['imported'] = $recording->getId();
            }
        }

        return $ids;
    }

    /**
     * Decorates an array of Recordings with the appropriate PYLON recordings
     *
     * @param array $recordings
     *
     * @return array
     */
    public function decorateRecordings(array $recordings)
    {
        $pylonRecordings = $this->getPylonRecordings();
        foreach ($recordings as $recording) {
            if (isset($pylonRecordings[$recording->getDataSiftRecordingId()])) {
                $recording->fromDataSiftRecording($pylonRecordings[$recording->getDataSiftRecordingId()]);
            }
        }
        return $recordings;
    }

    /**
     *
     * @param \Tornado\Project\Recording $recording
     *
     * @return \Tornado\Project\Recording
     * @throws \Tornado\Project\Recording\DataSiftRecordingException
     */
    public function start(RecordingModel $recording)
    {
        try {
            $this->pylon->compile($recording->getCsdl());
        } catch (\Exception $e) {
            $this->mapException($e);
        }

        $hash = $this->pylon->getHash();
        $existingRecording = $this->recordingRepository->findOne([
            'hash' => $hash,
            'brand_id' => $recording->getBrandId()
        ]);

        if ($existingRecording && RecordingModel::STATUS_STARTED === $existingRecording->getStatus()) {
            throw new DataSiftRecordingException(
                'Recording for given CSDL already exists. Please use it or modify your CSDL.',
                0,
                null,
                409
            );
        }

        if (!$existingRecording) {
            $recording->setHash($this->pylon->getHash());
        }

        return $this->doStart($recording);
    }

    /**
     * Resumes a stopped Recording
     *
     * @param \Tornado\Project\Recording $recording
     *
     * @return Recording
     *
     * @throws \Tornado\Project\Recording\DataSiftRecordingException
     */
    public function resume(RecordingModel $recording)
    {
        if (Recording::STATUS_STARTED === $recording->getStatus()) {
            throw new DataSiftRecordingException('Recording is already running.', 0, null, 409);
        }

        return $this->doStart($recording);
    }

    /**
     * Stops running Recording
     *
     * @param \Tornado\Project\Recording $recording
     ** @param bool $catchError for removing recording conflict exception shouldn't break the remove process.
     *                         For recording pause action conflict exception should be thrown
     *
     * @return Recording
     *
     * @throws \Tornado\Project\Recording\DataSiftRecordingException
     */
    public function pause(RecordingModel $recording, $catchError = true)
    {
        if (Recording::STATUS_STOPPED === $recording->getStatus()) {
            throw new DataSiftRecordingException('Recording has been already stopped.', 0, null, 409);
        }

        return $this->doStop($recording, $catchError);
    }

    /**
     * Perform transactional tornado recording remove in sync with stopping a DataSift Recording
     *
     * @param \Tornado\Project\Recording $recording
     *
     * @return int deleted item
     *
     * @throws \Exception unless it has 409 error code (conflict) which shouldn't break the process
     */
    public function delete(RecordingModel $recording)
    {
        $this->doStop($recording, false);
        return $this->recordingRepository->delete($recording);
    }

    /**
     * Performs a "transactional" batch removal process which consists of 2 phases:
     *  1. pausing DataSift Recording
     *  2.1 if all recordings are paused it would remove them from DB
     *  2.2 if a DataSift Recoding pause action throws an error (unless it has 409 error code (conflict)
     *      which shouldn't break the process), process is stopped and returns an error response
     *
     * @param RecordingModel[] $recordings
     *
     * @return int deleted items
     */
    public function deleteRecordings(array $recordings)
    {
        foreach ($recordings as $recording) {
            $this->doStop($recording, false);
        }

        return $this->recordingRepository->deleteRecordings($recordings);
    }

    /**
     * Manages a DataSift recording start action with catching and mapping its exceptions if thrown
     *
     * @param \Tornado\Project\Recording $recording
     *
     * @return Recording
     *
     * @throws \Tornado\Project\Recording\DataSiftRecordingException
     */
    protected function doStart(RecordingModel $recording)
    {
        try {
            $this->pylon->start($recording->getHash(), $recording->getName());
            $recording->setStatus(RecordingModel::STATUS_STARTED);

            return $recording;
        } catch (\Exception $e) {
            $this->mapException($e);
        }
    }

    /**
     * Manages a DataSift recording stop action with catching and mapping its exceptions if thrown
     *
     * @param \Tornado\Project\Recording $recording
     * @param bool $throwError for removing recording conflict exception shouldn't break the remove process.
     *                         For recording pause action conflict exception should be thrown
     *
     * @return Recording|null
     *
     * @throws \Tornado\Project\Recording\DataSiftRecordingException
     */
    protected function doStop(RecordingModel $recording, $throwError = true)
    {
        try {
            $this->pylon->stop($recording->getHash());
            $recording->setStatus(RecordingModel::STATUS_STOPPED);

            return $recording;
        } catch (\Exception $e) {
            if (!$throwError && 409 === $e->getCode()) {
                return;
            }

            $this->mapException($e);
        }
    }

    /**
     * Maps Pylon recording exceptions to Tornado understandable exceptions
     *
     * @param \Exception $e
     *
     * @throws \Tornado\Project\Recording\DataSiftRecordingException
     */
    protected function mapException(\Exception $e)
    {
        // log the DS API exception
        $this->logger->error($e);

        $exceptionMap = [
            'DataSift_Exception_AccessDenied' => [
                'statusCode' => 403
            ],
            'DataSift_Exception_APIError' => [
                'statusCode' => 500
            ],
            'DataSift_Exception_InvalidData' => [
                'statusCode' => 400
            ],
            'DataSift_Exception_RateLimitExceeded' => [
                'statusCode' => 429
            ]
        ];

        $excClass = get_class($e);
        if (!isset($exceptionMap[$excClass])) {
            $exc = ['statusCode' => 500];
        } else {
            $exc = $exceptionMap[$excClass];
        }

        throw new DataSiftRecordingException($e->getMessage(), $e->getCode(), $e, $exc['statusCode']);
    }
}
