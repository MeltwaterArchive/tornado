<?php

namespace Tornado\Controller;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\Project\Project;
use Tornado\Project\Workbook;
use Tornado\Project\Worksheet;
use Tornado\Organization\Brand;
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
trait ProjectDataAwareTrait
{

    /**
     * Brand Repository.
     *
     * @var DataMapperInterface
     */
    protected $brandRepository;

    /**
     * Project Repository.
     *
     * @var DataMapperInterface
     */
    protected $projectRepository;

    /**
     * Workbook Repository.
     *
     * @var DataMapperInterface
     */
    protected $workbookRepository;

    /**
     * Worksheet Repository.
     *
     * @var DataMapperInterface
     */
    protected $worksheetRepository;

    /**
     * Authorization manager.
     *
     * @var AccessDecisionManagerInterface
     */
    protected $authorizationManager;

    /**
     * Retrieves project by ID and validates access.
     *
     * @param integer $projectId
     *
     * @return \Tornado\Project\Project
     *
     * @throws NotFoundHttpException When such project was not found.
     * @throws AccessDeniedHttpException if Session User cannot access the Project
     */
    protected function getProject($projectId)
    {
        $project = $this->projectRepository->findOne(['id' => $projectId]);
        if (!$project) {
            throw new NotFoundHttpException('This project could not be found.');
        }

        if (!$this->authorizationManager->isGranted($project)) {
            throw new AccessDeniedHttpException('You cannot access this Project.');
        }

        return $project;
    }

    /**
     * Retrieves a brand by ID and validates access.
     *
     * @param integer $brandId
     *
     * @return \Tornado\Organization\Brand
     *
     * @throws NotFoundHttpException When the referenced Brand was not found.
     */
    protected function getBrand($brandId)
    {
        $brand = $this->brandRepository->findOne(['id' => $brandId]);
        if (!$brand) {
            throw new NotFoundHttpException('This brand could not be found.');
        }

        return $brand;
    }

    /**
     * Retrieves a workbook for the project.
     *
     * @param  Project $project    Owning project.
     * @param  integer $workbookId ID of the workbook.
     *
     * @return \Tornado\Project\Workbook
     *
     * @throws NotFoundHttpException When such workbook was not found.
     */
    protected function getWorkbook(Project $project, $workbookId)
    {
        $workbook = $this->workbookRepository->findOneByProject($workbookId, $project);
        if (!$workbook) {
            throw new NotFoundHttpException(
                sprintf('Could not find workbook %s for project %s', $workbookId, $project->getId())
            );
        }

        return $workbook;
    }

    /**
     * Gets the worksheet's owning workbook.
     *
     * @param  Worksheet $worksheet  Worksheet to get the workbook for.
     *
     * @return Workbook
     *
     * @throws NotFoundHttpException When such workbook was not found.
     */
    protected function getWorkbookForWorksheet(Worksheet $worksheet)
    {
        $workbook = $this->workbookRepository->findOneByWorksheet($worksheet);
        if (!$workbook) {
            throw new NotFoundHttpException(sprintf(
                'Could not find workbook %s for worksheet %s',
                $worksheet->getWorkbookId(),
                $worksheet->getId()
            ));
        }

        return $workbook;
    }

    /**
     * Retrieves all project data based only on the worksheet ID.
     *
     * Returns an array where 0 => Project, 1 => Workbook, 2 = Worksheet.
     *
     * @param  integer $worksheetId Worksheet ID.
     * @param  integer $projectId   Optional project ID (e.g. coming from route's url for verification)
     *
     * @return array
     *
     * @throws NotFoundHttpException When the worksheet could not be found.
     * @throws ConflictHttpException When there is a mismatch between the passed project and workbook's project.
     */
    protected function getProjectDataForWorksheetId($worksheetId, $projectId = null)
    {
        $worksheet = $this->worksheetRepository->findOne(['id' => $worksheetId]);
        if (!$worksheet) {
            throw new NotFoundHttpException(
                sprintf('Could not find worksheet %s', $worksheetId)
            );
        }

        $workbook = $this->getWorkbookForWorksheet($worksheet);
        $project = $this->getProject($workbook->getProjectId());
        $brand = $this->getBrand($project->getBrandId());

        // double check if the project and workbook's project id match (if wrong was passed)
        if ($projectId && $project->getId() !== $projectId) {
            throw new ConflictHttpException('Mismatch between project and workbook');
        }

        return [$project, $workbook, $worksheet, $brand];
    }

    /**
     * Sets the brandrepository.
     *
     * @param DataMapperInterface $brandRepository Brand repository.
     */
    public function setBrandRepository(DataMapperInterface $brandRepository)
    {
        $this->brandRepository = $brandRepository;
    }

    /**
     * Sets the project repository.
     *
     * @param DataMapperInterface $projectRepository Project repository.
     */
    public function setProjectRepository(DataMapperInterface $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }

    /**
     * Sets the workbook repository.
     *
     * @param DataMapperInterface $workbookRepository Workbook repository.
     */
    public function setWorkbookRepository(DataMapperInterface $workbookRepository)
    {
        $this->workbookRepository = $workbookRepository;
    }

    /**
     * Sets the worksheet repository.
     *
     * @param DataMapperInterface $worksheetRepository Worksheet repository.
     */
    public function setWorksheetRepository(DataMapperInterface $worksheetRepository)
    {
        $this->worksheetRepository = $worksheetRepository;
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
