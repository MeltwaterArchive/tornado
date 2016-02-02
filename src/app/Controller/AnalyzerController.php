<?php

namespace Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use DataSift\Http\Request;
use DataSift\Form\FormInterface;

use Tornado\Analyze\Dimension;
use Tornado\Analyze\Analysis;
use Tornado\Analyze\Analyzer;
use Tornado\Analyze\DataSet;
use Tornado\Analyze\DataSet\Generator as DataSetGenerator;
use Tornado\Analyze\DataSet\Generator\RedactedException;
use Tornado\Controller\ProjectDataAwareInterface;
use Tornado\Controller\ProjectDataAwareTrait;
use Tornado\Controller\Result;
use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\DoctrineRepository;
use Tornado\Organization\User;
use Tornado\Project\Chart;
use Tornado\Project\Chart\Factory as ChartFactory;
use Tornado\Project\Chart\DataMapper as ChartRepository;
use Tornado\Project\Workbook;
use Tornado\Project\Workbook\Locker;
use Tornado\Project\Worksheet;
use Tornado\Project\Recording;
use Tornado\Project\Recording\DataMapper as RecordingRepository;

/**
 * AnalyzerController
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Controller
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects,PHPMD.NPathComplexity,PHPMD.CyclomaticComplexity)
 */
class AnalyzerController implements ProjectDataAwareInterface
{
    use ProjectDataAwareTrait;

    /**
     * @var RecordingRepository
     */
    protected $recordingRepository;

    /**
     * @var ChartRepository
     */
    protected $chartRepository;

    /**
     * @var DoctrineRepository
     */
    protected $datasetRepository;

    /**
     * @var \DataSift\Form\FormInterface
     */
    protected $createForm;

    /**
     * @var \Tornado\Analyze\Analyzer
     */
    protected $analyzer;

    /**
     * @var \Tornado\Analyze\DataSet\Generator
     */
    protected $datasetGenerator;

    /**
     * @var \Tornado\Project\Chart\Factory
     */
    protected $chartFactory;

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
     * @param \Tornado\DataMapper\DataMapperInterface $recordingRepository
     * @param \Tornado\DataMapper\DataMapperInterface $chartRepository
     * @param \Tornado\DataMapper\DataMapperInterface $datasetRepository
     * @param \DataSift\Form\FormInterface            $createForm
     * @param \Tornado\Analyze\Analyzer               $analyzer
     * @param \Tornado\Analyze\DataSet\Generator      $datasetGenerator
     * @param \Tornado\Project\Chart\Factory          $chartFactory
     * @param \Tornado\Project\Workbook\Locker        $workbookLocker
     * @param \Tornado\Organization\User              $sessionUser
     */
    public function __construct(
        DataMapperInterface $recordingRepository,
        DataMapperInterface $chartRepository,
        DataMapperInterface $datasetRepository,
        FormInterface $createForm,
        Analyzer $analyzer,
        DataSetGenerator $datasetGenerator,
        ChartFactory $chartFactory,
        Locker $workbookLocker,
        User $sessionUser
    ) {
        $this->recordingRepository = $recordingRepository;
        $this->chartRepository = $chartRepository;
        $this->datasetRepository = $datasetRepository;
        $this->createForm = $createForm;
        $this->analyzer = $analyzer;
        $this->datasetGenerator = $datasetGenerator;
        $this->chartFactory = $chartFactory;
        $this->workbookLocker = $workbookLocker;
        $this->sessionUser = $sessionUser;
    }

