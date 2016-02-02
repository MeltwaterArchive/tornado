<?php

namespace Controller\ProjectApp;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use DataSift\Form\FormInterface;
use DataSift\Http\Request;

use Tornado\Controller\ProjectDataAwareInterface;
use Tornado\Controller\ProjectDataAwareTrait;
use Tornado\Controller\Result;
use Tornado\DataMapper\DataMapperInterface;
use Tornado\Organization\User;
use Tornado\Project\Workbook;
use Tornado\Project\Workbook\DataMapper;
use Tornado\Project\Workbook\Locker;

/**
 * Chart controller.
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
class ChartController implements ProjectDataAwareInterface
{
    use ProjectDataAwareTrait;

    /**
     * @var DataMapperInterface
     */
    protected $chartsRepository;

    /**
     * @var FormInterface
     */
    protected $updateForm;

    /**
     * @var \Tornado\Project\Workbook\Locker
     */
    protected $workbookLocker;

    /**
     * @var \Tornado\Organization\User
     */
    protected $sessionUser;

    /**
     * Constructor.
     *
     * @param \Tornado\DataMapper\DataMapperInterface $chartsRepository
     * @param \DataSift\Form\FormInterface            $updateForm
     * @param \Tornado\Project\Workbook\Locker        $workbookLocker
     * @param \Tornado\Organization\User              $sessionUser
     */
    public function __construct(
        DataMapperInterface $chartsRepository,
        FormInterface $updateForm,
        Locker $workbookLocker,
        User $sessionUser
    ) {
        $this->chartsRepository = $chartsRepository;
        $this->updateForm = $updateForm;
        $this->workbookLocker = $workbookLocker;
        $this->sessionUser = $sessionUser;
    }

    /**
     * Updates a chart.
     *
     * @param  Request $request     Request.
     * @param  integer $projectId   Project ID.
     * @param  integer $chartId     Chart ID.
     *
     * @return Result
     */
    public function update(Request $request, $projectId, $chartId)
    {
        $this->getProject($projectId);
        $chart = $this->chartsRepository->findOne(['id' => $chartId]);

        if (!$chart) {
            throw new NotFoundHttpException('Chart not found.');
        }

        $workbook = $this->workbookRepository->findOneByWorksheet(
            $this->worksheetRepository->findOne(['id' => $chart->getWorksheetId()])
        );
        if (!$this->isUserAllowedToEditWorkbook($workbook)) {
            return new Result([], ['error' => sprintf(
                'This Workbook is locked by "%s".',
                $this->workbookLocker->getLockingUser()->getEmail()
            )], Response::HTTP_FORBIDDEN);
        }

        $postParams = $request->getPostParams();
        $this->updateForm->submit($postParams, $chart);

        if (!$this->updateForm->isValid()) {
            return new Result([], $this->updateForm->getErrors(), Response::HTTP_BAD_REQUEST);
        }

        $chart = $this->updateForm->getData();
        $this->chartsRepository->update($chart);

        return new Result([
            'chart' => $chart
        ]);
    }

    /**
     * Delete a chart.
     *
     * @param  integer $projectId ID of the project.
     * @param  integer $chartId   ID of the chart.
     *
     * @return Result
     */
    public function delete($projectId, $chartId)
    {
        $this->getProject($projectId);
        $chart = $this->chartsRepository->findOne(['id' => $chartId]);

        if (!$chart) {
            throw new NotFoundHttpException('Chart not found.');
        }

        $workbook = $this->workbookRepository->findOneByWorksheet(
            $this->worksheetRepository->findOne(['id' => $chart->getWorksheetId()])
        );
        if (!$this->isUserAllowedToEditWorkbook($workbook)) {
            return new Result([], ['error' => sprintf(
                'This Workbook is locked by "%s".',
                $this->workbookLocker->getLockingUser()->getEmail()
            )], Response::HTTP_FORBIDDEN);
        }

        $this->chartsRepository->delete($chart);

        return new Result([]);
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
