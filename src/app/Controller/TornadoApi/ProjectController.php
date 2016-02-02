<?php

namespace Controller\TornadoApi;

use MD\Foundation\Utils\ObjectUtils;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception as HttpException;

use DataSift\Http\Request;
use DataSift_Pylon;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\Paginator;
use Tornado\Organization\Agency;
use Tornado\Organization\Brand;
use Tornado\Organization\Brand\DataMapper as BrandRepository;
use Tornado\Project\Project;
use Tornado\Project\Project\Form\Create as CreateProjectForm;
use Tornado\Project\Project\Form\Update as UpdateProjectForm;
use Tornado\Project\Project\DataMapper as ProjectRepository;
use Tornado\Project\Recording;
use Tornado\Project\Recording\DataMapper as RecordingRepository;
use Tornado\Project\Recording\Form\Create as CreateRecordingForm;

/**
 * ProjectController
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Controller\Api\Tornado
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects,PHPMD.ExcessiveClassComplexity)
 */
class ProjectController
{

    /**
     * @var \Tornado\Project\Recording\DataMapper
     */
    protected $recordingRepo;

    /**
     * @var \Tornado\Project\Project\DataMapper
     */
    protected $projectRepo;

    /**
     * @var \Tornado\Project\Project\Form\Create
     */
    protected $createProjectForm;

    /**
     * @var \Tornado\Project\Project\Form\Update
     */
    protected $updateProjectForm;

    /**
     * @var \Tornado\Project\Recording\Form\Create
     */
    protected $createRecordingForm;

    /**
     * The PYLON client
     *
     * @var \DataSift_Pylon
     */
    protected $pylon;

    /**
     * Constructs a new Project API controller
     *
     * @param \Tornado\Project\Recording\DataMapper $recordingRepo
     * @param \Tornado\Project\Project\DataMapper $projectRepo
     * @param \Tornado\Project\Project\Form\Create $createProjectForm
     * @param \Tornado\Project\Project\Form\Update $updateProjectForm
     * @param \Tornado\Project\Recording\Form\Create $createRecordingForm
     * @param DataSift_Pylon $pylon
     */
    public function __construct(
        RecordingRepository $recordingRepo,
        ProjectRepository $projectRepo,
        CreateProjectForm $createProjectForm,
        UpdateProjectForm $updateProjectForm,
        CreateRecordingForm $createRecordingForm,
        DataSift_Pylon $pylon
    ) {
        $this->recordingRepo = $recordingRepo;
        $this->projectRepo = $projectRepo;
        $this->createProjectForm = $createProjectForm;
        $this->updateProjectForm = $updateProjectForm;
        $this->createRecordingForm = $createRecordingForm;
        $this->pylon = $pylon;
    }

