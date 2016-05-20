<?php

namespace Controller\ProjectApp;

use MD\Foundation\Utils\StringUtils;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use DataSift\Form\FormInterface;
use DataSift\Http\Request;

use Tornado\Controller\ProjectDataAwareInterface;
use Tornado\Controller\ProjectDataAwareTrait;
use Tornado\Controller\Result;
use Tornado\DataMapper\DataMapperInterface;
use Tornado\Organization\User;
use Tornado\Project\Recording\Sample;
use Tornado\Project\Workbook;
use Tornado\Project\Workbook\Locker;
use Tornado\Project\Worksheet;
use Tornado\Project\Worksheet\Explorer;
use Tornado\Project\Worksheet\Exporter;

use Tornado\Project\Recording\DataMapper as RecordingRepository;
use Tornado\Project\Recording\Sample\DataMapper as RecordingSampleRepository;

/**
 * Displays a project worksheet.
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
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects,PHPMD.ExcessiveParameterList)
 */
class WorksheetController implements ProjectDataAwareInterface
{
    use ProjectDataAwareTrait;

    /**
     * Charts repository.
     *
     * @var DataMapperInterface
     */
    protected $chartRepository;

    /**
     * @var FormInterface
     */
    protected $createForm;

    /**
     * @var FormInterface
     */
    protected $exploreForm;

    /**
     * @var FormInterface
     */
    protected $updateForm;

    /**
     * @var Explorer
     */
    protected $explorer;

    /**
     * @var Exporter
     */
    protected $exporter;

    /**
     * Workbook Locker.
     *
     * @var Locker
     */
    protected $workbookLocker;

    /**
     * @var \Tornado\Organization\User
     */
    protected $sessionUser;

    /**
     * Recording repository.
     *
     * @var RecordingRepository
     */
    protected $recordingRepo;

    /**
     * Recording repository.
     *
     * @var RecordingSampleRepository
     */
    protected $recordingSampleRepo;

    /**
     * Constructor.
     *
     * @param \Tornado\DataMapper\DataMapperInterface $chartRepository
     * @param \DataSift\Form\FormInterface            $createForm
     * @param \DataSift\Form\FormInterface            $exploreForm
     * @param \DataSift\Form\FormInterface            $updateForm
     * @param \Tornado\Project\Worksheet\Explorer     $explorer
     * @param \Tornado\Project\Worksheet\Exporter     $exporter
     * @param \Tornado\Project\Workbook\Locker        $workbookLocker
     * @param \Tornado\Organization\User              $sessionUser
     * @param RecordingRepository                     $recordingRepo
     * @param RecordingSampleRepository               $recordingSampleRepo
     */
    public function __construct(
        DataMapperInterface $chartRepository,
        FormInterface $createForm,
        FormInterface $exploreForm,
        FormInterface $updateForm,
        Explorer $explorer,
        Exporter $exporter,
        Locker $workbookLocker,
        User $sessionUser,
        RecordingRepository $recordingRepo,
        RecordingSampleRepository $recordingSampleRepo
    ) {
        $this->chartRepository = $chartRepository;
        $this->createForm = $createForm;
        $this->exploreForm = $exploreForm;
        $this->updateForm = $updateForm;
        $this->explorer = $explorer;
        $this->exporter = $exporter;
        $this->workbookLocker = $workbookLocker;
        $this->sessionUser = $sessionUser;
        $this->recordingRepo = $recordingRepo;
        $this->recordingSampleRepo = $recordingSampleRepo;
    }

    /**
     * Retrieves project info and all related stuff based on the passed project ID.
     *
     * @param  Request $request     The request object
     * @param  integer $projectId   ID of the project.
     * @param  integer $worksheetId ID of the visible worksheet.
     *
     * @return Result
     *
     * @throws NotFoundHttpException When such project or worksheet was not found.
     */
    public function index(Request $request, $projectId, $worksheetId)
    {
        list($project, $workbook, $worksheet) = $this->getProjectDataForWorksheetId($worksheetId, $projectId);

        $charts = $this->chartRepository->findByWorksheet($worksheet);
        $sqlFilter = ['recording_id' => $workbook->getRecordingId()];
        if ($worksheet->getFilter('generated_csdl') != null) {
            $sqlFilter['filter_hash'] = md5($worksheet->getFilter('generated_csdl'));
        }
        $posts = $this->recordingSampleRepo->find(
            $sqlFilter,
            ['created_at' => 'DESC'],
            Sample::RESULT_LIMIT,
            $request->get('sample-offset')
        );

        return new Result([
            'project' => $project,
            'workbook' => $workbook,
            'worksheet' => $worksheet,
            'charts' => $charts,
            'posts' => $posts
        ]);
    }

