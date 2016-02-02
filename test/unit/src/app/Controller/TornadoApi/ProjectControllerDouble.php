<?php

namespace Test\Controller\TornadoApi;

use Tornado\Organization\Brand;
use Tornado\Project\Project;

use Controller\TornadoApi\ProjectController;

/**
 * Quick and dirty double for the ProjectController test
 */
class ProjectControllerDouble extends ProjectController
{
    /**
     * {@inheritdoc}
     */
    public function saveRecordings(array $recordings, Project $project, $cleanDelta = false)
    {
        return parent::saveRecordings($recordings, $project, $cleanDelta);
    }

    /**
     * {@inheritdoc}
     */
    public function processRecordings(Brand $brand, array $recordingIds, Project $project = null)
    {
        return parent::processRecordings($brand, $recordingIds, $project);
    }

    /**
     * {@inheritdoc}
     */
    public function findOrImportRecording(Brand $brand, $id, Project $project = null)
    {
        return parent::findOrImportRecording($brand, $id, $project);
    }
}
