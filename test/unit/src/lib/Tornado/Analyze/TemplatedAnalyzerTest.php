<?php

namespace Test\Tornado\Analyze;

use Mockery;

use DataSift\Loader\LoaderInterface;

use Tornado\Analyze\Analysis\Collection as AnalysisCollection;
use Tornado\Analyze\Analysis\Group;
use Tornado\Analyze\Analysis;
use Tornado\Analyze\Analyzer;
use Tornado\Analyze\Dimension\Collection as DimensionsCollection;
use Tornado\Analyze\Dimension\Factory as DimensionsFactory;
use Tornado\Analyze\Dimension;
use Tornado\Analyze\TemplatedAnalyzer;
use Tornado\Project\Chart;
use Tornado\Project\Recording;

/**
 * TemplatedAnalyzerTest
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
 * @covers \Tornado\Analyze\TemplatedAnalyzer
 */
class TemplatedAnalyzerTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function provideTemplates()
    {
        return [
            [ // #0
                'template' => [
                    'title' => 'Empty',
                    'analyses' => []
                ]
            ],
            [ // #1
                'template' => [
                    'title' => 'Tornado',
                    'analyses' => [
                        [
                            'title' => 'Basic Demographics',
                            'type' => 'tornado',
                            'dimensions' => [
                                ['target' => 'fb.author.gender'],
                                ['target' => 'fb.author.age']
                            ],
                            'start' => '1 week ago',
                            'end' => '1 day ago'
                        ]
                    ]
                ]
            ],
            [ // #2
                'template' => [
                    'title' => 'Multiple',
                    'analyses' => [
                        [
                            'title' => 'Countries',
                            'type' => 'histogram',
                            'dimensions' => [
                                ['target' => 'fb.author.country']
                            ]
                        ],
                        [
                            'title' => 'Time Series',
                            'type' => 'timeseries',
                            'start' => '2 weeks ago'
                        ]
                    ]
                ]
            ],
            [ // #3
                'template' => [
                    'title' => 'Everything',
                    'analyses' => [
                        [
                            'title' => 'Basic Demographics',
                            'type' => 'tornado',
                            'dimensions' => [
                                ['target' => 'fb.author.gender'],
                                ['target' => 'fb.author.age']
                            ],
                            'start' => '1 week ago',
                            'end' => '1 day ago'
                        ],
                        [
                            'title' => 'Countries',
                            'type' => 'histogram',
                            'dimensions' => [
                                ['target' => 'fb.author.country']
                            ]
                        ],
                        [
                            'title' => 'Time Series',
                            'type' => 'timeseries',
                            'start' => '2 weeks ago'
                        ]
                    ]
                ]
            ],
            'filter specified' => [
                'template' => [
                    'title' => 'Everything',
                    'analyses' => [
                        [
                            'title' => 'Basic Demographics',
                            'type' => 'tornado',
                            'dimensions' => [
                                ['target' => 'fb.author.gender'],
                                ['target' => 'fb.author.age']
                            ],
                            'start' => '1 week ago',
                            'end' => '1 day ago',
                            'filters' => [
                                'csdl' => 'interaction.content exists'
                            ]
                        ],
                        [
                            'title' => 'Countries',
                            'type' => 'histogram',
                            'dimensions' => [
                                ['target' => 'fb.author.country']
                            ]
                        ],
                        [
                            'title' => 'Time Series',
                            'type' => 'timeseries',
                            'start' => '2 weeks ago'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider provideTemplates
     */
    public function testPerformFromTemplate(array $template)
    {
        $mocks = $this->getMocks();

        $mocks['loader']->shouldReceive('load')
            ->once()
            ->andReturn(['/tmp/templates.json' => ['default' => $template]]);

        $analyses = [];

        // setup expectations for proper analyzer calls
        foreach ($template['analyses'] as $analysisTemplate) {
            $analysisType = $analysisTemplate['type'] === Chart::TYPE_TIME_SERIES
                ? Analysis::TYPE_TIME_SERIES
                : Analysis::TYPE_FREQUENCY_DISTRIBUTION;

            $dimensionsCollection = Mockery::mock(DimensionsCollection::class);
            $expectedDimensions = $analysisType === Analysis::TYPE_TIME_SERIES
                ? [['target' => Dimension::TIME]]
                : $analysisTemplate['dimensions'];
            $mocks['dimensionsFactory']->shouldReceive('getDimensionCollection')
                ->with($expectedDimensions, $mocks['recording'])
                ->andReturn($dimensionsCollection);

            $analysis = Mockery::mock(Analysis::class);
            $analyses[] = $analysis;

            $mocks['analyzer']->shouldReceive('buildAnalysis')
                ->with(
                    $mocks['recording'],
                    $dimensionsCollection,
                    $analysisType,
                    Mockery::on(function ($start) {
                        return $start === null || is_int($start);
                    }),
                    Mockery::on(function ($end) {
                        return $end === null || is_int($end);
                    }),
                    [
                        'span' => isset($analysisTemplate['span']) ? $analysisTemplate['span'] : 1,
                        'interval' => isset($analysisTemplate['interval'])
                            ? $analysisTemplate['interval']
                            : Analyzer::INTERVAL_DAY,
                    ],
                    (isset($analysisTemplate['filters'], $analysisTemplate['filters']['csdl']))
                    ? $analysisTemplate['filters']['csdl']
                    : null
                )
                ->andReturn($analysis);
        }

        $mocks['analyzer']->shouldReceive('analyzeCollection')
            ->with(Mockery::on(function ($collection) use ($analyses) {
                $this->assertInstanceOf(AnalysisCollection::class, $collection);
                $this->assertEquals($analyses, $collection->getAnalyses());
                return true;
            }))
            ->once();

        $templatedAnalyzer = $this->getTemplatedAnalyzer($mocks);

        $result = $templatedAnalyzer->performFromTemplate($mocks['recording'], 'default');
        $this->assertInstanceOf(Group::class, $result);
        $this->assertEquals($template['title'], $result->getTitle());

        $collections = $result->getAnalysisCollections();
        $this->assertCount(count($template['analyses']), $collections);

        foreach ($template['analyses'] as $i => $analysisTemplate) {
            $this->assertEquals($analysisTemplate['title'], $collections[$i]->getTitle());
        }
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNotFoundTemplate()
    {
        $mocks = $this->getMocks();
        $mocks['loader']->shouldReceive('load')
            ->once()
            ->andReturn([]);
        $templatedAnalyzer = $this->getTemplatedAnalyzer($mocks);

        $templatedAnalyzer->performFromTemplate($mocks['recording'], 'undefined');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testInvalidTemplate()
    {
        $mocks = $this->getMocks();
        $mocks['loader']->shouldReceive('load')
            ->once()
            ->andReturn(['/tmp/templates.json' => ['invalid' => []]]);
        $templatedAnalyzer = $this->getTemplatedAnalyzer($mocks);

        $templatedAnalyzer->performFromTemplate($mocks['recording'], 'invalid');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testInvalidAnalysisTemplate()
    {
        $mocks = $this->getMocks();
        $mocks['loader']->shouldReceive('load')
            ->once()
            ->andReturn(['/tmp/templates.json' => [
                'invalid' => [
                    'title' => 'Invalid',
                    'analyses' => [
                        [
                            'title' => 'Have title, has no type'
                        ]
                    ]
                ]
            ]]);
        $templatedAnalyzer = $this->getTemplatedAnalyzer($mocks);

        $templatedAnalyzer->performFromTemplate($mocks['recording'], 'invalid');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testInvalidAnalysisTypeTemplate()
    {
        $mocks = $this->getMocks();
        $mocks['loader']->shouldReceive('load')
            ->once()
            ->andReturn(['/tmp/templates.json' => [
                'invalid' => [
                    'title' => 'Invalid',
                    'analyses' => [
                        [
                            'title' => 'Has wrong type',
                            'type' => 'pie'
                        ]
                    ]
                ]
            ]]);
        $templatedAnalyzer = $this->getTemplatedAnalyzer($mocks);

        $templatedAnalyzer->performFromTemplate($mocks['recording'], 'invalid');
    }

    protected function getMocks()
    {
        $mocks = [];

        $mocks['recording'] = Mockery::mock(Recording::class);
        $mocks['loader'] = Mockery::mock(LoaderInterface::class);
        $mocks['dimensionsFactory'] = Mockery::mock(DimensionsFactory::class);
        $mocks['analyzer'] = Mockery::mock(Analyzer::class);

        return $mocks;
    }

    protected function getTemplatedAnalyzer(array $mocks)
    {
        return new TemplatedAnalyzer($mocks['loader'], $mocks['analyzer'], $mocks['dimensionsFactory']);
    }
}
