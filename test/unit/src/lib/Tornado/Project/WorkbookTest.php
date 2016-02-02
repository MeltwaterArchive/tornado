<?php

namespace Test\Tornado\Project;

use Tornado\Analyze\Analysis;
use Tornado\Project\Chart;
use Tornado\Project\Chart\Generator as ChartGenerator;
use Tornado\Project\Workbook;
use Tornado\Project\Worksheet;

/**
 * WorkbookTest
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
 * @covers      \Tornado\Project\Workbook
 */
class WorkbookTest extends \PHPUnit_Framework_TestCase
{
    /**
     * DataProvider for testGetterSetter
     *
     * @return array
     */
    public function getterSetterProvider()
    {
        return [
            [
                'setter' => 'setId',
                'value' => 10,
                'getter' => 'getId',
                'expected' => 10
            ],
            [
                'setter' => 'setProjectId',
                'value' => 20,
                'getter' => 'getProjectId',
                'expected' => 20
            ],
            [
                'setter' => 'setName',
                'value' => 'testName',
                'getter' => 'getName',
                'expected' => 'testName'
            ],
            [
                'setter' => 'setRecordingId',
                'value' => 20,
                'getter' => 'getRecordingId',
                'expected' => 20
            ],
            [
                'setter' => 'setPrimaryKey',
                'value' => 20,
                'getter' => 'getPrimaryKey',
                'expected' => 20
            ],
            [
                'setter' => 'setRank',
                'value' => 1,
                'getter' => 'getRank',
                'expected' => 1
            ]
        ];
    }

    public function testPrimaryKeyName()
    {
        $obj = new Workbook();
        $this->assertEquals('id', $obj->getPrimaryKeyName());
    }

    /**
     * @dataProvider getterSetterProvider
     *
     * @param string $setter
     * @param mixed  $value
     * @param string $getter
     * @param mixed  $expected
     */
    public function testGetterSetter($setter, $value, $getter, $expected)
    {
        $obj = new Workbook();
        $obj->$setter($value);
        $this->assertEquals($expected, $obj->$getter());
    }

