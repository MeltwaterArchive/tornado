<?php

namespace Controller;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;

use MD\Foundation\Utils\ArrayUtils;

use DataSift\Form\FormInterface;

use DataSift\Http\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;

use Tornado\Controller\Result;
use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\Paginator;

use DataSift\Pylon\Schema\Provider;

use Tornado\Controller\Brand\DataAwareInterface as BrandDataAwareInterface;
use Tornado\Controller\Brand\DataAwareTrait as BrandDataAwareTrait;
use Tornado\Application\Flash\AwareTrait as FlashAwareTrait;

use Tornado\Analyze\DataSet\StoredDataSet;
use Tornado\Analyze\Analyzer;
use Tornado\Analyze\DataSet\Generator as DataSetGenerator;
use Tornado\Organization\Brand;

use Tornado\Analyze\DataSet\Generator\RedactedException;

/**
 * Returns list of available DataSet
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Controller
 * @author      Daniel Waligora <danielwaligora@gmail.com>
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataSetController implements BrandDataAwareInterface
{
    use BrandDataAwareTrait;
    use FlashAwareTrait;

    /**
     * Batch actions
     */
    const BATCH_DELETE = 'delete';
    static protected $BATCH_ACTIONS = [self::BATCH_DELETE];

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var \Tornado\DataMapper\DataMapperInterface
     */
    protected $dataSetRepository;

    /**
     * @var \DataSift\Pylon\Schema\Provider
     */
    protected $schemaProvider;

    /**
     * The form to use for accepting DataSets
     *
     * @var \DataSift\Form\FormInterface
     */
    protected $form;

    /**
     * The URL generator for this controller
     *
     * @var \Symfony\Component\Routing\Generator\UrlGenerator
     */
    protected $urlGenerator;

    /**
     * The analyzer
     *
     * @var \Tornado\Analyze\Analyzer
     */
    protected $analyzer;

    /**
     * The DataSet generator
     *
     * @var \Tornado\Analyze\DataSet\Generator
     */
    protected $datasetGenerator;

    /**
     * Constructs a new DataSet controller
     *
     * @param \Tornado\DataMapper\DataMapperInterface $dataSetRepository
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param \DataSift\Pylon\Schema\Provider $schemaProvider
     * @param \Tornado\DataMapper\DataMapperInterface $recordingRepo
     * @param \DataSift\Form\FormInterface $form
     * @param \Symfony\Component\Routing\Generator\UrlGenerator $urlGenerator
     * @param \Tornado\Analyze\Analyzer $analyzer
     * @param \Tornado\Analyze\DataSet\Generator $datasetGenerator
     */
    public function __construct(
        DataMapperInterface $dataSetRepository,
        SessionInterface $session,
        Provider $schemaProvider,
        DataMapperInterface $recordingRepo,
        FormInterface $form,
        UrlGenerator $urlGenerator,
        Analyzer $analyzer,
        DataSetGenerator $datasetGenerator
    ) {
        $this->dataSetRepository = $dataSetRepository;
        $this->session = $session;
        $this->schemaProvider = $schemaProvider;
        $this->recordingRepo = $recordingRepo;
        $this->form = $form;
        $this->urlGenerator = $urlGenerator;
        $this->analyzer = $analyzer;
        $this->datasetGenerator = $datasetGenerator;
    }

    public function index(Request $request, $brandId)
    {
        $sessionUser = $this->session->get('user');
        $brand = $this->getBrand($brandId);
        $brands = $this->brandRepository->findUserAssigned($sessionUser);

        $paginator = new Paginator(
            $this->dataSetRepository,
            $request->get('page', 1),
            $request->get('sort', 'name'),
            $request->get('perPage', 5),
            $request->get('order', DataMapperInterface::ORDER_ASCENDING)
        );
        $paginator->paginate(['brand_id' => $brand->getId()]);
        $datasets = $paginator->getCurrentItems();

        return new Result(
            [
                'selectedBrand' => $brand,
                'brands' => $brands,
                'datasets' => $datasets
            ],
            [
                'brands' => ['count' => count($brands)],
                'datasets' => ['count' => count($datasets)],
                'pagination' => $paginator
            ]
        );
    }

    /**
     * Creates a stored dataset
     *
     * @param \DataSift\Http\Request $request
     * @param integer $brandId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Tornado\Controller\Result
     */
    public function create(Request $request, $brandId)
    {
        $sessionUser = $this->session->get('user');
        $brand = $this->getBrand($brandId);
        $brands = $this->brandRepository->findUserAssigned($sessionUser);
        $schema = $this->schemaProvider->getSchema();
        $targets = $schema->getObjects(
            [],
            ['is_analysable' => true],
            $brand->getTargetPermissions()
        );

        $flatTargets = ArrayUtils::pluck($targets, 'target');

        $dataset = [];

        $recordings = $this->recordingRepo->findByBrand($brand);
        $errors = [];

        if ($request->getMethod() == Request::METHOD_POST) {
            $dataset = new StoredDataSet();
            $dataset->setBrandId($brandId);
            $dataset->setStatus(StoredDataSet::STATUS_RUNNING);
            $this->form->submit($request->getPostParams(), $dataset);
            $dataset = $this->form->getData();
            if ($this->form->isValid()) {
                try {
                    $recording = $this->recordingRepo->findOne(['id' => $dataset->getRecordingId()]);
                    $analyses = $this->analyzer->fromStoredDataSet($dataset, $recording);
                    $data = $this->datasetGenerator->fromAnalyses($analyses, $dataset->getDimensions());
                    $dataset->setData($data->getData());
                    $dataset->setLastRefreshed(time());
                    $this->dataSetRepository->create($dataset);
                    $this->flashSuccess('Your dataset was created successfully');
                    return new RedirectResponse(
                        $this->urlGenerator->generate('brand.datasets', ['brandId' => $brandId])
                    );
                } catch (RedactedException $ex) {
                    $errors[FormInterface::NOTIFICATION_KEY] = [
                        'level' => 'error',
                        'message' => 'Sorry, there is no data for that query; please try again'
                    ];
                } catch (\Exception $ex) {
                    if ($ex->getMessage() == 'The Nested Query contains invalid nested targets.') {
                        $errors['dimensions'] = 'The Nested Query contains invalid nested targets.';
                        $errors[FormInterface::NOTIFICATION_KEY] = [
                            'level' => 'error',
                            'message' => 'There were errors with your submission, please try again'
                        ];
                    } else {
                        $errors[FormInterface::NOTIFICATION_KEY] = [
                            'level' => 'error',
                            'message' => $ex->getMessage()
                        ];
                    }
                }
            } else {
                $errors = $this->form->getErrors('There were errors with your submission, please try again');
            }
        }

        return new Result(
            [
                'selectedBrand' => $brand,
                'brands' => $brands,
                'targets' => $targets,
                'flatTargets' => $flatTargets,
                'recordings' => $recordings,
                'dataset' => $dataset
            ],
            array_merge(
                $errors,
                ['brands' => ['count' => count($brands)]]
            )
        );
    }

    /**
     * Edits a stored DataSet
     *
     * @param \DataSift\Http\Request $request
     * @param integer $brandId
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Tornado\Controller\Result
     *
     * @throws NotFoundHttpException
     */
    public function edit(Request $request, $brandId, $id)
    {
        $sessionUser = $this->session->get('user');
        $brand = $this->getBrand($brandId);

        $dataset = $this->dataSetRepository->findOne(['brand_id' => $brandId, 'id' => $id]);
        if (!$dataset) {
            throw new NotFoundHttpException('DataSet not found');
        }

        $brands = $this->brandRepository->findUserAssigned($sessionUser);
        $schema = $this->schemaProvider->getSchema();
        $targets = $schema->getObjects(
            [],
            ['is_analysable' => true],
            $brand->getTargetPermissions()
        );

        $flatTargets = ArrayUtils::pluck($targets, 'target');

        $recordings = $this->recordingRepo->findByBrand($brand);
        $errors = [];

        if ($request->getMethod() == Request::METHOD_POST) {
            $this->form->submit($request->getPostParams(), $dataset);
            $dataset = $this->form->getData();
            if ($this->form->isValid()) {
                try {
                    $recording = $this->recordingRepo->findOne(['id' => $dataset->getRecordingId()]);
                    $analyses = $this->analyzer->fromStoredDataSet($dataset, $recording);
                    $data = $this->datasetGenerator->fromAnalyses($analyses, $dataset->getDimensions());
                    $dataset->setData($data->getData());
                    $dataset->setLastRefreshed(time());
                    $this->dataSetRepository->update($dataset);
                    $this->flashSuccess('Your dataset was saved successfully');
                    return new RedirectResponse(
                        $this->urlGenerator->generate('brand.datasets', ['brandId' => $brandId])
                    );
                } catch (RedactedException $ex) {
                    $errors[FormInterface::NOTIFICATION_KEY] = [
                        'level' => 'error',
                        'message' => 'Sorry, there is no data for that query; please try again'
                    ];
                } catch (\Exception $ex) {
                    if ($ex->getMessage() == 'The Nested Query contains invalid nested targets.') {
                        $errors['dimensions'] = 'The Nested Query contains invalid nested targets.';
                        $errors[FormInterface::NOTIFICATION_KEY] = [
                            'level' => 'error',
                            'message' => 'There were errors with your submission, please try again'
                        ];
                    } else {
                        $errors[FormInterface::NOTIFICATION_KEY] = [
                            'level' => 'error',
                            'message' => $ex->getMessage()
                        ];
                    }
                }
            } else {
                $errors = $this->form->getErrors('There were errors with your submission, please try again');
            }
        }

        return new Result(
            [
                'selectedBrand' => $brand,
                'brands' => $brands,
                'targets' => $targets,
                'flatTargets' => $flatTargets,
                'recordings' => $recordings,
                'dataset' => $dataset
            ],
            array_merge(
                $errors,
                ['brands' => ['count' => count($brands)]]
            )
        );
    }

    /**
     * Pauses the selected DataSet
     *
     * @param integer $brandId
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws NotFoundHttpException
     */
    public function pause($brandId, $id)
    {
        $brand = $this->getBrand($brandId);

        $dataset = $this->dataSetRepository->findOne(['brand_id' => $brand->getId(), 'id' => $id]);
        if (!$dataset) {
            throw new NotFoundHttpException('DataSet not found');
        }
        $dataset->setStatus(StoredDataSet::STATUS_PAUSED);
        $this->dataSetRepository->update($dataset);

        return new RedirectResponse($this->urlGenerator->generate('brand.datasets', ['brandId' => $brandId]));
    }

    /**
     * Resumes the selected DataSet
     *
     * @param integer $brandId
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws NotFoundHttpException
     */
    public function resume($brandId, $id)
    {
        $brand = $this->getBrand($brandId);

        $dataset = $this->dataSetRepository->findOne(['brand_id' => $brand->getId(), 'id' => $id]);
        if (!$dataset) {
            throw new NotFoundHttpException('DataSet not found');
        }
        $dataset->setStatus(StoredDataSet::STATUS_RUNNING);
        $this->dataSetRepository->update($dataset);

        return new RedirectResponse($this->urlGenerator->generate('brand.datasets', ['brandId' => $brandId]));
    }

    /**
     * Deletes the selected DataSet
     *
     * @param integer $brandId
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws NotFoundHttpException
     */
    public function delete($brandId, $id)
    {
        $brand = $this->getBrand($brandId);

        $dataset = $this->dataSetRepository->findOne(['brand_id' => $brand->getId(), 'id' => $id]);
        if (!$dataset) {
            throw new NotFoundHttpException('DataSet not found');
        }
        $this->dataSetRepository->delete($dataset);
        $this->flashSuccess('DataSet deleted');
        return new RedirectResponse($this->urlGenerator->generate('brand.datasets', ['brandId' => $brandId]));
    }

    /**
     * Performs batch processing on a list of Datasets
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
                ['redirect_uri' => $this->urlGenerator->generate('brand.datasets', ['brandId' => $brandId])]
            );
        }

        switch (strtolower($params['action'])) {
            case self::BATCH_DELETE:
                return $this->batchDelete($brand, $params['ids']);
                break;
        }
    }

    /**
     * Performs batch DataSet delete
     *
     * @param \Tornado\Organization\Brand $brand
     * @param array                       $ids
     *
     * @return \Tornado\Controller\Result
     */
    protected function batchDelete(Brand $brand, array $ids)
    {
        $datasets = $this->dataSetRepository->find(['brand_id' => $brand->getId(), 'id' => $ids]);
        try {
            $ids = array_map(
                function ($obj) {
                    return $obj->getId();
                },
                $datasets
            );
            $this->dataSetRepository->deleteByIds($ids);
        } catch (\Exception $e) {
            return new Result(
                [],
                ['error' => sprintf('DataSet batch delete error: %s.', $e->getMessage())],
                500
            );
        }

        return new Result(
            [],
            [
                'redirect_uri' => $this->urlGenerator->generate('brand.datasets', ['brandId' => $brand->getId()])
            ]
        );
    }

    /**
     * Returns list of all available DataSets (API)
     *
     * @return \Tornado\Controller\Result
     */
    public function apiIndex()
    {
        $datasets = $this->dataSetRepository->find();
        return new Result($datasets, ['count' => count($datasets)]);
    }
}
