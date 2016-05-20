<?php

namespace Controller\ProjectApp;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use DataSift\Pylon\Schema\Grouper;
use DataSift\Pylon\Schema\Provider;
use DataSift\Pylon\Schema\Schema;
use DataSift\Pylon\Schema\TagsLoader;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\Controller\ProjectDataAwareInterface;
use Tornado\Controller\ProjectDataAwareTrait;
use Tornado\Controller\Result;

/**
 * Returns list of available dimensions for a project
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Controller\ProjectApp
 * @author      Daniel Waligora <danielwaligora@gmail.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class DimensionController implements ProjectDataAwareInterface
{
    use ProjectDataAwareTrait;

    /**
     * @var DataMapperInterface
     */
    protected $recordingRepository;

    /**
     * @var SchemaProvider
     */
    protected $schemaProvider;

    /**
     * @var Grouper
     */
    protected $schemaGrouper;

    /**
     * Constructor.
     *
     * @param \Tornado\DataMapper\DataMapperInterface   $recordingRepository
     * @param \Tornado\DataMapper\DataMapperInterface   $brandRepository
     * @param \DataSift\Pylon\Schema\Provider           $schemaProvider
     * @param \DataSift\Pylon\Schema\tagsLoader         $tagsLoader
     * @param \DataSift\Pylon\Schema\Grouper            $schemaGrouper
     */
    public function __construct(
        DataMapperInterface $recordingRepository,
        DataMapperInterface $brandRepository,
        Provider $schemaProvider,
        Grouper $schemaGrouper
    ) {
        $this->recordingRepository = $recordingRepository;
        $this->brandRepository = $brandRepository;
        $this->schemaProvider = $schemaProvider;
        $this->schemaGrouper = $schemaGrouper;
    }

    /**
     * Returns list of all available dimensions based on the loaded pylon schema
     *
     * @param  integer                      $projectId
     * @param  integer                      $worksheetId
     * @return \Tornado\Controller\Result
     */
    public function index($projectId, $worksheetId = null, $workbookId = null)
    {

        if ($workbookId) {
            $project = $this->getProject($projectId);
            $workbook = $this->getWorkbook($project, $workbookId);
        } else {
            list($project, $workbook) = $this->getProjectDataForWorksheetId($worksheetId, $projectId);
        }

        // find the brand (to get permission levels)
        $brand = $this->brandRepository->findOneByProject($project);

        // find the recording (to fetch tags)
        $recording = $this->recordingRepository->findOneByWorkbook($workbook);

        $schema = $this->schemaProvider->getSchema($recording);

        // get dimensions
        $dimensions = $schema->getObjects(
            [],
            ['is_analysable' => true],
            $brand->getTargetPermissions()
        );

        $dimensions = array_filter($dimensions, function ($item) {
            return !(isset($item['is_time']) && $item['is_time']);
        });

        // group the tags
        $groupedDimensions = $this->schemaGrouper->groupObjects($dimensions);

        return new Result([
            'groups' => $groupedDimensions
        ], [
            'dimensions_count' => count($dimensions),
            'groups_count' => count($groupedDimensions)
        ]);
    }
}
