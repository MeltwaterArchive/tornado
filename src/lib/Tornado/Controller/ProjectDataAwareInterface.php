<?php

namespace Tornado\Controller;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\Security\Authorization\AccessDecisionManagerInterface;

/**
 * Allows for setter injection of project data related services and provides
 * convenience methods to fetch project-related data like worksheets, including
 * auth checks.
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Controller
 * @author      Michał Pałys-Dudek
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
interface ProjectDataAwareInterface
{

    /**
     * Sets the project repository.
     *
     * @param DataMapperInterface $projectRepository Project repository.
     */
    public function setProjectRepository(DataMapperInterface $projectRepository);

    /**
     * Sets the workbook repository.
     *
     * @param DataMapperInterface $workbookRepository Workbook repository.
     */
    public function setWorkbookRepository(DataMapperInterface $workbookRepository);

    /**
     * Sets the worksheet repository.
     *
     * @param DataMapperInterface $worksheetRepository Worksheet repository.
     */
    public function setWorksheetRepository(DataMapperInterface $worksheetRepository);

    /**
     * Sets the authorization manager.
     *
     * @param AccessDecisionManagerInterface $authorizationManager Authorization manager.
     */
    public function setAuthorizationManager(AccessDecisionManagerInterface $authorizationManager);
}
