<?php

namespace Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGenerator;

use DataSift\Http\Request;
use DataSift\Form\FormInterface;

use Tornado\Controller\ProjectDataAwareInterface;
use Tornado\Controller\ProjectDataAwareTrait;
use Tornado\Controller\Result;
use Tornado\DataMapper\DataMapperInterface;

/**
 * Retrieves a project and redirects to the worksheet controller with the first worksheet selected.
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
class ProjectController implements ProjectDataAwareInterface
{
    use ProjectDataAwareTrait;

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
     * @var \Tornado\Project\Project\Form\Create
     */
    protected $createForm;

    /**
     * @var \Tornado\Project\Project\Form\Update
     */
    protected $updateForm;

    /**
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface     $session
     * @param \Symfony\Component\Routing\Generator\UrlGenerator              $urlGenerator
     * @param \Tornado\DataMapper\DataMapperInterface                        $brandRepository
     * @param \DataSift\Form\FormInterface                                   $createForm
     * @param \DataSift\Form\FormInterface                                   $updateForm
     */
    public function __construct(
        SessionInterface $session,
        UrlGenerator $urlGenerator,
        DataMapperInterface $brandRepository,
        FormInterface $createForm,
        FormInterface $updateForm
    ) {
        $this->session = $session;
        $this->urlGenerator = $urlGenerator;
        $this->brandRepository = $brandRepository;
        $this->createForm = $createForm;
        $this->updateForm = $updateForm;
    }

    /**
     * Creates a new Project for the given Brand
     *
     * @param \DataSift\Http\Request $request
     * @param integer                $brandId
     *
     * @return \Tornado\Controller\Result|RedirectResponse
     *
     * @throws NotFoundHttpException When Brand was not found.
     * @throws AccessDeniedHttpException if Session User can not access the Brand.
     */
    public function create(Request $request, $brandId)
    {
        $brand = $this->getBrand($brandId);

        $sessionUser = $this->session->get('user');
        $brands = $this->brandRepository->findUserAssigned($sessionUser);
        $responseData = ['selectedBrand' => $brand, 'brands' => $brands];

        if ('POST' === $request->getMethod()) {
            $postParams = $request->getPostParams();
            $postParams['brand_id'] = $brand->getId();

            $this->createForm->submit($postParams);

            if (!$this->createForm->isValid()) {
                return new Result($responseData, $this->createForm->getErrors(), 400);
            }

            $project = $this->createForm->getData();
            $this->projectRepository->create($project);

            return new RedirectResponse(
                $this->urlGenerator->generate('project.get', ['projectId' => $project->getId()])
            );
        }

        return new Result($responseData, [], 200);
    }

    /**
     * Updates the given Project
     *
     * @param \DataSift\Http\Request $request
     * @param integer                $projectId
     *
     * @return \Tornado\Controller\Result
     *
     * @throws NotFoundHttpException When Project was not found.
     * @throws AccessDeniedHttpException if Session User can not access the Project.
     */
    public function update(Request $request, $projectId)
    {
        $project = $this->getProject($projectId);

        $sessionUser = $this->session->get('user');
        $brand = $this->brandRepository->findOneByProject($project);
        $brands = $this->brandRepository->findUserAssigned($sessionUser);
        $responseData = ['selectedBrand' => $brand, 'brands' => $brands, 'project' => $project];

        if (!in_array($request->getMethod(), ['GET', 'DELETE'])) {
            $postParams = $request->getPostParams();
            $postParams['brand_id'] = $brand->getId();

            $this->updateForm->submit($postParams, $project);

            if (!$this->updateForm->isValid()) {
                return new Result($responseData, $this->updateForm->getErrors(), 400);
            }

            $project = $this->updateForm->getData();
            $this->projectRepository->update($project);
        }

        return new Result($responseData);
    }

    /**
     * Removes the given Project
     *
     * @param integer $projectId
     *
     * @return \Tornado\Controller\Result
     *
     * @throws NotFoundHttpException When Project was not found.
     * @throws AccessDeniedHttpException if Session User can not access the Project.
     */
    public function delete($projectId)
    {
        $project = $this->getProject($projectId);
        $brandId = $project->getBrandId();
        $this->projectRepository->delete($project);

        return new Result(
            [],
            ['redirect_uri' => $this->urlGenerator->generate('brand.get', ['brandId' => $brandId])]
        );
    }

    /**
     * Performs Brand's projects batch processing
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
            throw new BadRequestHttpException('Invalid action type.');
        }

        $redirectUri = $this->urlGenerator->generate('brand.get', ['brandId' => $brandId]);
        if (!isset($params['ids']) || !is_array($params['ids']) || !count($params['ids']) > 0) {
            return new Result([], ['redirect_uri' => $redirectUri], 400);
        }

        switch (strtolower($params['action'])) {
            case self::BATCH_DELETE:
                $this->projectRepository->deleteProjectsByBrand($brand, $params['ids']);
                break;
        }

        return new Result([], ['redirect_uri' => $redirectUri]);
    }

    /**
     * Retrieves Brand by ID and validates access.
     *
     * due to duplicated setAuthorizationManager method. I left it for future refactor if necessary
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
        $brand = $this->brandRepository->findOne(['id' => $brandId]);
        if (!$brand) {
            throw new NotFoundHttpException(sprintf('Could not find Brand with ID %s', $brandId));
        }

        if (!$this->authorizationManager->isGranted($brand)) {
            throw new AccessDeniedHttpException('You can not access this Brand.');
        }

        return $brand;
    }
}
