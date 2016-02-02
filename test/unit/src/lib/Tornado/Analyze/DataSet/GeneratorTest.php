<?php

namespace Test\Tornado\Analyze\DataSet;

use Tornado\Analyze\DataSet;
use Tornado\Analyze\DataSet\TimeSeries;
use Tornado\Analyze\DataSet\Generator;

use Tornado\Analyze\Dimension\Collection as DimensionCollection;
use Tornado\Analyze\Analysis\Collection as AnalysisCollection;
use Tornado\Analyze\Analysis;

use Mockery;

use Test\DataSift\FixtureLoader;

/**
 * GeneratorTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Analyze
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers      \Tornado\Analyze\DataSet\Generator
 */
class GeneratorTest extends \PHPUnit_Framework_TestCase
{

    use FixtureLoader;

    /**
     * DataProvider for testFromAnalyses
     *
     * @return array
     */
    public function fromAnalysesProvider()
    {
        return [
            [ // #0
                "analyses" => $this->getAnalysisCollection([$this->getAnalysis($this->loadJSONFixture('analysis1'))]),
                "dimensions" => $this->getDimensionCollection([
                    [
                        "target" => "fb.author.gender",
                    ],
                    [
                        "target" => "fb.author.age",
                    ]
                ]),
                "expected" => [
                    DataSet::KEY_MEASURE_INTERACTIONS => [
                        DataSet::KEY_VALUE => 36300,
                        DataSet::KEY_REDACTED => false,
                        "dimension:fb.author.gender" => [
                            "unknown" => [
                                DataSet::KEY_VALUE => 33000,
                                DataSet::KEY_REDACTED => false,
                                "dimension:fb.author.age" => [
                                    "unknown" => [
                                        DataSet::KEY_VALUE => 21100,
                                        DataSet::KEY_REDACTED => false,
                                    ],
                                    "18-24" => [
                                        DataSet::KEY_VALUE => 4100,
                                        DataSet::KEY_REDACTED => false,
                                    ],
                                ]
                            ],
                            "male" => [
                                DataSet::KEY_VALUE => 1600,
                                DataSet::KEY_REDACTED => false,
                                "dimension:fb.author.age" => [
                                    "unknown" => [
                                        DataSet::KEY_VALUE => 21100,
                                        DataSet::KEY_REDACTED => false,
                                    ],
                                    "18-24" => [
                                        DataSet::KEY_VALUE => 4100,
                                        DataSet::KEY_REDACTED => false,
                                    ],
                                ]
                            ]
                        ]
                    ],
                    DataSet::KEY_MEASURE_UNIQUE_AUTHORS => [
                        DataSet::KEY_VALUE => 33000,
                        DataSet::KEY_REDACTED => false,
                        "dimension:fb.author.gender" => [
                            "unknown" => [
                                DataSet::KEY_VALUE => 31500,
                                DataSet::KEY_REDACTED => false,
                                "dimension:fb.author.age" => [
                                    "unknown" => [
                                        DataSet::KEY_VALUE => 19500,
                                        DataSet::KEY_REDACTED => false,
                                    ],
                                    "18-24" => [
                                        DataSet::KEY_VALUE => 3000,
                                        DataSet::KEY_REDACTED => false,
                                    ],
                                ]
                            ],
                            "male" => [
                                DataSet::KEY_VALUE => 1500,
                                DataSet::KEY_REDACTED => false,
                                "dimension:fb.author.age" => [
                                    "unknown" => [
                                        DataSet::KEY_VALUE => 19500,
                                        DataSet::KEY_REDACTED => false,
                                    ],
                                    "18-24" => [
                                        DataSet::KEY_VALUE => 3000,
                                        DataSet::KEY_REDACTED => false,
                                    ],
                                ]
                            ]
                        ]
                    ],
                ],
                'expectedType' => '\Tornado\Analyze\DataSet'
            ],
            [ // #1
                "analyses" => $this->getAnalysisCollection([$this->getAnalysis($this->loadJSONFixture('analysis2'))]),
                "dimensions" => $this->getDimensionCollection([
                    [
                        "target" => "fb.author.gender",
                    ],
                ]),
                "expected" => [
                    DataSet::KEY_MEASURE_INTERACTIONS => [
                        DataSet::KEY_VALUE => 36300,
                        DataSet::KEY_REDACTED => false,
                        "dimension:fb.author.gender" => [
                            "unknown" => [
                                DataSet::KEY_VALUE => 33000,
                                DataSet::KEY_REDACTED => false,
                            ],
                            "male" => [
                                DataSet::KEY_VALUE => 1600,
                                DataSet::KEY_REDACTED => false,
                            ]
                        ]
                    ],
                    DataSet::KEY_MEASURE_UNIQUE_AUTHORS => [
                        DataSet::KEY_VALUE => 33000,
                        DataSet::KEY_REDACTED => false,
                        "dimension:fb.author.gender" => [
                            "unknown" => [
                                DataSet::KEY_VALUE => 31500,
                                DataSet::KEY_REDACTED => false,
                            ],
                            "male" => [
                                DataSet::KEY_VALUE => 1500,
                                DataSet::KEY_REDACTED => false,
                            ]
                        ]
                    ],
                ],
                'expectedType' => '\Tornado\Analyze\DataSet'
            ],
            [ // #2
                "analyses" => $this->getAnalysisCollection([
                    $this->getAnalysis($this->loadJSONFixture('redacted_analysis'))
                ]),
                "dimensions" => $this->getDimensionCollection([
                    [
                        "target" => "fb.author.gender",
                    ],
                ]),
                "expected" => [
                    DataSet::KEY_MEASURE_INTERACTIONS => [
                        DataSet::KEY_VALUE => 0,
                        DataSet::KEY_REDACTED => true,
                    ],
                    DataSet::KEY_MEASURE_UNIQUE_AUTHORS => [
                        DataSet::KEY_VALUE => 0,
                        DataSet::KEY_REDACTED => true
                    ],
                ],
                'expectedType' => '\Tornado\Analyze\DataSet',
                'interval' => '',
                'span' => '',
                'expectedException' => '\Tornado\Analyze\DataSet\Generator\RedactedException'
            ],
            [ // #3
                "analyses" => $this->getAnalysisCollection([
                    $this->getAnalysis($this->loadJSONFixture('redacted_analysis2'))
                ]),
                "dimensions" => $this->getDimensionCollection([
                    [
                        "target" => "fb.author.gender",
                    ],
                    [
                        "target" => "fb.author.age",
                    ]
                ]),
                "expected" => [
                    DataSet::KEY_MEASURE_INTERACTIONS => [
                        DataSet::KEY_VALUE => 36300,
                        DataSet::KEY_REDACTED => false,
                        "dimension:fb.author.gender" => [
                            "unknown" => [
                                DataSet::KEY_VALUE => 33000,
                                DataSet::KEY_REDACTED => false,
                            ],
                            "male" => [
                                DataSet::KEY_VALUE => 1600,
                                DataSet::KEY_REDACTED => false,
                            ]
                        ]
                    ],
                    DataSet::KEY_MEASURE_UNIQUE_AUTHORS => [
                        DataSet::KEY_VALUE => 33000,
                        DataSet::KEY_REDACTED => false,
                        "dimension:fb.author.gender" => [
                            "unknown" => [
                                DataSet::KEY_VALUE => 31500,
                                DataSet::KEY_REDACTED => false,
                            ],
                            "male" => [
                                DataSet::KEY_VALUE => 1500,
                                DataSet::KEY_REDACTED => false,
                            ]
                        ]
                    ],
                ],
                'expectedType' => '\Tornado\Analyze\DataSet'
            ],
            [ // #4
                "analyses" => $this->getAnalysisCollection([
                    $this->getAnalysis(
                        $this->loadJSONFixture('redacted_analysis2'),
                        Analysis::TYPE_TIME_SERIES,
                        [
                            'getInterval' => 'hour',
                            'getSpan' => 1
                        ]
                    )
                ]),
                "dimensions" => $this->getDimensionCollection([
                    [
                        "target" => "fb.author.gender",
                    ],
                    [
                        "target" => "fb.author.age",
                    ]
                ]),
                "expected" => [
                    DataSet::KEY_MEASURE_INTERACTIONS => [
                        DataSet::KEY_VALUE => 36300,
                        DataSet::KEY_REDACTED => false,
                        "dimension:time" => [
                            "unknown" => [
                                DataSet::KEY_VALUE => 33000,
                                DataSet::KEY_REDACTED => false,
                            ],
                            "male" => [
                                DataSet::KEY_VALUE => 1600,
                                DataSet::KEY_REDACTED => false,
                            ]
                        ]
                    ],
                    DataSet::KEY_MEASURE_UNIQUE_AUTHORS => [
                        DataSet::KEY_VALUE => 33000,
                        DataSet::KEY_REDACTED => false,
                        "dimension:time" => [
                            "unknown" => [
                                DataSet::KEY_VALUE => 31500,
                                DataSet::KEY_REDACTED => false,
                            ],
                            "male" => [
                                DataSet::KEY_VALUE => 1500,
                                DataSet::KEY_REDACTED => false,
                            ]
                        ]
                    ],
                ],
                'expectedType' => '\Tornado\Analyze\DataSet\TimeSeries',
                'interval' => 'hour',
                'span' => 1
            ],
        ];
    }