    /**
     * Creates a worksheet and returns it.
     *
     * @param  Request $request   Request.
     * @param  integer $projectId ID of the project.
     *
     * @return Result
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function create(Request $request, $projectId)
    {
        $project = $this->getProject($projectId);
        $brand = $this->getBrand($project->getBrandId());

        $postParams = $request->getPostParams();
        if (!isset($postParams['workbook_id'])) {
            throw new NotFoundHttpException('Workbook not found');
        }

        $workbook = $this->workbookRepository->findOne(['id' => $postParams['workbook_id']]);

        $recording = $this->recordingRepo->findOne(['id' => $workbook->getRecordingId()]);
        if (!$recording) {
            throw new NotFoundHttpException(sprintf(
                'Could not find recording with ID %s.',
                $workbook->getRecordingId()
            ));
        }

        $ws = new Worksheet();
        $ws->setWorkbookId($workbook->getId());
        $postParams['name'] = $this->worksheetRepository->getUniqueName($ws, $postParams['name']);

        $this->createForm->submit($postParams, null, $recording, $brand->getTargetPermissions());
        if (!$this->createForm->isValid()) {
            return new Result([], $this->createForm->getErrors(), Response::HTTP_BAD_REQUEST);
        }

        $worksheet = $this->createForm->getData();

        if (!$this->isUserAllowedToEditWorkbook($workbook)) {
            return new Result([], ['error' => sprintf(
                'This Workbook is locked by "%s".',
                $this->workbookLocker->getLockingUser()->getEmail()
            )], Response::HTTP_FORBIDDEN);
        }

        $this->worksheetRepository->create($worksheet);

        return new Result(
            [
                'project' => $project,
                'worksheet' => $worksheet
            ],
            [],
            Response::HTTP_CREATED
        );
    }

    /**
     * Updates a worksheet.
     *
     * @param  Request $request     Request.
     * @param  integer $projectId   Project ID.
     * @param  integer $worksheetId Worksheet ID.
     *
     * @return Result
     */
    public function update(Request $request, $projectId, $worksheetId)
    {
        list($project, $workbook, $worksheet) = $this->getProjectDataForWorksheetId($worksheetId, $projectId);

        if (!$this->isUserAllowedToEditWorkbook($workbook)) {
            return new Result([], ['error' => sprintf(
                'This Workbook is locked by "%s".',
                $this->workbookLocker->getLockingUser()->getEmail()
            )], Response::HTTP_FORBIDDEN);
        }

        $postParams = $request->getPostParams();
        // add workbook ID to trigger unique name check
        $postParams['workbook_id'] = $workbook->getId();

        $this->updateForm->submit($postParams, $worksheet);
        if (!$this->updateForm->isValid()) {
            return new Result([], $this->updateForm->getErrors(), Response::HTTP_BAD_REQUEST);
        }

        $worksheet = $this->updateForm->getData();

        $this->worksheetRepository->update($worksheet);

        return new Result([
            'project' => $project,
            'worksheet' => $worksheet
        ]);
    }

