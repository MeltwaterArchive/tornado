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
use Tornado\Organization\Role;
use Tornado\Organization\User;
use Tornado\Organization\Organization\DataMapper as OrganizationDataMapper;
use Tornado\Organization\Agency\DataMapper as AgencyDataMapper;
use Tornado\Organization\Brand\DataMapper as BrandDataMapper;
use Tornado\Organization\User\DataMapper as UserDataMapper;
use Tornado\Organization\Role\DataMapper as RoleDataMapper;

use Tornado\Organization\User\Form\Create as CreateForm;
use Tornado\Organization\User\Form\Update as UpdateForm;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * UserController
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
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects,PHPMD.ExcessiveClassComplexity,PHPMD.ExcessiveParameterList)
 */
class UserController
{
    use OrganizationControllerTrait;

    const BATCH_DELETE = 'delete';

    /**
     * @var \Tornado\Organization\Organization\DataMapper
     */
    protected $organizationRepo;

    /**
     * @var \Tornado\Organization\User\DataMapper
     */
    protected $userRepo;

    /**
     * @var \Tornado\Organization\User\Form\Create
     */
    protected $createForm;

    /**
     * @var \Tornado\Organization\User\Form\Update
     */
    protected $updateForm;

    /**
     * @var \Tornado\Organization\Role\DataMapper
     */
    protected $roleRepo;

    /**
     * @var \Tornado\Organization\Agency\DataMapper
     */
    protected $agencyRepo;

    /**
     * @var \Tornado\Organization\Brand\DataMapper
     */
    protected $brandRepo;

    /**
     * The session handler
     *
     * @var \SessionHandlerInterface
     */
    protected $sessionHandler;

    /**
     *
     *
     * @param \Tornado\Organization\Organization\DataMapper $organizationRepo
     * @param \Tornado\Organization\User\DataMapper $userRepo
     * @param \Symfony\Component\Routing\Generator\UrlGenerator $urlGenerator
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param \Tornado\Organization\User\Form\Create $createForm
     * @param \Tornado\Organization\User\Form\Update $updateForm
     * @param \Tornado\Organization\Role\DataMapper $roleRepo
     * @param \Tornado\Organization\Agency\DataMapper $agencyRepo
     * @param \Tornado\Organization\Brand\DataMapper $brandRepo
     * @param \SessionHandlerInterface $sessionHandler
     */
    public function __construct(
        OrganizationDataMapper $organizationRepo,
        UserDataMapper $userRepo,
        UrlGenerator $urlGenerator,
        SessionInterface $session,
        CreateForm $createForm,
        UpdateForm $updateForm,
        RoleDataMapper $roleRepo,
        AgencyDataMapper $agencyRepo,
        BrandDataMapper $brandRepo,
        \SessionHandlerInterface $sessionHandler
    ) {
        $this->organizationRepo = $organizationRepo;
        $this->userRepo = $userRepo;
        $this->urlGenerator = $urlGenerator;
        $this->session = $session;
        $this->createForm = $createForm;
        $this->updateForm = $updateForm;
        $this->roleRepo = $roleRepo;
        $this->agencyRepo = $agencyRepo;
        $this->brandRepo = $brandRepo;
        $this->sessionHandler = $sessionHandler;
    }

    /**
     * Lists all Users
     *
     * @param Request $request
     *
     * @return \Tornado\Controller\Result
     */
    public function index(Request $request, $id)
    {
        /** @var Organization $organization */
        $organization = $this->getOrganization($id);
        $usersCount = $this->userRepo->count(['organization_id' => $id]);
        $hasReachedAccLimit = $organization->hasReachedAccountLimit($usersCount);

        $paginator = new Paginator(
            $this->userRepo,
            $request->get('page', 1),
            $request->get('sort', 'username'),
            $request->get('perPage', 20),
            $request->get('order', DataMapperInterface::ORDER_ASCENDING)
        );
        $paginator->paginate(['organization_id' => $id]);

        return new Result([
            'organization' => $organization,
            'users' => $paginator->getCurrentItems(),
            'account_limit_reached' => $hasReachedAccLimit
        ], [
            'pagination' => $paginator,
            'count' => $paginator->getCurrentItemsCount(),
            'tabs' => $this->getTabs($id, 'users')
        ]);
    }

