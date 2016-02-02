<?php

namespace Tornado\Analyze;

use DataSift_Pylon;
use DataSift\Stats\Collector as StatsCollector;

use MD\Foundation\Utils\ArrayUtils;

use Tornado\Analyze\Dimension\Collection as DimensionCollection;
use Tornado\Analyze\Dimension;
use Tornado\Analyze\Analysis\Collection as AnalysisCollection;
use Tornado\Analyze\Analysis\FrequencyDistribution;
use Tornado\Analyze\Analysis\TimeSeries;
use Tornado\Analyze\Analysis;
use Tornado\Project\Recording;
use Tornado\Project\Worksheet;

/**
 * Performs analysis of a recording based on a collection of dimensions,
 * by calling the Pylon API.
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
 */
class Analyzer
{

    const INTERVAL_MINUTE = 'minute';
    const INTERVAL_HOUR = 'hour';
    const INTERVAL_DAY = 'day';
    const INTERVAL_WEEK = 'week';

    /**
     * DataSift Pylon API client.
     *
     * @var DataSift_Pylon
     */
    protected $pylon;

    /**
     * Stats tracker.
     *
     * @var DataSift\Stats\Collector
     */
    protected $stats;

    /**
     * Constructor.
     *
     * @param DataSift_Pylon $pylon DataSift Pylon API client.
     */
    public function __construct(DataSift_Pylon $pylon, StatsCollector $stats)
    {
        $this->pylon = $pylon;
        $this->stats = $stats;
    }

    /**
     * Perform analysis of the given recording using the given dimensions and parameters.
     *
     * Returns a collection of one or more analyses.
     *
     * @param  Recording            $recording  Recording to be analyzed.
     * @param  DimensionCollection  $dimensions Dimensions for the analysis.
     * @param  string               $type       Type of the analysis, one of `Analysis::TYPE_*` constants.
     * @param  integer|null         $start      Start time of the analysis.
     * @param  integer|null         $end        End time of the analysis.
     * @param  array                $parameters Analysis parameters.
     * @param  string|null          $filter     The secondary filter for this analysis
     *
     * @return AnalysisCollection
     */
    public function perform(
        Recording $recording,
        DimensionCollection $dimensions,
        $type,
        $start = null,
        $end = null,
        array $parameters = [],
        $filter = null
    ) {
        $analysis = $this->buildAnalysis(
            $recording,
            $dimensions,
            $type,
            $start,
            $end,
            $parameters,
            $filter
        );

        $analyses = new AnalysisCollection([$analysis]);
        $this->analyzeCollection($analyses);
        return $analyses;
    }

    /**
     * Call Pylon API to analyze the given Analysis.
     *
     * Will automatically set the results on the Analysis object.
     *
     * @param Analysis $analysis Analysis to be analyzed by Pylon.
     *
     * @return Analysis
     */
    public function analyze(Analysis $analysis)
    {
        $recording = $analysis->getRecording();
        if (!$recording) {
            throw new \InvalidArgumentException('Analyzer requires a Recording to be passed.');
        }

        $analyses = new AnalysisCollection([$analysis]);
        $this->analyzeCollection($analyses);
        return $analysis;
    }

    /**
     * Performs an analysis on a collection of analyses, using multi curl feature of Pylon client.
     *
     * @param AnalysisCollection $analyses Collection of analyses to analyze.
     *
     * @return AnalysisCollection
     */
    public function analyzeCollection(AnalysisCollection $analyses)
    {
        $this->stats->startTimer('analyze');
        $this->pylon->analyzeMulti($analyses);
        $this->stats->endTimer('analyze');
        return $analyses;
    }

    /**
     * Creates an analysis (possibly nested).
     *
     * @param  Recording        $recording  Recording for this analysis.
     * @param  Dimension        $dimension  Dimension for this analysis.
     * @param  string           $type       Type of the analysis, one of `Analysis::TYPE_*` constants.
     * @param  integer|null     $start      Start time of the analysis.
     * @param  integer|null     $end        End time of the analysis.
     * @param  array            $parameters Analysis parameters.
     *
     * @return Analysis
     */
    public function buildAnalysis(
        Recording $recording,
        DimensionCollection $dimensions,
        $type,
        $start = null,
        $end = null,
        array $parameters = [],
        $filter = null
    ) {
        $topAnalysis = null;
        $currentAnalysis = null;

        foreach ($dimensions->getDimensions(DimensionCollection::ORDER_CARDINALITY_DESC) as $dimension) {
            $analysis = $this->createAnalysis(
                $recording,
                $dimension,
                $type,
                $start,
                $end,
                $parameters,
                $filter
            );

            // set as a child of current analysis (if any)
            if ($currentAnalysis) {
                $currentAnalysis->setChild($analysis);
            }

            // reference this analysis as current analysis, so children can attach to it
            $currentAnalysis = $analysis;

            // if no top analysis defined yet, then this should be it
            if ($topAnalysis === null) {
                $topAnalysis = $analysis;
            }
        }

        return $topAnalysis;
    }

