<?php

namespace Controller\PylonApi;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use DataSift\Api\User as DataSift_User;
use DataSift_Account_Identity;
use DataSift_Account_Identity_Token;
use DataSift_Account_Identity_Limit;

use Tornado\Organization\Brand\DataMapper as BrandRepository;
use Tornado\Organization\Brand;
use Tornado\Organization\User\DataMapper as UserRepository;
use Tornado\Organization\User;

/**
 * IdentityController proxies account/identity related endpoints to DS.
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
 * @SuppressWarnings("unused")
 */
class IdentityController
{
    /**
     * DataSift API User.
     *
     * @var DataSift_User
     */
    protected $client;

    /**
     * DataSift Account Identity API client.
     *
     * @var DataSift_Account_Identity
     */
    protected $identityApi;

    /**
     * DataSift Account Identity Service Token API client.
     *
     * @var DataSift_Account_Identity_Token
     */
    protected $identityTokenApi;

    /**
     * DataSift Account Identity Service Limit API client.
     *
     * @var DataSift_Account_Identity_Limit
     */
    protected $identityLimitApi;

    /**
     * Brand repository.
     *
     * @var BrandRepository
     */
    protected $brandRepository;

    /**
     * User repository.
     *
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * Constructor.
     *
     * @param DataSift_User $client DataSift API User.
     * @param DataSift_Account_Identity $identityApi DataSift Account Identity API client.
     * @param DataSift_Account_Identity_Token $identityTokenApi DataSift Account Identity Service Token API client.
     * @param DataSift_Account_Identity_Limit $identityLimitApi DataSift Account Identity Service Limit API client.
     * @param BrandRepository $brandRepository Brand repository.
     * @param UserRepository $userRepository User repository.
     */
    public function __construct(
        DataSift_User $client,
        DataSift_Account_Identity $identityApi,
        DataSift_Account_Identity_Token $identityTokenApi,
        DataSift_Account_Identity_Limit $identityLimitApi,
        BrandRepository $brandRepository,
        UserRepository $userRepository
    ) {
        $this->client = $client;
        $this->identityApi = $identityApi;
        $this->identityTokenApi = $identityTokenApi;
        $this->identityLimitApi = $identityLimitApi;
        $this->brandRepository = $brandRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Proxies to `GET account/identity` DS API endpoint.
     *
     * @param  Request $request Request.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        return $this->client->proxyResponse(function () use ($request) {
            $this->identityApi->getAll(
                $request->query->get('label', null),
                $request->query->get('page', 1),
                $request->query->get('per_page', 25)
            );
        });
    }

    /**
     * Proxies to `GET account/identity/{id}` DS API endpoint.
     *
     * @param  string $id Identity ID which should be fetched.
     *
     * @return JsonResponse
     */
    public function show($id)
    {
        return $this->client->proxyResponse(function () use ($id) {
            $this->identityApi->get($id);
        });
    }

    /**
     * Creates a new identity by calling `POST account/identity` on DS API and then also inserting appropriate
     * Brand and User to Tornado DB.
     *
     * @param  Request $request Request.
     *
     * @return JsonResponse
     */
    public function create(Request $request)
    {

        $label = $request->get('label');
        $status = $request->get('status', 'active');
        $master = $request->get('master', false);


        //does a brand with the same label exist?
        $brand = $this->brandRepository->findOne(['name' => $label]);

        if ($brand) {
            return new JsonResponse(['error' => 'An Identity with that label already exists'], Response::HTTP_CONFLICT);
        }

        $response = $this->client->proxyResponse(function () use ($request, $label, $master, $status) {
            $this->identityApi->create($label, $master, $status);
        });

        // if failed then return directly
        if ($response->getStatusCode() !== Response::HTTP_CREATED) {
            return $response;
        }

        $data = json_decode($response->getContent(), true);
        $agency = $request->attributes->get('agency');

        // create a Brand linked to this identity
        $brand = new Brand();
        $brand->setAgencyId($agency->getId());
        $brand->setName($data['label']);
        $brand->setDatasiftIdentityId($data['id']);
        $brand->setDatasiftApiKey($data['api_key']);


        $this->brandRepository->create($brand);

        // also create a user
        $user = new User();
        $user->setOrganizationId($agency->getOrganizationId());
        $user->setEmail($data['id']); // will be used later to identify this user as linked with the identity
        $user->setPassword('identity-' . md5($data['api_key'] . time()));
        $user->setUsername($data['label']);
        $user->setType(User::TYPE_IDENTITY_API);

        $this->userRepository->create($user);
        $this->userRepository->addBrands($user, [$brand]);


        return $response;
    }

