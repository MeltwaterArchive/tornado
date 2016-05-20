<?php

namespace Test\Tornado\Project\Recording;

use Psr\Log\NullLogger;

use \Mockery;

use Tornado\Project\Recording;
use Tornado\Project\Workbook;
use Tornado\Project\Recording\DataSiftRecording;
use Tornado\Project\Recording\DataSiftRecordingException;

use Test\DataSift\ReflectionAccess;

/**
 * DataSiftRecordingTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category           Applications
 * @package            \Test\Tornado\Project\Recording
 * @copyright          2015-2016 MediaSift Ltd.
 * @license            http://mediasift.com/licenses/internal MediaSift Internal License
 * @link               https://github.com/datasift/tornado
 *
 * @coversDefaultClass Tornado\Project\Recording\DataSiftRecording
 */
class DataSiftRecordingTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess;

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @covers ::__construct
     * @covers ::start
     * @covers ::doStart
     */
    public function testStart()
    {
        $mocks = $this->getMocks();
        $mocks['pylon']->shouldReceive('compile')
            ->with($mocks['csdl'])
            ->once()
            ->andReturn(true);
        $mocks['pylon']->shouldReceive('start')
            ->with($mocks['hash'], $mocks['name'])
            ->once()
            ->andReturn(true);
        $mocks['pylon']->shouldReceive('getHash')
            ->withNoArgs()
            ->andReturn($mocks['hash']);
        $mocks['recordingRepository']->shouldReceive('findOne')
            ->once()
            ->with(['hash' => $mocks['hash'], 'brand_id' => $mocks['brandId']])
            ->andReturn(null);

        $dataSiftRecording = new DataSiftRecording($mocks['pylon'], $mocks['recordingRepository'], $mocks['logger']);
        $dataSiftRecording->setLogger(new NullLogger());
        $result = $dataSiftRecording->start($mocks['recording']);

        $this->assertInstanceOf('\Tornado\Project\Recording', $result);
        $this->assertEquals($mocks['hash'], $result->getHash());
    }

    /**
     * @covers ::__construct
     * @covers ::mapException
     */
    public function testMapException()
    {
        $mocks = $this->getMocks();
        $dataSiftRecording = new DataSiftRecording($mocks['pylon'], $mocks['recordingRepository'], $mocks['logger']);
        $dataSiftRecording->setLogger(new NullLogger());

        try {
            $exc = new \DataSift_Exception_AccessDenied();
            $this->invokeMethod($dataSiftRecording, 'mapException', [$exc]);
        } catch (DataSiftRecordingException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }

        try {
            $exc = new \DataSift_Exception_APIError();
            $this->invokeMethod($dataSiftRecording, 'mapException', [$exc]);
        } catch (DataSiftRecordingException $e) {
            $this->assertEquals(500, $e->getStatusCode());
        }

        try {
            $exc = new \DataSift_Exception_InvalidData();
            $this->invokeMethod($dataSiftRecording, 'mapException', [$exc]);
        } catch (DataSiftRecordingException $e) {
            $this->assertEquals(400, $e->getStatusCode());
        }

        try {
            $exc = new \DataSift_Exception_RateLimitExceeded();
            $this->invokeMethod($dataSiftRecording, 'mapException', [$exc]);
        } catch (DataSiftRecordingException $e) {
            $this->assertEquals(429, $e->getStatusCode());
        }

        try {
            $exc = new \InvalidArgumentException();
            $this->invokeMethod($dataSiftRecording, 'mapException', [$exc]);
        } catch (DataSiftRecordingException $e) {
            $this->assertEquals(500, $e->getStatusCode());
        }
    }

    /**
     * @covers ::__construct
     * @covers ::start
     * @covers ::doStart
     *
     * @expectedException \Tornado\Project\Recording\DataSiftRecordingException
     */
    public function testStartUnlessPylonCompileFail()
    {
        $mocks = $this->getMocks();
        $mocks['pylon']->shouldReceive('compile')
            ->with($mocks['csdl'])
            ->once()
            ->andThrow(new \DataSift_Exception_RateLimitExceeded());
        $mocks['pylon']->shouldReceive('start')
            ->never();
        $mocks['pylon']->shouldReceive('getHash')
            ->never();
        $mocks['recordingRepository']->shouldReceive('findOne')
            ->never();

        $dataSiftRecording = new DataSiftRecording($mocks['pylon'], $mocks['recordingRepository'], $mocks['logger']);
        $dataSiftRecording->setLogger(new NullLogger());

        $dataSiftRecording->start($mocks['recording']);
    }

    /**
     * @covers ::__construct
     * @covers ::start
     * @covers ::doStart
     *
     * @expectedException \Tornado\Project\Recording\DataSiftRecordingException
     */
    public function testStartUnlessRecordingExists()
    {
        $mocks = $this->getMocks();
        $mocks['pylon']->shouldReceive('compile')
            ->with($mocks['csdl'])
            ->once()
            ->andReturn(true);
        $mocks['pylon']->shouldReceive('start')
            ->never();
        $mocks['pylon']->shouldReceive('getHash')
            ->withNoArgs()
            ->andReturn($mocks['hash']);
        $mocks['recording']->setStatus(Recording::STATUS_STARTED);
        $mocks['recordingRepository']->shouldReceive('findOne')
            ->once()
            ->with(['hash' => $mocks['hash'], 'brand_id' => $mocks['brandId']])
            ->andReturn($mocks['recording']);

        $dataSiftRecording = new DataSiftRecording($mocks['pylon'], $mocks['recordingRepository'], $mocks['logger']);
        $dataSiftRecording->setLogger(new NullLogger());

        $dataSiftRecording->start($mocks['recording']);
    }

    /**
     * @covers ::__construct
     * @covers ::start
     * @covers ::doStart
     *
     * @expectedException \Tornado\Project\Recording\DataSiftRecordingException
     */
    public function testStartUnlessPylonStartFail()
    {
        $mocks = $this->getMocks();
        $mocks['pylon']->shouldReceive('compile')
            ->with($mocks['csdl'])
            ->once()
            ->andReturn(true);
        $mocks['pylon']->shouldReceive('start')
            ->with($mocks['hash'], $mocks['name'])
            ->andThrow(new \DataSift_Exception_RateLimitExceeded());
        $mocks['pylon']->shouldReceive('getHash')
            ->withNoArgs()
            ->andReturn($mocks['hash']);
        $mocks['recordingRepository']->shouldReceive('findOne')
            ->once()
            ->with(['hash' => $mocks['hash'], 'brand_id' => $mocks['brandId']])
            ->andReturn($mocks['recording']);

        $dataSiftRecording = new DataSiftRecording($mocks['pylon'], $mocks['recordingRepository'], $mocks['logger']);
        $dataSiftRecording->setLogger(new NullLogger());

        $dataSiftRecording->start($mocks['recording']);
    }

    /**
     * @covers ::__construct
     * @covers ::resume
     * @covers ::doStart
     */
    public function testResume()
    {
        $mocks = $this->getMocks();

        $mocks['recording']->setStatus(Recording::STATUS_STOPPED);
        $mocks['recording']->setHash($mocks['hash']);

        $mocks['pylon']->shouldReceive('start')
            ->with($mocks['hash'], $mocks['name'])
            ->once()
            ->andReturn(true);

        $dataSiftRecording = new DataSiftRecording($mocks['pylon'], $mocks['recordingRepository'], $mocks['logger']);
        $res = $dataSiftRecording->resume($mocks['recording']);

        $this->assertEquals(Recording::STATUS_STARTED, $res->getStatus());
    }

    /**
     * @covers ::__construct
     * @covers ::resume
     * @covers ::doStart
     *
     * @expectedException \Tornado\Project\Recording\DataSiftRecordingException
     */
    public function testResumeUnlessRecordingIsStopped()
    {
        $mocks = $this->getMocks();
        $mocks['recording']->setStatus(Recording::STATUS_STARTED);
        $mocks['pylon']->shouldReceive('start')
            ->never();

        $dataSiftRecording = new DataSiftRecording($mocks['pylon'], $mocks['recordingRepository'], $mocks['logger']);
        $dataSiftRecording->setLogger(new NullLogger());

        $dataSiftRecording->resume($mocks['recording']);
    }

    /**
     * @covers ::__construct
     * @covers ::resume
     * @covers ::doStart
     *
     * @expectedException \Tornado\Project\Recording\DataSiftRecordingException
     */
    public function testResumeUnlessPylonStartFail()
    {
        $mocks = $this->getMocks();

        $mocks['recording']->setStatus(Recording::STATUS_STOPPED);
        $mocks['recording']->setHash($mocks['hash']);

        $mocks['pylon']->shouldReceive('start')
            ->with($mocks['hash'], $mocks['name'])
            ->andThrow(new \DataSift_Exception_RateLimitExceeded());

        $dataSiftRecording = new DataSiftRecording($mocks['pylon'], $mocks['recordingRepository'], $mocks['logger']);
        $dataSiftRecording->setLogger(new NullLogger());

        $dataSiftRecording->resume($mocks['recording']);
    }

    /**
     * @covers ::__construct
     * @covers ::pause
     * @covers ::doStop
     */
    public function testPause()
    {
        $mocks = $this->getMocks();

        $mocks['recording']->setStatus(Recording::STATUS_STARTED);
        $mocks['recording']->setHash($mocks['hash']);

        $mocks['pylon']->shouldReceive('stop')
            ->with($mocks['hash'])
            ->once()
            ->andReturn(true);

        $dataSiftRecording = new DataSiftRecording($mocks['pylon'], $mocks['recordingRepository'], $mocks['logger']);
        $res = $dataSiftRecording->pause($mocks['recording']);

        $this->assertEquals(Recording::STATUS_STOPPED, $res->getStatus());
    }

    /**
     * @covers ::__construct
     * @covers ::pause
     * @covers ::doStop
     *
     * @expectedException \Tornado\Project\Recording\DataSiftRecordingException
     */
    public function testPauseUnlessRecordingIsStopped()
    {
        $mocks = $this->getMocks();
        $mocks['recording']->setStatus(Recording::STATUS_STOPPED);
        $mocks['pylon']->shouldReceive('stop')
            ->never();

        $dataSiftRecording = new DataSiftRecording($mocks['pylon'], $mocks['recordingRepository'], $mocks['logger']);
        $dataSiftRecording->setLogger(new NullLogger());

        $dataSiftRecording->pause($mocks['recording']);
    }

    /**
     * @covers ::__construct
     * @covers ::pause
     * @covers ::doStop
     *
     * @expectedException \Tornado\Project\Recording\DataSiftRecordingException
     */
    public function testPauseUnlessPylonStartFail()
    {
        $mocks = $this->getMocks();

        $mocks['recording']->setStatus(Recording::STATUS_STARTED);
        $mocks['recording']->setHash($mocks['hash']);

        $mocks['pylon']->shouldReceive('stop')
            ->with($mocks['hash'], $mocks['name'])
            ->andThrow(new \DataSift_Exception_APIError());

        $dataSiftRecording = new DataSiftRecording($mocks['pylon'], $mocks['recordingRepository'], $mocks['logger']);
        $dataSiftRecording->setLogger(new NullLogger());

        $dataSiftRecording->pause($mocks['recording']);
    }

    /**
     * @covers ::__construct
     * @covers ::delete
     * @covers ::doStop
     */
    public function testDelete()
    {
        $mocks = $this->getMocks();

        $mocks['recording']->setStatus(Recording::STATUS_STOPPED);
        $mocks['recording']->setHash($mocks['hash']);
        $mocks['recordingRepository']->shouldReceive('delete')
            ->once()
            ->with($mocks['recording'])
            ->andReturn(1);
        $mocks['pylon']->shouldReceive('stop')
            ->once()
            ->with($mocks['hash'])
            ->andReturn(true);

        $dataSiftRecording = new DataSiftRecording($mocks['pylon'], $mocks['recordingRepository'], $mocks['logger']);
        $res = $dataSiftRecording->delete($mocks['recording']);

        $this->assertEquals(1, $res);
    }

    /**
     * @covers ::__construct
     * @covers ::delete
     * @covers ::doStop
     */
    public function testDeleteEvenConflictExceptionThrown()
    {
        $mocks = $this->getMocks();

        $mocks['recording']->setStatus(Recording::STATUS_STARTED);
        $mocks['recording']->setHash($mocks['hash']);
        $mocks['recordingRepository']->shouldReceive('delete')
            ->once()
            ->with($mocks['recording'])
            ->andReturn(1);
        $mocks['pylon']->shouldReceive('stop')
            ->with($mocks['hash'])
            ->once()
            ->andThrow(new \DataSift_Exception_APIError('error', 409));

        $dataSiftRecording = new DataSiftRecording($mocks['pylon'], $mocks['recordingRepository'], $mocks['logger']);
        $res = $dataSiftRecording->delete($mocks['recording']);

        $this->assertEquals(1, $res);
    }

    /**
     * @covers ::__construct
     * @covers ::deleteRecordings
     */
    public function testDeleteRecordings()
    {
        $mocks = $this->getMocks();

        $recordings = [];
        for ($i = 1; $i < 5; $i++) {
            $rec = new Recording();
            $rec->setId($i);
            $rec->setStatus(Recording::STATUS_STARTED);
            $rec->setHash('hash' . $i);
            $recordings[] = $rec;

            $mocks['pylon']->shouldReceive('stop')
                ->with('hash' . $i)
                ->once()
                ->andReturn(true);
        }

        $mocks['recordingRepository']->shouldReceive('deleteRecordings')
            ->once()
            ->with($recordings)
            ->andReturn(4);

        $dataSiftRecording = new DataSiftRecording($mocks['pylon'], $mocks['recordingRepository'], $mocks['logger']);
        $res = $dataSiftRecording->deleteRecordings($recordings);

        $this->assertEquals(4, $res);
    }

    /**
     * DataProvider for testGetPylonRecordings
     *
     * @return array
     */
    public function getPylonRecordingsProvider()
    {
        $p1 = Mockery::Mock('DataSift_Pylon');
        $p1->shouldReceive('getHash')->andReturn('abc123');
        $p2 = Mockery::Mock('DataSift_Pylon');
        $p2->shouldReceive('getHash')->andReturn('def456');
        return [
            [ // #0
                'pylon' => $this->getRecordingsPylon([$p1, $p2], 1),
                'clearCache' => false,
                'expected' => [
                    'abc123' => $p1,
                    'def456' => $p2
                ]
            ],
            [ // #1
                'pylon' => $this->getRecordingsPylon([$p1, $p2], 2),
                'clearCache' => true,
                'expected' => [
                    'abc123' => $p1,
                    'def456' => $p2
                ]
            ],
            [ // #2
                'pylon' => $this->getRecordingsPylon([$p1, $p2], 2, '\DataSift_Exception_APIError'),
                'clearCache' => true,
                'expected' => [
                    'abc123' => $p1,
                    'def456' => $p2
                ],
                'expectedException' => '\Tornado\Project\Recording\DataSiftRecordingException'
            ],
        ];
    }

    /**
     * @dataProvider getPylonRecordingsProvider
     *
     * @covers ::getPylonRecordings
     *
     * @param \DataSift_Pylon $pylon
     * @param boolean $clearCache
     * @param array $expected
     * @param string|false $expectedException
     */
    public function testGetPylonRecordings(
        \DataSift_Pylon $pylon,
        $clearCache,
        array $expected,
        $expectedException = false
    ) {
        $logger = Mockery::mock('\Psr\Log\LoggerInterface');
        if ($expectedException) {
            $logger->shouldReceive('error');
            $this->setExpectedException($expectedException);
        }

        $obj = new DataSiftRecording($pylon, Mockery::mock('Tornado\Project\Recording\DataMapper'), $logger);

        $this->assertEquals($expected, $obj->getPylonRecordings($clearCache));
        $this->assertEquals($expected, $obj->getPylonRecordings($clearCache)); // Repeated twice for cache checking
    }

    private function getRecordingToDecorate($hash, \DataSift_Pylon $pylon = null, $id = 1, $volume = 0)
    {
        $recording = Mockery::mock(
            '\Tornado\Project\Recording',
            [],
            [
                'getDatasiftRecordingId' => $hash,
                'getId' => $id,
                'getVolume' => $volume
            ]
        );

        if ($pylon) {
            $recording->shouldReceive('fromDataSiftRecording')
                ->with($pylon);
        }

        return $recording;
    }

    /**
     * DataProvider for testDecorateRecordings
     *
     * @return array
     */
    public function decorateRecordingsProvider()
    {
        $p1 = new \DataSift_Pylon(null, ['hash' => 'abc123']);
        $p2 = new \DataSift_Pylon(null, ['hash' => 'def456']);

        return [
            [ // #0
                'pylonRecordings' => [
                    $p1->getHash() => $p1,
                    $p2->getHash() => $p2
                ],
                'recordings' => [
                    $this->getRecordingToDecorate($p1->getHash(), $p1, 1),
                    $this->getRecordingToDecorate($p2->getHash(), $p2, 2),
                    $this->getRecordingToDecorate('ghi789', null, 3),
                ]
            ]
        ];
    }

    /**
     * @dataProvider decorateRecordingsProvider
     *
     * @covers ::decorateRecordings
     *
     * @param array $pylonRecordings
     * @param array $recordings
     */
    public function testDecorateRecordings(array $pylonRecordings, array $recordings)
    {
        $pylon = Mockery::mock('\DataSift_Pylon');
        $pylon->shouldReceive('findAll')
            ->andReturn($pylonRecordings);

        $obj = new DataSiftRecording(
            $pylon,
            Mockery::mock('Tornado\Project\Recording\DataMapper'),
            Mockery::mock('\Psr\Log\LoggerInterface')
        );


        $obj->decorateRecordings($recordings);
    }

    /**
     *
     * @param type $hash
     *
     * @return \Tornado\Project\Workbook
     */
    private function getWorkbookToDecorate($recordingId, $expectedStatus)
    {
        $workbook = Mockery::mock(
            '\Tornado\Project\Workbook',
            [],
            [
                'getRecordingId' => $recordingId
            ]
        );

        $workbook->shouldReceive('setStatus')
            ->once()
            ->with($expectedStatus);

        return $workbook;
    }

    /**
     * DataProvider for testDecorateWorkbooks
     *
     * @return array
     */
    public function decorateWorkbooksProvider()
    {
        $p1 = new \DataSift_Pylon(null, ['hash' => 'abc123']);
        $p2 = new \DataSift_Pylon(null, ['hash' => 'def456']);

        return [
            [ // #0
                'pylonRecordings' => [
                    $p1->getHash() => $p1,
                    $p2->getHash() => $p2
                ],
                'recordings' => [
                    $this->getRecordingToDecorate($p1->getHash(), $p1, 1, 0),
                    $this->getRecordingToDecorate($p2->getHash(), $p2, 2, 10),
                    $this->getRecordingToDecorate('ghi789', null, 3, 0),
                ],
                'workbooks' => [
                    [1, Workbook::STATUS_ARCHIVED],
                    [2, Workbook::STATUS_ACTIVE],
                ]
            ]
        ];
    }

    /**
     * @dataProvider decorateWorkbooksProvider
     *
     * @covers ::decorateWorkbooks
     *
     * @param array $pylonRecordings
     * @param array $recordings
     * @param array $workbooks
     */
    public function testDecorateWorkbooks(array $pylonRecordings, array $recordings, array $workbooks)
    {
        $pylon = Mockery::mock('\DataSift_Pylon');
        $pylon->shouldReceive('findAll')
            ->andReturn($pylonRecordings);

        $obj = new DataSiftRecording(
            $pylon,
            Mockery::mock('Tornado\Project\Recording\DataMapper'),
            Mockery::mock('\Psr\Log\LoggerInterface')
        );

        $workbookList = [];
        foreach ($workbooks as $workbook) {
            $workbookList[] = $this->getWorkbookToDecorate($workbook[0], $workbook[1]);
        }

        $obj->decorateWorkbooks($workbookList, $recordings);
    }

    /**
     * @return array
     */
    protected function getMocks()
    {
        $pylon = Mockery::mock('\DataSift_Pylon');
        $brandId = 1;
        $name = 'test';
        $recordingRepository = Mockery::mock('Tornado\Project\Recording\DataMapper');
        $csdl = 'JCSDL_START 41fffbe21e24fb396cf174d991bc9ce8 fb.type,equals,12-4 1 fb.type == "like"';
        $recording = new Recording();
        $recording->setCsdl($csdl);
        $recording->setBrandId($brandId);
        $recording->setName($name);

        return [
            'pylon' => $pylon,
            'brandId' => $brandId,
            'name' => $name,
            'recordingRepository' => $recordingRepository,
            'csdl' => $csdl,
            'hash' => '6260ffab43850eae8ec90102f4a05b3a',
            'recording' => $recording,
            'logger' => Mockery::mock('\Psr\Log\LoggerInterface')
        ];
    }

    /**
     * Gets a DataSift_Pylon object for finding all recordings
     *
     * @param array $recordings
     * @param integer $expectedCalls
     * @param string $throwException
     *
     * @return \DataSift_Pylon
     */
    protected function getRecordingsPylon(array $recordings, $expectedCalls, $throwException = false)
    {

        /**
         *
         * The following use of Mockery had side-effects in BrandPermissions for
         * some reason
         *
        $pylon = Mockery::mock('\DataSift_Pylon');
        $pylon->shouldReceive('findAll')
            ->times($expectedCalls)
            ->andReturn($recordings);
         *
         */

        if ($throwException) {
            $pylon = $this->getMock('\DataSift_Pylon', array('findAll'), array(null, []));
            $pylon->expects($this->any())
                ->method('findAll')
                ->will($this->throwException(new $throwException));
            return $pylon;
        }

        $pylon = $this->getMock('\DataSift_Pylon', array('findAll'), array(null, []));
        $pylon->expects($this->exactly($expectedCalls))
              ->method('findAll')
              ->willReturn($recordings);

        return $pylon;
    }
}
