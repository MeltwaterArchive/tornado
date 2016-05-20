<?php

namespace Test\Tornado\Project\Worksheet;

use Mockery;

use Tornado\Analyze\Analysis\Group;
use Tornado\Project\Chart;
use Tornado\Project\Recording;
use Tornado\Project\Workbook;
use Tornado\Project\Worksheet;
use Tornado\Project\Worksheet\Generator as WorksheetGenerator;
use Tornado\Analyze\Analysis\Collection as AnalysisCollection;

/**
 * GeneratorTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Project
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers \Tornado\Project\Worksheet\Generator
 */
class GeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function provideTemplatesAndWorksheets()
    {
        return [
            'Empty Template' => [
                'template' => ['analyses' => []],
                'worksheetsData' => []
            ],
            'Single Worksheet' => [
                'template' => [
                    'analyses' => [
                        [
                            'title' => 'Basic Demographics',
                            'type' => 'tornado',
                            'analysis_type' => 'freqDist',
                            'dimensions' => [
                                ['target' => 'fb.author.gender'],
                                ['target' => 'fb.author.age']
                            ],
                            'span' => 1,
                            'interval' => 'day',
                            'start' => '1 week ago',
                            'end' => '1 day ago',
                            'filters' => ['csdl' => '']
                        ]
                    ]
                ],
                'worksheetsData' => [
                    [
                        'worksheet_id' => 0,
                        'chart_type' => 'tornado',
                        'dimensions' => [
                            ['target' => 'fb.author.gender'],
                            ['target' => 'fb.author.age']
                        ],
                        'type' => 'freqDist',
                        'span' => 1,
                        'interval' => 'day',
                        'start' => '1 week ago',
                        'end' => '1 day ago',
                        'filters' => ['csdl' => '']
                    ]
                ]
            ],
            'Multiple Worksheets' => [
                'template' => [
                    'analyses' => [
                        [
                            'title' => 'Basic Demographics',
                            'type' => 'tornado',
                            'analysis_type' => 'freqDist',
                            'dimensions' => [
                                ['target' => 'fb.author.gender'],
                                ['target' => 'fb.author.age']
                            ],
                            'span' => 1,
                            'interval' => 'day',
                            'start' => '1 week ago',
                            'end' => '1 day ago',
                            'filters' => ['csdl' => '']
                        ],
                        [
                            'title' => 'Countries',
                            'type' => 'histogram',
                            'analysis_type' => 'freqDist',
                            'dimensions' => [
                                ['target' => 'fb.author.country']
                            ],
                            'span' => 1,
                            'interval' => 'day',
                            'start' => '1 week ago',
                            'end' => 'yesterday',
                            'filters' => ['csdl' => '']
                        ],
                        [
                            'title' => 'Time Series',
                            'type' => 'timeseries',
                            'analysis_type' => 'timeSeries',
                            'dimensions' => [
                                ['target' => 'time']
                            ],
                            'span' => 1,
                            'interval' => 'day',
                            'start' => '2 weeks ago',
                            'end' => '1 week ago',
                            'filters' => ['csdl' => '']
                        ]
                    ]
                ],
                'worksheetsData' => [
                    [
                        'worksheet_id' => 0,
                        'chart_type' => 'tornado',
                        'type' => 'freqDist',
                        'dimensions' => [
                            ['target' => 'fb.author.gender'],
                            ['target' => 'fb.author.age']
                        ],
                        'span' => 1,
                        'interval' => 'day',
                        'start' => '1 week ago',
                        'end' => '1 day ago',
                        'filters' => ['csdl' => '']
                    ],
                    [
                        'worksheet_id' => 0,
                        'chart_type' => 'histogram',
                        'type' => 'freqDist',
                        'dimensions' => [
                            ['target' => 'fb.author.country']
                        ],
                        'span' => 1,
                        'interval' => 'day',
                        'start' => '1 week ago',
                        'end' => 'yesterday',
                        'filters' => ['csdl' => '']
                    ],
                    [
                        'worksheet_id' => 0,
                        'chart_type' => 'timeseries',
                        'type' => 'timeSeries',
                        'dimensions' => [
                            ['target' => 'time']
                        ],
                        'span' => 1,
                        'interval' => 'day',
                        'start' => '2 weeks ago',
                        'end' => '1 week ago',
                        'filters' => ['csdl' => '']
                    ]
                ]
            ],
            'Null Values in Start and End' => [
                'template' => [
                    'analyses' => [
                        [
                            'title' => 'Basic Demographics',
                            'type' => 'tornado',
                            'analysis_type' => 'freqDist',
                            'dimensions' => [
                                ['target' => 'fb.author.gender'],
                                ['target' => 'fb.author.age']
                            ],
                            'span' => 1,
                            'interval' => 'day',
                            'start' => null,
                            'end' => null,
                            'filters' => ['csdl' => '']
                        ],
                        [
                            'title' => 'Time Series',
                            'type' => 'timeseries',
                            'analysis_type' => 'timeSeries',
                            'dimensions' => [
                                ['target' => 'time']
                            ],
                            'span' => 1,
                            'interval' => 'day',
                            'start' => null,
                            'end' => null,
                            'filters' => ['csdl' => '']
                        ]
                    ]
                ],
                'worksheetsData' => [
                    [
                        'worksheet_id' => 0,
                        'chart_type' => 'tornado',
                        'type' => 'freqDist',
                        'dimensions' => [
                            ['target' => 'fb.author.gender'],
                            ['target' => 'fb.author.age']
                        ],
                        'span' => 1,
                        'interval' => 'day',
                        'filters' => ['csdl' => '']
                    ],
                    [
                        'worksheet_id' => 0,
                        'chart_type' => 'timeseries',
                        'type' => 'timeSeries',
                        'dimensions' => [
                            ['target' => 'time']
                        ],
                        'span' => 1,
                        'interval' => 'day',
                        'filters' => ['csdl' => '']
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider provideTemplatesAndWorksheets
     */
    public function testGenerateFromTemplate(array $template, array $worksheetsData)
    {
        $mocks = $this->getMocks();

        $mocks['group'] = new Group();

        $mocks['templatedAnalyzer']->shouldReceive('readTemplate')
            ->with($mocks['templateName'])
            ->andReturn($template)
            ->once();

        $mocks['templatedAnalyzer']->shouldReceive('performFromTemplate')
            ->with($mocks['recording'], $mocks['templateName'])
            ->andReturn($mocks['group'])
            ->once();

        $expectedWorksheets = [];
        foreach ($worksheetsData as $data) {
            $analysisCollection = Mockery::mock(AnalysisCollection::class);

            $mocks['group']->addAnalysisCollection($analysisCollection);

            $mocks['analyzeForm']->shouldReceive('submit')
                ->with($data, Mockery::type(Worksheet::class), $mocks['recording']);

            $worksheet = new Worksheet();
            $worksheet->setChartType($data['chart_type']);
            $worksheet->setDimensions($data['dimensions']);

            $mocks['datasetGenerator']->shouldReceive('fromAnalyses')
                ->with($analysisCollection, $worksheet->getDimensions())
                ->andReturn($mocks['dataset']);

            $mocks['chartFactory']->shouldReceive('fromDataSet')
                ->with(
                    $data['chart_type'],
                    $worksheet->getDimensions(),
                    $mocks['dataset'],
                    null,
                    $worksheet->getComparison()
                )
                ->andReturn($mocks['charts']);

            $mocks['worksheetRepository']->shouldReceive('create')
                ->with($worksheet)
                ->once();

            $expectedWorksheets[] = $worksheet;
        }

        $mocks['chartRepository']->shouldReceive('create')
            ->with($mocks['chart'])
            ->times(count($mocks['charts']) * count($worksheetsData));

        $mocks['analyzeForm']->shouldReceive('isValid')
            ->andReturn(true);

        $mocks['analyzeForm']->shouldReceive('getData')
            ->andReturnValues($expectedWorksheets);

        $generator = $this->getGenerator($mocks);

        $worksheets = $generator->generateFromTemplate($mocks['workbook'], $mocks['recording'], $mocks['templateName']);
        $this->assertEquals($expectedWorksheets, $worksheets);

        foreach ($worksheets as $i => $worksheet) {
            $this->assertEquals($mocks['workbookId'], $worksheet->getWorkbookId());
            $this->assertEquals($template['analyses'][$i]['title'], $worksheet->getName());
        }
    }

    public function getMocks()
    {
        $mocks = [];

        $mocks['templateName'] = 'default';

        $mocks['workbookId'] = 11;
        $mocks['recordingId'] = 20;

        $mocks['workbook'] = new Workbook();
        $mocks['workbook']->setId($mocks['workbookId']);

        $mocks['recording'] = new Recording();
        $mocks['recording']->setId($mocks['recordingId']);

        $mocks['dataset'] = Mockery::mock('Tornado\Analyze\DataSet');

        $mocks['chart'] =  new Chart();
        $mocks['charts'] = [$mocks['chart'], $mocks['chart'], $mocks['chart'], $mocks['chart']];

        $mocks['worksheetRepository'] = Mockery::mock('Tornado\Project\Worksheet\DataMapper');
        $mocks['chartRepository'] = Mockery::mock('Tornado\Project\Chart\DataMapper');
        $mocks['templatedAnalyzer'] = Mockery::mock('Tornado\Analyze\TemplatedAnalyzer');
        $mocks['analyzeForm'] = Mockery::mock('Tornado\Analyze\Analysis\Form\Create');
        $mocks['datasetGenerator'] = Mockery::mock('Tornado\Analyze\DataSet\Generator');
        $mocks['chartFactory'] = Mockery::mock('Tornado\Project\Chart\Factory');

        return $mocks;
    }

    public function getGenerator(array $mocks)
    {
        return new WorksheetGenerator(
            $mocks['worksheetRepository'],
            $mocks['chartRepository'],
            $mocks['templatedAnalyzer'],
            $mocks['analyzeForm'],
            $mocks['datasetGenerator'],
            $mocks['chartFactory']
        );
    }
}
