<?php

namespace DataSift\Pylon\Analyze;

use \Tornado\Analyze\Analysis;
use \DataSift_User as User;

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
class Request
{

    /**
     * The Analysis this Request represents
     *
     * @var \Tornado\Analyze\Analysis
     */
    private $analysis;

    /**
     * The underlying DataSift_User to get connection information from
     *
     * @var \DataSift_User
     */
    private $user;

    /**
     * The cURL handle for this Request
     *
     * @var resource
     */
    private $handle;

    /**
     * Set to true if this Request resulted in an error
     *
     * @var boolean
     */
    private $hasError = false;

    /**
     * The error message this Request raised
     *
     * @var string
     */
    private $error = '';

    /**
     * Constructs a new Request
     *
     * @param \DataSift_User $user
     * @param \Tornado\Analyze\Analysis $analysis
     */
    public function __construct(User $user, Analysis $analysis)
    {
        $this->user = $user;
        $this->setAnalysis($analysis);
    }

    /**
     * Gets the Analysis object this Request represents
     *
     * @return \Tornado\Analyze\Analysis
     */
    public function getAnalysis()
    {
        return $this->analysis;
    }

    /**
     * Sets the Analysis object this Request represents
     *
     * @param \Tornado\Analyze\Analysis $analysis
     */
    public function setAnalysis(Analysis $analysis)
    {
        $this->analysis = $analysis;
    }

    /**
     * Sets the error message for this Request
     *
     * @param string $error
     */
    public function setError($error)
    {
        $this->error = $error;
        $this->hasError = true;
    }

    /**
     * Gets the error message for this Request
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Gets whether this Request resulted in an error
     *
     * @return boolean
     */
    public function hasError()
    {
        return $this->hasError;
    }

    /**
     * Gets the User associated with this Request
     *
     * @return DataSift_User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Gets the cURL handle for this Request
     *
     * @codeCoverageIgnore
     *
     * @return resource
     */
    public function getCurlHandle()
    {
        if (!$this->handle) {
            $params = $this->getBody();
            $url = 'http' . ($this->user->useSSL() ? 's' : '') . '://'
                . $this->user->getApiUrl() . $this->user->getApiVersion()
                . '/pylon/analyze';

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Auth: '.$this->user->getUsername().':'.$this->user->getAPIKey(),
                    'Expect:', 'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, $this->user->getUserAgent());

            if ($this->user->useSSL()) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_SSLVERSION, 'CURL_SSLVERSION_TLSv1_2');
            }

            $this->handle = $ch;
        }

        return $this->handle;
    }

    /**
     * Closes this Request in such a way that it might be opened again
     *
     * @codeCoverageIgnore
     */
    public function close()
    {
        if ($this->handle) {
            @curl_close($this->handle);
            $this->handle = null;
        }
    }

    /**
     * Gets the request body required to call the DataSift /pylon/analyze endpoint
     *
     * @return array
     *
     * @throws \DataSift_Exception_InvalidData
     */
    public function getBody()
    {
        $analysis = $this->getAnalysis();
        $parameters = $this->buildPylonParameters($analysis);
        $filter = $analysis->getFilter();
        $start = $analysis->getStart();
        $end = $analysis->getEnd();
        $hash = $analysis->getRecording()->getDatasiftRecordingId();

        //If parameters is not an array try and decode it
        if (!is_array($parameters)) {
            $parameters = json_decode($parameters);
        }

        if (empty($parameters)) {
            throw new \DataSift_Exception_InvalidData('Parameters must be supplied as an array or valid JSON');
        }

        $params = [
            'hash'       =>    $hash,
            'parameters' =>    $parameters
        ];

        //Set optional request parameters
        if ($filter) {
            $params['filter'] = $filter;
        }

        if ($start) {
            $params['start'] = $start;
        }

        if ($end) {
            $params['end'] = $end;
        }

        return $params;
    }

    /**
     * Builds an array of parameters for Pylon API /analyze endpoint.
     *
     * @param  Analysis $analysis Analysis which will be analyzed by Pylon
     *
     * @return array
     */
    protected function buildPylonParameters(Analysis $analysis)
    {
        $parameters = [];
        switch ($analysis->getType()) {
            case Analysis::TYPE_FREQUENCY_DISTRIBUTION:
                $parameters['analysis_type'] = 'freqDist';
                $parameters['parameters'] = [
                    'threshold' => $analysis->getThreshold(),
                    'target' => $analysis->getTarget()
                ];
                break;

            case Analysis::TYPE_TIME_SERIES:
                $parameters['analysis_type'] = 'timeSeries';
                $parameters['parameters'] = [
                    'interval' => $analysis->getInterval(),
                    'span' => $analysis->getSpan()
                ];
                break;
        }

        if ($childAnalysis = $analysis->getChild()) {
            $parameters['child'] = $this->buildPylonParameters($childAnalysis);
        }

        return $parameters;
    }
}