    /**
     * Creates a User
     *
     * @param \DataSift\Http\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Tornado\Controller\Result
     *
     * @throws AccessDeniedHttpException if Session User cannot access the Brand.
     */
    public function create(Request $request, $id)
    {
        /** @var Organization $organization */
        $organization = $this->getOrganization($id);
        $usersCount = $this->userRepo->count(['organization_id' => $id]);
        $hasReachedAccLimit = $organization->hasReachedAccountLimit($usersCount);

        if ($request->getMethod() == Request::METHOD_POST && !$hasReachedAccLimit) {
            $postParams = $request->getPostParams();
            $postParams['organizationId'] = $organization->getId();
            $this->createForm->submit($postParams, null, $this->getCurrentUser()->isSuperAdmin());

            if ($this->createForm->isValid()) {
                $user = $this->createForm->getData();
                $this->userRepo->create($user);
                $this->processUserRoles($user, $postParams['permissions']);
                $this->flashSuccess('User created successfully');
                return new RedirectResponse(
                    $this->getUrl('users', $id)
                );
            }
        }

        return new Result(
            [
                'user' => $this->createForm->getNormalizedData(),
                'organization' => $organization,
                'account_limit_reached' => $hasReachedAccLimit
            ],
            array_merge(
                ['tabs' => $this->getTabs($id, 'users')],
                $this->createForm->getErrors('There were errors creating the User')
            )
        );
    }

    /**
     * The single-organization path for listing users
     *
     * @param \DataSift\Http\Request $request
     *
     * @return mixed
     */
    public function singleIndex(Request $request)
    {
        return $this->index($request, $this->session->get('user')->getOrganizationId());
    }

    /**
     * The single-organization path for creating
     *
     * @param \DataSift\Http\Request $request
     *
     * @return mixed
     */
    public function singleCreate(Request $request)
    {
        return $this->create($request, $this->session->get('user')->getOrganizationId());
    }

    /**
     * The single-organization path for editing
     *
     * @param \DataSift\Http\Request $request
     * @param integer $id
     *
     * @return mixed
     */
    public function singleEdit(Request $request, $id)
    {
        return $this->edit($request, $this->session->get('user')->getOrganizationId(), $id);
    }

    /**
     * The single-organization path for agencies
     *
     * @param \DataSift\Http\Request $request
     * @param integer $id
     *
     * @return mixed
     */
    public function singleAgencies(Request $request, $id)
    {
        return $this->agencies($request, $this->session->get('user')->getOrganizationId(), $id);
    }

    /**
     * The single-organization path for brands
     *
     * @param \DataSift\Http\Request $request
     * @param integer $id
     *
     * @return mixed
     */
    public function singleBrands(Request $request, $id)
    {
        return $this->brands($request, $this->session->get('user')->getOrganizationId(), $id);
    }

    /**
     * The single-organization path for batch modification
     *
     * @param \DataSift\Http\Request $request
     *
     * @return mixed
     */
    public function singleBatch(Request $request)
    {
        return $this->batch($request, $this->session->get('user')->getOrganizationId());
    }

