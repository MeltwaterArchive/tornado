<?php

namespace Tornado\Project\Worksheet;

/**
 * Generates a CSDL filter code based on an array of params.
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
 */
class FilterCsdlGenerator
{
    /**
     * Map of filter keys and their respective Pylon targets and prefered operators to be used.
     *
     * @var array
     */
    protected $filterTargetMap = [
        'keywords' => ['target' => 'fb.all.content', 'operator' => 'any'],
        'links' => ['target' => 'links.url', 'operator' => 'in'], // url_in seems to be unsupported atm
        'country' => ['target' => 'fb.author.country', 'operator' => 'in'],
        'region' => ['target' => 'fb.author.region', 'operator' => 'in'],
        'gender' => ['target' => 'fb.author.gender', 'operator' => 'in'],
        'age' => ['target' => 'fb.author.age', 'operator' => 'in']
    ];

    /**
     * Generates a CSDL from the given configuration of filters.
     *
     * @param  array|null  $filters Filters to be applied.
     * @return string
     */
    public function generate($filters)
    {
        if (!is_array($filters)) {
            return '';
        }

        $csdlFilters = [];

        // include simple filters
        foreach ($this->filterTargetMap as $key => $info) {
            if (isset($filters[$key]) && is_array($filters[$key]) && !empty($filters[$key])) {
                $csdlFilters[$key] = $this->generateFilter($info['target'], $filters[$key], $info['operator']);
            }
        }

        // join to a string
        $csdl = $this->mergeCsdlFilters($csdlFilters);

        // merge in with the custom CSDL query
        if (isset($filters['csdl'])) {
            $csdl = $this->mergeCsdl($filters['csdl'], $csdl);
        }

        return $csdl;
    }

    /**
     * Gets a filter name from the target passed
     *
     * @param string|false $target
     */
    public function getFilterFromTarget($target)
    {
        foreach ($this->filterTargetMap as $key => $info) {
            if ($info['target'] === $target) {
                return $key;
            }
        }
        return false;
    }

    /**
     * Generates CSDL that filters the given target by the given values.
     *
     * @param  string $target   CSDL target.
     * @param  array  $values   Values to filter by.
     * @param  string $operator Filter operator. Default: `in`.
     * @return string
     */
    protected function generateFilter($target, array $values, $operator = 'in')
    {
        return sprintf('%s %s "%s"', $target, $operator, implode(',', $values));
    }

    /**
     * Merges simple CSDL filters into one CSDL string.
     *
     * @param  array  $filters Lines of CSDL filters.
     * @return string
     */
    protected function mergeCsdlFilters(array $filters)
    {
        // country and region filters are additive - they should be merged with OR and wrapped in ()
        if (isset($filters['country']) && isset($filters['region'])) {
            $filters['country_region'] = "(\n" . implode("\nOR\n", [$filters['country'], $filters['region']]) . "\n)";
            unset($filters['country']);
            unset($filters['region']);
        }

        return implode("\nAND\n", $filters);
    }

    /**
     * Merges two CSDL strings into one valid CSDL.
     *
     * @param  string $csdl        Custom CSDL that can contain classification tags
     *                             (but doesn't have to).
     * @param  string $filtersCsdl CSDL used only for filtering - cannot contain
     *                             classification tags.
     * @return string
     */
    protected function mergeCsdl($csdl, $filtersCsdl)
    {
        $csdl = $this->cleanCsdl($csdl);
        $filtersCsdl = $this->cleanCsdl($filtersCsdl);

        if (empty($csdl)) {
            return $filtersCsdl;
        }

        if (empty($filtersCsdl)) {
            return $csdl;
        }

        if ($this->isClassificationCsdl($csdl)) {
            return $this->mergeInClassificationCsdl($csdl, $filtersCsdl);
        }

        return $filtersCsdl ."\nAND\n". $csdl;
    }

    /**
     * Checks whether or not the given CSDL code contains classification tags.
     *
     * @param  string  $csdl CSDL code.
     * @return boolean
     */
    protected function isClassificationCsdl($csdl)
    {
        // there has to be a line that starts with a word `tag`, `tag.` or `tags`
        // all lines should be trimmed at this point, so no need to check for whitespace
        return preg_match('/^tag(s\s|\.|\s)/mi', $csdl) === 1;
    }

    /**
     * Merges a CSDL used for filtering into a CSDL that contains classification tags.
     *
     * @param  string $classificationCsdl CSDL that contains classification tags.
     * @param  string $filtersCsdl        CSDL for filtering.
     * @return string
     */
    protected function mergeInClassificationCsdl($classificationCsdl, $filtersCsdl)
    {
        // check for a return statement and if it exists merge the filters csdl inside
        if (preg_match('/return(\s+)\{([^\}]*)}/si', $classificationCsdl) === 1) {
            return preg_replace_callback('/return \{([^\}]*)}/si', function ($matches) use ($filtersCsdl) {
                $localCsdl = trim($matches[1], " \n");
                $returnCsdl = empty($localCsdl)
                    // if the return statement is empty, just put filters csdl in it
                    ? $filtersCsdl
                    // merge what was previously in the return statement with the filters csdl
                    : $localCsdl . "\nAND\n" . $filtersCsdl;

                return "return {\n" . $returnCsdl . "\n}";
            }, $classificationCsdl);
        }

        // no return statement was found so just wrap the filter in it and append
        return implode("\n", [
            $classificationCsdl,
            'return {',
            $filtersCsdl,
            '}'
        ]);
    }

    /**
     * Cleans a CSDL code by clearing lines that are only comments and any whitespace from
     * all lines and beginning and end.
     *
     * This is needed for properly recognizing and merging two CSDL codes.
     *
     * @param  string $csdl CSDL code.
     * @return string
     */
    protected function cleanCsdl($csdl)
    {
        $lines = explode("\n", $csdl);

        foreach ($lines as $i => $line) {
            $line = trim($line);

            // if line is a comment then clear it
            if (substr($line, 0, 2) === '//') {
                $line = '';
            }

            $lines[$i] = $line;
        }

        $result = implode("\n", $lines);
        return trim($result, " \n");
    }
}
