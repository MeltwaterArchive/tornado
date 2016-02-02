<?php

namespace Controller\TornadoApi;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use DataSift\Http\Request;
use DataSift_Pylon;

use Tornado\Organization\Brand;
use Tornado\Project\Recording;
use Tornado\Project\Recording\DataMapper as RecordingRepository;
use Tornado\Project\Recording\Form\Create as CreateRecordingForm;

/**
 * RecordingController
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Controller\Api\Tornado
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 *
 * @xSuppressWarnings(PHPMD.CouplingBetweenObjects,PHPMD.ExcessiveClassComplexity)
 */
class RecordingController
{

    /**
     * @var \Tornado\Project\Recording\DataMapper
     */
    protected $recordingRepo;

    /**
     * @var \Tornado\Project\Recording\Form\Create
     */
    protected $createRecordingForm;

    /**
     * The PYLON client
     *
     * @var \DataSift_Pylon
     */
    protected $pylon;

    /**
     * Constructs a new Project API controller
     *
     * @param \Tornado\Project\Recording\DataMapper $recordingRepo
     * @param \Tornado\Project\Recording\Form\Create $createRecordingForm
     * @param DataSift_Pylon $pylon
     */
    public function __construct(
        RecordingRepository $recordingRepo,
        CreateRecordingForm $createRecordingForm,
        DataSift_Pylon $pylon
    ) {
        $this->recordingRepo = $recordingRepo;
        $this->createRecordingForm = $createRecordingForm;
        $this->pylon = $pylon;
    }

    /**
     * Creates a Recording
     *
     * @param  Request $request Request.
     *
     * @return JsonResponse
     */
    public function create(Request $request)
    {
        $brand = $request->attributes->get('brand');
        if (!$brand || !$brand instanceof Brand) {
            return new JsonResponse(['error' => 'Authorization failed.'], Response::HTTP_UNAUTHORIZED);
        }

        $hash = $request->get('hash');
        $name = $request->get('name');

        // validate project creation
        $this->createRecordingForm->submit([
            'brand_id' => $brand->getId(),
            'name' => $name,
            'csdl' => '// Created via API',
            'hash' => $hash
        ]);

        if (!$this->createRecordingForm->isValid()) {
            return new JsonResponse(
                [
                    'errors' => $this->createRecordingForm->getErrors()
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Does it exist?
        try {
            $recording = $this->findOrImportRecording($brand, $hash);
        } catch (\RuntimeException $ex) {
            if ($ex->getCode() == Response::HTTP_CONFLICT) {
                return new JsonResponse(
                    [
                        'error' => $ex->getMessage()
                    ],
                    Response::HTTP_CONFLICT
                );
            }
            throw $ex;
        }

        if (!$recording) {
            if (!$this->pylon->hashExists($hash)) {
                return new JsonResponse(
                    [
                        'error' => 'The referenced CSDL does not exist'
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }
            $recording = $this->createRecordingForm->getData();
            $recording->setHash($hash);
            $recording->setDatasiftRecordingId($hash);
            $recording->setCreatedAt(time());
            $recording->setStatus(Recording::STATUS_STOPPED);
            $recording->setBrandId($brand->getId());
        }

        $recording->setName($name);

        $this->recordingRepo->upsert($recording);

        // and return all
        $responseData = [
            'id' => $recording->getDataSiftRecordingId(),
            'hash' => $recording->getHash(),
            'name' => $recording->getName(),
            'status' => $recording->getStatus(),
            'created_at' => (int)$recording->getCreatedAt()
        ];

        return new JsonResponse($responseData, Response::HTTP_CREATED);
    }

    /**
     * Either finds the recording with id $id or imports it from the underlying
     * PYLON API
     *
     * @param \Tornado\Organization\Brand $brand
     * @param string $id
     * @param \Tornado\Project\Project|null $project
     *
     * @return \Tornado\Project\Recording
     *
     * @throws \RuntimeException
     */
    protected function findOrImportRecording(Brand $brand, $id)
    {

        $recording = $this->recordingRepo->findOne(['brand_id' => $brand->getId(), 'datasift_recording_id' => $id]);
        if ($recording) {
            throw new \RuntimeException(
                "Recording {$id} already exists",
                Response::HTTP_CONFLICT
            );
        }

        return $this->recordingRepo->importRecording($this->pylon, $id);
    }
}
