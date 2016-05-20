<?php

namespace Tornado\Analyze;

use MD\Foundation\Utils\ArrayUtils;

use DataSift\Loader\LoaderInterface;

use Tornado\Analyze\Analysis\Collection as AnalysisCollection;
use Tornado\Analyze\Analysis\Group;
use Tornado\Analyze\Analysis;
use Tornado\Analyze\Analyzer;
use Tornado\Analyze\Dimension;
use Tornado\Analyze\Dimension\Factory as DimensionsFactory;
use Tornado\Project\Chart;
use Tornado\Project\Recording;

/**
 * Performs analyses from predefined templates. The templates can include grouped analyses.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Analyze
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TemplatedAnalyzer
{
    /**
     * Templates loader.
     *
     * @var LoaderInterface
     */
    protected $loader;

    /**
     * The analyzer.
     *
     * @var Analyzer
     */
    protected $analyzer;

    /**
     * Dimensions factory.
     *
     * @var DimensionsFactory
     */
    protected $dimensionsFactory;

    /**
     * Loaded templates.
     *
     * @var array
     */
    protected $templates = [];

    /**
     * Constructor.
     *
     * @param LoaderInterface   $loader            Templates loader.
     * @param Analyzer          $analyzer          The analyzer.
     * @param DimensionsFactory $dimensionsFactory Dimensions factory.
     */
    public function __construct(LoaderInterface $loader, Analyzer $analyzer, DimensionsFactory $dimensionsFactory)
    {
        $this->loader = $loader;
        $this->analyzer = $analyzer;
        $this->dimensionsFactory = $dimensionsFactory;
    }

    /**
     * Performs all analyses defined in the given template.
     *
     * Returns a group of analyses collections, so that they can be used separately,
     * e.g. a group = workbook, each analysis collection = worksheet.
     *
     * @param Recording $recording    Recording on which the analyses should be performed.
     * @param string    $templateName Name of the template to load.
     *
     * @return Group
     */
    public function performFromTemplate(Recording $recording, $templateName)
    {
        $template = $this->readTemplate($templateName);

        $analysesGroup = new Group($template['title']);

        // wrap all analyses in a collection, so they can be analyzed in one go
        $analysisCollection = new AnalysisCollection();
        foreach ($template['analyses'] as $analysisTemplate) {
            $dimensions = $this->dimensionsFactory->getDimensionCollection(
                $analysisTemplate['dimensions'],
                $recording
            );

            $analysis = $this->analyzer->buildAnalysis(
                $recording,
                $dimensions,
                $analysisTemplate['analysis_type'],
                $analysisTemplate['start'],
                $analysisTemplate['end'],
                [
                    'span' => $analysisTemplate['span'],
                    'interval' => $analysisTemplate['interval'],
                ],
                $analysisTemplate['filters']['csdl']
            );

            $analysisCollection->addAnalysis($analysis);

            // we also need an individual collection for this analysis
            // because everything else is based on it
            $singleCollection = new AnalysisCollection([$analysis]);
            $singleCollection->setTitle($analysisTemplate['title']);

            // and add to the grouped result
            $analysesGroup->addAnalysisCollection($singleCollection);
        }

        // fire the analysis of the collection in curl multi
        $this->analyzer->analyzeCollection($analysisCollection);

        return $analysesGroup;
    }

    /**
     * Reads a template.
     *
     * @param string $name Template name.
     *
     * @return array
     */
    public function readTemplate($name)
    {
        $templates = $this->loadTemplates();

        if (!isset($templates[$name])) {
            throw new \InvalidArgumentException(sprintf('Could not find analysis template %s', $name));
        }

        $template = $templates[$name];

        if (!isset($template['title']) || !isset($template['analyses']) || !is_array($template['analyses'])) {
            throw new \RuntimeException(sprintf('Invalid analysis template structure for template %s', $name));
        }

        // already verify and augment all analyses templates
        foreach ($template['analyses'] as $i => $analysisTemplate) {
            $template['analyses'][$i] = $this->verifyAnalysisTemplate($analysisTemplate, $name);
        }

        return $template;
    }

    /**
     * Gets a list of templates in this Analyzer
     *
     * @return array
     */
    public function getTemplates()
    {
        return $this->loadTemplates();
    }

    /**
     * Loads templates from disk
     *
     * @return array
     */
    private function loadTemplates()
    {
        if (empty($this->templates)) {
            foreach ($this->loader->load() as $templates) {
                $this->templates = array_merge($this->templates, $templates);
            }
        }
        return $this->templates;
    }

    /**
     * Verifies a single analysis template and augments it with some default values, if they're missing.
     *
     * Returns the augmented template.
     *
     * @param array  $template     Template structure.
     * @param string $templateName Parent template name, for debug.
     *
     * @return array
     */
    private function verifyAnalysisTemplate(array $template, $templateName)
    {
        if (!ArrayUtils::checkValues($template, ['title', 'type'])) {
            throw new \RuntimeException(
                sprintf('Invalid analysis definition for templated analysis %s', $templateName)
            );
        }

        if (!in_array($template['type'], [Chart::TYPE_TORNADO, Chart::TYPE_HISTOGRAM, Chart::TYPE_TIME_SERIES])) {
            throw new \RuntimeException(
                sprintf('Invalid analysis type defined for templated analysis %s', $templateName)
            );
        }

        return $this->augmentAnalysisTemplate($template);
    }

    /**
     * Augments the given analysis template with default values.
     *
     * @param array  $template Template structure.
     *
     * @return array
     */
    private function augmentAnalysisTemplate(array $template)
    {
        switch ($template['type']) {
            case Chart::TYPE_TIME_SERIES:
                $template['analysis_type'] = Analysis::TYPE_TIME_SERIES;
                $template['dimensions'] = [['target' => Dimension::TIME]];
                break;

            case Chart::TYPE_HISTOGRAM:
            case Chart::TYPE_TORNADO:
            default:
                $template['analysis_type'] = Analysis::TYPE_FREQUENCY_DISTRIBUTION;
        }

        // fill with some defaults
        $template = array_merge([
            'dimensions' => [],
            'span' => 1,
            'interval' => Analyzer::INTERVAL_DAY,
            'filters' => ['csdl' => '']
        ], $template);

        $template['start'] = isset($template['start']) ? strtotime($template['start']) : null;
        $template['end'] = isset($template['end']) ? strtotime($template['end']) : null;

        return $template;
    }
}