    /**
     * Creates the set of charts for given Recording and Worksheet
     *
     * @param \DataSift\Http\Request $request
     *
     * @return \Tornado\Controller\Result
     *
     * @throws NotFoundHttpException When worksheet or project was not found.
     * @throws ConflictHttpException When analyzer receives both secondary_recording_id and baseline_dataset_id
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function create(Request $request)
    {
        $postParams = $request->getPostParams();
        $postParams['worksheet_id'] = isset($postParams['worksheet_id']) ? $postParams['worksheet_id'] : 0;
        list($project, $workbook, $worksheet, $brand) = $this->getProjectDataForWorksheetId(
            $postParams['worksheet_id']
        );

        if (!$this->isUserAllowedToEditWorkbook($workbook)) {
            return new Result([], ['error' => sprintf(
                'This Workbook is locked by "%s".',
                $this->workbookLocker->getLockingUser()->getEmail()
            )], Response::HTTP_FORBIDDEN);
        }

        $recording = $this->recordingRepository->findOne(['id' => $workbook->getRecordingId()]);
        if (!$recording) {
            throw new NotFoundHttpException(sprintf(
                'Could not find recording with ID %s.',
                $workbook->getRecordingId()
            ));
        }

        if ($worksheet->getAnalysisType() == Analysis::TYPE_TIME_SERIES) {
            $postParams['dimensions'] = [['target' => Dimension::TIME]];
        }

        $permissions = ($brand) ? $brand->getTargetPermissions() : [];

        //var_dump($postParams);

        $this->createForm->submit($postParams, $worksheet, $recording, $permissions);
        if (!$this->createForm->isValid()) {
            return new Result([], $this->createForm->getErrors(), Response::HTTP_BAD_REQUEST);
        }

        $worksheet = $this->createForm->getData($recording, $permissions);
        try {
            $charts = $this->getCharts($worksheet, $recording);
        } catch (BadRequestHttpException $ex) {
            return new Result(
                [],
                ['error' => $ex->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        } catch (RedactedException $ex) {
            return new Result(
                [],
                ['error' => 'Sorry, there is no data for the given analysis'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // delete all existing Worksheet charts
        $this->chartRepository->deleteByWorksheet($worksheet);

        foreach ($charts as $index => $chart) {
            $chart->setWorksheetId($worksheet->getId());
            $chart->setRank($index);

            $this->chartRepository->create($chart);
        }

        // update worksheet at the very end for ensuring that PYLON api was done successfully.
        // That will prevent from updating worksheet data in db when i.e. csdlQuery filter is invalid|illogical
        $this->worksheetRepository->update($worksheet);

        return new Result($charts, ['charts_count' => count($charts)]);
    }

    /**
     *
     * @param \Tornado\Project\Worksheet $worksheet
     * @param \Tornado\Project\Recording $recording
     *
     * @return array
     */
    protected function getCharts(Worksheet $worksheet, Recording $recording)
    {
        if ($worksheet->getSecondaryRecordingId() && $worksheet->getBaselineDataSetId()) {
            throw new ConflictHttpException(
                'Could not perform analysis with both baseline_dataset_id and secondary_recording_id. Use just one.'
            );
        }

        // get primary dataset
        try {
            $primaryAnalyses = $this->analyzer->perform(
                $recording,
                $worksheet->getDimensions(),
                $worksheet->getAnalysisType(),
                $worksheet->getStart(),
                $worksheet->getEnd(),
                [
                    'span' => $worksheet->getSpan(),
                    'interval' => $worksheet->getInterval()
                ],
                $worksheet->getFilter('generated_csdl')
            );
        } catch (\DataSift_Exception_APIError $ex) {
            throw new BadRequestHttpException($ex->getMessage());
        }

        $primaryDataset = $this->datasetGenerator->fromAnalyses($primaryAnalyses, $worksheet->getDimensions());

        // get secondary dataset or baseline DataSet
        $secondaryDataset = null;
        if ($worksheet->getSecondaryRecordingId()) {
            $secondaryRecording = $this->recordingRepository->findOne(['id' => $worksheet->getSecondaryRecordingId()]);
            if (!$secondaryRecording) {
                throw new NotFoundHttpException(sprintf(
                    'Could not find secondary recording with ID %s.',
                    $worksheet->getSecondaryRecordingId()
                ));
            }

            try {
                $secondaryAnalyses = $this->analyzer->perform(
                    $secondaryRecording,
                    $worksheet->getDimensions(),
                    $worksheet->getAnalysisType(),
                    $worksheet->getSecondaryRecordingFilter('start'),
                    $worksheet->getSecondaryRecordingFilter('end'),
                    [
                        'span' => $worksheet->getSpan(),
                        'interval' => $worksheet->getInterval()
                    ],
                    $worksheet->getSecondaryRecordingFilter('generated_csdl')
                );
            } catch (\DataSift_Exception_APIError $ex) {
                throw new BadRequestHttpException($ex->getMessage());
            }

            $secondaryDataset = $this->datasetGenerator->fromAnalyses($secondaryAnalyses, $worksheet->getDimensions());
        } elseif ($worksheet->getBaselineDataSetId()) {
            $secondaryDataset = $this->datasetRepository->findOne(['id' => $worksheet->getBaselineDataSetId()]);
            if (!$secondaryDataset) {
                throw new NotFoundHttpException(sprintf(
                    'Could not find baseline dataset with ID %s.',
                    $worksheet->getBaselineDataSetId()
                ));
            }
            if (!$secondaryDataset->getDimensions()->isSubset($worksheet->getDimensions())) {
                throw new BadRequestHttpException(
                    'Sorry, that curated dataset is not compatible with your analysis query'
                );
            }
        }

        // generates charts
        $charts = $this->chartFactory->fromDataSet(
            $worksheet->getChartType(),
            $worksheet->getDimensions(),
            $primaryDataset,
            $secondaryDataset,
            $worksheet->getComparison()
        );

        return $charts;
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
