<?php

namespace Tornado\Controller\Brand;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\Project\Project;
use Tornado\Project\Worksheet;
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
interface DataAwareInterface
{
    /**
     * Sets the Brand repository.
     *
     * @param DataMapperInterface $projectRepository Brand repository.
     */
    public function setBrandRepository(DataMapperInterface $projectRepository);

    /**
     * Sets the authorization manager.
     *
     * @param AccessDecisionManagerInterface $authorizationManager Authorization manager.
     */
    public function setAuthorizationManager(AccessDecisionManagerInterface $authorizationManager);
}
