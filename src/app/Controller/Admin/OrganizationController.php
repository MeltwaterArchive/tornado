<?php

namespace Controller\Admin;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGenerator;

use DataSift\Http\Request;

use Tornado\Controller\Result;
use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\Paginator;
use Tornado\Organization\Organization;
use Tornado\Organization\Organization\Form\Create;
use Tornado\Organization\Organization\Form\Update;
use Tornado\Organization\Organization\DataMapper;

use Tornado\Application\Flash\AwareTrait as FlashAwareTrait;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * OrganizationController
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Controller\Admin
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrganizationController
{
    use OrganizationControllerTrait;

    const BATCH_DELETE = 'delete';

    /**
     * @var \Tornado\Organization\Organization\DataMapper
     */
    protected $organizationRepo;

    /**
     * @var Create
     */
    protected $createForm;

    /**
     * @var Update
     */
    protected $updateForm;

    /**
     * Organization to which User session belongs to
     *
     * @var \Tornado\Organization\Organization
     */
    protected $sesUserOrganization;

    /**
     * @param \Tornado\Organization\Organization\DataMapper $organizationRepo
     * @param \Symfony\Component\Routing\Generator\UrlGenerator $urlGenerator
     * @param \Tornado\Organization\Organization\Form\Create $createForm
     * @param \Tornado\Organization\Organization\Form\Update $updateForm
     * @param \Tornado\Organization\Organization $sesUserOrganization
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     */
    public function __construct(
        DataMapper $organizationRepo,
        UrlGenerator $urlGenerator,
        Create $createForm,
        Update $updateForm,
        Organization $sesUserOrganization,
        SessionInterface $session
    ) {
        $this->organizationRepo = $organizationRepo;
        $this->urlGenerator = $urlGenerator;
        $this->createForm = $createForm;
        $this->updateForm = $updateForm;
        $this->sesUserOrganization = $sesUserOrganization;
        $this->session = $session;
    }

    /**
     * Lists all Organizations
     *
     * @param Request $request
     *
     * @return \Tornado\Controller\Result
     */
    public function index(Request $request)
    {
        $paginator = new Paginator(
            $this->organizationRepo,
            $request->get('page', 1),
            $request->get('sort', 'name'),
            $request->get('perPage', 10),
            $request->get('order', DataMapperInterface::ORDER_ASCENDING)
        );
        $paginator->paginate();

        return new Result([
            'organizations' => $paginator->getCurrentItems()
        ], [
            'pagination' => $paginator,
            'count' => $paginator->getCurrentItemsCount()
        ]);
    }

    /**
     * Retrieves the Organization create form
     *
     * @return \Tornado\Controller\Result
     */
    public function createForm()
    {
        return new Result([]);
    }

    /**
     * Creates an Organization
     *
     * @param \DataSift\Http\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Tornado\Controller\Result
     *
     * @throws NotFoundHttpException When Brand was not found.
     * @throws AccessDeniedHttpException if Session User can not access the Brand.
     */
    public function create(Request $request)
    {
        $postParams = $request->getPostParams();
        $this->createForm->submit($postParams);

        if (!$this->createForm->isValid()) {
            return new Result(
                [],
                $this->createForm->getErrors('There were errors saving the Organization.'),
                Response::HTTP_BAD_REQUEST
            );
        }

        $organization = $this->createForm->getData();
        $this->organizationRepo->create($organization);

        $this->setFlash('Organization added successfully', 'success');
        return new RedirectResponse(
            $this->urlGenerator->generate(
                'admin.organization.edit',
                ['id' => $organization->getId()]
            )
        );
    }

    /**
     * Retrieves Organization update form
     *
     * @param int $id
     *
     * @return \Tornado\Controller\Result
     * @throws NotFoundHttpException
     */
    public function overview($id)
    {
        $organization = $this->getOrganization($id);
        return new Result(
            [
                'organization' => $organization
            ],
            [
                'tabs' => $this->getTabs($id, 'overview')
            ]
        );
    }

    public function organization()
    {
        return $this->overview($this->session->get('user')->getOrganizationId());
    }

    /**
     * Retrieves Organization update form
     *
     * @param int $id
     *
     * @return \Tornado\Controller\Result
     * @throws NotFoundHttpException
     */
    public function edit($id)
    {
        $organization = $this->getOrganization($id);
        return new Result(
            [
                'organization' => $organization
            ],
            [
                'tabs' => $this->getTabs($id, 'edit')
            ]
        );
    }

    /**
     * Updates Organization
     *
     * @param \DataSift\Http\Request $request
     * @param int $organizationId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Tornado\Controller\Result
     * @throws NotFoundHttpException
     */
    public function update(Request $request, $organizationId)
    {
        $organization = $this->getOrganization($organizationId);
        $postParams = $request->getPostParams();

        $this->updateForm->submit($postParams, $organization);
        if (!$this->updateForm->isValid()) {
            return new Result([
                'organization' => $organization,
            ], $this->updateForm->getErrors('There were errors saving the Organization.'), Response::HTTP_BAD_REQUEST);
        }
        $organization = $this->updateForm->getData();
        $this->organizationRepo->update($organization);

        $this->flashSuccess('Organization saved successfully');

        return new RedirectResponse(
            $this->urlGenerator->generate(
                'admin.organization.edit',
                ['id' => $organization->getId()]
            )
        );
    }

    /**
     * Removes organization
     *
     * @param int $organizationId
     *
     * @return \Tornado\Controller\Result
     */
    public function delete($organizationId)
    {
        $organization = $this->getOrganization($organizationId);
        if ($this->sesUserOrganization->getId() === $organization->getId()) {
            throw new AccessDeniedHttpException('You can\'t remove the Organization to which you belong to.');
        }

        $this->organizationRepo->delete($organization);

        return new Result(
            [],
            ['redirect_uri' => $this->urlGenerator->generate('admin.organizations')]
        );
    }

    /**
     * Performs Organizations batch processing
     *
     * @param \DataSift\Http\Request $request
     *
     * @return Result
     *
     * @throws BadRequestHttpException when missing action param or it has invalid value
     */
    public function batch(Request $request)
    {
        $params = $request->getPostParams();
        if (!isset($params['ids']) || !is_array($params['ids']) || !count($params['ids']) > 0) {
            return new Result(
                [],
                ['redirect_uri' => $this->urlGenerator->generate('admin.organizations')]
            );
        }

        switch (strtolower($params['action'])) {
            case self::BATCH_DELETE:
                return $this->batchDelete($params['ids']);
                break;
            default:
                throw new BadRequestHttpException('Batch action is missing or not supported.');
        }
    }

    /**
     * Performs batch Organization delete
     *
     * @param array                       $ids
     *
     * @return \Tornado\Controller\Result
     */
    protected function batchDelete(array $ids)
    {
        if (in_array($this->sesUserOrganization->getId(), $ids)) {
            throw new AccessDeniedHttpException('You can\'t remove the Organization to which you belong to.');
        }

        $this->organizationRepo->deleteByIds($ids);
        return new Result(
            [],
            [
                'redirect_uri' => $this->urlGenerator->generate('admin.organizations')
            ]
        );
    }

    /**
     * Retrieves Organization
     *
     * @param int $organizationId
     *
     * @return null|\Tornado\DataMapper\DataObjectInterface
     */
    protected function getOrganization($organizationId)
    {
        $organization = $this->organizationRepo->findOne(['id' => $organizationId]);
        if (!$organization) {
            throw new NotFoundHttpException('Organization not found.');
        }

        return $organization;
    }
}
