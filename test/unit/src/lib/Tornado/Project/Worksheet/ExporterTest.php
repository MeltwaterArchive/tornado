<?php

namespace Test\Tornado\Project\Worksheet;

use Mockery;

use Tornado\Project\Chart;
use Tornado\Project\Chart\DataMapper as ChartRepository;
use Tornado\Project\Chart\Generator as ChartGenerator;
use Tornado\Project\Worksheet\Exporter;
use Tornado\Project\Worksheet;

use Test\DataSift\FixtureLoader;

/**
 * ExporterTest
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
 * @covers      \Tornado\Project\Worksheet\Exporter
 */
class ExporterTest extends \PHPUnit_Framework_TestCase
{
    use FixtureLoader;

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @dataProvider provideWorksheetData
     *
     * @param  array  $targets
     * @param  bool|string $comparison
     * @param  array  $chartsData
     * @param  array  $expectedHeaders
     * @param  array  $expectedRows
     */
    public function testExportWorksheet(
        array $targets,
        $comparison,
        array $chartsData,
        array $expectedHeaders,
        array $expectedRows
    ) {
        // prepare worksheet
        $worksheet = new Worksheet();
        $worksheet->setDimensions($targets);
        if ($comparison) {
            $worksheet->setComparison($comparison);
            $worksheet->setSecondaryRecordingId(23); // doesn't matter what ID
        }

        // prepare charts
        $charts = [];

        foreach ($chartsData as $chartInfo) {
            $chart = new Chart();
            $chart->setType($chartInfo['type']);
            $chart->setRawData(json_encode($chartInfo['data']));
            $charts[] = $chart;
        }

        $chartRepository = Mockery::mock(ChartRepository::class);
        $chartRepository->shouldReceive('findByWorksheet')
            ->with($worksheet)
            ->andReturn($charts)
            ->once();

        $exporter = new Exporter($chartRepository);

        $expected = array_merge([$expectedHeaders], $expectedRows);
        $this->assertEquals($expected, $exporter->exportWorksheet($worksheet));
    }

