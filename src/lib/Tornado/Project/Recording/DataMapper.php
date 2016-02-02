<?php

namespace Tornado\Project\Recording;

use Doctrine\Common\Util\Debug;
use MD\Foundation\Utils\ObjectUtils;

use Tornado\DataMapper\DoctrineRepository;
use Tornado\Organization\Brand;
use Tornado\Project\Project;
use Tornado\Project\Workbook;
use Tornado\Project\Recording;

/**
 * DataMapper class for Tornado Project's Recording
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
     * Finds recordings by project.
     *
     * @param Project $project
     * @param array   $filter
     * @param array   $sortBy
     * @param integer $limit
     * @param integer $offset
     *
     * @return array
     */
    public function findByProject(Project $project, array $filter = [], array $sortBy = [], $limit = 0, $offset = 0)
    {
        $qb = $this->createQueryBuilder()
            ->select('*')
            ->from($this->tableName);

        $qb->where($qb->expr()->eq('project_id', ':project_id'));
        $qb->setParameter('project_id', $project->getId());

        // if not limited to project then also get from brand
        if (Project::RECORDING_FILTER_API != $project->getRecordingFilter()) {
            $qb->orWhere($qb->expr()->eq('brand_id', ':brand_id'));
            $qb->setParameter('brand_id', $project->getBrandId());
        }

        $this->addFilterToQueryBuilder($qb, $filter);
        $this->addRangeStatements($qb, $sortBy, $limit, $offset);

        // execute the query
        $results = $qb->execute();
        return $this->mapResults($results);
    }

    /**
     * Finds a list of Recordings for the given Project
     *
     * @param \Tornado\Organization\Brand $brand
     * @param array                    $filter
     * @param array                    $sortBy
     * @param integer                  $limit
     * @param integer                  $offset
     *
     * @return array|null
     */
    public function findByBrand(Brand $brand, array $filter = [], array $sortBy = [], $limit = 0, $offset = 0)
    {
        $filter['brand_id'] = $brand->getPrimaryKey();
        return $this->find($filter, $sortBy, (int)$limit, (int)$offset);
    }

    /**
     * Finds a Recording for the given workbook.
     *
     * @param  Workbook $workbook
     * @return Recording|null
     */
    public function findOneByWorkbook(Workbook $workbook)
    {
        return $this->findOne([
            'id' => $workbook->getRecordingId()
        ]);
    }

    /**
     * Finds Recordings by ids and Brand
     *
     * @param Brand $brand
     * @param array $ids
     *
     * @return int number of deleted items
     */
    public function findRecordingsByBrand(Brand $brand, array $ids = [])
    {
        $qb = $this->createQueryBuilder();
        $qb
            ->select('*')
            ->from($this->tableName)
            ->add('where', $qb->expr()->in('id', $ids))
            ->andWhere('brand_id = :brandId')
            ->setParameter('brandId', $brand->getId());

        return $this->mapResults(
            $qb->execute()
        );
    }

    /**
     * Removes Recordings by ids
     *
     * @param Recording[] $recordings
     *
     * @return int number of deleted items
     */
    public function deleteRecordings(array $recordings)
    {
        $ids = ObjectUtils::pluck($recordings, 'id');

        $qb = $this->createQueryBuilder();
        $qb
            ->delete($this->tableName)
            ->add('where', $qb->expr()->in('id', $ids));

        return $qb->execute();
    }

    /**
     * @param array $ids
     *
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function findByProjectIds(array $ids)
    {
        $qb = $this->createQueryBuilder();
        $qb
            ->select('*')
            ->from($this->tableName)
            ->add('where', $qb->expr()->in('project_id', $ids));

        return $this->mapResults(
            $qb->execute()
        );
    }

    /**
     * Imports a Recording
     *
     * @param \DataSift_Pylon $pylon
     * @param string $id
     *
     * @return \Tornado\Project\Recording|null
     */
    public function importRecording(\DataSift_Pylon $pylon, $id)
    {
        try {
            $subscription = $pylon->find($id);
        } catch (\DataSift_Exception_APIError $ex) {
            if ($ex->getCode() == 404) {
                return null;
            }
            throw new \RuntimeException(
                "There was an error retrieving the Recording $id; please try again shortly",
                500,
                $ex
            );
        }

        if ($subscription->getHash()) {
            $recording = new Recording();
            $recording->setDatasiftRecordingId($id);
            $recording->setHash($id);
            $recording->fromDataSiftRecording($subscription, true);
            return $recording;
        }

        return null;
    }

    /**
     * @param array $ids
     *
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function findByDataSiftRecordingIds(array $ids)
    {
        $qb = $this->createQueryBuilder();
        $qb->select('*')
           ->from($this->tableName)
           ->where('datasift_recording_id IN (:recIds)')
           ->setParameter('recIds', $ids, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);

        return $this->mapResults(
            $qb->execute()
        );
    }
}