    /**
     * Retrieves Organization update form
     *
     * @param int $id
     *
     * @return \Tornado\Controller\Result
     * @throws NotFoundHttpException
     */
    public function edit(Request $request, $organizationId, $id)
    {
        $this->checkOrganization($organizationId);
        $organization = $this->getOrganization($organizationId);

        $user = $this->userRepo->findOne(['id' => $id, 'organization_id' => $organizationId]);
        foreach ($this->roleRepo->findUserAssigned($user) as $role) {
            $user->addRole($role);
        }

        $password = '';

        if ($request->getMethod() == Request::METHOD_POST) {
            $postParams = $request->getPostParams();
            $password = (isset($postParams['password'])) ? $postParams['password'] : '';
            $this->updateForm->submit($postParams, $user, $this->getCurrentUser()->isSuperAdmin());

            $user = $this->updateForm->getData();

            if ($this->updateForm->isValid()) {
                $this->userRepo->update($user);
                $this->processUserRoles($user, $postParams['permissions']);

                if ($user->isDisabled()) {
                    /**
                     * Fetch the session id of the user from session storage and then destroy it
                     */
                    $this->sessionHandler->destroy($this->sessionHandler->read("session-{$user->getId()}"));
                }

                $this->flashSuccess('User saved successfully');
                return new RedirectResponse(
                    $this->getUrl('user.edit', $id, $organizationId)
                );
            }
        }

        $agencies = $this->agencyRepo->findUserAssigned($user);
        $brands = $this->brandRepo->findUserAssigned($user);

        return new Result(
            [
                'organization' => $organization,
                'user' => $user,
                'password' => $password,
                'agencyCount' => count($agencies),
                'brandCount' => count($brands),
            ],
            array_merge(
                ['tabs' => $this->getTabs($organizationId, 'users')],
                $this->updateForm->getErrors('There were errors saving the User')
            )
        );
    }

    /**
     * Manages the Agencies a user has access to
     *
     * @param \DataSift\Http\Request $request
     * @param integer $organizationId
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Tornado\Controller\Result
     */
    public function agencies(Request $request, $organizationId, $id)
    {
        $this->checkOrganization($organizationId);
        $organization = $this->getOrganization($organizationId);
        $user = $this->userRepo->findOne(['id' => $id, 'organization_id' => $organization->getId()]);

        if ($request->getMethod() == Request::METHOD_POST) {
            $postParams = $request->getPostParams();
            if (isset($postParams['agency_id'], $postParams['action'])) {
                $agency = $this->agencyRepo->findOne([
                    'id' => $postParams['agency_id'],
                    'organization_id' => $organization->getId()
                ]);

                if ($agency) {
                    switch ($postParams['action']) {
                        case 'grant':
                            $this->userRepo->addAgencies($user, [$agency]);
                            $this->flashSuccess('Access granted for the "' . $agency->getName() . '" Agency');
                            break;
                        case 'remove':
                            $this->userRepo->removeAgencies($user, [$agency]);
                            $this->flashSuccess('Access removed for the "' . $agency->getName() . '" Agency');
                            break;
                    }
                }
            }
            return new RedirectResponse($this->getUrl('user.agencies', $id, $organizationId));
        }

        $paginator = new Paginator(
            $this->agencyRepo,
            $request->get('page', 1),
            $request->get('sort', 'name'),
            $request->get('perPage', 20),
            $request->get('order', DataMapperInterface::ORDER_ASCENDING)
        );
        $paginator->paginate(['organization_id' => $organization->getId()]);

        $userAgencies = [];

        foreach ($this->agencyRepo->findUserAssigned($user) as $agency) {
            $userAgencies[$agency->getId()] = $agency;
        }

        return new Result(
            [
                'organization' => $organization,
                'user' => $user,
                'user_agencies' => $userAgencies,
            ],
            [
                'tabs' => $this->getTabs($organizationId, 'users'),
                'pagination' => $paginator
            ]
        );
    }