    /**
     * @dataProvider fromAnalysesProvider
     *
     * @covers \Tornado\Analyze\DataSet\Generator::fromAnalyses
     * @covers \Tornado\Analyze\DataSet\Generator::getResultArray
     *
     * @param \Tornado\Analyze\Analysis\Collection $analyses
     * @param \Tornado\Analyze\Dimension\Collection $dimensions
     * @param array $expected
     * @param string $expectedType
     */
    public function testFromAnalyses(
        AnalysisCollection $analyses,
        DimensionCollection $dimensions,
        array $expected,
        $expectedType,
        $interval = null,
        $span = null,
        $expectedException = false
    ) {

        if ($expectedException) {
            $this->setExpectedException($expectedException);
        }

        $obj = new Generator();
        $dataSet = $obj->fromAnalyses($analyses, $dimensions);
        $this->assertInstanceOf($expectedType, $dataSet);
        $this->assertEquals($dimensions, $dataSet->getDimensions());
        $this->assertEquals($expected, $dataSet->getData());
        if ($dataSet instanceof TimeSeries) {
            $this->assertEquals($interval, $dataSet->getInterval());
            $this->assertEquals($span, $dataSet->getSpan());
        }
    }

    /**
     * Gets a mocked Analysis object for use in testing
     *
     * @param \stdClass $results
     *
     * @return \Tornado\Analyze\Analysis
     */
    private function getAnalysis(\stdClass $results, $type = Analysis::TYPE_FREQUENCY_DISTRIBUTION, array $getters = [])
    {
        $analysis = Mockery::mock('\Tornado\Analyze\Analysis');
        $getters = array_merge(
            [
                'getResults' => $results,
                'getType' => $type
            ],
            $getters
        );

        foreach ($getters as $getter => $return) {
            $analysis->shouldReceive($getter)->andReturn($return);
        }
        return $analysis;
    }

