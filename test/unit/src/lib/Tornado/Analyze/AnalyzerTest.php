<?php

namespace Test\Tornado\Analyze;

use Mockery;

use MD\Foundation\Utils\ArrayUtils;

use Tornado\Analyze\Analysis\Collection as AnalysisCollection;
use Tornado\Analyze\Analysis;
use Tornado\Analyze\Analyzer;
use Tornado\Analyze\Dimension;
use Tornado\Analyze\Dimension\Collection as DimensionsCollection;

use Test\DataSift\FixtureLoader;

/**
 * AnalyzerTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Analyze
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers \Tornado\Analyze\Analyzer
 */
class AnalyzerTest extends \PHPUnit_Framework_TestCase
{

    use FixtureLoader;

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @dataProvider providePerformArguments
     */
    public function testPerform(array $dimensions, array $arguments, array $expectedAnalyses)
    {
        $recording = Mockery::mock('\Tornado\Project\Recording', [
            'getCreatedAt' => 0
        ]);

        $dimensionsCollection = new DimensionsCollection();
        foreach ($dimensions as $i => $dimension) {
            $dimension = new Dimension(
                $dimension['target'],
                99 - $i,
                null,
                isset($dimension['threshold']) ? $dimension['threshold'] : null
            );
            $dimensionsCollection->addDimension($dimension);
        }

        $pylon = Mockery::mock('\DataSift\Pylon\Pylon');
        $pylon->shouldReceive('analyzeMulti')
            ->with(Mockery::type('\Tornado\Analyze\Analysis\Collection'))
            ->once();
        $stats = Mockery::mock('\DataSift\Stats\Collector', ['startTimer' => '', 'endTimer' => '']);

        $analyzer = new Analyzer($pylon, $stats);

        $analyses = $analyzer->perform(
            $recording,
            $dimensionsCollection,
            $arguments['analysis_type'],
            $arguments['start'],
            $arguments['end'],
            [
                'interval' => $arguments['interval'],
                'span' => $arguments['span']
            ]
        );

        $this->assertInstanceOf('\Tornado\Analyze\Analysis\Collection', $analyses);

        foreach ($analyses->getAnalyses() as $analysis) {
            $current = $analysis;

            for ($i = 0; $i < count($dimensions); $i++) {
                $currentExpected = $expectedAnalyses[$i];
                $this->assertInstanceOf('\Tornado\Analyze\Analysis', $current);
                $this->assertInstanceOf($currentExpected['class'], $current);

                $this->assertSame(
                    $recording,
                    $current->getRecording(),
                    'The returned analyses do not have a proper recording set.'
                );

                foreach ($currentExpected['getters'] as $getter => $value) {
                    $this->assertEquals($value, $current->{$getter}());
                }

                // test if the analyses have been correctly nested (depending on the number of dimensions)
                $child = $current->getChild();

                // if this last dimension analysis / deepest dimension analysis
                // there shouldn't be any child
                if ($i + 1 === count($dimensions)) {
                    $this->assertNull($child, 'The returned analyses are not properly nested (too deep).');
                } else {
                    $this->assertNotNull($child, 'The returned analyses are not properly nested.');
                }

                $current = $child;
            }
        }
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPerformWithInvalidType()
    {
        $recording = Mockery::mock('\Tornado\Project\Recording');

        $dimensionsCollection = new DimensionsCollection();
        foreach (['fb.author.age', 'fb.author.location'] as $target) {
            $dimension = new Dimension($target, 8);
            $dimensionsCollection->addDimension($dimension);
        }

        $pylon = Mockery::mock('\DataSift\Pylon\Pylon');
        $stats = Mockery::mock('\DataSift\Stats\Collector', ['startTimer' => '', 'endTimer' => '']);
        $analyzer = new Analyzer($pylon, $stats);

        $analyzer->perform($recording, $dimensionsCollection, 'correlation');
    }

    public function testAnalyze()
    {
        $recording = Mockery::mock('\Tornado\Project\Recording', [
            'getDatasiftRecordingId' => 'dfgdgdfgdg24tdfg'
        ]);

        $analysis = Mockery::mock('\Tornado\Analyze\Analysis\FrequencyDistribution', [
            'getType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
            'getThreshold' =>  5,
            'getTarget' => 'fb.author.age',
            'getRecording' => $recording,
            'getChild' => null,
            'getStart' => 1231231230,
            'getEnd' => 1234567890,
            'getFilter' => 'interaction.content exists'
        ]);

        $pylon = Mockery::mock('\DataSift\Pylon\Pylon');
        $pylon->shouldReceive('analyzeMulti')
            ->with(Mockery::on(function ($collection) use ($analysis) {
                if (!$collection instanceof AnalysisCollection) {
                    return false;
                }

                // make sure the passed analysis is in there
                $analyses = $collection->getAnalyses();
                $this->assertSame($analysis, current($analyses));

                return true;
            }))
            ->once();

        $stats = Mockery::mock('\DataSift\Stats\Collector', ['startTimer' => '', 'endTimer' => '']);
        $analyzer = new Analyzer($pylon, $stats);
        $result = $analyzer->analyze($analysis);

        $this->assertSame($result, $analysis);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAnalyzeWithNoRecording()
    {
        $analysis = Mockery::mock('\Tornado\Analyze\Analysis\FrequencyDistribution', [
            'getRecording' => null
        ]);
        $stats = Mockery::mock('\DataSift\Stats\Collector', ['startTimer' => '', 'endTimer' => '']);
        $analyzer = new Analyzer(Mockery::mock('\DataSift_Pylon'), $stats);
        $analyzer->analyze($analysis);
    }

    /**
     * Data provider for testPerform
     *
     * @return array
     */
    public function providePerformArguments()
    {

        $start = time();
        $end = time();

        return [
            [
                'dimensions' => [
                    ['target' => 'fb.author.age', 'threshold' => 5],
                    ['target' => 'fb.author.gender', 'threshold' => 10],
                    ['target' => 'fb.author.location', 'threshold' => 7],
                ],
                'arguments' => [
                    'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'start' => 1231231230,
                    'end' => 1234567890,
                    'interval' => null,
                    'span' => null
                ],
                'expectedAnalyses' => [
                    [ // top level
                        'class' => '\Tornado\Analyze\Analysis\FrequencyDistribution',
                        'getters' => [
                            'getType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                            'getTarget' => 'fb.author.age',
                            'getStart' => 1231231230,
                            'getEnd' => 1234567890,
                            'getThreshold' => 5
                        ]
                    ],
                    [ // 2nd level
                        'class' => '\Tornado\Analyze\Analysis\FrequencyDistribution',
                        'getters' => [
                            'getType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                            'getTarget' => 'fb.author.gender',
                            'getStart' => 1231231230,
                            'getEnd' => 1234567890,
                            'getThreshold' => 10
                        ]
                    ],
                    [ // 3rd level
                        'class' => '\Tornado\Analyze\Analysis\FrequencyDistribution',
                        'getters' => [
                            'getType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                            'getTarget' => 'fb.author.location',
                            'getStart' => 1231231230,
                            'getEnd' => 1234567890,
                            'getThreshold' => 7
                        ]
                    ]
                ]
            ],
            [
                'dimensions' => [
                    ['target' => 'fb.author.age', 'threshold' => 5],
                    ['target' => 'fb.author.gender']
                ],
                'arguments' => [
                    'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'start' => 1231231230,
                    'end' => 1234567890,
                    'interval' => null,
                    'span' => null
                ],
                'expectedAnalyses' => [
                    [ // top level
                        'class' => '\Tornado\Analyze\Analysis\FrequencyDistribution',
                        'getters' => [
                            'getType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                            'getTarget' => 'fb.author.age',
                            'getStart' => 1231231230,
                            'getEnd' => 1234567890,
                            'getThreshold' => 5
                        ]
                    ],
                    [ // 2nd level
                        'class' => '\Tornado\Analyze\Analysis\FrequencyDistribution',
                        'getters' => [
                            'getType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                            'getTarget' => 'fb.author.gender',
                            'getStart' => 1231231230,
                            'getEnd' => 1234567890,
                            'getThreshold' => 100
                        ]
                    ]
                ]
            ],
            [
                'dimensions' => [
                    ['target' => 'fb.author.age']
                ],
                'arguments' => [
                    'analysis_type' => Analysis::TYPE_TIME_SERIES,
                    'start' => $start,
                    'end' => $end,
                    'threshold' => null,
                    'interval' => 'minute',
                    'span' => 5
                ],
                'expectedAnalyses' => [
                    [ // top level
                        'class' => '\Tornado\Analyze\Analysis\TimeSeries',
                        'getters' => [
                            'getType' => Analysis::TYPE_TIME_SERIES,
                            'getTarget' => 'fb.author.age',
                            'getStart' => $start,
                            'getEnd' => $end,
                            'getInterval' => 'minute',
                            'getSpan' => 5
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @covers \Tornado\Analyze\Analyzer::fromStoredDataSet
     */
    public function testFromStoredDataSet()
    {
        $analyzer = Mockery::mock(
            '\Tornado\Analyze\Analyzer[buildAnalysis,analyzeCollection]',
            [],
            [Mockery::mock('\DataSift_Pylon'), Mockery::mock('\DataSift\Stats\Collector')]
        );

        $analysis = Mockery::mock('\Tornado\Analyze\Analysis');
        $recording = Mockery::mock('\Tornado\Project\Recording');

        $dimensions = Mockery::mock('\Tornado\Analyze\Dimension\Collection');
        $analysisType = Analysis::TYPE_FREQUENCY_DISTRIBUTION;
        $start = time();
        $end = $start + 10;
        $filter = 'test filter';

        $dataset = Mockery::mock(
            '\Tornado\Analyze\DataSet\StoredDataSet',
            [
                'getDimensions' => $dimensions,
                'getAnalysisType' => $analysisType,
                'getStart' => $start,
                'getEnd' => $end,
                'getFilter' => $filter
            ]
        );

        $analyzer->shouldReceive('buildAnalysis')
            ->once()
            ->with(
                $recording,
                $dimensions,
                $analysisType,
                $start,
                $end,
                [],
                $filter
            )
            ->andReturn($analysis);

        $analyzer->shouldReceive('analyzeCollection')
            ->once();

        $result = $analyzer->fromStoredDataSet($dataset, $recording);

        $this->assertInstanceOf('\Tornado\Analyze\Analysis\Collection', $result);
        $this->assertEquals(1, count($result->getAnalyses()));
    }
}
