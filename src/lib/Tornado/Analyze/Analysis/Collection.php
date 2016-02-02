<?php

namespace Tornado\Analyze\Analysis;

use Tornado\Analyze\Analysis;

/**
 * Models a Collection of Tornado Analysis
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
class Collection
{
    /**
     * Collection title (if any).
     *
     * @var string
     */
    protected $title = '';

    /**
     * A list of Analysis
     *
     * @var array
     */
    protected $analyses = [];

    /**
     * Constructs a new Collection of Analysis
     *
     * @param \Tornado\Analyze\Analysis[] $analysis
     * @param string                      $title
     */
    public function __construct(array $analysis = [], $title = '')
    {
        $this->setAnalyses($analysis);
        $this->title = $title;
    }

    /**
     * Sets the title.
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Gets the title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Adds a single Analysis to this Collection
     *
     * @param \Tornado\Analyze\Analysis $analysis
     */
    public function addAnalysis(Analysis $analysis)
    {
        $this->analyses[] = $analysis;
    }

    /**
     * Resets the Analysis this Collection contains and adds them, in order as the come
     *
     * @param \Tornado\Analyze\Analysis[] $analyses
     */
    public function setAnalyses(array $analyses)
    {
        $this->analyses = [];
        foreach ($analyses as $analysis) {
            $this->addAnalysis($analysis);
        }
    }

    /**
     * Gets a list of Analysis
     *
     * @return \Tornado\Analyze\Analysis[]
     */
    public function getAnalyses()
    {
        return $this->analyses;
    }
}
