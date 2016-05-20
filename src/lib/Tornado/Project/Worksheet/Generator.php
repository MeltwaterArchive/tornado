<?php

namespace Tornado\Project\Worksheet;

use Tornado\Analyze\DataSet\Generator as DataSetGenerator;
use Tornado\Analyze\DataSet\Generator\RedactedException;
use Tornado\Analyze\Analysis\Form\Create as AnalyzeForm;
use Tornado\Analyze\TemplatedAnalyzer;
use Tornado\Project\Chart\Factory as ChartFactory;
use Tornado\Project\Chart\DataMapper as ChartRepository;
use Tornado\Project\Recording;
use Tornado\Project\Worksheet\DataMapper as WorksheetRepository;
use Tornado\Project\Worksheet;
use Tornado\Project\Workbook;

use Tornado\Project\Worksheet\FilterCsdlGenerator;

/**
 * Worksheets generator.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Project
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Generator
{

    /**
     * Worksheet repository.
     *
     * @var WorksheetRepository
     */
    protected $worksheetRepository;

    /**
     * Chart repository.
     *
     * @var ChartRepository
     */
    protected $chartRepository;

    /**
     * Templated analyzer.
     *
     * @var TemplatedAnalyzer
     */
    protected $templatedAnalyzer;

    /**
     * Analysis create form.
     *
     * @var AnalyzeForm
     */
    protected $analyzeForm;

    /**
     * DataSet generator.
     *
     * @var DataSetGenerator
     */
    protected $datasetGenerator;

    /**
     * Chart factory.
     *
     * @var ChartFactory
     */
    protected $chartFactory;

    /**
     * The CSDL Generator
     *
     * @var Tornado\Project\Worksheet\FilterCsdlGenerator
     */
    protected $csdlGenerator;

    /**
     * Constructor.
     *
     * @param WorksheetRepository $worksheetRepository Worksheet repository.
     * @param ChartRepository     $chartRepository     Chart repository.
     * @param TemplatedAnalyzer   $templatedAnalyzer   Templated analyzer.
     * @param AnalyzeForm         $analyzeForm         Analyze form.
     * @param DataSetGenerator    $datasetGenerator    Dataset generator.
     * @param ChartFactory        $chartFactory        Chart factory.
     * @param FilterCsdlGenerator $csdlGenerator       CSDL Generator
     */
    public function __construct(
        WorksheetRepository $worksheetRepository,
        ChartRepository $chartRepository,
        TemplatedAnalyzer $templatedAnalyzer,
        AnalyzeForm $analyzeForm,
        DataSetGenerator $datasetGenerator,
        ChartFactory $chartFactory
    ) {
        $this->worksheetRepository = $worksheetRepository;
        $this->chartRepository = $chartRepository;
        $this->templatedAnalyzer = $templatedAnalyzer;
        $this->analyzeForm = $analyzeForm;
        $this->datasetGenerator = $datasetGenerator;
        $this->chartFactory = $chartFactory;
    }

    /**
     * Generates worksheets and their charts for the given workbook and recording from the given template name.
     *
     * Persists all the created data and returns the worksheets.
     *
     * @param Workbook  $workbook     Workbook for which the worksheets should be generated.
     * @param Recording $recording    Recording to use when generating the worksheets.
     * @param string    $templateName Template name to use.
     *
     * @return array
     */
    public function generateFromTemplate(Workbook $workbook, Recording $recording, $templateName)
    {
        $worksheets = [];

        $template = $this->templatedAnalyzer->readTemplate($templateName);

        // perform all templated analyses and create the worksheets based on results
        $analysisGroup = $this->templatedAnalyzer->performFromTemplate($recording, $templateName);
        foreach ($analysisGroup->getAnalysisCollections() as $i => $analysesCollection) {
            // link to the analysis template related to this analysis
            $analysisTemplate = $template['analyses'][$i];

            // create a worksheet from the analysis template
            $worksheetData = [
                'dimensions' => $analysisTemplate['dimensions'],
                'chart_type' => $analysisTemplate['type'],
                'type' => $analysisTemplate['analysis_type'],
                'span' => $analysisTemplate['span'],
                'interval' => $analysisTemplate['interval'],
                'start' => $analysisTemplate['start'],
                'end' => $analysisTemplate['end'],
                'filters' => $analysisTemplate['filters']
            ];

            $worksheet = $this->createWorksheet($worksheetData, $recording);
            $worksheet->setWorkbookId($workbook->getId());
            $worksheet->setName($analysisTemplate['title']);

            // generate datasets and charts
            try {
                $dataset = $this->datasetGenerator->fromAnalyses($analysesCollection, $worksheet->getDimensions());
                $charts = $this->chartFactory->fromDataSet(
                    $worksheet->getChartType(),
                    $worksheet->getDimensions(),
                    $dataset,
                    null,
                    $worksheet->getComparison()
                );

                // if everything was correct, then persist it all
                $this->worksheetRepository->create($worksheet);

                foreach ($charts as $i => $chart) {
                    $chart->setWorksheetId($worksheet->getId());
                    $chart->setRank($i);

                    $this->chartRepository->create($chart);
                }

                $worksheets[] = $worksheet;
            } catch (RedactedException $e) {
                // ignore redacted exceptions, but don't persist the related worksheet
            }
        }

        return $worksheets;
    }

    /**
     * Creates a worksheet from the given data (if they pass validation).
     *
     * @param  array          $data      Data for the worksheet.
     * @param  Recording|null $recording Recording for the worksheet.
     *
     * @return Worksheet
     *
     * @throws \InvalidArgumentException If the $data didn't pass validation.
     */
    private function createWorksheet(array $data, Recording $recording = null)
    {
        // unset optionals as they cannot be blank
        foreach (['start', 'end'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null) {
                unset($data[$field]);
            }
        }

        $this->analyzeForm->submit(
            array_merge(['worksheet_id' => 0], $data),
            new Worksheet(),
            $recording
        );

        if (!$this->analyzeForm->isValid()) {
            throw new \InvalidArgumentException(sprintf(
                'Could not create a valid worksheet from the given data. Errors: %s. Data: %s',
                json_encode($this->analyzeForm->getErrors()),
                json_encode($data)
            ));
        }

        $worksheet = $this->analyzeForm->getData();
        return $worksheet;
    }
}
