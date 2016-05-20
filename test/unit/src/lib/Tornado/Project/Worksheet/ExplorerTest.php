<?php

namespace Test\Tornado\Project\Worksheet;

use Tornado\Project\Worksheet\Explorer;
use Tornado\Project\Worksheet;
use Tornado\Project\Worksheet\FilterCsdlGenerator;

use Tornado\Analyze\Analysis;
use Tornado\Project\Chart;
use Tornado\Project\Chart\Generator as ChartGenerator;

/**
 * ExplorerTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Project
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers      \Tornado\Project\Worksheet\Explorer
 */
class ExplorerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * DataProvider for testExplore
     *
     * @return array
     */
    public function exploreProvider()
    {
        $emptySecondaryFilters = [
            'keywords' => null,
            'links' => null,
            'country' => null,
            'region' => null,
            'gender' => null,
            'age' => null,
            'csdl' => null,
            'generated_csdl' => null,
            'start' => null,
            'end' => null
        ];
        $emptySecondaryFiltersString = json_encode($emptySecondaryFilters);

        return [
            [ // #0
                'input' => [
                    'id' => 10,
                    'workbook_id' => 20,
                    'name' => 'newName',
                    'rank' => 1,
                    'comparison' => ChartGenerator::MODE_COMPARE,
                    'measurement' => ChartGenerator::MEASURE_INTERACTIONS,
                    'chart_type' => Chart::TYPE_TORNADO,
                    'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'secondary_recording_id' => 345,
                    'secondary_recording_filters' => null,
                    'baseline_dataset_id' => 456,
                    'filters' => json_encode([
                        'generated_csdl' => '',
                        'csdl' => 'interaction.content contains_any "bob"'
                    ]),
                    'dimensions' => '[{"target":"fb.author.gender","cardinality":null,"label":null,"threshold":100},'
                    . '{"target":"fb.author.age","cardinality":null,"label":null,"threshold":null}]',
                    'start' => 123456,
                    'end' => 123456,
                    'parent_worksheet_id' => null,
                    'display_options' => null,
                    'created_at' => 123456789,
                    'updated_at' => 123456780
                ],
                'explore' => [
                    "fb.author.age" => "18-24",
                    "fb.author.gender" => "male"
                ],
                'name' => 'Another New Name',
                'start' => null,
                'end' => null,
                'chartType' => Chart::TYPE_TORNADO,
                'analysisType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                'expected' => [
                    'id' => null,
                    'workbook_id' => 20,
                    'name' => 'Another New Name',
                    'rank' => null,
                    'comparison' => ChartGenerator::MODE_COMPARE,
                    'measurement' => ChartGenerator::MEASURE_INTERACTIONS,
                    'chart_type' => Chart::TYPE_TORNADO,
                    'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'secondary_recording_id' => null,
                    'secondary_recording_filters' => $emptySecondaryFiltersString,
                    'baseline_dataset_id' => null,
                    'filters' => json_encode([
                        'generated_csdl' => "fb.author.gender in \"male\"\n"
                            . "AND\nfb.author.age in \"18-24\"\n"
                            . "AND\ninteraction.content contains_any \"bob\"",
                        'csdl' => 'interaction.content contains_any "bob"',
                        'age' => ["18-24"],
                        'gender' => ["male"],
                        'keywords' => null,
                        'links' => null,
                        'country' => null,
                        'region' => null,
                        'span' => null,
                        'interval' => null
                    ]),
                    'dimensions' => '[]',
                    'start' => 123456,
                    'end' => 123456,
                    'parent_worksheet_id' => 10,
                    'display_options' => '{}',
                    'created_at' => null,
                    'updated_at' => null
                ]
            ],
            [ // #1
                'input' => [
                    'id' => 10,
                    'workbook_id' => 20,
                    'name' => 'newName',
                    'rank' => 1,
                    'comparison' => ChartGenerator::MODE_COMPARE,
                    'measurement' => ChartGenerator::MEASURE_INTERACTIONS,
                    'chart_type' => Chart::TYPE_TORNADO,
                    'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'secondary_recording_id' => 345,
                    'secondary_recording_filters' => null,
                    'baseline_dataset_id' => 456,
                    'filters' => json_encode([
                        'generated_csdl' => 'dave'
                    ]),
                    'dimensions' => '[{"target":"fb.author.gender","cardinality":null,"label":null,"threshold":100},'
                    . '{"target":"fb.author.age","cardinality":null,"label":null,"threshold":null}]',
                    'start' => 123456,
                    'end' => 123456,
                    'parent_worksheet_id' => null,
                    'display_options' => json_encode([
                        'sort' => ['label' => 'desc', 'outliers' => false]
                    ]),
                    'created_at' => 123456789,
                    'updated_at' => 223456789
                ],
                'explore' => [
                    'fb.author.age' => '18-24'
                ],
                'name' => 'Another New Name',
                'start' => null,
                'end' => null,
                'chartType' => Chart::TYPE_TORNADO,
                'analysisType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                'expected' => [
                    'id' => null,
                    'workbook_id' => 20,
                    'name' => 'Another New Name',
                    'rank' => null,
                    'comparison' => ChartGenerator::MODE_COMPARE,
                    'measurement' => ChartGenerator::MEASURE_INTERACTIONS,
                    'chart_type' => Chart::TYPE_TORNADO,
                    'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'secondary_recording_id' => null,
                    'secondary_recording_filters' => $emptySecondaryFiltersString,
                    'baseline_dataset_id' => null,
                    'filters' => json_encode([
                        'generated_csdl' => 'fb.author.age in "18-24"',
                        'age' => ['18-24'],
                        'keywords' => null,
                        'links' => null,
                        'country' => null,
                        'region' => null,
                        'gender' => null,
                        'csdl' => null,
                        'span' => null,
                        'interval' => null
                    ]),
                    'dimensions' => '[]',
                    'start' => 123456,
                    'end' => 123456,
                    'parent_worksheet_id' => 10,
                    'display_options' => json_encode([
                        'sort' => ['label' => 'desc', 'outliers' => false]
                    ]),
                    'created_at' => null,
                    'updated_at' => null
                ]
            ],
            [ // #2
                'input' => [
                    'id' => 10,
                    'workbook_id' => 20,
                    'name' => 'newName',
                    'rank' => 1,
                    'comparison' => ChartGenerator::MODE_COMPARE,
                    'measurement' => ChartGenerator::MEASURE_INTERACTIONS,
                    'chart_type' => Chart::TYPE_TORNADO,
                    'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'secondary_recording_id' => 345,
                    'secondary_recording_filters' => null,
                    'baseline_dataset_id' => 456,
                    'filters' => json_encode([
                        'generated_csdl' => 'fb.author.age == "18-24"',
                        'csdl' => ''
                    ]),
                    'dimensions' => '[{"target":"fb.author.gender","cardinality":null,"label":null,"threshold":100},'
                    . '{"target":"fb.author.age","cardinality":null,"label":null,"threshold":null}]',
                    'start' => 123456,
                    'end' => 123456,
                    'parent_worksheet_id' => null,
                    'display_options' => json_encode([
                        'sort' => ['label' => 'desc', 'outliers' => false]
                    ]),
                    'created_at' => 123456789,
                    'updated_at' => 123456780,
                ],
                'explore' => [
                    'fb.author.age' => '18-24',
                    'fb.content' => 'dave'
                ],
                'name' => 'Another New Name',
                'start' => null,
                'end' => null,
                'chartType' => Chart::TYPE_TORNADO,
                'analysisType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                'expected' => [
                    'id' => null,
                    'workbook_id' => 20,
                    'name' => 'Another New Name',
                    'rank' => null,
                    'comparison' => ChartGenerator::MODE_COMPARE,
                    'measurement' => ChartGenerator::MEASURE_INTERACTIONS,
                    'chart_type' => Chart::TYPE_TORNADO,
                    'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'secondary_recording_id' => null,
                    'secondary_recording_filters' => $emptySecondaryFiltersString,
                    'baseline_dataset_id' => null,
                    'filters' => json_encode([
                        'generated_csdl' => "fb.author.age in \"18-24\"\nAND\nfb.content == \"dave\"",
                        'csdl' => 'fb.content == "dave"',
                        'age' => ['18-24'],
                        'keywords' => null,
                        'links' => null,
                        'country' => null,
                        'region' => null,
                        'gender' => null,
                        'span' => null,
                        'interval' => null
                    ]),
                    'dimensions' => '[]',
                    'start' => 123456,
                    'end' => 123456,
                    'parent_worksheet_id' => 10,
                    'display_options' => json_encode([
                        'sort' => ['label' => 'desc', 'outliers' => false]
                    ]),
                    'created_at' => null,
                    'updated_at' => null
                ]
            ],
            [ // #3
                'input' => [
                    'id' => 10,
                    'workbook_id' => 20,
                    'name' => 'newName',
                    'rank' => 1,
                    'comparison' => ChartGenerator::MODE_COMPARE,
                    'measurement' => ChartGenerator::MEASURE_INTERACTIONS,
                    'chart_type' => Chart::TYPE_TORNADO,
                    'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'secondary_recording_id' => 345,
                    'secondary_recording_filters' => null,
                    'baseline_dataset_id' => 456,
                    'filters' => json_encode([
                        'generated_csdl' => 'fb.author.age == "18-24"',
                        'csdl' => ''
                    ]),
                    'dimensions' => '[{"target":"fb.author.gender","cardinality":null,"label":null,"threshold":100}'
                    . ',{"target":"fb.author.age","cardinality":null,"label":null,"threshold":null}]',
                    'start' => 123456789,
                    'end' => 123456789,
                    'parent_worksheet_id' => null,
                    'display_options' => json_encode([
                        'sort' => ['label' => 'desc', 'outliers' => false]
                    ]),
                    'created_at' => 123456789,
                    'updated_at' => 123456780,
                ],
                'explore' => [
                    'fb.author.age' => '18-24',
                    'fb.content' => 'dave'
                ],
                'name' => 'Another New Name',
                'start' => 223456789,
                'end' => 113456789,
                'chartType' => Chart::TYPE_TORNADO,
                'analysisType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                'expected' => [
                    'id' => null,
                    'workbook_id' => 20,
                    'name' => 'Another New Name',
                    'rank' => null,
                    'comparison' => ChartGenerator::MODE_COMPARE,
                    'measurement' => ChartGenerator::MEASURE_INTERACTIONS,
                    'chart_type' => Chart::TYPE_TORNADO,
                    'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'secondary_recording_id' => null,
                    'secondary_recording_filters' => $emptySecondaryFiltersString,
                    'baseline_dataset_id' => null,
                    'filters' => json_encode([
                        'generated_csdl' => "fb.author.age in \"18-24\"\nAND\nfb.content == \"dave\"",
                        'csdl' => 'fb.content == "dave"',
                        'age' => ['18-24'],
                        'keywords' => null,
                        'links' => null,
                        'country' => null,
                        'region' => null,
                        'gender' => null,
                        'span' => null,
                        'interval' => null
                    ]),
                    'dimensions' => '[]',
                    'start' => 223456789,
                    'end' => 113456789,
                    'parent_worksheet_id' => 10,
                    'display_options' => json_encode([
                        'sort' => ['label' => 'desc', 'outliers' => false]
                    ]),
                    'created_at' => null,
                    'updated_at' => null
                ]
            ],
            [ // #4
                'input' => [
                    'id' => 10,
                    'workbook_id' => 20,
                    'name' => 'newName',
                    'rank' => 1,
                    'comparison' => ChartGenerator::MODE_COMPARE,
                    'measurement' => ChartGenerator::MEASURE_INTERACTIONS,
                    'chart_type' => Chart::TYPE_TORNADO,
                    'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'secondary_recording_id' => 345,
                    'secondary_recording_filters' => null,
                    'baseline_dataset_id' => 456,
                    'filters' => json_encode([
                        'generated_csdl' => 'fb.author.age == "18-24"',
                        'csdl' => ''
                    ]),
                    'dimensions' => '[{"target":"fb.author.gender","cardinality":null,"label":null,"threshold":100},'
                    . '{"target":"fb.author.age","cardinality":null,"label":null,"threshold":null}]',
                    'start' => 123456789,
                    'end' => 123456789,
                    'parent_worksheet_id' => null,
                    'display_options' => json_encode([
                        'sort' => ['label' => 'desc', 'outliers' => false]
                    ]),
                    'created_at' => 123456789,
                    'updated_at' => 123456780,
                ],
                'explore' => [
                    'fb.author.age' => '18-24',
                    'fb.content' => 'dave'
                ],
                'name' => 'Another New Name',
                'start' => 223456789,
                'end' => null,
                'chartType' => Chart::TYPE_TORNADO,
                'analysisType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                'expected' => [
                    'id' => null,
                    'workbook_id' => 20,
                    'name' => 'Another New Name',
                    'rank' => null,
                    'comparison' => ChartGenerator::MODE_COMPARE,
                    'measurement' => ChartGenerator::MEASURE_INTERACTIONS,
                    'chart_type' => Chart::TYPE_TORNADO,
                    'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'secondary_recording_id' => null,
                    'secondary_recording_filters' => $emptySecondaryFiltersString,
                    'baseline_dataset_id' => null,
                    'filters' => json_encode([
                        'generated_csdl' => "fb.author.age in \"18-24\"\nAND\nfb.content == \"dave\"",
                        'csdl' => 'fb.content == "dave"',
                        'age' => ['18-24'],
                        'keywords' => null,
                        'links' => null,
                        'country' => null,
                        'region' => null,
                        'gender' => null,
                        'span' => null,
                        'interval' => null
                    ]),
                    'dimensions' => '[]',
                    'start' => 223456789,
                    'end' => 123456789,
                    'parent_worksheet_id' => 10,
                    'display_options' => json_encode([
                        'sort' => ['label' => 'desc', 'outliers' => false]
                    ]),
                    'created_at' => null,
                    'updated_at' => null
                ]
            ],
            [ // #5
                'input' => [
                    'id' => 10,
                    'workbook_id' => 20,
                    'name' => 'newName',
                    'rank' => 1,
                    'comparison' => ChartGenerator::MODE_COMPARE,
                    'measurement' => ChartGenerator::MEASURE_INTERACTIONS,
                    'chart_type' => Chart::TYPE_TORNADO,
                    'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'secondary_recording_id' => 345,
                    'secondary_recording_filters' => null,
                    'baseline_dataset_id' => 456,
                    'filters' => json_encode([
                        'generated_csdl' => 'fb.author.age == "18-24"',
                        'csdl' => ''
                    ]),
                    'dimensions' => '[{"target":"fb.author.gender","cardinality":null,"label":null,"threshold":100},'
                    . '{"target":"fb.author.age","cardinality":null,"label":null,"threshold":null}]',
                    'start' => 123456789,
                    'end' => 123456789,
                    'parent_worksheet_id' => null,
                    'display_options' => json_encode([
                        'sort' => ['label' => 'desc', 'outliers' => false]
                    ]),
                    'created_at' => 123456789,
                    'updated_at' => 123456780,
                ],
                'explore' => [
                    'fb.author.age' => '18-24',
                    'fb.content' => 'dave'
                ],
                'name' => 'Another New Name',
                'start' => null,
                'end' => 113456789,
                'chartType' => Chart::TYPE_HISTOGRAM,
                'analysisType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                'expected' => [
                    'id' => null,
                    'workbook_id' => 20,
                    'name' => 'Another New Name',
                    'rank' => null,
                    'comparison' => ChartGenerator::MODE_COMPARE,
                    'measurement' => ChartGenerator::MEASURE_INTERACTIONS,
                    'chart_type' => Chart::TYPE_HISTOGRAM,
                    'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'secondary_recording_id' => null,
                    'secondary_recording_filters' => $emptySecondaryFiltersString,
                    'baseline_dataset_id' => null,
                    'filters' => json_encode([
                        'generated_csdl' => "fb.author.age in \"18-24\"\nAND\nfb.content == \"dave\"",
                        'csdl' => 'fb.content == "dave"',
                        'age' => ['18-24'],
                        'keywords' => null,
                        'links' => null,
                        'country' => null,
                        'region' => null,
                        'gender' => null,
                        'span' => null,
                        'interval' => null
                    ]),
                    'dimensions' => '[]',
                    'start' => 123456789,
                    'end' => 113456789,
                    'parent_worksheet_id' => 10,
                    'display_options' => json_encode([
                        'sort' => ['label' => 'desc', 'outliers' => false]
                    ]),
                    'created_at' => null,
                    'updated_at' => null
                ]
            ]
        ];
    }

    /**
     * @dataProvider exploreProvider
     *
     * @param array $input
     * @param array $explore
     * @param string $name
     * @param integer $start
     * @param integer $end
     * @param string $chartType
     * @param string $analysisType
     * @param array $expected
     */
    public function testExplore(
        array $input,
        array $explore,
        $name,
        $start,
        $end,
        $chartType,
        $analysisType,
        array $expected
    ) {
        $obj = new Worksheet();
        $obj->loadFromArray($input);
        $explorer = new Explorer(new FilterCsdlGenerator());

        $explored = $explorer->explore($obj, $name, $explore, $start, $end, $chartType, $analysisType);

        $this->assertTrue($explored !== $obj);
        $this->assertEquals($expected, $explored->toArray());
    }
}
