<?php

namespace Tornado\Controller\Brand;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\Organization\Brand;
use Tornado\Security\Authorization\AccessDecisionManagerInterface;

/**
 * Allows for setter injection of Brand data related services and provides
 * convenience methods to fetch brand-related data and auth checks.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Controller\Brand
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
trait DataAwareTrait
{
    /**
     * Brand Repository.
     *
     * @var DataMapperInterface
     */
    protected $brandRepository;

    /**
     * Authorization manager.
     *
     * @var AccessDecisionManagerInterface
     */
    protected $authorizationManager;

    /**
     * Retrieves Brand by ID and validates access.
     *
     * @param integer $brandId
     *
     * @return \Tornado\Organization\Brand
     *
     * @throws NotFoundHttpException When such Brand was not found.
     * @throws AccessDeniedHttpException if Session User cannot access the Brand
     */
    protected function getBrand($brandId)
    {
        $brand = $this->brandRepository->findOne(['id' => $brandId]);
        if (!$brand) {
            throw new NotFoundHttpException(sprintf('Could not find Brand with ID %s', $brandId));
        }

        if (!$this->authorizationManager->isGranted($brand)) {
            throw new AccessDeniedHttpException('You cannot access this Brand.');
        }

        return $brand;
    }

    /**
     * Sets the Brand repository.
     *
     * @param DataMapperInterface $brandRepository Brand repository.
     */
    public function setBrandRepository(DataMapperInterface $brandRepository)
    {
        $this->brandRepository = $brandRepository;
    }

    /**
     * Sets the authorization manager.
     *
     * @param AccessDecisionManagerInterface $authorizationManager Authorization manager.
     */
    public function setAuthorizationManager(AccessDecisionManagerInterface $authorizationManager)
    {
        $this->authorizationManager = $authorizationManager;
    }
}
