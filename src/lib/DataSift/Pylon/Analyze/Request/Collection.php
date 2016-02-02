<?php

namespace DataSift\Pylon\Analyze\Request;

use DataSift\Pylon\Analyze\Request;
use \DataSift_User;
use Tornado\Analyze\Analysis\Collection as AnalysisCollection;

use MD\Foundation\Utils\ArrayUtils;

/**
 * License
 *
 * PHP Version 5.3
 *
 * This software is the intellectual property of DataSift Ltd., and is covered by retained intellectual property rights,
 * including copyright. Distribution of this software is strictly forbidden under the terms of this license.
 *
 * @category  Fido
 * @author    Christopher Hoult <chris.hoult@datasift.com>
 * @copyright 2015-2016 MediaSift Ltd.
 * @license   http://datasift.com DataSift Internal License
 * @link      http://www.datasift.com
 */
class Collection
{

    /**
     * The list of this Collection's Requests
     *
     * @var array
     */
    private $requests = [];

    /**
     * The User this Collection will access the API as
     *
     * @var \DataSift_User
     */
    private $user;

    /**
     * If set to true, curl_multi will be used
     *
     * @var boolean
     */
    private $parallel = true;

    /**
     * Constructs a new Request Collection
     *
     * @param DataSift_User $user
     */
    public function __construct(DataSift_User $user, AnalysisCollection $analyses = null, $parallel = true)
    {
        $this->user = $user;
        $this->parallel = $parallel;
        if ($analyses) {
            $this->fromAnalysisCollection($analyses);
        }
    }

    /**
     * Loads the Analyses provided by an AnalysisCollection
     *
     * @param \Tornado\Analyze\Analysis\Collection $analyses
     */
    public function fromAnalysisCollection(AnalysisCollection $analyses)
    {
        foreach ($analyses->getAnalyses() as $analysis) {
            $this->addRequest(new Request($this->user, $analysis));
        }
    }

    /**
     * Adds a Request to this Collection
     *
     * @param \DataSift\Pylon\Analyze\Request $request
     */
    public function addRequest(Request $request)
    {
        $this->requests[] = $request;
    }

    /**
     * Gets a list of Requests contained by this Collection
     *
     * @return array
     */
    public function getRequests()
    {
        return $this->requests;
    }

    /**
     * Returns true if one or more of the Requests contained in this Collection
     * are in an error state
     *
     * @return boolean
     */
    public function hasErrors()
    {
        foreach ($this->requests as $request) {
            if ($request->hasError()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets a list of errors for the current Collection
     *
     * @return array
     */
    public function getErrors()
    {
        $errors = [];
        foreach ($this->requests as $request) {
            if ($request->hasError()) {
                $errors[] = $request->getError();
            }
        }

        return $errors;
    }

    /**
     * Runs the appropriate Analyze calls on each of the Requests in this
     * Collection
     *
     * @codeCoverageIgnore
     */
    public function analyze()
    {
        if ($this->parallel) {
            $this->analyzeParallel();
        } else {
            foreach ($this->requests as $request) {
                $handle = $request->getCurlHandle();
                $this->processCurlResult($request, curl_exec($handle));
            }
        }
        $this->close();
    }

    /**
     * Runs the analyses in parallel via curl_multi
     */
    private function analyzeParallel()
    {
        $mh = curl_multi_init();
        foreach ($this->requests as $request) {
            curl_multi_add_handle($mh, $request->getCurlHandle());
        }

        $active = null;
        do {
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            curl_multi_select($mh);
        } while ($active);

        foreach ($this->requests as $request) {
            $this->processCurlResult($request);
            curl_multi_remove_handle($mh, $request->getCurlHandle());
            curl_close($request->getCurlHandle());
        }

        curl_multi_close($mh);
    }

    /**
     * Processes a curl Request for its results
     *
     * @param \DataSift\Pylon\Analyze\Request $request
     * @param string $content
     *
     * @return \DataSift\Pylon\Analyze\Request
     */
    private function processCurlResult(Request $request, $content = null)
    {
        $info = curl_getinfo($request->getCurlHandle());
        if ($content === null && $this->parallel) {
            $content = curl_multi_getcontent($request->getCurlHandle());
        }

        $content = json_decode($content, true);

        $code = (isset($info['http_code'])) ? $info['http_code'] : 0;

        if ($code !== 200) {
            $errorStr = (isset($content['error'])
                        ? $content['error']
                        : 'An unknown error occurred; please try again shortly');
            $request->setError($errorStr);
            return $request;
        }

        $request->getAnalysis()->setResults(
            ArrayUtils::toObject($content)
        );

        return $request;
    }

    /**
     * Closes all of the requests in this Collection
     */
    public function close()
    {
        foreach ($this->requests as $request) {
            $request->close();
        }
    }
}
