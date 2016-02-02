<?php

namespace Tornado\Project\Worksheet;

use Tornado\Project\Worksheet;
use Tornado\Analyze\Dimension\Collection as DimensionCollection;
use Tornado\Project\Worksheet\FilterCsdlGenerator;

/**
 * The Worksheet Explorer creates a new Worksheet from an existing one, based on
 * the selection of the user
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
class Explorer
{
    /**
     * This Explorer's CSDL Generator
     *
     * @var \Tornado\Project\Worksheet\FilterCsdlGenerator
     */
    private $csdlGenerator;

    /**
     * Constructs a new Explorer
     *
     * @param \Tornado\Project\Worksheet\FilterCsdlGenerator $csdlGenerator
     */
    public function __construct(FilterCsdlGenerator $csdlGenerator)
    {
        $this->csdlGenerator = $csdlGenerator;
    }

    /**
     * Creates a new Worksheet from the existing on, based on the selection of
     * the user
     *
     * @param \Tornado\Project\Worksheet $worksheet
     * @param string $name
     * @param array $explore
     * @param integer|null $start
     * @param integer|null $end
     * @param string|null $chartType
     * @param string|null $analysisType
     *
     * @return \Tornado\Project\Worksheet $worksheet
     */
    public function explore(
        Worksheet $worksheet,
        $name,
        array $explore,
        $start = null,
        $end = null,
        $chartType = null,
        $analysisType = null
    ) {
        $obj = clone $worksheet;
        $obj->setId(null);
        $obj->setName($name);
        $obj->setParentWorksheetId($worksheet->getId());
        $obj->setSecondaryRecordingId(null);
        $obj->setSecondaryRecordingFilters([]);
        $obj->setDimensions(new DimensionCollection([]));
        $obj->setBaselineDataSetId(null);
        $obj->setCreatedAt(null);
        $obj->setUpdatedAt(null);
        $obj->setFilters($this->generateFilters($obj->getFilters(), $explore));

        if ($start !== null) {
            $obj->setStart($start);
        }

        if ($end !== null) {
            $obj->setEnd($end);
        }

        if ($chartType !== null) {
            $obj->setChartType($chartType);
        }

        if ($analysisType !== null) {
            $obj->setAnalysisType($analysisType);
        }

        return $obj;
    }

    /**
     * Updates the passed list of filters with the parameters from explore
     *
     * @param \stdClass $filters
     * @param array $explore
     *
     * @return \stdClass
     */
    private function generateFilters(\stdClass $filters, array $explore)
    {
        $generator = $this->csdlGenerator;
        $clauses = [];
        foreach ($explore as $target => $value) {
            if ($filter = $generator->getFilterFromTarget($target)) {
                $filters->{$filter} = [$value];
            } else {
                $clauses[] = $target . ' == "' . $value . '"';
            }
        }

        if (count($clauses)) {
            $existingCSDL = (isset($filters->csdl) && $filters->csdl) ? "({$filters->csdl}) AND " : '';
            $filters->csdl = $existingCSDL . implode(' AND ', $clauses);
        }

        $filters->generated_csdl = $generator->generate((array)$filters);

        return $filters;
    }
}