    /**
     * Manages the Brands a user has access to
     *
     * @param \DataSift\Http\Request $request
     * @param integer $organizationId
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Tornado\Controller\Result
     */
    public function brands(Request $request, $organizationId, $id)
    {
        $this->checkOrganization($organizationId);
        $organization = $this->getOrganization($organizationId);
        $user = $this->userRepo->findOne(['id' => $id, 'organization_id' => $organization->getId()]);

        $userAgencies = [];
        foreach ($this->agencyRepo->findUserAssigned($user) as $agency) {
            $userAgencies[$agency->getId()] = $agency;
        }

        if ($request->getMethod() == Request::METHOD_POST) {
            $postParams = $request->getPostParams();
            if (isset($postParams['brand_id'], $postParams['action'])) {
                $brand = $this->brandRepo->findOne([
                    'id' => $postParams['brand_id'],
                    'agency_id' => array_keys($userAgencies)
                ]);

                if ($brand) {
                    switch ($postParams['action']) {
                        case 'grant':
                            $this->userRepo->addBrands($user, [$brand]);
                            $this->flashSuccess('Access granted for the "' . $brand->getName() . '" Brand');
                            break;
                        case 'remove':
                            $this->userRepo->removeBrands($user, [$brand]);
                            $this->flashSuccess('Access removed for the "' . $brand->getName() . '" Brand');
                            break;
                    }
                }
            }
            return new RedirectResponse($this->getUrl('user.brands', $id, $organizationId));
        }

        $userBrands = [];

        foreach ($this->brandRepo->findUserAssigned($user) as $brand) {
            $userBrands[$brand->getId()] = $brand;
        }

        $paginator = new Paginator(
            $this->brandRepo,
            $request->get('page', 1),
            $request->get('sort', 'name'),
            $request->get('perPage', 20),
            $request->get('order', DataMapperInterface::ORDER_ASCENDING)
        );

        $keys = array_keys($userAgencies);
        if (count($keys)) {
            $paginator->paginate(['agency_id' => $keys]);
        }

        return new Result(
            [
                'organization' => $organization,
                'user' => $user,
                'user_agencies' => $userAgencies,
                'user_brands' => $userBrands
            ],
            [
                'tabs' => $this->getTabs($organizationId, 'users'),
                'pagination' => $paginator
            ]
        );
    }

    /**
     * Removes a user
     *
     * @param int $organizationId
     *
     * @return \Tornado\Controller\Result
     */
    public function delete($organizationId, $id)
    {
        $this->checkOrganization($organizationId);
        $this->userRepo->delete($id);

        return new Result(
            [],
            [
                'redirect_uri' => $this->getUrl(
                    'users',
                    $organizationId
                )
            ]
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
    public function batch(Request $request, $id)
    {
        $this->checkOrganization($id);
        $params = $request->getPostParams();
        if (!isset($params['ids']) || !is_array($params['ids']) || !count($params['ids']) > 0) {
            return new Result(
                [],
                ['redirect_uri' => $this->urlGenerator->generate('admin.organizations')]
            );
        }

        switch (strtolower($params['action'])) {
            case self::BATCH_DELETE:
                return $this->batchDelete($id, $params['ids']);
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
    protected function batchDelete($organizationId, array $ids)
    {

        $this->userRepo->deleteByIds($ids);
        return new Result(
            [],
            [
                'redirect_uri' => $this->getUrl('users', $organizationId)
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
     * Checks whether the current user has the appropriate access to the current Organization
     *
     * @param integer $organizationId
     *
     * @throws AccessDeniedHttpException
     */
    protected function checkOrganization($organizationId)
    {
        $user = $this->session->get('user');
        if (!($user->isSuperadmin() || $user->getOrganizationId() == $organizationId)) {
            throw new AccessDeniedHttpException('You do not have access');
        }
    }

    /**
     * Adds the required roles to a User
     *
     * @param \Tornado\Organization\User $user
     * @param string $permissions
     */
    protected function processUserRoles(User $user, $permissions)
    {
        $roles = [];
        switch ($permissions) {
            case CreateForm::PERMISSION_SUPERADMIN:
                $roles[] = Role::ROLE_SUPERADMIN;
            // Fallthrough on purpose - all superadmins must be admins too
            case CreateForm::PERMISSION_ADMIN:
                $roles[] = Role::ROLE_ADMIN;
                break;
            case CreateForm::PERMISSION_SPAONLY:
                $roles[] = Role::ROLE_SPAONLY;
                break;
        }

        $this->roleRepo->setUserRoles($user, $roles);
    }
}
