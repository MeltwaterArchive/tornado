<?php

namespace Test\Tornado\Project\Chart;

use Tornado\Analyze\Dimension;
use Tornado\Analyze\Dimension\Collection;
use Tornado\Project\Chart;
use Tornado\Project\Chart\NameGenerator;
use Tornado\Analyze\Dimension\Collection as DimensionCollection;

use Test\DataSift\ReflectionAccess;

/**
 * NameGeneratorTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Project\Chart
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Project\Chart\NameGenerator
 */
class NameGeneratorTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess;

    /**
     * DataProvider for charts
     *
     * @return array
     */
    public function chartsProvider()
    {
        $tornadoChart = new Chart();
        $tornadoChart->setType(Chart::TYPE_TORNADO);

        $barChart = new Chart();
        $barChart->setType('bar');

        $dimA = new Dimension('test.a', 1, 'test A');
        $dimB = new Dimension('test.b', 2, 'Test B');
        $dimC = new Dimension('test.c', 3, 'Test C');

        return [
            [
                "chart" => $tornadoChart,
                "dimensionsCol" => new DimensionCollection([$dimA, $dimB, $dimC]),
                "lowestDimVal" => 'test Custom',
                "expected" => 'Test Custom'
            ],
            [
                "chart" => $tornadoChart,
                "dimensionsCol" => new DimensionCollection([$dimA, $dimB, $dimC]),
                "lowestDimVal" => null,
                "expected" => 'Test A x Test B x Test C'
            ],
            [
                "chart" => $tornadoChart,
                "dimensionsCol" => new DimensionCollection([$dimA, $dimC]),
                "lowestDimVal" => 'test Custom',
                "expected" => 'Test A x Test C'
            ],
            [
                "chart" => $tornadoChart,
                "dimensionsCol" => new DimensionCollection([$dimA, $dimB]),
                "lowestDimVal" => 'test Custom',
                "expected" => 'Test A x Test B'
            ],
            [
                "chart" => $barChart,
                "dimensionsCol" => new DimensionCollection([$dimA, $dimB, $dimC]),
                "lowestDimVal" => 'test Custom',
                "expected" => 'Test A x Test B x Test C'
            ],
            [
                "chart" => $barChart,
                "dimensionsCol" => new DimensionCollection([$dimA, $dimB]),
                "lowestDimVal" => null,
                "expected" => 'Test A x Test B'
            ],
            [
                "chart" => $tornadoChart,
                "dimensionsCol" => new DimensionCollection([]),
                "lowestDimVal" => 'test Custom',
                "expected" => ''
            ],
            [
                "chart" => $barChart,
                "dimensionsCol" => new DimensionCollection([]),
                "lowestDimVal" => 'test Custom',
                "expected" => ''
            ],
            [
                "chart" => $tornadoChart,
                "dimensionsCol" => new DimensionCollection([$dimA]),
                "lowestDimVal" => 'test Custom',
                "expected" => 'Test A'
            ],
            [
                "chart" => $barChart,
                "dimensionsCol" => new DimensionCollection([$dimA]),
                "lowestDimVal" => 'test Custom',
                "expected" => 'Test A'
            ],
        ];
    }

    /**
     * @dataProvider chartsProvider
     *
     * @covers ::generate
     *
     * @param \Tornado\Project\Chart                $chart
     * @param \Tornado\Analyze\Dimension\Collection $dimensionsCol
     * @param string|null                           $lowestDimVal
     * @param                                       $expected
     */
    public function testGenerateChartName(Chart $chart, Collection $dimensionsCol, $lowestDimVal, $expected)
    {
        $nameGenerator = new NameGenerator();
        $this->assertEquals($expected, $nameGenerator->generate($chart, $dimensionsCol, $lowestDimVal));
    }

    /**
     * @covers ::generate
     */
    public function testGenerateChartNameWithClonedCollection()
    {
        $tornadoChart = new Chart();
        $tornadoChart->setType(Chart::TYPE_TORNADO);

        $barChart = new Chart();
        $barChart->setType('bar');

        $dimA = new Dimension('test.a', 1, 'test A');
        $dimB = new Dimension('test.b', 2, 'Test B');
        $dimC = new Dimension('test.c', 3, 'Test C');

        $dimensionsCol = new DimensionCollection([$dimA, $dimB, $dimC]);

        $nameGenerator = new NameGenerator();
        $this->assertEquals(
            'Test C',
            $nameGenerator->generate($tornadoChart, $dimensionsCol, 'test C')
        );
        $this->assertEquals(
            'Test A x Test B x Test C',
            $nameGenerator->generate($barChart, $dimensionsCol)
        );
    }

    /**
     * @covers ::generate
     */
    public function testGenerateChartNameWithoutLabels()
    {
        $tornadoChart = new Chart();
        $tornadoChart->setType(Chart::TYPE_TORNADO);

        $barChart = new Chart();
        $barChart->setType('bar');

        $dimA = new Dimension('test.a');
        $dimB = new Dimension('test.b', 1, 'Test B');
        $dimC = new Dimension('test.c');

        $dimensionsCol = new DimensionCollection([$dimA, $dimB, $dimC]);

        $nameGenerator = new NameGenerator();
        $this->assertEquals(
            'Test C',
            $nameGenerator->generate($tornadoChart, $dimensionsCol, 'test C')
        );
        $this->assertEquals('test.a x Test B x test.c', $nameGenerator->generate($barChart, $dimensionsCol));
    }

    /**
     * @covers ::generateTornadoName
     */
    public function testGenerateTornadoName()
    {
        $tornadoChart = new Chart();
        $tornadoChart->setType(Chart::TYPE_TORNADO);

        $barChart = new Chart();
        $barChart->setType('bar');

        $dimA = new Dimension('test.a');
        $dimB = new Dimension('test.b', 1, 'Test B');
        $dimC = new Dimension('test.c');

        $nameGenerator = new NameGenerator();

        #1
        $dimensionsCol = new DimensionCollection([$dimA, $dimB, $dimC]);
        $data = $this->invokeMethod($nameGenerator, 'generateTornadoName', [$dimensionsCol, 'unit']);
        $this->assertEquals('Unit', $data);

        #2
        $dimensionsCol = new DimensionCollection([$dimA, $dimB]);
        $data = $this->invokeMethod($nameGenerator, 'generateTornadoName', [$dimensionsCol, 'unit']);
        $this->assertEquals('test.a x Test B', $data);
    }

    /**
     * @covers ::getLabels
     */
    public function testGetLabels()
    {
        $tornadoChart = new Chart();
        $tornadoChart->setType(Chart::TYPE_TORNADO);

        $barChart = new Chart();
        $barChart->setType('bar');

        $dimA = new Dimension('test.a');
        $dimB = new Dimension('test.b', 1, 'Test B');
        
        $nameGenerator = new NameGenerator();

        #2
        $dimensionsCol = new DimensionCollection([$dimA, $dimB]);
        $data = $this->invokeMethod($nameGenerator, 'getLabels', [$dimensionsCol]);

        $this->assertInternalType('array', $data);
        $this->assertEquals(['test.a', 'Test B'], $data);
    }
}
