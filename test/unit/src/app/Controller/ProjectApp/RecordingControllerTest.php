<?php

namespace Test\Controller\ProjectApp;

use Mockery;

use Controller\ProjectApp\RecordingController;

/**
 * RecordingControllerTest
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
 * @coversDefaultClass \Controller\ProjectApp\RecordingController
 */
class RecordingControllerTest extends \PHPUnit_Framework_TestCase
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

        $mocks['recordings'] = [];
        for ($i = 1; $i < 5; $i++) {
            $mocks['recordings'][] = Mockery::mock('Tornado\DataMapper\DataObjectInterface');
        }

        $mocks['recordingRepo']->shouldReceive('findByProject')
            ->with($mocks['project'])
            ->andReturn($mocks['recordings'])
            ->once();

        $controller = $this->getController($mocks);

        $result = $controller->index($mocks['projectId']);

        $this->assertInstanceOf('Tornado\Controller\Result', $result);
        $this->assertInternalType('array', $result->getData());
        $this->assertEquals(200, $result->getHttpCode());

        $this->assertEquals($mocks['recordings'], $result->getData());
        $this->assertEquals(['count' => 4], $result->getMeta());
    }

    /**
     * @return array
     */
    protected function getMocks()
    {
        $mocks = [];

        $mocks['brandId'] = 1;
        $mocks['projectId'] = 10;

        $mocks['project'] = Mockery::mock('Tornado\Project\Project', [
            'getId' => $mocks['projectId'],
            'getPrimaryKey' => $mocks['projectId'],
            'getBrandId' => $mocks['brandId']
        ]);

        $mocks['recordingRepo'] = Mockery::mock('Tornado\Project\Recording\DataMapper');

        return $mocks;
    }

    /**
     * @return RecordingController
     */
    protected function getController(array $mocks)
    {
        $controller = Mockery::mock(RecordingController::class, [
            $mocks['recordingRepo']
        ])->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // mock ProjectDataAwareTrait methods
        $controller->shouldReceive('getProject')
            ->with($mocks['projectId'])
            ->andReturn($mocks['project']);

        return $controller;
    }
}
