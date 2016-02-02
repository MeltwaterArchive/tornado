<?php

namespace Tornado\Project\Workbook;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\DoctrineRepository;
use Tornado\Project\Project;
use Tornado\Project\Worksheet;

/**
 * DataMapper class for Tornado Project's Worksheet
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Project
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class DataMapper extends DoctrineRepository
{

    /**
     * {@inheritdoc}
     */
    public function find(array $filter = [], array $sortBy = [], $limit = 0, $offset = 0)
    {
        if (empty($sortBy)) {
            $sortBy['rank'] = DataMapperInterface::ORDER_ASCENDING;
        }

        return parent::find($filter, $sortBy, $limit, $offset);
    }

    /**
     * Finds a list of workbooks for the given Project
     *
     * @param \Tornado\Project\Project $project
     * @param array                    $filter
     * @param array                    $sortBy
     * @param integer                  $limit
     * @param integer                  $offset
     *
     * @return array
     */
    public function findByProject(Project $project, array $filter = [], array $sortBy = [], $limit = 0, $offset = 0)
    {
        $filter['project_id'] = $project->getPrimaryKey();

        return $this->find($filter, $sortBy, (int)$limit, (int)$offset);
    }

    /**
     * Finds a workbook by ID assigned to the given project.
     *
     * @param  integer $workbookId
     * @param  Project $project
     * @return Workbook|null
     */
    public function findOneByProject($workbookId, Project $project)
    {
        return $this->findOne([
            'id' => $workbookId,
            'project_id' => $project->getPrimaryKey()
        ]);
    }

    /**
     * Finds a workbook by a worksheet.
     *
     * @param  Worksheet $worksheet Worksheet.
     * @return Workbook|null
     */
    public function findOneByWorksheet(Worksheet $worksheet)
    {
        return $this->findOne([
            'id' => $worksheet->getWorkbookId()
        ]);
    }
}
