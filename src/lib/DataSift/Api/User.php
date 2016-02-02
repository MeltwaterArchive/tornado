<?php

namespace DataSift\Api;

use Symfony\Component\HttpFoundation\JsonResponse;

use DataSift_Exception_APIError;

use Tornado\Project\Recording\DataSiftRecordingException;

/**
 * DataSift_User child class that provides some custom functionality.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \DataSift\Api
 * @author      Michał Pałys-Dudek <michal@neverblad.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class User extends \DataSift_User
{
    /**
     * Set debug mode on and off.
     *
     * @param boolean $debug Debug mode.
     */
    public function setDebug($debug)
    {
        $this->_debug = $debug;
    }

    /**
     * Returns the last logged DataSift API response in the form of `JsonResponse`.
     *
     * @return JsonResponse
     *
     * @throws \RuntimeException Where there was no last response logged.
     */
    public function proxyLastResponse()
    {
        $lastResponse = $this->getLastResponse();

        if (empty($lastResponse)) {
            throw new \RuntimeException('No last response logged in DataSift API.');
        }

        return new JsonResponse($lastResponse['body'], $lastResponse['status'], $lastResponse['headers']);
    }

    /**
     * Proxies a DataSift API response received when executing `$action` to a `JsonResponse`.
     *
     * @param  Closure $action Closure within which a call to DS API should be made. The raw response of that call
     *                         will be used to create a `JsonResponse` object return from this method.
     *
     * @return JsonResponse
     */
    public function proxyResponse(\Closure $action)
    {
        // turn debug mode on to record last response
        $originalDebug = $this->getDebug();
        $this->setDebug(true);

        // execute the closure
        try {
            $action();
        } catch (DataSiftRecordingException $e) {
            // silence these exceptions as we will just return the original response from the API
        } catch (DataSift_Exception_APIError $e) {
            // silence these exceptions as we will just return the original response from the API
        } catch (\DataSift_Exception_InvalidData $ex) {
            $this->setDebug($originalDebug);
            return new JsonResponse(['error' => $ex->getMessage()], 400);
        }

        // proxy last response to a `JsonResponse`
        $response = $this->proxyLastResponse();

        // revert to previous debug mode status
        $this->setDebug($originalDebug);

        return $response;
    }
}