    public function provideWorksheetData()
    {
        return [
            [ // #0 - empty
                'targets' => [],
                'comparison' => false,
                'chartsData' => [],
                'expectedHeaders' => ['interactions', 'unique_authors'],
                'expectedRows' => []
            ],
            [ // #1 - tornado 2d
                'targets' => [['target' => 'fb.author.age'], ['target' => 'fb.author.gender']],
                'comparison' => false,
                'chartsData' => $this->loadJSONFixture('tornado_2d', null, true),
                'expectedHeaders' => [
                    'fb.author.age',
                    'fb.author.gender',
                    'interactions',
                    'unique_authors'
                ],
                'expectedRows' => [
                    ['18-24', 'male', 5100, 3000],
                    ['18-24', 'unknown', 4100, 3100],
                    ['unknown', 'male', 31100, 19500],
                    ['unknown', 'unknown', 21100, 20500]
                ]
            ],
            [ // #2 - tornado 2d baseline
                'targets' => [['target' => 'fb.author.age'], ['target' => 'fb.author.gender']],
                'comparison' => ChartGenerator::MODE_BASELINE,
                'chartsData' => $this->loadJSONFixture('tornado_2d_baseline', null, true),
                'expectedHeaders' => [
                    'fb.author.age',
                    'fb.author.gender',
                    'interactions',
                    'unique_authors',
                    'interactions_baseline',
                    'unique_authors_baseline'
                ],
                'expectedRows' => [
                    ['18-24', 'male', 5100, 3000, 2000, 4000],
                    ['18-24', 'unknown', 4100, 3100, 1500, 5000],
                    ['unknown', 'male', 31100, 19500, 25000, 20000],
                    ['unknown', 'unknown', 21100, 20500, 35000, 25600]
                ]
            ],
            [ // #3 - tornado 2d compare
                'targets' => [['target' => 'fb.author.age'], ['target' => 'fb.author.gender']],
                'comparison' => ChartGenerator::MODE_COMPARE,
                'chartsData' => $this->loadJSONFixture('tornado_2d_compare', null, true),
                'expectedHeaders' => [
                    'fb.author.age',
                    'fb.author.gender',
                    'interactions',
                    'unique_authors',
                    'interactions_compare',
                    'unique_authors_compare'
                ],
                'expectedRows' => [
                    ['18-24', 'male', 5100, 3000, 2000, 4000],
                    ['18-24', 'unknown', 4100, 3100, 1500, 5000],
                    ['unknown', 'male', 31100, 19500, 25000, 20000],
                    ['unknown', 'unknown', 21100, 20500, 35000, 25600]
                ]
            ],
            [ // #4 - tornado 3d
                'targets' => [
                    ['target' => 'fb.author.gender'], ['target' => 'fb.author.age'], ['target' => 'fb.region']
                ],
                'comparison' => false,
                'chartsData' => $this->loadJSONFixture('tornado_3d', null, true),
                'expectedHeaders' => [
                    'fb.author.gender',
                    'fb.author.age',
                    'fb.region',
                    'interactions',
                    'unique_authors'
                ],
                'expectedRows' => [
                    ['male', '18-24', 'alabama', 0, 62100],
                    ['male', 'unknown', 'alabama', 31100, 32100],
                    ['unknown', '18-24', 'alabama', 5100, 5200],
                    ['unknown', 'unknown', 'alabama', 6100, 6200],
                    ['male', '18-24', 'alaska', 0, 62100],
                    ['male', 'unknown', 'alaska', 31100, 32100],
                    ['unknown', '18-24', 'alaska', 5100, 5200],
                    ['unknown', 'unknown', 'alaska', 6100, 6200],
                    ['male', '18-24', 'detroit', 0, 62100],
                    ['male', 'unknown', 'detroit', 31100, 32100],
                    ['unknown', '18-24', 'detroit', 5100, 5200],
                    ['unknown', 'unknown', 'detroit', 6100, 6200],
                ]
            ],
            [ // #5 - tornado 3d baseline
                'targets' => [
                    ['target' => 'fb.author.gender'], ['target' => 'fb.author.age'], ['target' => 'fb.region']
                ],
                'comparison' => ChartGenerator::MODE_BASELINE,
                'chartsData' => $this->loadJSONFixture('tornado_3d_baseline', null, true),
                'expectedHeaders' => [
                    'fb.author.gender',
                    'fb.author.age',
                    'fb.region',
                    'interactions',
                    'unique_authors',
                    'interactions_baseline',
                    'unique_authors_baseline'
                ],
                'expectedRows' => [
                    ['male', '18-24', 'alabama', 0, 62100, 151100, 151200],
                    ['male', 'unknown', 'alabama', 31100, 32100, 11100, 11200],
                    ['unknown', '18-24', 'alabama', 5100, 5200, 15100, 15200],
                    ['unknown', 'unknown', 'alabama', 6100, 6200, 4100, 4200],
                    ['male', '18-24', 'alaska', 0, 62100, 151100, 151200],
                    ['male', 'unknown', 'alaska', 31100, 32100, 11100, 11200],
                    ['unknown', '18-24', 'alaska', 5100, 5200, 15100, 15200],
                    ['unknown', 'unknown', 'alaska', 6100, 6200, 4100, 4200],
                    ['male', '18-24', 'detroit', 0, 62100, 151100, 151200],
                    ['male', 'unknown', 'detroit', 31100, 32100, 11100, 11200],
                    ['unknown', '18-24', 'detroit', 5100, 5200, 15100, 15200],
                    ['unknown', 'unknown', 'detroit', 6100, 6200, 4100, 4200]
                ]
            ],
            [ // #6 - tornado 3d compare
                'targets' => [
                    ['target' => 'fb.author.gender'], ['target' => 'fb.author.age'], ['target' => 'fb.region']
                ],
                'comparison' => ChartGenerator::MODE_COMPARE,
                'chartsData' => $this->loadJSONFixture('tornado_3d_compare', null, true),
                'expectedHeaders' => [
                    'fb.author.gender',
                    'fb.author.age',
                    'fb.region',
                    'interactions',
                    'unique_authors',
                    'interactions_compare',
                    'unique_authors_compare'
                ],
                'expectedRows' => [
                    ['male', '18-24', 'alabama', 0, 62100, 151100, 151200],
                    ['male', 'unknown', 'alabama', 31100, 32100, 11100, 11200],
                    ['unknown', '18-24', 'alabama', 5100, 5200, 15100, 15200],
                    ['unknown', 'unknown', 'alabama', 6100, 6200, 4100, 4200],
                    ['male', '18-24', 'alaska', 0, 62100, 151100, 151200],
                    ['male', 'unknown', 'alaska', 31100, 32100, 11100, 11200],
                    ['unknown', '18-24', 'alaska', 5100, 5200, 15100, 15200],
                    ['unknown', 'unknown', 'alaska', 6100, 6200, 4100, 4200],
                    ['male', '18-24', 'detroit', 0, 62100, 151100, 151200],
                    ['male', 'unknown', 'detroit', 31100, 32100, 11100, 11200],
                    ['unknown', '18-24', 'detroit', 5100, 5200, 15100, 15200],
                    ['unknown', 'unknown', 'detroit', 6100, 6200, 4100, 4200]
                ]
            ],
            [ // #7 - histogram
                'targets' => [['target' => 'fb.author.age'], ['target' => 'fb.author.gender']],
                'comparison' => false,
                'chartsData' => $this->loadJSONFixture('histogram', null, true),
                'expectedHeaders' => [
                    'fb.author.age',
                    'fb.author.gender',
                    'interactions',
                    'unique_authors'
                ],
                'expectedRows' => [
                    ['18-24', 'male', 5100, 3000],
                    ['18-24', 'unknown', 4100, 3100],
                    ['unknown', 'male', 31100, 19500],
                    ['unknown', 'unknown', 21100, 20500]
                ]
            ],
            [ // #8 - histogram baseline
                'targets' => [['target' => 'fb.author.age'], ['target' => 'fb.author.gender']],
                'comparison' => ChartGenerator::MODE_BASELINE,
                'chartsData' => $this->loadJSONFixture('histogram_baseline', null, true),
                'expectedHeaders' => [
                    'fb.author.age',
                    'fb.author.gender',
                    'interactions',
                    'unique_authors',
                    'interactions_baseline',
                    'unique_authors_baseline'
                ],
                'expectedRows' => [
                    ['18-24', 'male', 5100, 3000, 2000, 4000],
                    ['18-24', 'unknown', 4100, 3100, 1500, 5000],
                    ['unknown', 'male', 31100, 19500, 25000, 20000],
                    ['unknown', 'unknown', 21100, 20500, 35000, 25600]
                ]
            ],
            [ // #9 - histogram compare
                'targets' => [['target' => 'fb.author.age'], ['target' => 'fb.author.gender']],
                'comparison' => ChartGenerator::MODE_COMPARE,
                'chartsData' => $this->loadJSONFixture('histogram_compare', null, true),
                'expectedHeaders' => [
                    'fb.author.age',
                    'fb.author.gender',
                    'interactions',
                    'unique_authors',
                    'interactions_compare',
                    'unique_authors_compare'
                ],
                'expectedRows' => [
                    ['18-24', 'male', 5100, 3000, 2000, 4000],
                    ['18-24', 'unknown', 4100, 3100, 1500, 5000],
                    ['unknown', 'male', 31100, 19500, 25000, 20000],
                    ['unknown', 'unknown', 21100, 20500, 35000, 25600]
                ]
            ],
            [ // #10 - timeseries
                'targets' => [['target' => 'time']],
                'comparison' => false,
                'chartsData' => $this->loadJSONFixture('timeseries', null, true),
                'expectedHeaders' => [
                    'time',
                    'interactions',
                    'unique_authors'
                ],
                'expectedRows' => [
                    ["2015-08-26T00:00:00+00:00", 2836300, 1841700],
                    ["2015-08-27T00:00:00+00:00", 2263700, 1372500],
                    ["2015-08-28T00:00:00+00:00", 2201200, 1321100],
                    ["2015-08-29T00:00:00+00:00", 5047400, 3388100],
                    ["2015-08-30T00:00:00+00:00", 2432300, 1425400],
                    ["2015-08-31T00:00:00+00:00", 2345200, 1381200]
                ]
            ],
            [ // #11 - timeseries baseline
                'targets' => [['target' => 'time']],
                'comparison' => ChartGenerator::MODE_BASELINE,
                'chartsData' => $this->loadJSONFixture('timeseries_baseline', null, true),
                'expectedHeaders' => [
                    'time',
                    'interactions',
                    'unique_authors',
                    'interactions_baseline',
                    'unique_authors_baseline'
                ],
                'expectedRows' => [
                    ["2015-08-26T00:00:00+00:00", 2836300, 1841700, 2836300, 1841700],
                    ["2015-08-27T00:00:00+00:00", 2263700, 1372500, 2263700, 1372500],
                    ["2015-08-28T00:00:00+00:00", 2201200, 1321100, 2201200, 1321100],
                    ["2015-08-29T00:00:00+00:00", 5047400, 3388100, 5047400, 3388100],
                    ["2015-08-30T00:00:00+00:00", 2432300, 1425400, 2432300, 1425400],
                    ["2015-08-31T00:00:00+00:00", 2345200, 1381200, 2345200, 1381200]
                ]
            ],
            [ // #12 - timeseries compare
                'targets' => [['target' => 'time']],
                'comparison' => ChartGenerator::MODE_COMPARE,
                'chartsData' => $this->loadJSONFixture('timeseries_compare', null, true),
                'expectedHeaders' => [
                    'time',
                    'interactions',
                    'unique_authors',
                    'interactions_compare',
                    'unique_authors_compare'
                ],
                'expectedRows' => [
                    ["2015-08-26T00:00:00+00:00", 2836300, 1841700, 2836300, 1841700],
                    ["2015-08-27T00:00:00+00:00", 2263700, 1372500, 2263700, 1372500],
                    ["2015-08-28T00:00:00+00:00", 2201200, 1321100, 2201200, 1321100],
                    ["2015-08-29T00:00:00+00:00", 5047400, 3388100, 5047400, 3388100],
                    ["2015-08-30T00:00:00+00:00", 2432300, 1425400, 2432300, 1425400],
                    ["2015-08-31T00:00:00+00:00", 2345200, 1381200, 2345200, 1381200]
                ]
            ],
            'NEV-522 - generating some kind of fatal error' => [
                'targets' => [['target' => 'links.normalized_url']],
                'comparison' => false,
                'chartsData' => $this->loadJSONFixture('nev-522', null, true),
                'expectedHeaders' => [
                    'links.normalized_url',
                    'interactions',
                    'unique_authors'
                ],
                'expectedRows' => $this->loadJSONFixture('nev-522-expected')
            ]
        ];
    }
}
