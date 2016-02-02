<?php

namespace Controller;

use MD\Foundation\Utils\ArrayUtils;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGenerator;

use DataSift\Form\FormInterface;
use DataSift\Http\Request;
use DataSift\Pylon\Schema\Provider;

use Tornado\Controller\Brand\DataAwareInterface as BrandDataAwareInterface;
use Tornado\Controller\Brand\DataAwareTrait as BrandDataAwareTrait;
use Tornado\Controller\Result;
use Tornado\DataMapper\DataMapperInterface;
use Tornado\Project\Recording;
use Tornado\Project\Recording\DataSiftRecordingException;
use Tornado\Project\Recording\DataSiftRecording;
use Tornado\Organization\Brand;

/**
 * Recordings CRUD management actions
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
class RecordingController implements BrandDataAwareInterface
{
    use BrandDataAwareTrait;

    const BATCH_DELETE = 'delete';
    static protected $BATCH_ACTIONS = [self::BATCH_DELETE];

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var UrlGenerator
     */
    protected $urlGenerator;

    /**
     * @var DataSiftRecording
     */
    protected $dataSiftRecording;

    /**
     * @var \Tornado\Project\Recording\DataMapper
     */
    protected $recordingRepository;

    /**
     * @var \Tornado\Project\Recording\Form\Create
     */
    protected $createForm;

    /**
     * @var \Tornado\Project\Recording\Form\Update
     */
    protected $updateForm;

    /**
     * @var \DataSift\Pylon\Schema\Provider
     */
    protected $schemaProvider;

    public function __construct(
        SessionInterface $session,
        UrlGenerator $urlGenerator,
        DataSiftRecording $dataSiftRecording,
        DataMapperInterface $recordingRepository,
        FormInterface $createForm,
        FormInterface $updateForm,
        Provider $schemaProvider
    ) {
        $this->session = $session;
        $this->urlGenerator = $urlGenerator;
        $this->recordingRepository = $recordingRepository;
        $this->dataSiftRecording = $dataSiftRecording;
        $this->createForm = $createForm;
        $this->updateForm = $updateForm;
        $this->schemaProvider = $schemaProvider;
    }

    /**
     * Retrieves the Recording create form
     *
     * @param integer $brandId
     *
     * @return \Tornado\Controller\Result
     *
     * @throws NotFoundHttpException When Brand was not found.
     * @throws AccessDeniedHttpException if Session User can not access the Brand.
     */
    public function createForm($brandId)
    {
        $brand = $this->getBrand($brandId);
        $sessionUser = $this->session->get('user');

        // get targets for CSDL editor autocomplete
        $schema = $this->schemaProvider->getSchema();
        $dimensions = $schema->getObjects(
            [],
            ['is_analysable' => true],
            $brand->getTargetPermissions()
        );
        $targets = ArrayUtils::pluck($dimensions, 'target');

        return new Result([
            'selectedBrand' => $brand,
            'brands' => $this->brandRepository->findUserAssigned($sessionUser),
            'targets' => $targets
        ]);
    }

    /**
     * Creates the Recording and starts the Pylon recording process
     *
     * @param \DataSift\Http\Request $request
     * @param integer                $brandId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Tornado\Controller\Result
     *
     * @throws NotFoundHttpException When Brand was not found.
     * @throws AccessDeniedHttpException if Session User can not access the Brand.
     */
    public function create(Request $request, $brandId)
    {
        $brand = $this->getBrand($brandId);

        $postParams = $request->getPostParams();
        $postParams['brand_id'] = $brand->getId();

        $this->createForm->submit($postParams);
        $errorResponseData = [
            'selectedBrand' => $brand,
            'brands' => $this->brandRepository->findUserAssigned($this->session->get('user'))
        ];

        if (!$this->createForm->isValid()) {
            return new Result($errorResponseData, $this->createForm->getErrors(), Response::HTTP_BAD_REQUEST);
        }

        $recording = $this->createForm->getData();

        try {
            $this->dataSiftRecording->start($recording);
        } catch (\Exception $e) {
            $statusCode = 500;
            if ($e instanceof DataSiftRecordingException) {
                $statusCode = $e->getStatusCode();
            }

            return new Result(
                $errorResponseData,
                ['csdl' => $e->getMessage()],
                $statusCode
            );
        }

        $this->recordingRepository->create($recording);

        return new Result(
            [],
            [
                'redirect_uri' => $this->urlGenerator
                    ->generate('recording.update_form', ['recordingId' => $recording->getId()])
            ],
            Response::HTTP_CREATED
        );
    }

    /**
     * Removes the given Recording and stops a DataSift Recording process
     *
     * @param integer $recordingId
     *
     * @return \Tornado\Controller\Result
     *
     * @throws NotFoundHttpException When Recording was not found.
     * @throws AccessDeniedHttpException if Session User can not access the Recording.
     */
    public function delete($recordingId)
    {
        $recording = $this->getRecording($recordingId);
        $brandId = $recording->getBrandId();

        try {
            $this->dataSiftRecording->delete($recording);
        } catch (\Exception $e) {
            $statusCode = 500;
            if ($e instanceof DataSiftRecordingException) {
                $statusCode = $e->getStatusCode();
            }

            return new Result(
                [],
                ['error' => sprintf('Recording "%s" error: %s.', $recording->getName(), $e->getMessage())],
                $statusCode
            );
        }

        return new Result(
            [],
            ['redirect_uri' => $this->urlGenerator->generate('brand.get.recordings', ['brandId' => $brandId])]
        );
    }

    /**
     * Retrieves the Recording update form
     *
     * @param integer $recordingId
     *
     * @return \Tornado\Controller\Result
     *
     * @throws NotFoundHttpException When Recording was not found.
     * @throws AccessDeniedHttpException if Session User can not access the Recording.
     */
    public function updateForm($recordingId)
    {
        $recording = $this->getRecording($recordingId);
        $sessionUser = $this->session->get('user');

        return new Result([
            'selectedBrand' => $this->getBrand($recording->getBrandId()),
            'brands' => $this->brandRepository->findUserAssigned($sessionUser),
            'recording' => $recording
        ]);
    }

    /**
     * Updates Recording
     *
     * @param \DataSift\Http\Request $request
     * @param                        $recordingId
     *
     * @return \Tornado\Controller\Result
     *
     * @throws NotFoundHttpException When Recording was not found.
     * @throws AccessDeniedHttpException if Session User can not access the Recording.
     */
    public function update(Request $request, $recordingId)
    {
        $recording = $this->getRecording($recordingId);
        $brand = $this->getBrand($recording->getBrandId());

        $postParams = $request->getPostParams();
        $postParams['brand_id'] = $brand->getId();

        $this->updateForm->submit($postParams, $recording);

        if (!$this->updateForm->isValid()) {
            $sessionUser = $this->session->get('user');

            return new Result([
                'selectedBrand' => $brand,
                'brands' => $this->brandRepository->findUserAssigned($sessionUser),
                'recording' => $recording
            ], $this->updateForm->getErrors(), Response::HTTP_BAD_REQUEST);
        }

        $recording = $this->updateForm->getData();

        // or stop and start recording here
        $this->recordingRepository->update($recording);

        return new RedirectResponse(
            $this->urlGenerator->generate('recording.update_form', ['recordingId' => $recording->getId()])
        );
    }

    /**
     * Stops running Pylon Recording process
     *
     * @param int $recordingId
     *
     * @return \Tornado\Controller\Result
     * @throws \DataSift_Exception_InvalidData
     */
    public function pause($recordingId)
    {
        $recording = $this->getRecording($recordingId);

        try {
            $this->dataSiftRecording->pause($recording);
        } catch (\Exception $e) {
            $statusCode = 500;
            if ($e instanceof DataSiftRecordingException) {
                $statusCode = $e->getStatusCode();
            }

            return new Result([], ['error' => $e->getMessage()], $statusCode);
        }

        $this->recordingRepository->update($recording);

        return new Result(
            [
                'recording' => $recording
            ],
            [
                'redirect_uri' => $this->urlGenerator
                    ->generate('brand.get.recordings', ['brandId' => $recording->getBrandId()])
            ]
        );
    }

    /**
     * Starts stopped Pylon Recording process
     *
     * @param int $recordingId
     *
     * @return \Tornado\Controller\Result
     * @throws \DataSift_Exception_InvalidData
     */
    public function resume($recordingId)
    {
        $recording = $this->getRecording($recordingId);

        try {
            $this->dataSiftRecording->resume($recording);
        } catch (\Exception $e) {
            $statusCode = 500;
            if ($e instanceof DataSiftRecordingException) {
                $statusCode = $e->getStatusCode();
            }

            return new Result([], ['error' => $e->getMessage()], $statusCode);
        }

        $this->recordingRepository->update($recording);

        return new Result(
            [
                'recording' => $recording
            ],
            [
                'redirect_uri' => $this->urlGenerator
                    ->generate('brand.get.recordings', ['brandId' => $recording->getBrandId()])
            ]
        );
    }

    /**
     * Performs Brand's recordings batch processing
     *
     * @param \DataSift\Http\Request $request
     * @param int $brandId
     *
     * @return Result
     *
     * @throws BadRequestHttpException when missing action param or it has invalid value
     */
    public function batch(Request $request, $brandId)
    {
        // check user has access to this brand (trait)
        $brand = $this->getBrand($brandId);
        $params = $request->getPostParams();

        if (!isset($params['action']) || !in_array(strtolower($params['action']), self::$BATCH_ACTIONS)) {
            throw new BadRequestHttpException('Batch action is missing or not supported.');
        }

        if (!isset($params['ids']) || !is_array($params['ids']) || !count($params['ids']) > 0) {
            return new Result(
                [],
                ['redirect_uri' => $this->urlGenerator->generate('brand.get.recordings', ['brandId' => $brandId])]
            );
        }

        switch (strtolower($params['action'])) {
            case self::BATCH_DELETE:
                return $this->batchDelete($brand, $params['ids']);
                break;
        }
    }

    /**
     * Performs batch Recording delete
     *
     * @param \Tornado\Organization\Brand $brand
     * @param array                       $ids
     *
     * @return \Tornado\Controller\Result
     */
    protected function batchDelete(Brand $brand, array $ids)
    {
        $recordings = $this->recordingRepository->findRecordingsByBrand($brand, $ids);
        try {
            $this->dataSiftRecording->deleteRecordings($recordings);
        } catch (\Exception $e) {
            $statusCode = 500;
            if ($e instanceof DataSiftRecordingException) {
                $statusCode = $e->getStatusCode();
            }

            return new Result(
                [],
                ['error' => sprintf('Recording batch delete error: %s.', $e->getMessage())],
                $statusCode
            );
        }

        return new Result(
            [],
            [
                'redirect_uri' => $this->urlGenerator->generate('brand.get.recordings', ['brandId' => $brand->getId()])
            ]
        );
    }

    /**
     * Retrieves Recording by ID and validates access.
     *
     * @param integer $recordingId
     *
     * @return \Tornado\Project\Recording
     *
     * @throws NotFoundHttpException When such Recording was not found.
     * @throws AccessDeniedHttpException if Session User can not access the Recording
     */
    protected function getRecording($recordingId)
    {
        $recording = $this->recordingRepository->findOne(['id' => $recordingId]);
        if (!$recording) {
            throw new NotFoundHttpException(sprintf('Could not find Recording with ID %s', $recordingId));
        }

        if (!$this->authorizationManager->isGranted($recording)) {
            throw new AccessDeniedHttpException('You can not access this Recording.');
        }

        return $recording;
    }
}