    /**
     * Gets a mocked Analysis\Collection for use in testing
     *
     * @param array $analyses
     *
     * @return \Tornado\Analyze\Analysis\Collection
     */
    private function getAnalysisCollection(array $analyses)
    {
        $analysisCollection = Mockery::mock('\Tornado\Analyze\Analysis\Collection');
        $analysisCollection->shouldReceive('getAnalyses')->andReturn($analyses);
        return $analysisCollection;
    }

    /**
     * Gets a mocked Dimension\Collection for use in testing
     *
     * @param array $dimensions
     *
     * @return \Tornado\Analyze\Dimension\Collection
     */
    private function getDimensionCollection(array $dimensions)
    {
        $dims = [];
        foreach ($dimensions as $dim) {
            $dimension = Mockery::mock('\Tornado\Analyze\Dimension');
            $dimension->shouldReceive('getTarget')->andReturn($dim['target']);
            if (isset($dim['cardinality'])) {
                $dimension->shouldReceive('getCardinality')->andReturn($dim['cardinality']);
            }
            $dims[] = $dimension;
        }

        $dimensionCollection = Mockery::mock('\Tornado\Analyze\Dimension\Collection');
        $dimensionCollection->shouldReceive('getDimensions')->andReturn($dims);

        return $dimensionCollection;
    }
}
