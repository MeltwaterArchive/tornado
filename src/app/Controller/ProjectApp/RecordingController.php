<?php

namespace Controller\ProjectApp;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use DataSift\Http\Request;

use Tornado\Controller\ProjectDataAwareInterface;
use Tornado\Controller\ProjectDataAwareTrait;
use Tornado\Controller\Result;
use Tornado\DataMapper\DataMapperInterface;
use Tornado\Project\Project\DataMapper as ProjectRepository;
use Tornado\Project\Recording\DataMapper as RecordingRepository;

/**
 * Returns list of Recording which belongs to the Project given in url
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
class RecordingController implements ProjectDataAwareInterface
{
    use ProjectDataAwareTrait;

    /**
     * Recording repository.
     *
     * @var RecordingRepository
     */
    protected $recordingRepository;

    /**
     * Constructor.
     *
     * @param \Tornado\DataMapper\DataMapperInterface $recordingRepository
     */
    public function __construct(
        DataMapperInterface $recordingRepository
    ) {
        $this->recordingRepository = $recordingRepository;
    }

    /**
     * Retrieves the project info and fetch all recordings which belongs to this project
     *
     * @param int                    $projectId
     *
     * @return \Tornado\Controller\Result
     *
     * @throws NotFoundHttpException when project was not found
     */
    public function index($projectId)
    {
        $project = $this->getProject($projectId);

        // get the project's recordings
        $recordings = $this->recordingRepository->findByProject($project);
        return new Result($recordings, ['count' => count($recordings)]);
    }
}
