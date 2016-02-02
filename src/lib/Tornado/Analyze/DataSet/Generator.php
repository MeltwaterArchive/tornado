<?php

namespace Tornado\Analyze\DataSet;

use Tornado\Analyze\DataSet\Generator\RedactedException;
use Tornado\Analyze\DataSet;
use Tornado\Analyze\DataSet\TimeSeries;
use Tornado\Analyze\Dimension\Collection as DimensionCollection;
use Tornado\Analyze\Analysis\Collection as AnalysisCollection;
use Tornado\Analyze\Analysis;

/**
 * Generates a DataSet
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Analyze
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Generator
{
    /**
     * Constructs a DataSet from the passed Analyses
     *
     * @param \Tornado\Analyze\Analysis\Collection  $analyses
     * @param \Tornado\Analyze\Dimension\Collection $dimensions
     *
     * @return \Tornado\Analyze\DataSet
     */
    public function fromAnalyses(AnalysisCollection $analyses, DimensionCollection $dimensions)
    {
        $analysisType = Analysis::TYPE_FREQUENCY_DISTRIBUTION;
        $interval = $span = null;
        foreach ($analyses->getAnalyses() as $analysis) {
            $results = $analysis->getResults();
            $analysisType = $analysis->getType();
            if ($analysisType == Analysis::TYPE_TIME_SERIES) {
                $interval = $analysis->getInterval();
                $span = $analysis->getSpan();
            }
            if ($results->analysis->redacted) {
                throw new RedactedException();
            }
            $data = $this->getResultArray($results->analysis, $analysis->getType());
            $data[DataSet::KEY_MEASURE_INTERACTIONS][DataSet::KEY_REDACTED] = false;
            $data[DataSet::KEY_MEASURE_UNIQUE_AUTHORS][DataSet::KEY_REDACTED] = false;
            $data[DataSet::KEY_MEASURE_INTERACTIONS][DataSet::KEY_VALUE] = $results->interactions;
            $data[DataSet::KEY_MEASURE_UNIQUE_AUTHORS][DataSet::KEY_VALUE] = $results->unique_authors;
            break; // Right now, we only have one piece to deal with
        }

        if ($analysisType == Analysis::TYPE_TIME_SERIES) {
            return new TimeSeries($dimensions, $data, $interval, $span);
        }
        return new DataSet($dimensions, $data);
    }

    /**
     * Gets an array of results from the passed PYLON results object
     *
     * @param \stdClass $results
     * @param string $analysisType
     *
     * @return array
     */
    private function getResultArray(\stdClass $results, $analysisType)
    {
        if ($analysisType == Analysis::TYPE_TIME_SERIES) {
            $target = DataSet::KEY_DIMENSION_TIME;
        } else {
            $target = DataSet::KEY_DIMENSION_PREFIX . $results->parameters->target;
        }
        $redacted = $results->redacted;

        $authors = $interactions = [];

        if (isset($results->results)) {
            $interactions[$target] = [];
            $authors[$target] = [];
            foreach ($results->results as $item) {
                $key = $item->key;
                if (isset($item->child) && count($item->child)) {
                    $child = $this->getResultArray($item->child, $analysisType);
                    $authors[$target][$key] = $child[DataSet::KEY_MEASURE_UNIQUE_AUTHORS];
                    $interactions[$target][$key] = $child[DataSet::KEY_MEASURE_INTERACTIONS];
                }
                $interactions[$target][$key][DataSet::KEY_VALUE] = ($redacted) ? 0 : $item->interactions;
                $interactions[$target][$key][DataSet::KEY_REDACTED] = $redacted;
                $authors[$target][$key][DataSet::KEY_VALUE] = ($redacted) ? 0 : $item->unique_authors;
                $authors[$target][$key][DataSet::KEY_REDACTED] = $redacted;
            }
        }

        return [
            DataSet::KEY_MEASURE_INTERACTIONS => $interactions,
            DataSet::KEY_MEASURE_UNIQUE_AUTHORS => $authors
        ];
    }
}