    /**
     * Creates a worksheet via explore and returns it.
     *
     * @param  Request $request   Request.
     * @param  integer $projectId ID of the project.
     *
     * @return Result
     */
    public function explore(Request $request, $projectId, $worksheetId)
    {
        list($project, $workbook, $worksheet, $brand) = $this->getProjectDataForWorksheetId($worksheetId, $projectId);

        if (!$this->isUserAllowedToEditWorkbook($workbook)) {
            return new Result([], ['error' => sprintf(
                'This Workbook is locked by "%s".',
                $this->workbookLocker->getLockingUser()->getEmail()
            )], Response::HTTP_FORBIDDEN);
        }

        $postParams = $request->getPostParams();
        if (isset($postParams['explore']) && is_string($postParams['explore'])) {
            $postParams['explore'] = json_decode($postParams['explore'], true);
        }

        // add workbook ID to trigger unique name check
        $postParams['workbook_id'] = $workbook->getId();

        $recording =
            ($this->recordingRepo)
            ? $this->recordingRepo->findOne(['id' => $workbook->getRecordingId()])
            : null;

        $this->exploreForm->submit($postParams, null, $recording, $brand->getTargetPermissions());

        if (!$this->exploreForm->isValid()) {
            return new Result([], $this->exploreForm->getErrors(), Response::HTTP_BAD_REQUEST);
        }

        $data = $this->exploreForm->getData();

        $newWorksheet = $this->explorer->explore(
            $worksheet,
            $data['name'],
            $data['explore'],
            $data['start'],
            $data['end'],
            $data['chart_type'],
            $data['type']
        );

        $this->worksheetRepository->create($newWorksheet);

        return new Result(
            [
                'project' => $project,
                'workbook' => $workbook,
                'worksheet' => $newWorksheet
            ],
            [],
            Response::HTTP_CREATED
        );
    }

    /**
     * Exports a worksheet in CSV format.
     *
     * @param  integer $projectId   Project ID.
     * @param  integer $worksheetId Worksheet ID.
     *
     * @return StreamedResponse
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function export($projectId, $worksheetId)
    {
        list($project, $workbook, $worksheet) = $this->getProjectDataForWorksheetId($worksheetId, $projectId);

        $response = new StreamedResponse(function () use ($worksheet) {
            $output = fopen('php://output', 'w');

            foreach ($this->exporter->exportWorksheetGenerator($worksheet) as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
        });

        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition', sprintf(
            'attachment; filename="%s.csv"',
            StringUtils::fileNameFriendly($worksheet->getName())
        ));

        return $response;
    }

    /**
     * Delete a worksheet.
     *
     * @param  integer $projectId   ID of the project.
     * @param  integer $worksheetId ID of the visible worksheet.
     *
     * @return Result
     *
     * @throws NotFoundHttpException When such project or worksheet was not found.
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function delete($projectId, $worksheetId)
    {
        list($project, $workbook, $worksheet) = $this->getProjectDataForWorksheetId($worksheetId, $projectId);

        if (!$this->isUserAllowedToEditWorkbook($workbook)) {
            return new Result([], ['error' => sprintf(
                'This Workbook is locked by "%s".',
                $this->workbookLocker->getLockingUser()->getEmail()
            )], Response::HTTP_FORBIDDEN);
        }

        $this->worksheetRepository->delete($worksheet);

        return new Result([]);
    }

    /**
     * Duplicates a worksheet.
     *
     * @param  integer $projectId   Project ID.
     * @param  integer $worksheetId Worksheet ID.
     *
     * @return Result
     */
    public function duplicate($projectId, $worksheetId)
    {
        list($project, $workbook, $worksheet) = $this->getProjectDataForWorksheetId($worksheetId, $projectId);

        if (!$this->isUserAllowedToEditWorkbook($workbook)) {
            return new Result([], ['error' => sprintf(
                'This Workbook is locked by "%s".',
                $this->workbookLocker->getLockingUser()->getEmail()
            )], Response::HTTP_FORBIDDEN);
        }

        $newWorksheet = clone($worksheet);
        $newWorksheet->setName(
            $this->worksheetRepository->getUniqueName($newWorksheet, "Copy of {$newWorksheet->getName()}")
        );
        $this->worksheetRepository->create($newWorksheet);

        $charts = $this->chartRepository->find(['worksheet_id' => $worksheet->getId()]);
        $newCharts = [];
        foreach ($charts as $chart) {
            $newChart = clone($chart);
            $newChart->setWorksheetId($newWorksheet->getId());
            $newCharts[] = $newChart;
            $this->chartRepository->create($newChart);
        }

        return new Result(
            [
                'project' => $project,
                'worksheet' => $newWorksheet,
                'charts' => $newCharts
            ],
            [],
            Response::HTTP_CREATED
        );
    }

    /**
     * Checks if user is granted to modify the workbook data
     *
     * @param \Tornado\Project\Workbook $workbook
     *
     * @return bool
     */
    protected function isUserAllowedToEditWorkbook(Workbook $workbook)
    {
        if ($this->workbookLocker->isLocked($workbook) &&
            !$this->workbookLocker->isGranted($workbook, $this->sessionUser)
        ) {
            return false;
        }

        return true;
    }
}
