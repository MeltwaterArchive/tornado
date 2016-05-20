<?php

namespace Test\Tornado\Project;

use Tornado\Analyze\Analysis;
use Tornado\Analyze\Dimension;
use Tornado\Analyze\Dimension\Collection as DimensionCollection;
use Tornado\Project\Chart;
use Tornado\Project\Chart\Generator as ChartGenerator;
use Tornado\Project\Worksheet;

/**
 * WorksheetTest
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
 * @coversDefaultClass      \Tornado\Project\Worksheet
 */
class WorksheetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::getPrimaryKeyName
     */
    public function testPrimaryKeyName()
    {
        $worksheet = new Worksheet();
        $this->assertEquals('id', $worksheet->getPrimaryKeyName());
    }

    /**
     * DataProvider for testGetterSetter
     *
     * @return array
     */
    public function getterSetterProvider()
    {
        $emptyFiltersObj = $this->getFiltersObject();
        $filtersArray = $this->getFiltersArray([
            'age' => 18,
            'csdl' => 'fb.content any "iphone"'
        ]);
        $filtersObj = $this->getFiltersObject($filtersArray);
        $filtersString = $this->getFiltersString($filtersObj);

        $emptySecondaryRecordingFiltersObj = $this->getFiltersObject(array(), true);
        $secondaryRecordingFiltersArray = $this->getFiltersArray([
            'age' => 18,
            'csdl' => 'fb.content any "iphone"'
        ], true);
        $secondaryRecordingFiltersObj = $this->getFiltersObject($secondaryRecordingFiltersArray, true);
        $secondaryRecordingFiltersString = $this->getFiltersString($secondaryRecordingFiltersObj, true);

        $dimensionsArray = [
            ['target' => 'fb.author.gender', 'threshold' => 100],
            ['target' => 'fb.author.age']
        ];
        $dimensionsString = '[{"target":"fb.author.gender","cardinality":null,"label":null,"threshold":100},'
        . '{"target":"fb.author.age","cardinality":null,"label":null,"threshold":null}]';

        $dimensions = new DimensionCollection();
        $dimensions->addDimension(
            new Dimension($dimensionsArray[0]['target'], null, null, $dimensionsArray[0]['threshold'])
        );
        $dimensions->addDimension(
            new Dimension($dimensionsArray[1]['target'])
        );

        return [
            [
                'setter' => 'setId',
                'value' => 10,
                'getter' => 'getId',
                'expected' => 10
            ],
            [
                'setter' => 'setWorkbookId',
                'value' => 20,
                'getter' => 'getWorkbookId',
                'expected' => 20
            ],
            [
                'setter' => 'setName',
                'value' => 'testName',
                'getter' => 'getName',
                'expected' => 'testName'
            ],
            [
                'setter' => 'setRank',
                'value' => 1,
                'getter' => 'getRank',
                'expected' => 1
            ],
            [
                'setter' => 'setPrimaryKey',
                'value' => 20,
                'getter' => 'getPrimaryKey',
                'expected' => 20
            ],
            [
                'setter' => 'setComparison',
                'value' => ChartGenerator::MODE_COMPARE,
                'getter' => 'getComparison',
                'expected' => ChartGenerator::MODE_COMPARE
            ],
            [
                'setter' => 'setMeasurement',
                'value' => ChartGenerator::MEASURE_INTERACTIONS,
                'getter' => 'getMeasurement',
                'expected' => ChartGenerator::MEASURE_INTERACTIONS
            ],
            [
                'setter' => 'setChartType',
                'value' => Chart::TYPE_TORNADO,
                'getter' => 'getChartType',
                'expected' => Chart::TYPE_TORNADO
            ],
            [
                'setter' => 'setAnalysisType',
                'value' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                'getter' => 'getAnalysisType',
                'expected' => Analysis::TYPE_FREQUENCY_DISTRIBUTION
            ],
            [
                'setter' => 'setSecondaryRecordingId',
                'value' => 20,
                'getter' => 'getSecondaryRecordingId',
                'expected' => 20
            ],
            [
                'setter' => 'setBaselineDataSetId',
                'value' => 20,
                'getter' => 'getBaselineDataSetId',
                'expected' => 20
            ],
            [
                'setter' => 'setSecondaryRecordingFilters',
                'value' => $secondaryRecordingFiltersString,
                'getter' => 'getSecondaryRecordingFilters',
                'expected' => $secondaryRecordingFiltersObj
            ],
            [
                'setter' => 'setSecondaryRecordingFilters',
                'value' => null,
                'getter' => 'getSecondaryRecordingFilters',
                'expected' => $emptySecondaryRecordingFiltersObj
            ],
            [
                'setter' => 'setSecondaryRecordingFilters',
                'value' => $secondaryRecordingFiltersArray,
                'getter' => 'getSecondaryRecordingFilters',
                'expected' => $secondaryRecordingFiltersObj
            ],
            [
                'setter' => 'setSecondaryRecordingFilters',
                'value' => $secondaryRecordingFiltersObj,
                'getter' => 'getSecondaryRecordingFilters',
                'expected' => $secondaryRecordingFiltersObj
            ],
            [
                'setter' => 'setSecondaryRecordingFilters',
                'value' => $secondaryRecordingFiltersString,
                'getter' => 'getRawSecondaryRecordingFilters',
                'expected' => $secondaryRecordingFiltersString
            ],
            [
                'setter' => 'setSecondaryRecordingFilters',
                'value' => $secondaryRecordingFiltersArray,
                'getter' => 'getRawSecondaryRecordingFilters',
                'expected' => $secondaryRecordingFiltersString
            ],
            [
                'setter' => 'setSecondaryRecordingFilters',
                'value' => null,
                'getter' => 'getRawSecondaryRecordingFilters',
                'expected' => $this->getFiltersString(null, true)
            ],
            [
                'setter' => 'setSecondaryRecordingFilters',
                'value' => $secondaryRecordingFiltersObj,
                'getter' => 'getRawSecondaryRecordingFilters',
                'expected' => $secondaryRecordingFiltersString
            ],
            [
                'setter' => 'setStart',
                'value' => 20,
                'getter' => 'getStart',
                'expected' => 20
            ],
            [
                'setter' => 'setEnd',
                'value' => 20,
                'getter' => 'getEnd',
                'expected' => 20
            ],
            [
                'setter' => 'setFilters',
                'value' => $filtersString,
                'getter' => 'getFilters',
                'expected' => $filtersObj
            ],
            [
                'setter' => 'setFilters',
                'value' => null,
                'getter' => 'getFilters',
                'expected' => $emptyFiltersObj
            ],
            [
                'setter' => 'setFilters',
                'value' => $filtersArray,
                'getter' => 'getFilters',
                'expected' => $filtersObj
            ],
            [
                'setter' => 'setFilters',
                'value' => $filtersObj,
                'getter' => 'getFilters',
                'expected' => $filtersObj
            ],
            [
                'setter' => 'setFilters',
                'value' => $filtersString,
                'getter' => 'getRawFilters',
                'expected' => $filtersString
            ],
            [
                'setter' => 'setFilters',
                'value' => $filtersArray,
                'getter' => 'getRawFilters',
                'expected' => $filtersString
            ],
            [
                'setter' => 'setFilters',
                'value' => null,
                'getter' => 'getRawFilters',
                'expected' => $this->getFiltersString()
            ],
            [
                'setter' => 'setFilters',
                'value' => $filtersObj,
                'getter' => 'getRawFilters',
                'expected' => $filtersString
            ],
            [
                'setter' => 'setDimensions',
                'value' => null,
                'getter' => 'getDimensions',
                'expected' => null
            ],
            [
                'setter' => 'setDimensions',
                'value' => $dimensions,
                'getter' => 'getDimensions',
                'expected' => $dimensions
            ],
            [
                'setter' => 'setDimensions',
                'value' => $dimensionsArray,
                'getter' => 'getDimensions',
                'expected' => $dimensions
            ],
            [
                'setter' => 'setDimensions',
                'value' => $dimensionsString,
                'getter' => 'getDimensions',
                'expected' => $dimensions
            ],
            [
                'setter' => 'setDimensions',
                'value' => null,
                'getter' => 'getRawDimensions',
                'expected' => null
            ],
            [
                'setter' => 'setDimensions',
                'value' => $dimensions,
                'getter' => 'getRawDimensions',
                'expected' => $dimensionsString
            ],
            [
                'setter' => 'setDimensions',
                'value' => $dimensionsArray,
                'getter' => 'getRawDimensions',
                'expected' => $dimensionsString
            ],
            [
                'setter' => 'setDimensions',
                'value' => $dimensionsString,
                'getter' => 'getRawDimensions',
                'expected' => $dimensionsString
            ],
            [
                'setter' => 'setParentWorksheetId',
                'value' => 10,
                'getter' => 'getParentWorksheetId',
                'expected' => 10
            ],
            [
                'setter' => 'setCreatedAt',
                'value' => 40,
                'getter' => 'getCreatedAt',
                'expected' => 40
            ],
            [
                'setter' => 'setUpdatedAt',
                'value' => 60,
                'getter' => 'getUpdatedAt',
                'expected' => 60
            ]
        ];
    }

    /**
     * @dataProvider getterSetterProvider
     *
     * @covers ::getId
     * @covers ::setId
     * @covers ::getWorkbookId
     * @covers ::setWorkbookId
     * @covers ::getName
     * @covers ::setName
     * @covers ::getRank
     * @covers ::setRank
     * @covers ::getPrimaryKey
     * @covers ::setPrimaryKey
     * @covers ::getComparison
     * @covers ::setComparison
     * @covers ::getMeasurement
     * @covers ::setMeasurement
     * @covers ::getChartType
     * @covers ::setChartType
     * @covers ::getAnalysisType
     * @covers ::setAnalysisType
     * @covers ::getSecondaryRecordingId
     * @covers ::setSecondaryRecordingId
     * @covers ::getSecondaryRecordingFilters
     * @covers ::setSecondaryRecordingFilters
     * @covers ::getRawSecondaryRecordingFilters
     * @covers ::setRawSecondaryRecordingFilters
     * @covers ::getBaselineDataSetId
     * @covers ::setBaselineDataSetId
     * @covers ::getFilters
     * @covers ::setFilters
     * @covers ::getRawFilters
     * @covers ::setRawFilters
     * @covers ::getDimensions
     * @covers ::getRawDimensions
     * @covers ::setDimensions
     * @covers ::getStart
     * @covers ::setStart
     * @covers ::getEnd
     * @covers ::setEnd
     * @covers ::getParentWorksheetId
     * @covers ::setParentWorksheetId
     * @covers ::getCreatedAt
     * @covers ::setCreatedAt
     * @covers ::getUpdatedAt
     * @covers ::setUpdatedAt
     *
     * @param string $setter
     * @param mixed  $value
     * @param string $getter
     * @param mixed  $expected
     */
    public function testGetterSetter($setter, $value, $getter, $expected)
    {
        $obj = new Worksheet();
        $obj->$setter($value);
        $this->assertEquals($expected, $obj->$getter());
    }

    /**
     * DataProvider for testToFromArray
     *
     * @return array
     */
    public function toFromArrayProvider()
    {
        $filtersArray = $this->getFiltersArray([
            'age' => 18,
            'csdl' => 'fb.content any "iphone"'
        ]);
        $filtersObj = $this->getFiltersObject($filtersArray);
        $filtersString = $this->getFiltersString($filtersObj);

        $secondaryRecordingFiltersArray = $this->getFiltersArray([
            'age' => 18,
            'csdl' => 'fb.content any "iphone"'
        ], true);
        $secondaryRecordingFiltersObj = $this->getFiltersObject($secondaryRecordingFiltersArray, true);
        $secondaryRecordingFiltersString = $this->getFiltersString($secondaryRecordingFiltersObj, true);

        $dimensionsArray = [
            ['target' => 'fb.author.gender', 'threshold' => 100],
            ['target' => 'fb.author.age']
        ];
        $dimensionsString = '[{"target":"fb.author.gender","cardinality":null,"label":null,"threshold":100},'
        . '{"target":"fb.author.age","cardinality":null,"label":null,"threshold":null}]';

        $dimensions = new DimensionCollection();
        $dimensions->addDimension(
            new Dimension($dimensionsArray[0]['target'], null, null, $dimensionsArray[0]['threshold'])
        );
        $dimensions->addDimension(
            new Dimension($dimensionsArray[1]['target'])
        );

        return [
            [
                // with default data
                'data' => [
                    'id' => 10,
                    'workbook_id' => 20,
                    'name' => 'newName',
                    'rank' => 1,
                    'secondary_recording_id' => 456,
                    'secondary_recording_filters' => $secondaryRecordingFiltersString,
                    'baseline_dataset_id' => 789,
                    'filters' => $filtersString,
                    'dimensions' => $dimensionsString,
                    'start' => 123456,
                    'end' => 123456,
                    'parent_worksheet_id' => null,
                    'display_option' => '{}',
                    'created_at' => 123456789,
                    'updated_at' => 223456789,
                ],
                'getters' => [
                    'getId' => 10,
                    'getWorkbookId' => 20,
                    'getName' => 'newName',
                    'getRank' => 1,
                    'getComparison' => ChartGenerator::MODE_COMPARE,
                    'getMeasurement' => ChartGenerator::MEASURE_UNIQUE_AUTHORS,
                    'getChartType' => Chart::TYPE_TORNADO,
                    'getAnalysisType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'getSecondaryRecordingId' => 456,
                    'getSecondaryRecordingFilters' => $secondaryRecordingFiltersObj,
                    'getBaselineDataSetId' => 789,
                    'getFilters' => $filtersObj,
                    'getDimensions' => $dimensions,
                    'getStart' => 123456,
                    'getEnd' => 123456,
                    'getParentWorksheetId' => null,
                    'getDisplayOptions' => null,
                    'getCreatedAt' => 123456789,
                    'getUpdatedAt' => 223456789
                ],
                'expected' => [
                    'id' => 10,
                    'workbook_id' => 20,
                    'name' => 'newName',
                    'rank' => 1,
                    'comparison' => ChartGenerator::MODE_COMPARE,
                    'measurement' => ChartGenerator::MEASURE_UNIQUE_AUTHORS,
                    'chart_type' => Chart::TYPE_TORNADO,
                    'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'secondary_recording_id' => 456,
                    'secondary_recording_filters' => $secondaryRecordingFiltersString,
                    'baseline_dataset_id' => 789,
                    'filters' => $filtersString,
                    'dimensions' => $dimensionsString,
                    'start' => 123456,
                    'end' => 123456,
                    'parent_worksheet_id' => null,
                    'display_options' => '{}',
                    'created_at' => 123456789,
                    'updated_at' => 223456789
                ],
            ],
            [
                'data' => [
                    'id' => 10,
                    'workbook_id' => 20,
                    'name' => 'newName',
                    'rank' => 1,
                    'comparison' => ChartGenerator::MODE_BASELINE,
                    'measurement' => ChartGenerator::MEASURE_INTERACTIONS,
                    'chart_type' => Chart::TYPE_TORNADO,
                    'analysis_type' => Analysis::TYPE_TIME_SERIES,
                    'secondary_recording_id' => 456,
                    'secondary_recording_filters' => $secondaryRecordingFiltersString,
                    'baseline_dataset_id' => 789,
                    'filters' => $filtersString,
                    'dimensions' => $dimensionsString,
                    'start' => 123456,
                    'end' => 123456,
                    'parent_worksheet_id' => 20,
                    'display_options' => '{"sort":"label:desc","outliers":false}',
                    'created_at' => 123456789,
                    'updated_at' => 123456780
                ],
                'getters' => [
                    'getId' => 10,
                    'getWorkbookId' => 20,
                    'getName' => 'newName',
                    'getRank' => 1,
                    'getComparison' => ChartGenerator::MODE_BASELINE,
                    'getMeasurement' => ChartGenerator::MEASURE_INTERACTIONS,
                    'getChartType' => Chart::TYPE_TORNADO,
                    'getAnalysisType' => Analysis::TYPE_TIME_SERIES,
                    'getSecondaryRecordingId' => 456,
                    'getSecondaryRecordingFilters' => $secondaryRecordingFiltersObj,
                    'getBaselineDataSetId' => 789,
                    'getFilters' => $filtersObj,
                    'getDimensions' => $dimensions,
                    'getStart' => 123456,
                    'getEnd' => 123456,
                    'getParentWorksheetId' => 20,
                    'getDisplayOptions' => json_decode('{"sort":"label:desc","outliers":false}'),
                    'getCreatedAt' => 123456789,
                    'getUpdatedAt' => 123456780
                ],
                'expected' => [
                    'id' => 10,
                    'workbook_id' => 20,
                    'name' => 'newName',
                    'rank' => 1,
                    'comparison' => ChartGenerator::MODE_BASELINE,
                    'measurement' => ChartGenerator::MEASURE_INTERACTIONS,
                    'chart_type' => Chart::TYPE_TORNADO,
                    'analysis_type' => Analysis::TYPE_TIME_SERIES,
                    'secondary_recording_id' => 456,
                    'secondary_recording_filters' => $secondaryRecordingFiltersString,
                    'baseline_dataset_id' => 789,
                    'filters' => $filtersString,
                    'dimensions' => $dimensionsString,
                    'start' => 123456,
                    'end' => 123456,
                    'parent_worksheet_id' => 20,
                    'display_options' => '{"sort":"label:desc","outliers":false}',
                    'created_at' => 123456789,
                    'updated_at' => 123456780
                ],
            ],
            [
                'data' => [
                    'id' => 10
                ],
                'getters' => [
                    'getId' => 10,
                    'getWorkbookId' => null,
                    'getName' => null,
                    'getRank' => null,
                    'getComparison' => ChartGenerator::MODE_COMPARE,
                    'getMeasurement' => ChartGenerator::MEASURE_UNIQUE_AUTHORS,
                    'getChartType' => Chart::TYPE_TORNADO,
                    'getAnalysisType' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'getSecondaryRecordingId' => null,
                    'getSecondaryRecordingFilters' => null,
                    'getBaselineDataSetId' => null,
                    'getFilters' => null,
                    'getRawFilters' => '{}',
                    'getDimensions' => null,
                    'getStart' => null,
                    'getEnd' => null,
                    'getParentWorksheetId' => null,
                    'getDisplayOptions' => null,
                    'getRawDisplayOptions' => '{}',
                    'getCreatedAt' => null,
                    'getUpdatedAt' => null
                ],
                'expected' => [
                    'id' => 10,
                    'workbook_id' => null,
                    'name' => null,
                    'rank' => null,
                    'comparison' => ChartGenerator::MODE_COMPARE,
                    'measurement' => ChartGenerator::MEASURE_UNIQUE_AUTHORS,
                    'chart_type' => Chart::TYPE_TORNADO,
                    'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                    'secondary_recording_id' => null,
                    'secondary_recording_filters' => '{}',
                    'baseline_dataset_id' => null,
                    'filters' => '{}',
                    'dimensions' => null,
                    'start' => null,
                    'end' => null,
                    'parent_worksheet_id' => null,
                    'display_options' => '{}',
                    'created_at' => null,
                    'updated_at' => null
                ],
            ]
        ];
    }

    /**
     * @dataProvider toFromArrayProvider
     *
     * @covers       ::loadFromArray
     * @covers       ::toArray
     * @covers       ::getRawFilters
     * @covers       ::setRawFilters
     * @covers       ::getRawDimensions
     *
     * @param array $data
     * @param array $getters
     * @param array $expected
     */
    public function testToFromArray(array $data, array $getters, array $expected)
    {
        $obj = new Worksheet();
        $obj->loadFromArray($data);

        foreach ($getters as $getter => $value) {
            $this->assertEquals($value, $obj->$getter(), sprintf('Invalid result for %s', $getter));
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
            // in JSON we want to have an array here
            $item['expected']['dimensions'] = json_decode($item['expected']['dimensions'], true);
            // in JSON we want to have an array here, not double JSON encoded string
            $item['expected']['secondary_recording_filters'] = json_decode(
                $item['expected']['secondary_recording_filters']
            );
            // in JSON we want to have an array here, not double JSON encoded string
            $item['expected']['filters'] = json_decode($item['expected']['filters']);
            $item['expected']['display_options'] = json_decode($item['expected']['display_options']);

            $item['expected']['span'] = 1;
            $item['expected']['interval'] = 'day';

            $item['expected'] = json_encode($item['expected']);
        }
        return $data;
    }

    /**
     * @dataProvider toJsonProvider
     *
     * @covers ::jsonSerialize
     *
     * @param array  $data
     * @param string $expected
     */
    public function testJsonSerialization(array $data, $expected)
    {
        $obj = new Worksheet();
        $obj->loadFromArray($data);

        $this->assertEquals($expected, json_encode($obj));
    }

    /**
     * @dataProvider toFromArrayProvider
     *
     * @covers       ::__clone
     *
     * @param array $data
     * @param array $getters
     * @param array $expected
     */
    public function testClone(array $data, array $getters, array $expected)
    {
        unset($getters);
        $obj = new Worksheet();
        $obj->loadFromArray($data);
        $expected['id'] = null;
        $expected['created_at'] = null;
        $expected['updated_at'] = null;
        $expected['rank'] = null;

        $newWorksheet = clone($obj);

        $this->assertEquals($expected, $newWorksheet->toArray());
    }

    /**
     * @covers ::setComparison
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionUnlessValidComparisonModeGiven()
    {
        $obj = new Worksheet();
        $obj->setComparison('noValid');
    }

    /**
     * @covers ::setMeasurement
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionUnlessValidMeasurementModeGiven()
    {
        $obj = new Worksheet();
        $obj->setMeasurement('noValid');
    }

    /**
     * @covers ::setChartType
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionUnlessValidChartTypeGiven()
    {
        $obj = new Worksheet();
        $obj->setChartType('noValid');
    }

    /**
     * @covers ::setAnalysisType
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionUnlessValidAnalysisTypeGiven()
    {
        $obj = new Worksheet();
        $obj->setAnalysisType('noValid');
    }

    /**
     * @covers ::setFilters
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionUnlessFiltersAsJsonStringGiven()
    {
        $obj = new Worksheet();
        $obj->setFilters('noValid');
    }

    /**
     * @covers ::setFilters
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionIfFiltersAsNumberGiven()
    {
        $obj = new Worksheet();
        $obj->setFilters(123);
    }

    /**
     * @covers ::setFilters
     */
    public function testSetTheFilterObjectIfNullGiven()
    {
        $obj = new Worksheet();
        $obj->setFilters(null);

        $this->assertInstanceOf('\StdClass', $obj->getFilters());
        $this->assertNull($obj->getFilters()->csdl);
    }

    /**
     * @covers ::setFilters
     */
    public function testExtendFiltersObjectWithEmptyCsdlPropertyIfNoneGiven()
    {
        $filters = new \StdClass();
        $filters->a = 1;

        $obj = new Worksheet();
        $obj->setFilters($filters);

        $this->assertInstanceOf('\StdClass', $obj->getFilters());
        $this->assertEquals($filters, $obj->getFilters());
        $this->assertNull($obj->getFilters()->csdl);
    }

    /**
     * @covers ::setFilters
     */
    public function testExtendFiltersArrayWithEmptyCsdlQuery()
    {
        $filters = ['a' => 'b', 'c' => 'd'];
        $obj = new Worksheet();
        $obj->setFilters($filters);

        $this->assertInstanceOf('\StdClass', $obj->getFilters());
        $this->assertEquals(
            (object)array_merge($filters, [
                'csdl' => null,
                'country' => null,
                'region' => null,
                'gender' => null,
                'age' => null,
                'generated_csdl' => null,
                'span' => null,
                'interval' => null,
                'keywords' => null,
                'links' => null
            ]),
            $obj->getFilters()
        );
        $this->assertNull($obj->getFilters()->csdl);
    }

    /**
     * @covers ::setFilters
     */
    public function testSetFiltersObjectFromJsonString()
    {
        $filters = '{"a": 123}';
        $filtersObj = new \StdClass();
        $filtersObj->a = 123;
        $filtersObj->csdl = null;
        $filtersObj->age = null;
        $filtersObj->country = null;
        $filtersObj->region = null;
        $filtersObj->gender = null;
        $filtersObj->generated_csdl = null;
        $filtersObj->span = null;
        $filtersObj->interval = null;
        $filtersObj->keywords = null;
        $filtersObj->links = null;

        $obj = new Worksheet();
        $obj->setFilters($filters);

        $this->assertInstanceOf('\StdClass', $obj->getFilters());
        $this->assertEquals($filtersObj, $obj->getFilters());
        $this->assertNull($obj->getFilters()->csdl);
    }

    /**
     * @covers ::setDimensions
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionUnlessValidDimensionsGiven()
    {
        $obj = new Worksheet();
        $obj->setDimensions(123);
    }

    /**
     * @covers ::getFilter
     */
    public function testGettingSingleFilter()
    {
        $obj = new Worksheet();
        $this->assertNull($obj->getFilter('noExisting'));
        $this->assertNull($obj->getFilter(null));

        $obj->setFilters(['csdl' => 'select * from', 'filterA' => 'B']);

        $this->assertNull($obj->getFilter('noExisting'));
        $this->assertEquals('select * from', $obj->getFilter('csdl'));
        $this->assertEquals('B', $obj->getFilter('filterA'));
    }

    /**
     * @covers ::setSecondaryRecordingFilters
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionUnlessSecondaryRecordingFiltersAsJsonStringGiven()
    {
        $obj = new Worksheet();
        $obj->setSecondaryRecordingFilters('noValid');
    }

    /**
     * @covers ::setSecondaryRecordingFilters
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionIfSecondaryRecordingFiltersAsNumberGiven()
    {
        $obj = new Worksheet();
        $obj->setSecondaryRecordingFilters(123);
    }

    /**
     * @covers ::setSecondaryRecordingFilters
     */
    public function testSetTheSecondaryRecordingFilterObjectIfNullGiven()
    {
        $obj = new Worksheet();
        $obj->setSecondaryRecordingFilters(null);

        $this->assertInstanceOf('\StdClass', $obj->getSecondaryRecordingFilters());
        $this->assertNull($obj->getSecondaryRecordingFilters()->csdl);
    }

    /**
     * @covers ::setSecondaryRecordingFilters
     * @covers ::getSecondaryRecordingFilters
     */
    public function testExtendSecondaryRecordingFiltersObjectWithEmptyCsdlPropertyIfNoneGiven()
    {
        $filters = new \StdClass();
        $filters->a = 1;

        $obj = new Worksheet();
        $obj->setSecondaryRecordingFilters($filters);

        $this->assertInstanceOf('\StdClass', $obj->getSecondaryRecordingFilters());
        $this->assertEquals($filters, $obj->getSecondaryRecordingFilters());
        $this->assertNull($obj->getSecondaryRecordingFilters()->csdl);
    }

    /**
     * @covers ::setSecondaryRecordingFilters
     * @covers ::getSecondaryRecordingFilters
     */
    public function testExtendSecondaryRecordingFiltersArrayWithEmptyCsdlQuery()
    {
        $filters = ['a' => 'b', 'c' => 'd'];
        $obj = new Worksheet();
        $obj->setSecondaryRecordingFilters($filters);

        $this->assertInstanceOf('\StdClass', $obj->getSecondaryRecordingFilters());
        $this->assertEquals(
            (object)array_merge($filters, [
                'csdl' => null,
                'country' => null,
                'region' => null,
                'gender' => null,
                'age' => null,
                'generated_csdl' => null,
                'start' => null,
                'end' => null,
                'keywords' => null,
                'links' => null,
            ]),
            $obj->getSecondaryRecordingFilters()
        );
        $this->assertNull($obj->getSecondaryRecordingFilters()->csdl);
    }

    /**
     * @covers ::setSecondaryRecordingFilters
     * @covers ::getSecondaryRecordingFilters
     */
    public function testSetSecondaryRecordingFiltersObjectFromJsonString()
    {
        $filters = '{"a": 123}';
        $filtersObj = new \StdClass();
        $filtersObj->a = 123;
        $filtersObj->csdl = null;
        $filtersObj->age = null;
        $filtersObj->country = null;
        $filtersObj->region = null;
        $filtersObj->gender = null;
        $filtersObj->generated_csdl = null;
        $filtersObj->start = null;
        $filtersObj->end = null;
        $filtersObj->keywords = null;
        $filtersObj->links = null;

        $obj = new Worksheet();
        $obj->setSecondaryRecordingFilters($filters);

        $this->assertInstanceOf('\StdClass', $obj->getSecondaryRecordingFilters());
        $this->assertEquals($filtersObj, $obj->getSecondaryRecordingFilters());
        $this->assertNull($obj->getSecondaryRecordingFilters()->csdl);
    }

    /**
     * @covers ::getSecondaryRecordingFilter
     */
    public function testGettingSingleSecondaryRecordingFilter()
    {
        $obj = new Worksheet();
        $this->assertNull($obj->getSecondaryRecordingFilters('noExisting'));
        $this->assertNull($obj->getSecondaryRecordingFilter(null));

        $obj->setSecondaryRecordingFilters(['csdl' => 'select * from', 'filterA' => 'B']);

        $this->assertNull($obj->getSecondaryRecordingFilter('noExisting'));
        $this->assertEquals('select * from', $obj->getSecondaryRecordingFilter('csdl'));
        $this->assertEquals('B', $obj->getSecondaryRecordingFilter('filterA'));
    }

    protected function getFiltersArray(array $data = [], $secondary = false)
    {
        $filters = array(
            'keywords' => null,
            'links' => null,
            'country' => null,
            'region' => null,
            'gender' => null,
            'age' => null,
            'csdl' => null,
            'generated_csdl' => null
        );
        if ($secondary) {
            $filters['start'] = null;
            $filters['end'] = null;
        } else {
            $filters['span'] = null;
            $filters['interval'] = null;
        }
        $filters = array_merge($filters, $data);
        return $filters;
    }

    protected function getFiltersObject(array $filters = array(), $secondary = false)
    {
        $filters = $this->getFiltersArray($filters, $secondary);
        $filtersObj = new \StdClass();
        foreach ($filters as $key => $value) {
            $filtersObj->{$key} = $value;
        }
        return $filtersObj;
    }

    protected function getFiltersString($filters = null, $secondary = false)
    {
        $filters = $filters === null ? $this->getFiltersArray([], $secondary) : $filters;
        return json_encode($filters);
    }
}