    public function testSettingAndGettingWorksheets()
    {
        $obj = new Workbook();
        $obj->setId(5);

        $worksheets = [];
        for ($i = 1; $i < 8; $i++) {
            $worksheet = new Worksheet();
            $worksheet->setWorkbookId($obj->getId());
            $worksheets[] = $worksheet;
        }

        $obj->setWorksheets($worksheets);

        $this->assertEquals($worksheets, $obj->getWorksheets());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testAddingInvalidWorksheet()
    {
        $obj = new Workbook();
        $obj->setId(5);

        $worksheet = new Worksheet();
        $worksheet->setWorkbookId(6);

        $obj->addWorksheet($worksheet);
    }

    /**
     * DataProvider for testToFromArray
     *
     * @return array
     */
    public function toFromArrayProvider()
    {
        return [
            [
                'data' => [
                    'id' => 10,
                    'project_id' => 20,
                    'name' => 'newName',
                    'recording_id' => 5,
                    'rank' => 1
                ],
                'getters' => [
                    'getID' => 10,
                    'getProjectId' => 20,
                    'getName' => 'newName',
                    'getRecordingId' => 5,
                    'getRank' => 1
                ],
                'expected' => [
                    'id' => 10,
                    'project_id' => 20,
                    'name' => 'newName',
                    'recording_id' => 5,
                    'rank' => 1
                ],
            ],
            [
                'data' => [
                    'id' => 1,
                    'project_id' => 2,
                    'name' => 'newName #2',
                    'recording_id' => 3,
                    'rank' => 5
                ],
                'getters' => [
                    'getID' => 1,
                    'getProjectId' => 2,
                    'getName' => 'newName #2',
                    'getRecordingId' => 3,
                    'getRank' => 5
                ],
                'expected' => [
                    'id' => 1,
                    'project_id' => 2,
                    'name' => 'newName #2',
                    'recording_id' => 3,
                    'rank' => 5
                ],
            ]
        ];
    }

    /**
     * @dataProvider toFromArrayProvider
     *
     * @covers       \Tornado\Project\Workbook::loadFromArray
     * @covers       \Tornado\Project\Workbook::toArray
     *
     * @param array $data
     * @param array $getters
     * @param array $expected
     */
    public function testToFromArray(array $data, array $getters, array $expected)
    {
        $obj = new Workbook();
        $obj->loadFromArray($data);

        foreach ($getters as $getter => $value) {
            $this->assertEquals($value, $obj->$getter());
        }

        $this->assertEquals($expected, $obj->toArray());
    }

    /**
     * Data provider for testJsonSerialization
     *
     * @return array
     */
    public function toJsonProvider()
    {
        $data = $this->toFromArrayProvider();
        foreach ($data as &$item) {
            unset($item['getters']);
            $item['data']['worksheets'] = [];
            $item['expected']['worksheets'] = [];
            $item['expected'] = json_encode($item['expected']);
        }

        // also add one with embedded worksheets
        $data[] = [
            'data' => [
                'id' => 1,
                'project_id' => 2,
                'name' => 'newName #2',
                'recording_id' => 3,
                'rank' => 5,
                'worksheets' => [
                    ['id' => 5, 'workbook_id' => 1, 'name' => 'Worksheet #1'],
                    ['id' => 6, 'workbook_id' => 1, 'name' => 'Worksheet #2'],
                    ['id' => 7, 'workbook_id' => 1, 'name' => 'Worksheet #3'],
                ]
            ],
            'expected' => json_encode([
                'id' => 1,
                'project_id' => 2,
                'name' => 'newName #2',
                'recording_id' => 3,
                'rank' => 5,
                'worksheets' => [
                    [
                        'id' => 5,
                        'workbook_id' => 1,
                        'name' => 'Worksheet #1',
                        'rank' => null,
                        'comparison' => ChartGenerator::MODE_COMPARE,
                        'measurement' => ChartGenerator::MEASURE_UNIQUE_AUTHORS,
                        'chart_type' => Chart::TYPE_TORNADO,
                        'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                        'secondary_recording_id' => null,
                        'secondary_recording_filters' => new \StdClass(),
                        'baseline_dataset_id' => null,
                        'filters' => new \StdClass(),
                        'dimensions' => null,
                        'start' => null,
                        'end' => null,
                        'parent_worksheet_id' => null,
                        'created_at' => null,
                        'updated_at' => null,
                        'span' => 1,
                        'interval' => 'day'
                    ],
                    [
                        'id' => 6,
                        'workbook_id' => 1,
                        'name' => 'Worksheet #2',
                        'rank' => null,
                        'comparison' => ChartGenerator::MODE_COMPARE,
                        'measurement' => ChartGenerator::MEASURE_UNIQUE_AUTHORS,
                        'chart_type' => Chart::TYPE_TORNADO,
                        'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                        'secondary_recording_id' => null,
                        'secondary_recording_filters' => new \StdClass(),
                        'baseline_dataset_id' => null,
                        'filters' => new \StdClass(),
                        'dimensions' => null,
                        'start' => null,
                        'end' => null,
                        'parent_worksheet_id' => null,
                        'created_at' => null,
                        'updated_at' => null,
                        'span' => 1,
                        'interval' => 'day'
                    ],
                    [
                        'id' => 7,
                        'workbook_id' => 1,
                        'name' => 'Worksheet #3',
                        'rank' => null,
                        'comparison' => ChartGenerator::MODE_COMPARE,
                        'measurement' => ChartGenerator::MEASURE_UNIQUE_AUTHORS,
                        'chart_type' => Chart::TYPE_TORNADO,
                        'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                        'secondary_recording_id' => null,
                        'secondary_recording_filters' => new \StdClass(),
                        'baseline_dataset_id' => null,
                        'filters' => new \StdClass(),
                        'dimensions' => null,
                        'start' => null,
                        'end' => null,
                        'parent_worksheet_id' => null,
                        'created_at' => null,
                        'updated_at' => null,
                        'span' => 1,
                        'interval' => 'day'
                    ]
                ]
            ]),
        ];
        return $data;
    }

    /**
     * @dataProvider toJsonProvider
     *
     * @covers \Tornado\Project\Project::jsonSerialize
     *
     * @param array $data
     * @param string $expected
     */
    public function testJsonSerialization(
        array $data,
        $expected
    ) {
        $obj = new Workbook();
        $obj->loadFromArray($data);

        foreach ($data['worksheets'] as $worksheetData) {
            $worksheet = new Worksheet();
            $worksheet->setId($worksheetData['id']);
            $worksheet->setName($worksheetData['name']);
            $worksheet->setWorkbookId($worksheetData['workbook_id']);
            $obj->addWorksheet($worksheet);
        }

        $this->assertEquals($expected, json_encode($obj));
    }
}
