<?php

namespace Tornado\Security\Http\DataSiftApi;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

use DataSift\Http\Request;

use Tornado\Organization\Agency\DataMapper as AgencyRepository;
use Tornado\Organization\Brand\DataMapper as BrandRepository;

/**
 * AuthenticationManager
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Security\DataSiftApi
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class AuthenticationManager
{
    const TYPE_BRAND = 'brand';
    const TYPE_AGENCY = 'agency';
    const TYPE_BOTH = 'both';

    /**
     * @var AgencyRepository
     */
    protected $agencyRepository;

    /**
     * @var BrandRepository
     */
    protected $brandRepository;

    public function __construct(AgencyRepository $agencyRepository, BrandRepository $brandRepository)
    {
        $this->agencyRepository = $agencyRepository;
        $this->brandRepository = $brandRepository;
    }

    /**
     * Performs DataSift User authentication
     *
     * @param \DataSift\Http\Request $request
     * @param string                 $authType
     *
     * @return null|\Tornado\DataMapper\DataObjectInterface
     */
    public function auth(Request $request, $authType = self::TYPE_BRAND)
    {
        $credentials = $this->extractCredentials($request);
        if (!$credentials) {
            throw new UnauthorizedHttpException($request->headers->get('auth'));
        }

        if (in_array($authType, [self::TYPE_AGENCY, self::TYPE_BOTH])) {
            try {
                $ret =$this->authByAgency($request, $credentials);
            } catch (UnauthorizedHttpException $ex) {
                if ($authType !== self::TYPE_BOTH) {
                    throw $ex;
                }
            }
            return $ret;
        }

        return $this->authByBrand($request, $credentials);
    }

    /**
     * Authenticate DataSift User against agency
     *
     * @param \DataSift\Http\Request $request
     * @param array                  $credentials
     *
     * @return null|\Tornado\DataMapper\DataObjectInterface
     */
    protected function authByAgency(Request $request, array $credentials)
    {
        $agency = $this->agencyRepository->findOne([
            'datasift_username' => $credentials['username'],
            'datasift_apikey' => $credentials['api_key']
        ]);

        if (!$agency) {
            throw new UnauthorizedHttpException(
                $request->headers->get('auth'),
                'A valid DataSift main API key (and not that for an Identity) is required for this endpoint'
            );
        }

        $request->attributes->set('agency', $agency);
        $request->attributes->set('brand', null);
        return $agency;
    }

    /**
     * Authenticate DataSift User against agency & brand
     *
     * @param \DataSift\Http\Request $request
     * @param array                  $credentials
     *
     * @return null|\Tornado\DataMapper\DataObjectInterface
     */
    protected function authByBrand(Request $request, array $credentials)
    {
        $agency = $this->agencyRepository->findOne(['datasift_username' => $credentials['username']]);
        if (!$agency) {
            throw new UnauthorizedHttpException($request->headers->get('auth'));
        }
        $request->attributes->set('agency', $agency);

        $brand = $this->brandRepository->findOne([
            'agency_id' => $agency->getId(),
            'datasift_apikey' => $credentials['api_key']
        ]);

        if (!$brand) {
            throw new UnauthorizedHttpException(
                $request->headers->get('auth'),
                'A valid Identity API key is required for this endpoint'
            );
        }
        $request->attributes->set('brand', $brand);

        return $brand;
    }

    /**
     * Extracts authentication from the Request.
     *
     * @param \DataSift\Http\Request $request
     *
     * @return array|null
     */
    protected function extractCredentials(Request $request)
    {
        $authData = $request->headers->get(
            'authorization',
            $request->headers->get(
                'auth',
                $request->headers->get('authorisation')
            )
        );

        if (!$authData) {
            $username = $request->query->get('username', false);
            $apiKey = $request->query->get('api_key', false);
            if ($username && $apiKey) {
                return [
                    'username' => $username,
                    'api_key' => $apiKey
                ];
            }
            return null;
        }

        if (preg_match('/^Basic (.*)/', $authData, $matches)) {
            $authData = base64_decode($matches[1]);
        }

        $authData = explode(':', $authData);
        if (!(count($authData) > 1)) {
            return null;
        }

        $data['username'] = $authData[0];
        $data['api_key'] = $authData[1];

        return $data;
    }
}
