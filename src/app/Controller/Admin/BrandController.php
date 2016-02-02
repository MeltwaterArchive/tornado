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
use Tornado\Organization\Organization\DataMapper as OrganizationDataMapper;
use Tornado\Organization\Agency\DataMapper as AgencyDataMapper;

use Tornado\Organization\Brand\DataMapper as BrandDataMapper;
use Tornado\Organization\Brand\Form\Create as CreateForm;
use Tornado\Organization\Brand\Form\Update as UpdateForm;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * AgencyController
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Controller\Admin
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BrandController
{
    use OrganizationControllerTrait;

    const BATCH_DELETE = 'delete';

    /**
     * @var \Tornado\Organization\Organization\DataMapper
     */
    protected $organizationRepo;

    /**
     * @var \Tornado\Organization\Agency\DataMapper
     */
    protected $agencyRepo;

    /**
     * @var \Tornado\Organization\Brand\DataMapper
     */
    protected $brandRepo;

    /**
     * @var \Tornado\Organization\Brand\Form\Create
     */
    protected $createForm;

    /**
     * @var \Tornado\Organization\Brand\Form\Update
     */
    protected $updateForm;

    /**
     * @param \Tornado\Organization\Organization\DataMapper $organizationRepo
     * @param \Tornado\Organization\Agency\DataMapper $agencyRepo
     * @param \Tornado\Organization\Brand\DataMapper $brandRepo
     * @param \Symfony\Component\Routing\Generator\UrlGenerator $urlGenerator
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     */
    public function __construct(
        OrganizationDataMapper $organizationRepo,
        AgencyDataMapper $agencyRepo,
        BrandDataMapper $brandRepo,
        UrlGenerator $urlGenerator,
        SessionInterface $session,
        CreateForm $createForm,
        UpdateForm $updateForm
    ) {
        $this->organizationRepo = $organizationRepo;
        $this->agencyRepo = $agencyRepo;
        $this->brandRepo = $brandRepo;
        $this->urlGenerator = $urlGenerator;
        $this->session = $session;
        $this->createForm = $createForm;
        $this->updateForm = $updateForm;
    }

    /**
     * Lists all Agencies
     *
     * @param Request $request
     *
     * @return \Tornado\Controller\Result
     */
    public function index(Request $request, $id, $organizationId)
    {
        $organization = $this->getOrganization($organizationId);
        $agency = $this->getAgency($id, $organizationId);

        $paginator = new Paginator(
            $this->brandRepo,
            $request->get('page', 1),
            $request->get('sort', 'name'),
            $request->get('perPage', 20),
            $request->get('order', DataMapperInterface::ORDER_ASCENDING)
        );
        $paginator->paginate(['agency_id' => $id]);

        return new Result([
            'organization' => $organization,
            'agency' => $agency,
            'brands' => $paginator->getCurrentItems()
        ], [
            'pagination' => $paginator,
            'count' => $paginator->getCurrentItemsCount(),
            'tabs' => $this->getTabs($organizationId, 'brands', $agency->getId())
        ]);
    }

    /**
     * Creates an Agency
     *
     * @param \DataSift\Http\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Tornado\Controller\Result
     *
     * @throws AccessDeniedHttpException if Session User can not access the Brand.
     */
    public function create(Request $request, $id, $organizationId)
    {
        $organization = $this->getOrganization($organizationId);
        $agency = $this->getAgency($id, $organizationId);

        if ($request->getMethod() == Request::METHOD_POST) {
            $postParams = $request->getPostParams();
            $postParams['agencyId'] = $agency->getId();
            $this->createForm->submit($postParams);

            if ($this->createForm->isValid()) {
                $brand = $this->createForm->getData();
                $this->brandRepo->create($brand);
                $this->flashSuccess('Brand created successfully');
                return new RedirectResponse(
                    $this->getUrl('brands', $id, $organizationId)
                );
            }
        }

        return new Result(
            [
                'brand' => $this->createForm->getNormalizedData(),
                'agency' => $agency,
                'organization' => $organization
            ],
            array_merge(
                ['tabs' => $this->getTabs($organizationId, 'brands', $agency->getId())],
                $this->createForm->getErrors('There were errors creating the Brand')
            )
        );
    }

    /**
     * The single-organization path for listing agencies
     *
     * @param \DataSift\Http\Request $request
     * @param integer $id Agency Id
     *
     * @return mixed
     */
    public function singleIndex(Request $request, $id)
    {
        return $this->index($request, $id, $this->session->get('user')->getOrganizationId());
    }

    /**
     * The single-organization path for creating
     *
     * @param \DataSift\Http\Request $request
     * @param integer $id Agency ID
     *
     * @return mixed
     */
    public function singleCreate(Request $request, $id)
    {
        return $this->create($request, $id, $this->session->get('user')->getOrganizationId());
    }

    /**
     * The single-organization path for editing
     *
     * @param \DataSift\Http\Request $request
     * @param integer $id
     *
     * @return mixed
     */
    public function singleEdit(Request $request, $id, $brandId)
    {
        return $this->edit($request, $this->session->get('user')->getOrganizationId(), $id, $brandId);
    }

    /**
     * The single-organization path for batch modification
     *
     * @param \DataSift\Http\Request $request
     * @param integer $id
     *
     * @return mixed
     */
    public function singleBatch(Request $request, $id)
    {
        return $this->batch($request, $id, $this->session->get('user')->getOrganizationId());
    }

    /**
     * Edits an Agency
     *
     * @param int $id
     *
     * @return \Tornado\Controller\Result
     * @throws NotFoundHttpException
     */
    public function edit(Request $request, $organizationId, $id, $brandId)
    {
        $this->checkOrganization($organizationId);
        $organization = $this->getOrganization($organizationId);
        $agency = $this->getAgency($id, $organizationId);

        $brand = $this->brandRepo->findOne(['id' => $brandId, 'agency_id' => $id]);

        if ($request->getMethod() == Request::METHOD_POST) {
            $postParams = $request->getPostParams();
            $postParams['agencyId'] = $id;
            $this->updateForm->submit($postParams, $brand);

            $brand = $this->updateForm->getData();

            if ($this->updateForm->isValid()) {
                $this->brandRepo->update($brand);
                $this->flashSuccess('Brand saved successfully');
                return new RedirectResponse(
                    $this->getUrl('brand.edit', $id, $organizationId, ['brandId' => $brandId])
                );
            }
        }

        return new Result(
            [
                'organization' => $organization,
                'agency' => $agency,
                'brand' => $brand
            ],
            array_merge(
                ['tabs' => $this->getTabs($organizationId, 'brands', $agency->getId())],
                $this->updateForm->getErrors('There were errors saving the Brand')
            )
        );
    }

    /**
     * Removes an agency
     *
     * @param int $organizationId
     *
     * @return \Tornado\Controller\Result
     */
    public function delete($organizationId, $id, $brandId)
    {
        $this->checkOrganization($organizationId);
        $this->brandRepo->delete($brandId);

        return new Result(
            [],
            [
                'redirect_uri' => $this->getUrl(
                    'brands',
                    $id,
                    $organizationId
                )
            ]
        );
    }

    /**
     * Performs Agencies batch processing
     *
     * @param \DataSift\Http\Request $request
     *
     * @return Result
     *
     * @throws BadRequestHttpException when missing action param or it has invalid value
     */
    public function batch(Request $request, $id, $organizationId)
    {
        $this->checkOrganization($organizationId);

        $params = $request->getPostParams();
        if (!isset($params['ids']) || !is_array($params['ids']) || !count($params['ids']) > 0) {
            return new Result(
                [],
                ['redirect_uri' => $this->getUrl('brands', $id, $organizationId)]
            );
        }

        switch (strtolower($params['action'])) {
            case self::BATCH_DELETE:
                return $this->batchDelete($organizationId, $id, $params['ids']);
                break;
            default:
                throw new BadRequestHttpException('Batch action is missing or not supported.');
        }
    }

    /**
     * Performs batch Agency delete
     *
     * @param array                       $ids
     *
     * @return \Tornado\Controller\Result
     */
    protected function batchDelete($organizationId, $id, array $ids)
    {
        $this->brandRepo->deleteByIds($ids);
        return new Result(
            [],
            [
                'redirect_uri' => $this->getUrl('brands', $id, $organizationId)
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

    /**
     * Retrieves Agency
     *
     * @param int $agencyId
     * @param int $organizationId
     *
     * @return null|\Tornado\DataMapper\DataObjectInterface
     */
    protected function getAgency($agencyId, $organizationId)
    {
        $agency = $this->agencyRepo->findOne(['id' => $agencyId, 'organization_id' => $organizationId]);
        if (!$agency) {
            throw new NotFoundHttpException('Agency not found.');
        }

        return $agency;
    }

    /**
     * Checks whether the current user has the appropriate access to the current Organization
     *
     * @param integer $organizationId
     *
     * @throws AccessDeniedHttpException
     */
    protected function checkOrganization($organizationId)
    {
        $user = $this->session->get('user');
        if (!($user->hasRole('ROLE_SUPERADMIN') || $user->getOrganizationId() == $organizationId)) {
            throw new AccessDeniedHttpException('You do not have access');
        }
    }
}