    /**
     * Shows a single project info.
     *
     * @param Request $request Request.
     * @param integer $id      Project ID.
     *
     * @return JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $project = $this->getProject($request, $id);
        } catch (HttpException\HttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        }

        $recordings = $this->recordingRepo->findByProject($project);
        $responseData = $this->getProjectViewData($project, $recordings);
        return new JsonResponse($responseData, Response::HTTP_OK);
    }

    /**
     * Creates the Project
     *
     * @param  Request $request Request.
     *
     * @return JsonResponse
     */
    public function create(Request $request)
    {
        $brand = $request->attributes->get('brand');
        if (!$brand || !$brand instanceof Brand) {
            return new JsonResponse(['error' => 'Authorization failed.'], Response::HTTP_UNAUTHORIZED);
        }

        $recordingIds = $request->get('recordings', false);
        if ($recordingIds == false) {
            $recordingId = $request->get('recording_id', false);
            $recordingIds = [];
            if ($recordingId) {
                $recordingIds = [$recordingId];
            }
        }

        if (!is_array($recordingIds)) {
            return new JsonResponse(
                [
                    'error' => 'The recordings field must be an array of recording ids'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        // validate project creation
        $this->createProjectForm->submit([
            'brand_id' => $brand->getId(),
            'name' => $request->get('name')
        ]);

        if (!$this->createProjectForm->isValid()) {
            return new JsonResponse(
                [
                    'errors' => $this->createProjectForm->getErrors()
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $recordings = $this->processRecordings($brand, $recordingIds);
        if ($recordings instanceof JsonResponse) {
            return $recordings;
        }

        // get those two entities and link them
        $project = $this->createProjectForm->getData();
        $project->setType(Project::TYPE_API);
        $project->setRecordingFilter(Project::RECORDING_FILTER_API);

        $this->projectRepo->create($project);

        $this->saveRecordings($recordings, $project);

        // and return all
        $responseData = $this->getProjectViewData($project, $recordings);
        return new JsonResponse($responseData, Response::HTTP_CREATED);
    }

    /**
     * Gets the list of Projects including related recordings
     *
     * @param  Request $request Request.
     *
     * @return JsonResponse
     */
    public function get(Request $request)
    {
        $brand = $request->attributes->get('brand');
        if (!$brand || !$brand instanceof Brand) {
            return new JsonResponse(['error' => 'Authorization failed.'], Response::HTTP_UNAUTHORIZED);
        }

        $paginator = new Paginator(
            $this->projectRepo,
            $request->get('page', 1),
            $request->get('sort', 'created_at'),
            $request->get('per_page', 25),
            $request->get('order', DataMapperInterface::ORDER_ASCENDING)
        );
        $paginator->paginate(['brand_id' => $brand->getId()]);

        $responseData = [];
        $projects = $paginator->getCurrentItems();
        if ($projects) {
            $projectIds = ObjectUtils::pluck($projects, 'id');
            $recordings = $this->recordingRepo->findByProjectIds($projectIds);

            foreach ($projects as $project) {
                $responseData[] = $this->getProjectViewData($project, $recordings);
            }
        }

        return new JsonResponse([
            'page' => $paginator->getCurrentPage(),
            'pages' => $paginator->getTotalPages(),
            'per_page' => $paginator->getPerPage(),
            'count' => $paginator->getTotalItemsCount(),
            'projects' => $responseData
        ], Response::HTTP_OK);
    }

    /**
     * Deletes the given Project without stopping any of associated Recordings
     *
     * @param \DataSift\Http\Request $request
     * @param int                    $id
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function delete(Request $request, $id)
    {
        try {
            $project = $this->getProject($request, $id);
        } catch (HttpException\HttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        }

        $this->projectRepo->delete($project);
        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Updates the Project.
     *
     * @param Request $request Request.
     * @param integer $id      Project ID.
     *
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $project = $this->getProject($request, $id);
        } catch (HttpException\HttpException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        }

        $params = [
            'name' => $request->get('name', $project->getName())
        ];

        $this->updateProjectForm->submit($params, $project);
        if (!$this->updateProjectForm->isValid()) {
            return new JsonResponse(['errors' => $this->updateProjectForm->getErrors()], Response::HTTP_BAD_REQUEST);
        }

        $project = $this->updateProjectForm->getData();
        $recordingIds = $request->get('recordings', false);
        $existingRecordings = $this->recordingRepo->findByProject($project);

        if ($recordingIds !== false) {
            $recordings = $this->processRecordings($request->attributes->get('brand'), $recordingIds, $project);
            if ($recordings instanceof JsonResponse) {
                return $recordings;
            }

            foreach ($existingRecordings as $recording) {
                if (!isset($recordings[$recording->getDataSiftRecordingId()])) {
                    $recording->setProjectId(null);
                    $recordings[$recording->getDataSiftRecordingId()] = $recording;
                }
            }
        }

        $this->projectRepo->update($project);
        if ($recordingIds === false) {
            $recordings = $existingRecordings;
        } else {
            $recordings = $this->saveRecordings($recordings, $project, true);
        }

        $responseData = $this->getProjectViewData($project, $recordings);
        return new JsonResponse($responseData, Response::HTTP_OK);
    }

    /**
     * Gets a project and guards access to it.
     *
     * @param Request $request HTTP Request.
     * @param integer $id      Project ID.
     *
     * @return Project
     *
     * @throws HttpException\HttpException When the project cannot be accessed for some reason.
     */
    protected function getProject(Request $request, $id)
    {
        $brand = $request->attributes->get('brand');
        if (!$brand || !$brand instanceof Brand) {
            throw new HttpException\UnauthorizedHttpException('', 'Authorization failed.');
        }

        $project = $this->projectRepo->findOne(['id' => $id]);

        if (!$project) {
            throw new HttpException\NotFoundHttpException('Project not found.');
        }

        if ($project->getBrandId() !== $brand->getId()) {
            throw new HttpException\AccessDeniedHttpException('Access forbidden.');
        }

        return $project;
    }

    /**
     * Transforms Tornado Project object to ProjectApiView
     *
     * @param \Tornado\Project\Project $project
     * @param array                    $recordings
     *
     * @return array
     */
    protected function getProjectViewData(Project $project, array $recordings = [])
    {
        $recordings = $project->getRecordingFilter() === Project::RECORDING_FILTER_API
            ? ObjectUtils::filter($recordings, 'project_id', $project->getId())
            : $recordings;

        $projectViewData = [];
        $projectViewData['id'] = $project->getId();
        $projectViewData['name'] = $project->getName();
        $projectViewData['recordings'] = ObjectUtils::pluck($recordings, 'dataSiftRecordingId');

        return $projectViewData;
    }

    /**
     * Saves a list of Recordings
     *
     * @param array $recordings
     * @param \Tornado\Project\Project $project
     * @param boolean $cleanDelta
     *
     * @return array
     */
    protected function saveRecordings(array $recordings, Project $project, $cleanDelta = false)
    {
        foreach ($recordings as $idx => $recording) {
            if ($cleanDelta && $recording->getProjectId() == null) {
                unset($recordings[$idx]);
            } else {
                $recording->setProjectId($project->getId());
            }

            $this->recordingRepo->upsert($recording);
        }
        return $recordings;
    }

    /**
     * Processes a list of recording IDs for the given Brand and/or Project
     *
     * @param \Tornado\Organization\Brand $brand
     * @param array $recordingIds
     * @param \Tornado\Project\Project $project
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function processRecordings(Brand $brand, array $recordingIds, Project $project = null)
    {
        $recordings = [];
        foreach ($recordingIds as $recordingId) {
            if (!(is_string($recordingId) && preg_match('/^[a-f0-9]{32,}$/', $recordingId))) {
                return new JsonResponse(
                    [
                        'error' => 'The recordings field must be an array of recording ids'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }
            try {
                $recordings[$recordingId] = $this->findOrImportRecording($brand, $recordingId, $project);
                if ($project) {
                    $recordings[$recordingId]->setProjectId($project->getId());
                }
            } catch (\RuntimeException $ex) {
                if (in_array($ex->getCode(), [Response::HTTP_CONFLICT, Response::HTTP_NOT_FOUND])) {
                    return new JsonResponse(['error' => $ex->getMessage()], $ex->getCode());
                }
                throw $ex;
            }
        }
        return $recordings;
    }

    /**
     * Either finds the recording with id $id or imports it from the underlying
     * PYLON API
     *
     * @param \Tornado\Organization\Brand $brand
     * @param string $id
     * @param \Tornado\Project\Project|null $project
     *
     * @return \Tornado\Project\Recording
     *
     * @throws \RuntimeException
     */
    protected function findOrImportRecording(Brand $brand, $id, Project $project = null)
    {

        // Does the Recording exist in Tornado for this Brand?
        $recording = $this->recordingRepo->findOne(['brand_id' => $brand->getId(), 'datasift_recording_id' => $id]);
        if ($recording) {
            // Is it assigned to another Project?
            if ($recording->getProjectId() && ($project == null || $recording->getProjectId() != $project->getId())) {
                // Yes? Error
                throw new \RuntimeException(
                    "Recording {$id} is already associated with Project {$recording->getProjectId()}",
                    Response::HTTP_CONFLICT
                );
            }
            return $recording;
        }

        $recording = $this->recordingRepo->importRecording($this->pylon, $id);

        if (!$recording) {
            throw new \RuntimeException("Recording {$id} was not found", Response::HTTP_NOT_FOUND);
        }

        $recording->setBrandId($brand->getId());

        return $recording;
    }
}
