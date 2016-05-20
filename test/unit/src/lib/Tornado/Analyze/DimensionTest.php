<?php

namespace Test\Tornado\Analyze;

use Tornado\Analyze\Dimension;

/**
 * DimensionTest
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
 * @coversDefaultClass \Tornado\Analyze\Dimension
 */
class DimensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * DataProvider for testConstruct
     *
     * @return array
     */
    public function constructProvider()
    {
        return [
            [
                "target" => "fb.age",
                "cardinality" => 10,
                "threshold" => 100,
                "label" => "Age"
            ],
            [
                "target" => "fb.gender",
                "cardinality" => null,
                "threshold" => null,
                "label" => "Gender"
            ]
        ];
    }

    /**
     * @dataProvider constructProvider
     *
     * @covers ::__construct
     * @covers ::getTarget
     *
     * @param string       $target
     * @param integer|null $cardinality
     * @param string       $label
     */
    public function testConstruct($target, $cardinality, $threshold, $label)
    {
        $obj = new Dimension($target, $cardinality, $label, $threshold);
        $this->assertEquals($target, $obj->getTarget());
        $this->assertEquals($cardinality, $obj->getCardinality());
        $this->assertEquals($threshold, $obj->getThreshold());
        $this->assertEquals($label, $obj->getLabel());
    }

    /**
     * DataProvider for testGetSetCardinality
     *
     * @return array
     */
    public function getSetCardinalityProvider()
    {
        return [
            [
                "cardinality" => 20,
                "expected" => 20
            ],
            [
                "cardinality" => null,
                "expected" => null
            ],
        ];
    }

    /**
     * @dataProvider getSetCardinalityProvider
     *
     * @covers ::getCardinality
     * @covers ::setCardinality
     *
     * @param integer|null $cardinality
     * @param integer|null $expected
     */
    public function testGetSetCardinality($cardinality, $expected)
    {
        $obj = new Dimension('my.target');
        $this->assertNull($obj->getCardinality());
        $obj->setCardinality($cardinality);
        $this->assertEquals($expected, $obj->getCardinality());
    }

    /**
     * DataProvider for testToFromArray
     *
     * @return array
     */
    public function toJsonProvider()
    {
        return [
            [
                'data' => [
                    "target" => "fb.age",
                    "cardinality" => 10,
                    "label" => "Age",
                    "threshold" => 100
                ],
                'expected' => '{"target":"fb.age","cardinality":10,"label":"Age","threshold":100}'
            ],
            [
                'data' => [
                    "target" => "fb.gender",
                    "cardinality" => 20,
                    "threshold" => null,
                    "label" => "Gender Label"
                ],
                'expected' => '{"target":"fb.gender","cardinality":20,"label":"Gender Label","threshold":null}'
            ]
        ];
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
        $obj = new Dimension($data['target'], $data['cardinality'], $data['label'], $data['threshold']);
        $this->assertEquals($expected, json_encode($obj));
    }

    /**
     * @covers ::__toString
     */
    public function testToString()
    {
        $obj = new Dimension('test', 10, 'test', 10);
        $this->assertEquals('test', (string)$obj);
    }
}