    /**
     * Creates an instance of `Analysis` object based on the given parameters.
     *
     * @param  Recording        $recording  Recording for this analysis.
     * @param  Dimension        $dimension  Dimension for this analysis.
     * @param  string           $type       Type of the analysis, one of `Analysis::TYPE_*` constants.
     * @param  integer|null     $start      Start time of the analysis.
     * @param  integer|null     $end        End time of the analysis.
     * @param  array            $parameters Analysis parameters.
     * @param  string|null      $filter     The secondary filter for this analysis
     *
     * @return Analysis
     */
    protected function createAnalysis(
        Recording $recording,
        Dimension $dimension,
        $type,
        $start = null,
        $end = null,
        array $parameters = [],
        $filter = null
    ) {
        switch ($type) {
            case Analysis::TYPE_TIME_SERIES:
                $interval = isset($parameters['interval']) ? $parameters['interval'] : self::INTERVAL_DAY;
                $span = isset($parameters['span']) ? $parameters['span'] : 1;
                $intervalDuration = $this->intervalDuration($interval) * $span;
                $start = $this->adjustTimeSeriesStart($start, $intervalDuration, $recording);
                $end = $this->adjustTimeSeriesEnd($end, $intervalDuration);

                $analysis = new TimeSeries(
                    $dimension->getTarget(),
                    $interval,
                    $start,
                    $end,
                    $span,
                    $recording,
                    $filter
                );
                break;

            case Analysis::TYPE_FREQUENCY_DISTRIBUTION:
                $analysis = new FrequencyDistribution(
                    $dimension->getTarget(),
                    $dimension->getThreshold() ?: 100,
                    $start,
                    $end,
                    $recording,
                    $filter
                );
                break;

            default:
                throw new \InvalidArgumentException('Unrecognized analysis type passed to the analyzer.');
        }

        return $analysis;
    }

    /**
     * Adjusts the start time of time series analysis to minimise a chance of redaction.
     *
     * @link http://dev.datasift.com/pylon/howto/advanced/calculating-time-spans
     *
     * @param  integer   $start            Desired start time (in unix timestamp).
     * @param  integer   $intervalDuration Duration of a single interval in time series (in seconds).
     * @param  Recording $recording        Recording to be analysed.
     *
     * @return integer
     */
    private function adjustTimeSeriesStart($start, $intervalDuration, Recording $recording)
    {
        if ($start === null) {
            return null;
        }

        $pylonLimit = time() - (60 * 60 * 24 * 32);
        $minStart = max($recording->getCreatedAt(), $pylonLimit, $start);
        return ceil($minStart / $intervalDuration) * $intervalDuration;
    }

    /**
     * Adjusts the end time of time series analysis to minimise a chance of redaction.
     *
     * @link http://dev.datasift.com/pylon/howto/advanced/calculating-time-spans
     *
     * @param  integer   $end              Desired end time (in unix timestamp).
     * @param  integer   $intervalDuration Duration of a single interval in time series (in seconds).
     *
     * @return integer
     */
    private function adjustTimeSeriesEnd($end, $intervalDuration)
    {
        if ($end === null) {
            return null;
        }

        $maxEnd = min(time(), $end);
        return floor($maxEnd / $intervalDuration) * $intervalDuration;
    }

    /**
     * Duration in second of a single interval type.
     *
     * @param  string $interval One of 'week', 'day', 'hour', 'minute'.
     *
     * @return integer
     */
    private function intervalDuration($interval)
    {
        switch ($interval) {
            case self::INTERVAL_WEEK:
                $seconds = 60 * 60 * 24 * 7;
                break;

            case self::INTERVAL_DAY:
                $seconds = 60 * 60 * 24;
                break;

            case self::INTERVAL_HOUR:
                $seconds = 60 * 60;
                break;

            case self::INTERVAL_MINUTE:
            default:
                $seconds = 60;
        }

        return $seconds;
    }
}
