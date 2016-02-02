<?php

namespace Controller\PylonApi;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use DataSift\Api\User as DataSift_User;
use DataSift_Pylon;

use Tornado\Project\Recording\DataMapper as RecordingRepository;
use Tornado\Project\Recording;

/**
 * PylonController proxies various Pylon API calls to DS.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Controller
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PylonController
{
    /**
     * DataSift API User.
     *
     * @var DataSift_User
     */
    protected $client;

    /**
     * Pylon client.
     *
     * @var DataSift_Pylon
     */
    protected $pylon;

    /**
     * Recording repository.
     *
     * @var RecordingRepository
     */
    protected $recordingRepository;

    /**
     * Constructor.
     *
     * @param DataSift_User       $client              DataSift API user.
     * @param DataSift_Pylon      $pylon               Pylon client.
     * @param RecordingRepository $recordingRepository Recording repository.
     */
    public function __construct(
        DataSift_User $client,
        DataSift_Pylon $pylon,
        RecordingRepository $recordingRepository
    ) {
        $this->client = $client;
        $this->pylon = $pylon;
        $this->recordingRepository = $recordingRepository;
    }

    /**
     * Proxies to `pylon/validate` DS API endpoint.
     *
     * @param  Request $request Request.
     *
     * @return JsonResponse
     */
    public function validate(Request $request)
    {
        return $this->client->proxyResponse(function () use ($request) {
            DataSift_Pylon::validate($this->client, $request->get('csdl'));
        });
    }

    /**
     * Proxies to `pylon/compile` DS API endpoint.
     *
     * @param  Request $request Request.
     *
     * @return JsonResponse
     */
    public function compile(Request $request)
    {
        return $this->client->proxyResponse(function () use ($request) {
            // setting an empty default here because otherwise the api client will not make a request (if csdl is empty)
            $this->pylon->compile($request->get('csdl', '// empty'));
        });
    }

    /**
     * Proxes to `pylon/stop` DS API endpoint.
     *
     * Also make sure the status of this recording in Tornado DB is synched.
     *
     * @param  Request $request Request.
     *
     * @return JsonResponse
     */
    public function stop(Request $request)
    {
        $brand = $request->attributes->get('brand');
        $hash = $request->get('hash');

        $response = $this->client->proxyResponse(function () use ($hash) {
            $this->pylon->stop($hash);
        });

        // if there was an error then don't do anything else
        if ($response->getStatusCode() !== Response::HTTP_NO_CONTENT) {
            return $response;
        }

        // also synch with our DB if there is such recording
        $recording = $this->recordingRepository->findOne(['hash' => $hash, 'brand_id' => $brand->getId()]);
        if ($recording) {
            $recording->setStatus(Recording::STATUS_STOPPED);
            $this->recordingRepository->update($recording);
        }

        return $response;
    }

    /**
     * Proxies to `pylon/start` and starts/resumes a recording.
     *
     * If the recording doesn't exist yet in Tornado, it will create it and also create appropriate Project, Workbook
     * and default Worksheet.
     *
     * @param  Request $request Request.
     *
     * @return JsonResponse
     */
    public function start(Request $request)
    {
        $brand = $request->attributes->get('brand');
        $hash = $request->get('hash');
        $name = $request->get('name');

        $response = $this->client->proxyResponse(function () use ($hash, $name) {
            $this->pylon->start($hash, $name);
        });

        // if there was an error then don't do anything else
        if ($response->getStatusCode() !== Response::HTTP_NO_CONTENT) {
            return $response;
        }

        // also synch with our DB if there is such recording
        $recording = $this->recordingRepository->findOne(['hash' => $hash, 'brand_id' => $brand->getId()]);
        if ($recording) {
            $recording->setStatus(Recording::STATUS_STARTED);
        } else {
            $recording = new Recording();
            $recording->setDatasiftRecordingId($hash);
            $recording->setHash($hash);
            $recording->setBrandId($brand->getId());
            $recording->setStatus(Recording::STATUS_STARTED);
            $recording->setCsdl('// Generated from Pylon API');
            $recording->setVqbGenerated(0);
            $recording->setName($name);
            $recording->setCreatedAt(time());
        }

        $this->recordingRepository->upsert($recording);

        return $response;
    }

    /**
     * Proxies to `pylon/get`.
     *
     * If hash given then it retrieves only details of one of User interaction filters, if no, of all.
     *
     * @see {@link http://dev.datasift.com/pylon/docs/api/pylon-api-endpoints/pylonget}
     *
     * @param  Request $request Request.
     *
     * @return JsonResponse
     */
    public function get(Request $request)
    {
        $hash = $request->get('hash');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        return $this->client->proxyResponse(function () use ($hash, $page, $perPage) {
            if ($hash) {
                $this->pylon->get($this->client, $hash);
            } else {
                $this->pylon->getAll($this->client, $page, $perPage);
            }
        });
    }
}