    /**
     * Updates an identity by calling `PUT account/identity/{id}` on DS API and then also updating relevant
     * Brand and User in Tornado DB.
     *
     * @param  Request $request Request.
     * @param  string $id Identity ID.
     *
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $response = $this->client->proxyResponse(function () use ($request, $id) {
            $label = $request->get('label', null);
            $status = $request->get('status', 'active');
            $master = $request->get('master', false);

            $this->identityApi->update($id, $label, $master, $status);
        });

        // if failed then return directly
        if ($response->getStatusCode() !== Response::HTTP_OK) {
            return $response;
        }

        $data = json_decode($response->getContent(), true);
        $agency = $request->attributes->get('agency');

        // update the associated Brand with things that might have changed
        $brand = $this->brandRepository->findOne([
            'agency_id' => $agency->getId(),
            'datasift_identity_id' => $data['id']
        ]);

        if (!$brand) {
            throw new \RuntimeException(sprintf(
                'Could not find Brand associated with DataSift identity %s',
                $data['id']
            ));
        }

        $brand->setName($data['label']);
        $brand->setDatasiftApiKey($data['api_key']);

        $this->brandRepository->update($brand);

        // update the associated User with things that might have changed
        $user = $this->userRepository->findOne([
            'organization_id' => $agency->getOrganizationId(),
            'email' => $data['id'],
            'type' => User::TYPE_IDENTITY_API
        ]);

        if (!$user) {
            throw new \RuntimeException(sprintf(
                'Could not find User associated with DataSift identity %s',
                $data['id']
            ));
        }

        $user->setUsername($data['label']);

        $this->userRepository->update($user);

        return $response;
    }

    /**
     * Proxies to `PUT account/identity/{id}/token/{service}` DS API endpoint.
     *
     * @param  Request $request Request.
     * @param  string $id Identity ID.
     *
     * @return JsonResponse
     */
    public function updateToken(Request $request, $id)
    {
        return $this->client->proxyResponse(function () use ($request, $id) {
            $this->identityTokenApi->update($id, $request->get('service'), $request->get('token'));
        });
    }

    /**
     * Proxies to `POST account/identity/{id}/token` DS API endpoint.
     *
     * @param  Request $request Request.
     * @param  string $id Identity ID.
     *
     * @return JsonResponse
     */
    public function createToken(Request $request, $id)
    {
        return $this->client->proxyResponse(function () use ($request, $id) {
            $this->identityTokenApi->create($id, $request->get('service'), $request->get('token'));
        });
    }

    /**
     * Gets a list of identity tokens
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $id
     *
     * @return JsonResponse
     */
    public function tokenList(Request $request, $id)
    {
        $page = $request->query->get('page', 1);
        $perPage = $request->query->get('per_page', 25);

        return $this->client->proxyResponse(function () use ($id, $page, $perPage) {
            $this->identityTokenApi->getAll(
                $id,
                $page,
                $perPage
            );
        });
    }

    /**
     * Gets the token for the identity and service
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $id
     * @param string $service
     *
     * @return JsonResponse
     */
    public function tokenService(Request $request, $id, $service)
    {
        return $this->client->proxyResponse(function () use ($id, $service) {
            $this->identityTokenApi->get(
                $id,
                $service
            );
        });
    }

    /**
     * Deletes an Identity
     * - NB This endpoint will work in future, but for now it is being added to
     *      properly emulate the DataSift API
     *
     * @see https://jiradatasift.atlassian.net/browse/NEV-425
     *
     * @param  Request $request Request.
     * @param  string $id Identity ID.
     *
     * @return JsonResponse
     */
    public function delete(Request $request, $id)
    {
        return new JsonResponse(
            'Deletion of Identities is currently unavailable',
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Gets a list of limits by service
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $service
     *
     * @return JsonResponse
     */
    public function limitList(Request $request, $service)
    {
        $page = $request->query->get('page', 1);
        $perPage = $request->query->get('per_page', 25);

        return $this->client->proxyResponse(function () use ($service, $page, $perPage) {
            $this->identityLimitApi->getAll(
                $service,
                $page,
                $perPage
            );
        });
    }

    /**
     * Gets a the limit for an Identity by service
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $id
     * @param string $service
     *
     * @return JsonResponse
     */
    public function limitService(Request $request, $id, $service)
    {
        return $this->client->proxyResponse(function () use ($id, $service) {
            $this->identityLimitApi->get(
                $id,
                $service
            );
        });
    }

    /**
     * Creates a new limit for the given Identity
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $id
     * @param string|null $service
     *
     * @return JsonResponse
     */
    public function limitCreate(Request $request, $id, $service = null)
    {
        if ($service === null) {
            $service = $request->get('service', null);
        }
        $totalAllowance = $request->get('total_allowance', null);

        return $this->client->proxyResponse(function () use ($id, $service, $totalAllowance) {
            $this->identityLimitApi->create(
                $id,
                $service,
                $totalAllowance
            );
        });
    }

    /**
     * Updates a service limit for the given Identity
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $id
     * @param string $service
     *
     * @return JsonResponse
     */
    public function limitUpdate(Request $request, $id, $service)
    {
        $totalAllowance = $request->get('total_allowance', null);

        return $this->client->proxyResponse(function () use ($id, $service, $totalAllowance) {
            $this->identityLimitApi->update(
                $id,
                $service,
                $totalAllowance
            );
        });
    }

    /**
     * Deletes a limit for the given Identity
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $id
     * @param string $service
     *
     * @return JsonResponse
     */
    public function limitRemove(Request $request, $id, $service)
    {
        return $this->client->proxyResponse(function () use ($id, $service) {
            $this->identityLimitApi->delete(
                $id,
                $service
            );
        });
    }

    /**
     * Deletes a token for the given Identity
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $id
     * @param string $service
     *
     * @return JsonResponse
     */
    public function tokenRemove(Request $request, $id, $service)
    {

        return $this->client->proxyResponse(function () use ($id, $service) {
            $this->identityTokenApi->delete(
                $id,
                $service
            );
        });
    }
}
