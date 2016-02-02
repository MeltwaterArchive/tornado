<?php

namespace Tornado\Analyze\Analysis;

use Tornado\Analyze\Analysis\Collection;

/**
 * Group of analyses collections.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Analyze\Analysis
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Group
{
    /**
     * Group title.
     *
     * @var string
     */
    protected $title = '';

    /**
     * AnalysisCollection objects in this group.
     *
     * @var array
     */
    protected $analyses = [];

    /**
     * Constructor.
     *
     * @param string $title Title.
     */
    public function __construct($title = '')
    {
        $this->title = $title;
    }

    /**
     * Sets the title.
     *
     * @param string $title Title.
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
     * Adds an analysis collection.
     *
     * @param Collection $analyses Analysis collection.
     */
    public function addAnalysisCollection(Collection $analyses)
    {
        $this->analyses[] = $analyses;
    }

    /**
     * Gets all included analysis collections.
     *
     * @return array
     */
    public function getAnalysisCollections()
    {
        return $this->analyses;
    }
}
