<?php

namespace Controller\ProjectApp;

use Tornado\Controller\ProjectDataAwareInterface;
use Tornado\Controller\ProjectDataAwareTrait;
use Tornado\Controller\Result;

use Tornado\Project\Recording\DataMapper as RecordingDataMapper;
use Tornado\Project\Recording\DataSiftRecording;
use Tornado\Project\Workbook;

/**
 * The main project SPA entry point.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Controller\ProjectApp
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class AppController implements ProjectDataAwareInterface
{
    use ProjectDataAwareTrait;

    /**
     * The Recording Repository for this Controller
     *
     * @var Tornado\Project\Recording\DataMapper
     */
    private $recordingRepo;

    /**
     * The DataSift Recording adapter for this Controller
     *
     * @var Tornado\Project\Recording\DataSiftRecording
     */
    private $datasiftRecording;

    /**
     * Constructs a new AppController
     *
     * @param \Tornado\Project\Recording\DataMapper $recordingRepo
     * @param \Tornado\Project\Recording\DataSiftRecording $datasiftRecording
     */
    public function __construct(RecordingDataMapper $recordingRepo, DataSiftRecording $datasiftRecording)
    {
        $this->recordingRepo = $recordingRepo;
        $this->datasiftRecording = $datasiftRecording;
    }

    /**
     * Retrieves all required info for the given project id.
     *
     * @param  integer $projectId ID of the project.
     *
     * @return Result
     */
    public function get($projectId)
    {
        $project = $this->getProject($projectId);

        $workbooks = $this->workbookRepository->findByProject($project);
        $recordings = $this->recordingRepo->findRecordingsByWorkbooks($workbooks);
        $workbooks = $this->datasiftRecording->decorateWorkbooks($workbooks, $recordings);

        $worksheets = $this->worksheetRepository->findByWorkbooks($workbooks);

        return new Result([
            'project' => $project,
            'workbooks' => $workbooks,
            'worksheets' => $worksheets
        ]);
    }
}
