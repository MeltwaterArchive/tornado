<?php

namespace Test\DataSift\Pylon\Analyze;

use Tornado\Analyze\Analysis;
use DataSift\Pylon\Analyze\Request;
use Mockery;
use Tornado\Analyze\Analysis\FrequencyDistribution;

/**
 * RequestTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\DataSift\Pylon
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass  DataSift\Pylon\Analyze\Request
 */
class RequestTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::__construct
     * @covers ::setAnalysis
     * @covers ::getAnalysis
     * @covers ::getUser
     */
    public function testConstruct()
    {
        $user = Mockery::mock('DataSift_User');
        $analysis = Mockery::mock('\Tornado\Analyze\Analysis');
        $request = new Request($user, $analysis);

        $this->assertEquals($user, $request->getUser());
        $this->assertEquals($analysis, $request->getAnalysis());
        $this->assertEquals(false, $request->hasError());

        $analysis2 = Mockery::mock('\Tornado\Analyze\Analysis');
        $this->assertEquals($analysis2, $request->getAnalysis());
    }

    /**
     * DataProvider for testGetBody
     *
     * @return array
     */
    public function getBodyProvider()
    {
        $recording = Mockery::mock('\Tornado\Project\Recording');
        $recording->shouldReceive('getDatasiftRecordingId')
            ->andReturn('hash12345');
        return [
            'Happy path' => [
                'analysis' => new FrequencyDistribution(
                    'test.target',
                    10,
                    20,
                    30,
                    $recording,
                    []
                ),
                'expected' => [
                    'hash' => 'hash12345',
                    'parameters' => [
                        'analysis_type' => 'freqDist',
                        'parameters' => [
                            'target' => 'test.target',
                            'threshold' => 10
                        ]
                    ],
                    'start' => 20,
                    'end' => 30
                ],
            ]
        ];
    }

    /**
     * @dataProvider getBodyProvider
     *
     * @covers ::getBody
     *
     * @param \Test\DataSift\Pylon\Analyze\Analysis $analysis
     * @param mixed $expected
     * @param string $expectedException
     */
    public function testGetBody(Analysis $analysis, $expected, $expectedException = '')
    {
        $user = Mockery::mock('DataSift_User');
        $request = new Request($user, $analysis);
        if ($expectedException) {
            $this->setExpectedException($expectedException);
        }

        $this->assertEquals($expected, $request->getBody());
    }

    /**
     * @covers ::hasError
     * @covers ::setError
     * @covers ::getError
     */
    public function testErrors()
    {
        $user = Mockery::mock('DataSift_User');
        $analysis = Mockery::mock('\Tornado\Analyze\Analysis');
        $request = new Request($user, $analysis);

        $this->assertEquals(false, $request->hasError());
        $this->assertEquals('', $request->getError());

        $errorStr = 'There was an error ' . microtime(true);
        $request->setError($errorStr);
        $this->assertTrue($request->hasError());
        $this->assertEquals($errorStr, $request->getError());
    }
}
