<?php

namespace Test\Tornado\Project\Recording\Sample;

use \Mockery;

/**
 * Sample\DataMapper Test
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Project\Recording
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Project\Recording\Sample\DataMapper
 */
class DataMapperTest extends \PHPUnit_Framework_TestCase
{

    /**
     * DataProvider for testRetrieve
     *
     * @return array
     */
    public function retrieveProvider()
    {
        return [
            'Happy path' => [
                'recordingId' => 10,
                'datasiftRecordingId' => 'abc123abc123abc123abc123abc123ab',
                'count' => 5,
                'response' => [
                    'remaining' => 123,
                    'reset_at' => 123456789,
                    'interactions' => [
                        ['a' => 'b'],
                        ['c' => 'd'],
                        ['e' => 'f'],
                        ['g' => 'h'],
                        ['i' => 'j'],
                    ]
                ],
                'expected' => [
                    'remaining' => 123,
                    'reset_at' => 123456789
                ]
            ]
        ];
    }

    /**
     * @dataProvider retrieveProvider
     * @covers ::retrieve
     *
     * @param integer $recordingId
     * @param string $datasiftRecordingId
     * @param integer $count
     * @param array $response
     * @param array $expected
     */
    public function testRetrieve($recordingId, $datasiftRecordingId, $count, array $response, array $expected)
    {
        $pylon = Mockery::mock('\DataSift\Pylon\Pylon');
        $pylon->shouldReceive('sample')
            ->with(false, false, false, $count, $datasiftRecordingId)
            ->andReturn($response);

        $mapper = Mockery::mock(
            '\Tornado\Project\Recording\Sample\DataMapper',
            [
                Mockery::mock('\Doctrine\DBAL\Connection'),
                '\Tornado\Project\Recording\Sample',
                'recording_sample',
                $pylon
            ]
        )
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

        $created = [];

        $mapper->shouldReceive('create')
            ->times(count($response['interactions']))
            ->andReturnUsing(function ($item) use (&$created) {
                $created[] = $item;
            });

        $recording = Mockery::mock(
            '\Tornado\Project\Recording',
            [
                'getDatasiftRecordingId' => $datasiftRecordingId,
                'getId' => $recordingId
            ]
        );

        $this->assertEquals($expected, $mapper->retrieve($recording, false, false, false, $count));
        $this->assertEquals(count($response['interactions']), count($created));
        foreach ($created as $sample) {
            $this->assertInstanceOf('\Tornado\Project\Recording\Sample', $sample);
            $this->assertEquals($recordingId, $sample->getRecordingId());
            $this->assertLessThanOrEqual(time(), $sample->getCreatedAt());
        }
    }
}
