<?php

namespace Controller\ProjectApp;

use MD\Foundation\Utils\StringUtils;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

use DataSift\Form\FormInterface;
use DataSift\Http\Request;

use Tornado\Analyze\TemplatedAnalyzer;
use Tornado\Controller\ProjectDataAwareInterface;
use Tornado\Controller\ProjectDataAwareTrait;
use Tornado\Controller\Result;
use Tornado\Organization\User;
use Tornado\Project\Recording\DataMapper as RecordingRepository;
use Tornado\Project\Workbook;
use Tornado\Project\Workbook\Locker;
use Tornado\Project\Worksheet\Generator as WorksheetsGenerator;
use Tornado\Project\Project;
use Tornado\Project\Worksheet\Exporter;

use Tornado\DataMapper\DataMapperInterface;

/**
 * Workbook CRUD controller.
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
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class WorkbookController implements ProjectDataAwareInterface
{
    use ProjectDataAwareTrait;

    /**
     * @var FormInterface
     */
    protected $createForm;

    /**
     * @var FormInterface
     */
    protected $updateForm;

    /**
     * Recording repository.
     *
     * @var RecordingRepository
     */
    protected $recordingRepository;

    /**
     * Worksheets generator.
     *
     * @var WorksheetsGenerator
     */
    protected $worksheetsGenerator;

    /**
     * Templated analyzer.
     *
     * @var TemplatedAnalyzer
     */
    protected $templatedAnalyzer;

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
     * @var Exporter
     */
    protected $exporter;

    /**
     * Name of the default workbook template.
     *
     * @var string
     */
    protected $defaultTemplate = 'default';

    /**
     * @param \DataSift\Form\FormInterface          $createForm
     * @param \DataSift\Form\FormInterface          $updateForm
     * @param \Tornado\Project\Recording\DataMapper $recordingRepository
     * @param \Tornado\Project\Worksheet\Generator  $worksheetsGenerator
     * @param \Tornado\Analyze\TemplatedAnalyzer    $templatedAnalyzer
     * @param \Tornado\Project\Workbook\Locker      $workbookLocker
     * @param \Tornado\Organization\User            $sessionUser
     * @param \Tornado\Project\Worksheet\Exporter   $exporter
     * @param string                                $defaultTemplate
     */
    public function __construct(
        FormInterface $createForm,
        FormInterface $updateForm,
        RecordingRepository $recordingRepository,
        WorksheetsGenerator $worksheetsGenerator,
        TemplatedAnalyzer $templatedAnalyzer,
        Locker $workbookLocker,
        User $sessionUser,
        Exporter $exporter,
        $defaultTemplate = 'default'
    ) {
        $this->createForm = $createForm;
        $this->updateForm = $updateForm;
        $this->recordingRepository = $recordingRepository;
        $this->worksheetsGenerator = $worksheetsGenerator;
        $this->templatedAnalyzer = $templatedAnalyzer;
        $this->workbookLocker = $workbookLocker;
        $this->sessionUser = $sessionUser;
        $this->exporter = $exporter;
        $this->defaultTemplate = $defaultTemplate;
    }

    /**
     * Lists workbooks and their worksheets belonging to the given project.
     *
     * @param  integer $projectId Project ID.
     *
     * @return Result
     */
    public function workbooks($projectId)
    {
        $project = $this->getProject($projectId);
        $workbooks = $this->workbookRepository->findByProject($project);
        $worksheets = $this->worksheetRepository->findByWorkbooks($workbooks);

        return new Result([
            'workbooks' => $workbooks
        ], [
            'workbooks' => count($workbooks),
            'worksheets' => count($worksheets)
        ]);
    }

    /**
     * Reads the workbook and its worksheets.
     *
     * @param  integer $projectId  Project ID.
     * @param  integer $workbookId Workbook ID.
     *
     * @return Result
     */
    public function workbook($projectId, $workbookId)
    {
        $project = $this->getProject($projectId);
        $workbook = $this->getWorkbook($project, $workbookId);
        $worksheets = $this->worksheetRepository->findByWorkbook($workbook);

        return new Result([
            'workbook' => $workbook
        ], [
            'worksheets' => count($worksheets)
        ]);
    }

    /**
     * Creates a workbook and returns it.
     *
     * @param  Request $request   Request.
     * @param  integer $projectId ID of the project in which to create the workbook.
     *
     * @return Result
     */
    public function create(Request $request, $projectId)
    {
        $project = $this->getProject($projectId);

        $postParams = $request->getPostParams();
        $postParams['project_id'] = $project->getId();

        $this->createForm->submit($postParams);
        if (!$this->createForm->isValid()) {
            return new Result([], $this->createForm->getErrors(), 400);
        }

        $workbook = $this->createForm->getData();

        $this->workbookRepository->create($workbook);

        $resultData = [
            'workbook' => $workbook,
            'worksheets' => []
        ];

        // if the project is fresh then also generate default worksheets for it
        if ($postParams['template']) {
            $recording = $this->recordingRepository->findOne(['id' => $workbook->getRecordingId()]);
            $resultData['worksheets'] = $this->worksheetsGenerator->generateFromTemplate(
                $workbook,
                $recording,
                $postParams['template']
            );
        }

        return new Result($resultData, [], Response::HTTP_CREATED);
    }

    /**
     * Creates default workbook and worksheets with default analyses for the given project.
     *
     * This only works for projects created with the API.
     *
     * @param integer $projectId Project ID.
     *
     * @return Result
     */
    public function createDefaults($projectId)
    {
        $project = $this->getProject($projectId);

        // validate that we can do this for this project
        if (!$project->isFresh()) {
            return new Result(
                [],
                ['error' => 'We can create default workbooks only for fresh projects'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if ($project->getType() != Project::TYPE_API ||
            $project->getRecordingFilter() != Project::RECORDING_FILTER_API
        ) {
            return new Result(
                [],
                ['error' => 'Default workbooks can only be created for projects created by the API.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // find appropriate recording
        $recordings = $this->recordingRepository->findByProject($project);
        $recording = current($recordings);

        if (!$recording) {
            return new Result(
                [],
                ['error' => 'Could not find a default recording for the project.'],
                Response::HTTP_NOT_FOUND
            );
        }

        // create the default workbook
        $template = $this->templatedAnalyzer->readTemplate($this->defaultTemplate);

        $this->createForm->submit([
            'project_id' => $project->getId(),
            'name' => $template['title'],
            'recording_id' => $recording->getId()
        ]);

        if (!$this->createForm->isValid()) {
            return new Result([], ['errors' => $this->createForm->getErrors()], Response::HTTP_BAD_REQUEST);
        }

        $workbook = $this->createForm->getData();
        $this->workbookRepository->create($workbook);

        // this project is no longer fresh :)
        $project->setFresh(0);
        $this->projectRepository->update($project);

        // finally generate all the default worksheets
        $worksheets = $this->worksheetsGenerator->generateFromTemplate(
            $workbook,
            $recording,
            $this->defaultTemplate
        );

        return new Result([
            'workbook' => $workbook,
            'worksheets' => $worksheets
        ], [], Response::HTTP_CREATED);
    }

    /**
     * Updates a workbook.
     *
     * @param  Request $request    Request.
     * @param  integer $projectId  Project ID.
     * @param  integer $workbookId Workbook ID.
     *
     * @return Result
     */
    public function update(Request $request, $projectId, $workbookId)
    {
        $project = $this->getProject($projectId);
        $workbook = $this->getWorkbook($project, $workbookId);

        if (!$this->isUserAllowedToEditWorkbook($workbook)) {
            return new Result([], ['error' => sprintf(
                'This Workbook is locked by "%s".',
                $this->workbookLocker->getLockingUser()->getEmail()
            )], Response::HTTP_FORBIDDEN);
        }

        $postParams = $request->getPostParams();
        $this->updateForm->submit($postParams, $workbook);
        if (!$this->updateForm->isValid()) {
            return new Result([], $this->updateForm->getErrors(), 400);
        }

        $workbook = $this->updateForm->getData();

        $this->workbookRepository->update($workbook);

        $worksheets = $this->worksheetRepository->findByWorkbook($workbook);

        return new Result([
            'workbook' => $workbook
        ], [
            'worksheets' => count($worksheets)
        ]);
    }

    /**
     * Delete a workbook.
     *
     * @param  integer $projectId  ID of the project.
     * @param  integer $workbookId ID of the workbook.
     *
     * @return Result
     */
    public function delete($projectId, $workbookId)
    {
        $project = $this->getProject($projectId);
        $workbook = $this->getWorkbook($project, $workbookId);

        if (!$this->isUserAllowedToEditWorkbook($workbook)) {
            return new Result([], ['error' => sprintf(
                'This Workbook is locked by "%s".',
                $this->workbookLocker->getLockingUser()->getEmail()
            )], Response::HTTP_FORBIDDEN);
        }

        $this->workbookRepository->delete($workbook);

        return new Result([]);
    }

    /**
     * Locks given Workbook.
     *
     * @param int $projectId
     * @param int $workbookId
     *
     * @return \Tornado\Controller\Result based on the WorkbookLocker service it returns different HTTP statuses:
     *  - 201: lock set with fresh TTL reset limit counter
     *  - 403: cannot set lock due to other user lock
     */
    public function lock($projectId, $workbookId)
    {
        $project = $this->getProject($projectId);
        $workbook = $this->getWorkbook($project, $workbookId);

        // if not locked, lock it
        if (!$this->workbookLocker->isLocked($workbook)) {
            $this->workbookLocker->lock($workbook, $this->sessionUser);
            return new Result([], [], Response::HTTP_CREATED);
        }

        // if locked, checks by who
        if (!$this->workbookLocker->isGranted($workbook, $this->sessionUser)) {
            return new Result([], ['error' => sprintf(
                'This Workbook is locked by "%s". Only read access allowed.',
                $this->workbookLocker->getLockingUser()->getEmail()
            )], Response::HTTP_FORBIDDEN);
        }

        // if locked by session user, re-lock it to reset TTL reset limit counter
        $this->workbookLocker->lock($workbook, $this->sessionUser);
        return new Result([], [], Response::HTTP_CREATED);
    }

    /**
     * Resets Workbook lock TTL.
     *
     * @param int $projectId
     * @param int $workbookId
     *
     * @return \Tornado\Controller\Result based on the WorkbookLocker service it returns different HTTP statuses:
     *  - 404: workbook lock not found
     *  - 403: cannot reset TTL due to other user lock
     *  - 409: workbook TTL exceeded top limit of reset action
     *  - 200: TTL successful reset and counter decreased by 1
     */
    public function ttlReset($projectId, $workbookId)
    {
        $project = $this->getProject($projectId);
        $workbook = $this->getWorkbook($project, $workbookId);

        if (!$this->workbookLocker->isLocked($workbook)) {
            return new Result([], [
                'error' => 'Workbook is not locked to reset its TTL.'
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$this->workbookLocker->isGranted($workbook, $this->sessionUser)) {
            return new Result([], ['error' => sprintf(
                'This Workbook is locked by "%s". Try later.',
                $this->workbookLocker->getLockingUser()->getEmail()
            )], Response::HTTP_FORBIDDEN);
        }

        $remainingLimit = $this->workbookLocker->resetTtl($workbook, $this->sessionUser);
        // counter may be 0 which should be handled a correct value
        if (!$remainingLimit && 0 !== $remainingLimit) {
            return new Result(
                [],
                ['error' => sprintf(
                    'This Workbook was inactive for long time. Going to be unlocked in %d seconds.',
                    $this->workbookLocker->getTtl()
                )],
                Response::HTTP_CONFLICT
            );
        }

        return new Result([], [
            'remaining_counter' => $remainingLimit,
            'ttl' => $this->workbookLocker->getTtl()
        ], Response::HTTP_OK);
    }

    /**
     * Unlocks given Workbook.
     *
     * @param int $projectId
     * @param int $workbookId
     *
     * @return \Tornado\Controller\Result based on the WorkbookLocker service it returns different HTTP statuses:
     *  - 204: workbook unlocked
     *  - 204: lock does not exists but handles this as a successful unlocking
     */
    public function unlock($projectId, $workbookId)
    {
        $project = $this->getProject($projectId);
        $workbook = $this->getWorkbook($project, $workbookId);

        if (!$this->workbookLocker->isLocked($workbook)) {
            return new Result([], [], Response::HTTP_NO_CONTENT);
        }

        if (!$this->workbookLocker->isGranted($workbook, $this->sessionUser)) {
            return new Result([], [
                'error' => 'You are not granted to unlock this Workbook.'
            ], Response::HTTP_FORBIDDEN);
        }

        $this->workbookLocker->unlock($workbook);
        return new Result([], [], Response::HTTP_NO_CONTENT);
    }

    /**
     * Exports a workbook in ZIP format.
     *
     * @param  integer $projectId   Project ID.
     * @param  integer $workbookId  Workbook ID.
     *
     * @return StreamedResponse
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function export($projectId, $workbookId)
    {
        $project = $this->getProject($projectId);
        $workbook = $this->getWorkbook($project, $workbookId);

        $worksheets = $this->worksheetRepository->find(
            ['workbook_id' => $workbook->getId()],
            ['rank' => DataMapperInterface::ORDER_ASCENDING]
        );

        $filename = '/tmp/' . microtime(true) . '.zip';

        $this->exporter->exportWorksheets($filename, $worksheets);

        $response = new StreamedResponse(function () use ($filename) {
            @passthru("cat {$filename}", $err);
            if (file_exists($filename)) {
                unlink($filename);
            }
        });

        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition', sprintf(
            'attachment; filename="%s.zip"',
            StringUtils::fileNameFriendly($workbook->getName())
        ));

        return $response;
    }

    /**
     * Gets a list of available Workbook templates
     *
     * @return \Tornado\Controller\Result
     */
    public function templates()
    {
        $templates = $this->templatedAnalyzer->getTemplates();

        $outp = [
            ['id' => '', 'title' => '', 'description' => ''] // No template
        ];

        foreach ($templates as $key => $template) {
            $outp[] = [
                'id' => $key,
                'title' => $template['title'],
                'description' => (isset($template['description'])) ? $template['description'] : ''
            ];
        }

        return new Result($outp);
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
