<?php

namespace Test\Controller\ProjectApp;

use Mockery;

use Controller\ProjectApp\DimensionController;

use Tornado\Analyze\Dimension;
use Tornado\Project\Recording;

/**
 * DimensionControllerTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Controller\ProjectApp
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass      \Controller\ProjectApp\DimensionController
 */
class DimensionControllerTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testIsProjectDataAware()
    {
        $controller = $this->getController($this->getMocks());
        $this->assertInstanceOf('Tornado\Controller\ProjectDataAwareInterface', $controller);
    }

    /**
     * @covers ::__construct
     * @covers ::index
     */
    public function testIndex()
    {
        $mocks = $this->getMocks();

        $permissions = ['internal'];

        $dimensions = [
            'fb.author.age' => ['target' => 'fb.author.age'],
            'fb.author.gender' => ['target' => 'fb.author.gender'],
            'fb.author.location' => ['target' => 'fb.author.location'],
            'fb.story.content' => ['target' => 'fb.story.content'],
            'interaction.content' => ['target' => 'interaction.content'],
            'time' => ['target' => 'interaction.content', 'is_time' => true] // this should never appear...
        ];

        $groups = [
            [
                'name' => 'Author',
                'items' => [
                    ['target' => 'fb.author.age'],
                    ['target' => 'fb.author.gender'],
                    ['target' => 'fb.author.location']
                ]
            ],
            [
                'name' => 'Story',
                'items' => [
                    ['target' => 'fb.story.content'],
                    ['target' => 'interaction.content']
                ]
            ],
            [
                'name' => 'Tags',
                'items' => [
                    ['target' => 'interaction.tag_tree.lorem.ipsum'],
                    ['target' => 'interaction.tag_tree.dolor.sit_amet']
                ]
            ]
        ];

        $mocks['brand'] = Mockery::mock('Tornado\Organization\Brand');
        $mocks['brand']->shouldReceive('getTargetPermissions')
            ->andReturn($permissions)
            ->once();
        $mocks['brandRepo']->shouldReceive('findOneByProject')
            ->with($mocks['project'])
            ->andReturn($mocks['brand'])
            ->once();

        $mocks['recording'] = Mockery::mock('Tornado\Project\Recording');
        $mocks['recordingRepo']->shouldReceive('findOneByWorkbook')
            ->with($mocks['workbook'])
            ->andReturn($mocks['recording'])
            ->once();

        $mocks['schema']->shouldReceive('getObjects')
            ->with(Mockery::any(), ['is_analysable' => true], $permissions)
            ->andReturn($dimensions)
            ->once();

        $dimensions2 = $dimensions;
        unset($dimensions2['time']); // See FTD-157 - time shouldn't appear in the list

        $mocks['schemaGrouper']->shouldReceive('groupObjects')
            ->with($dimensions2)
            ->andReturn($groups)
            ->once();

        $controller = $this->getController($mocks);

        $result = $controller->index($mocks['projectId'], $mocks['worksheetId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
        $this->assertEquals(200, $result->getHttpCode());

        $data = $result->getData();
        $this->assertEquals([
            'groups' => $groups
        ], $data);

        $meta = $result->getMeta();
        $this->assertArrayHasKey('dimensions_count', $meta);
        $this->assertArrayHasKey('groups_count', $meta);
        $this->assertEquals(5, $meta['dimensions_count']);
        $this->assertEquals(3, $meta['groups_count']);
    }

    /**
     * @return array
     */
    protected function getMocks()
    {
        $mocks = [];

        $mocks['projectId'] = 10;
        $mocks['workbookId'] = 11;
        $mocks['worksheetId'] = 15;

        $mocks['project'] = Mockery::mock('Tornado\Project\Project', [
            'getId' => $mocks['projectId'],
            'getPrimaryKey' => $mocks['projectId']
        ]);

        $mocks['workbook'] = Mockery::mock('Tornado\Project\Workbook', [
            'getId' => $mocks['workbookId'],
            'getPrimaryKey' => $mocks['workbookId'],
            'getProjectId' => $mocks['projectId']
        ]);

        $mocks['worksheet'] = Mockery::mock('Tornado\Project\Worksheet', [
            'getId' => $mocks['worksheetId'],
            'getPrimaryKey' => $mocks['worksheetId'],
            'getWorkbookId' => $mocks['workbookId']
        ]);

        $mocks['recordingRepo'] = Mockery::mock('Tornado\Project\Recording\DataMapper');
        $mocks['brandRepo'] = Mockery::mock('Tornado\Organization\Brand\DataMapper');
        $mocks['schema'] = Mockery::mock('DataSift\Pylon\Schema\Schema');
        $mocks['schemaProvider'] = Mockery::mock('DataSift\Pylon\Schema\Provider', [
            'getSchema' => $mocks['schema']
        ]);
        $mocks['schemaGrouper'] = Mockery::mock('DataSift\Pylon\Schema\Grouper');

        return $mocks;
    }

    /**
     * @return DimensionController
     */
    protected function getController(array $mocks)
    {
        $controller = Mockery::mock(DimensionController::class, [
            $mocks['recordingRepo'],
            $mocks['brandRepo'],
            $mocks['schemaProvider'],
            $mocks['schemaGrouper']
        ])->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // mock ProjectDataAwareTrait methods
        $controller->shouldReceive('getProject')
            ->with($mocks['projectId'])
            ->andReturn($mocks['project']);
        $controller->shouldReceive('getProjectDataForWorksheetId')
            ->with($mocks['worksheetId'], $mocks['projectId'])
            ->andReturn([$mocks['project'], $mocks['workbook'], $mocks['worksheet']]);

        return $controller;
    }
}
