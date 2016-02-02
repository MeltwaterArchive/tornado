<?php

namespace Controller;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use DataSift\Http\Request;

use Tornado\Controller\Result;
use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\Paginator;
use Tornado\Security\Authorization\AccessDecisionManagerInterface;

use Tornado\Project\Recording\DataSiftRecording;

use DataSift\Pylon\Pylon;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * BrandController
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
class BrandController
{
    /**
     * @var \Tornado\Security\Authorization\AccessDecisionManagerInterface
     */
    protected $authorizationManager;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var \Tornado\Organization\Brand\DataMapper
     */
    protected $brandRepo;

    /**
     * @var \Tornado\Project\Project\DataMapper
     */
    protected $projectRepo;

    /**
     * @var \Tornado\Project\Recording\DataMapper
     */
    protected $recordingRepo;

    /**
     * @var \Tornado\Project\Recording\DataSiftRecording
     */
    protected $dataSiftRecording;

    /**
     * @var \DataSift\Pylon\Pylon
     */
    protected $pylon;

    /**
     * @var UrlGenerator
     */
    protected $urlGenerator;

    /**
     * @param SessionInterface               $session
     * @param AccessDecisionManagerInterface $authorizationManager
     * @param DataMapperInterface            $brandRepo
     * @param DataMapperInterface            $projectRepo
     * @param DataMapperInterface            $recordingRepo
     * @param \DataSift\Pylon\Pylon          $pylon
     */
    public function __construct(
        SessionInterface $session,
        AccessDecisionManagerInterface $authorizationManager,
        DataMapperInterface $brandRepo,
        DataMapperInterface $projectRepo,
        DataMapperInterface $recordingRepo,
        DataSiftRecording $dataSiftRecording,
        Pylon $pylon,
        UrlGenerator $urlGenerator
    ) {
        $this->session = $session;
        $this->authorizationManager = $authorizationManager;
        $this->brandRepo = $brandRepo;
        $this->projectRepo = $projectRepo;
        $this->recordingRepo = $recordingRepo;
        $this->dataSiftRecording = $dataSiftRecording;
        $this->pylon = $pylon;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Grants User access to the given Brand and retrieves it and its Project collection.
     *
     * @param Request $request
     * @param integer $brandId
     *
     * @return \Tornado\Controller\Result
     *
     * @throw NotFoundHttpException if Brand not found
     * @throw AccessDeniedHttpException if Session User can not access the Brand
     */
    public function get(Request $request, $brandId)
    {
        $brand = $this->brandRepo->findOne(['id' => $brandId]);
        if (!$brand) {
            throw new NotFoundHttpException(sprintf('Could not find Brand with ID %s.', $brandId));
        }

        if (!$this->authorizationManager->isGranted($brand)) {
            throw new AccessDeniedHttpException('You can not access this Brand.');
        }

        $paginator = new Paginator(
            $this->projectRepo,
            $request->get('page', 1),
            $request->get('sort', 'name'),
            $request->get('perPage', 5),
            $request->get('order', DataMapperInterface::ORDER_ASCENDING)
        );
        $paginator->paginate(['brand_id' => $brand->getId()]);

        $sessionUser = $this->session->get('user');
        $brand->projects = $paginator->getCurrentItems();
        $brands = $this->brandRepo->findUserAssigned($sessionUser);

        return new Result(
            [
                'selectedBrand' => $brand,
                'brands' => $brands
            ],
            [
                'brands' => ['count' => count($brands)],
                'projects' => ['count' => count($brand->projects)],
                'pagination' => $paginator
            ]
        );
    }

    /**
     * Grants User access to the given Brand and retrieves it and its Recording collection.
     *
     * @param Request $request
     * @param integer $brandId
     *
     * @return \Tornado\Controller\Result
     *
     * @throw NotFoundHttpException if Brand not found
     * @throw AccessDeniedHttpException if Session User can not access the Brand
     */
    public function getRecordings(Request $request, $brandId)
    {
        $brand = $this->getBrand($brandId);

        $paginator = new Paginator(
            $this->recordingRepo,
            $request->get('page', 1),
            $request->get('sort', 'name'),
            $request->get('perPage', 5),
            $request->get('order', DataMapperInterface::ORDER_ASCENDING)
        );
        $paginator->paginate(['brand_id' => $brand->getId()]);

        $sessionUser = $this->session->get('user');
        $brand->recordings = $this->dataSiftRecording->decorateRecordings(
            $paginator->getCurrentItems()
        );

        $brands = $this->brandRepo->findUserAssigned($sessionUser);

        return new Result(
            [
                'selectedBrand' => $brand,
                'brands' => $brands
            ],
            [
                'brands' => ['count' => count($brands)],
                'recordings' => ['count' => count($brand->recordings)],
                'pagination' => $paginator
            ]
        );
    }

    /**
     * Lists all Recordings the customer has PYLON-side
     *
     * @param Request $request
     * @param integer $brandId
     *
     * @return \Tornado\Controller\Result
     *
     * @throw NotFoundHttpException if Brand not found
     * @throw AccessDeniedHttpException if Session User can not access the Brand
     */
    public function importRecordings(Request $request, $brandId)
    {
        $brand = $this->getBrand($brandId);

        if ($request->getMethod() == Request::METHOD_POST) {
            $params = $request->getPostParams();
            $id = (isset($params['id'])) ? $params['id'] : '';
            if (!$id) {
                throw new NotFoundHttpException(sprintf('Invalid hash %s.', $id));
            }

            try {
                $rec = $this->recordingRepo->findOne(['brand_id' => $brand->getId(), 'datasift_recording_id' => $id]);
                if ($rec) {
                    // Redirect to recording page
                    return new RedirectResponse(
                        $this->urlGenerator->generate('recording.update', ['recordingId' => $rec->getId()])
                    );
                } else {
                    $newRecording = $this->recordingRepo->importRecording($this->pylon, $id);
                    $newRecording->setBrandId($brand->getId());
                    $newRecording->setCreatedAt(time());
                    $this->recordingRepo->create($newRecording);
                    return new RedirectResponse(
                        $this->urlGenerator->generate('recording.update', ['recordingId' => $newRecording->getId()])
                    );
                }
            } catch (\DataSift_Exception_ApiError $ex) {
                throw new AccessDeniedHttpException('You can not access this PYLON recording.');
            }
        }

        //$recordings = $this->dataSiftRecording->getPylonRecordings();

        $collection = $this->dataSiftRecording->getPaginatedCollection();
        $paginator = new Paginator(
            $collection,
            $request->get('page', 1),
            $request->get('sort', 'name'),
            $request->get('perPage', 5),
            $request->get('order', DataMapperInterface::ORDER_ASCENDING)
        );
        $paginator->paginate();

        $sessionUser = $this->session->get('user');
        $brand->recordings = $this->dataSiftRecording->decoratePylonCollection(
            $paginator->getCurrentItems()
        );
        $brands = $this->brandRepo->findUserAssigned($sessionUser);

        return new Result(
            [
                'selectedBrand' => $brand,
                'brands' => $brands
            ],
            [
                'brands' => ['count' => count($brands)],
                'recordings' => ['count' => count($brand->recordings)],
                'pagination' => $paginator
            ]
        );
    }

    /**
     * Performs the basic checks for actions related to the existing Brand and retrieves the
     * called Brand.
     *
     * @param integer $brandId
     *
     * @return \Tornado\Organization\Brand
     *
     * @throws NotFoundHttpException When such Brand was not found.
     * @throws AccessDeniedHttpException if Session User can not access the Brand
     */
    protected function getBrand($brandId)
    {
        $brand = $this->brandRepo->findOne(['id' => $brandId]);
        if (!$brand) {
            throw new NotFoundHttpException(sprintf('Could not find Brand with ID %s.', $brandId));
        }

        if (!$this->authorizationManager->isGranted($brand)) {
            throw new AccessDeniedHttpException('You can not access this Brand.');
        }

        return $brand;
    }
}
