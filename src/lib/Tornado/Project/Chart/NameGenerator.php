<?php

namespace Tornado\Project\Chart;

use Tornado\Analyze\Dimension\Collection;
use Tornado\Project\Chart;

/**
 * NameGenerator
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Project\Chart
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class NameGenerator
{
    /**
     * Generates Chart name based on the given chart type and Dimensions
     *
     * @see Tornado\Project\Chart\Generator::generateTornadoFromDataSet
     *
     * @param \Tornado\Project\Chart $chart
     * @param \Tornado\Analyze\Dimension\Collection $dimensionsCol
     * @param string|null $lowestDimVal
     *
     * @return string
     */
    public function generate(Chart $chart, Collection $dimensionsCol, $lowestDimVal = null)
    {
        switch ($chart->getType()) {
            case Chart::TYPE_TORNADO:
                return $this->generateTornadoName($dimensionsCol, $lowestDimVal);
            default:
                $targets = $this->getLabels($dimensionsCol);
                return join($targets, ' x ');
        }
    }

    /**
     * Generates tornado Chart name based on the Dimensions count according to the:
     * @see \Tornado\Project\Chart\Generator::reorderThreeDimensions
     *
     * @param \Tornado\Analyze\Dimension\Collection $dimensionsCol
     * @param string|null $lowestDimVal
     *
     * @return string
     */
    protected function generateTornadoName(Collection $dimensionsCol, $lowestDimVal = null)
    {
        $name = '';
        if ($lowestDimVal && 3 === count($dimensionsCol->getDimensions())) {
            // we must clone the collection to prevent real element removing
            $dimensionsCol = clone $dimensionsCol;

            $name = sprintf('%s: ', ucfirst($lowestDimVal));
            $dimensionsCol->removeElement(count($dimensionsCol->getDimensions()) - 1);
        }

        $labels = $this->getLabels($dimensionsCol);

        return sprintf('%s%s', $name, join($labels, ' x '));
    }

    /**
     * Gets list of Dimension target from the given Dimension Collection
     *
     * @param \Tornado\Analyze\Dimension\Collection $dimensionsCol
     *
     * @return array
     */
    private function getLabels(Collection $dimensionsCol)
    {
        $targets = [];

        foreach ($dimensionsCol->getDimensions() as $dimension) {
            $targets[] = $dimension->getLabel() ? ucfirst($dimension->getLabel()) : $dimension->getTarget();
        }

        return $targets;
    }
}
