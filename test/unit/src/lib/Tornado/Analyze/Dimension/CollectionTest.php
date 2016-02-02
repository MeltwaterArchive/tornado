<?php

namespace Test\Tornado\Analyze;

use Tornado\Analyze\Dimension\Collection;
use Tornado\Analyze\Dimension;

use \Mockery;

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
 * @covers      \Tornado\Analyze\Dimension\Collection
 */
class CollectionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * DataProvider for testSetGetDimensions()
     *
     * @return array
     */
    public function setGetDimensionsProvider()
    {
        $dimA = new Dimension('test.a', 1);
        $dimB = new Dimension('test.b', 2);
        $dimC = new Dimension('test.c', 3);
        $dimD = new Dimension('test.d');
        $dimE = new Dimension('test.e');

        return [
            [ // 0
                "dimensions" => [$dimA, $dimB, $dimC],
                "mode" => Collection::ORDER_NATURAL,
                "expected" => [$dimA, $dimB, $dimC]
            ],
            [ // 1
                "dimensions" => [$dimC, $dimA, $dimB],
                "mode" => Collection::ORDER_NATURAL,
                "expected" => [$dimC, $dimA, $dimB]
            ],
            [ // 2
                "dimensions" => [$dimC, $dimA, $dimB, $dimD],
                "mode" => Collection::ORDER_CARDINALITY_ASC,
                "expected" => [$dimA, $dimB, $dimC, $dimD]
            ],
            [ // 3
                "dimensions" => [$dimC, $dimA, $dimB, $dimE, $dimD],
                "mode" => Collection::ORDER_CARDINALITY_ASC,
                "expected" => [$dimA, $dimB, $dimC, $dimE, $dimD]
            ],
            [ // 4
                "dimensions" => [$dimC, $dimA, $dimD, $dimB],
                "mode" => Collection::ORDER_CARDINALITY_DESC,
                "expected" => [$dimD, $dimC, $dimB, $dimA]
            ],
            [ // 5
                "dimensions" => [$dimA, $dimE, $dimD, $dimB],
                "mode" => Collection::ORDER_CARDINALITY_DESC,
                "expected" => [$dimE, $dimD, $dimB, $dimA]
            ],
            [ // 6
                "dimensions" => [$dimA, $dimB, $dimC],
                "mode" => Collection::ORDER_TARGET_ASC,
                "expected" => [$dimA, $dimB, $dimC]
            ],
            [ // 7
                "dimensions" => [$dimA, $dimB, $dimC],
                "mode" => Collection::ORDER_TARGET_DESC,
                "expected" => [$dimC, $dimB, $dimA]
            ],
            [ // 8
                "dimensions" => [$dimA, $dimB, $dimC],
                "mode" => Collection::ORDER_LAST_FIRST,
                "expected" => [$dimC, $dimA, $dimB]
            ],
        ];
    }

    /**
     * @dataProvider setGetDimensionsProvider
     *
     * @covers  \Tornado\Analyze\Dimension\Collection::setDimensions
     * @covers  \Tornado\Analyze\Dimension\Collection::getDimensions
     * @covers  \Tornado\Analyze\Dimension\Collection::compareDimensions
     * @covers  \Tornado\Analyze\Dimension\Collection::getCount
     *
     * @param   array $dimensions
     * @param   string $mode
     * @param   array $expected
     */
    public function testSetGetDimensions(array $dimensions, $mode, array $expected)
    {
        $obj = new Collection([new Dimension('test.target')]);
        $obj->setDimensions($dimensions);
        $this->assertEquals($expected, $obj->getDimensions($mode));
        $this->assertEquals(count($expected), $obj->getCount());
    }

    /**
     * @covers  \Tornado\Analyze\Dimension\Collection::addDimension
     */
    public function testAddDimension()
    {
        $dimA = new Dimension('test.a', 1);
        $dimB = new Dimension('test.b', 2);
        $dimC = new Dimension('test.c', 3);

        $obj = new Collection([]);
        $obj->addDimension($dimA);
        $obj->addDimension($dimB);
        $this->assertEquals([$dimA, $dimB], $obj->getDimensions());

        $obj->addDimension($dimC);
        $this->assertEquals([$dimA, $dimB, $dimC], $obj->getDimensions());
    }

    /**
     * DataProvider for testIsSame
     *
     * @return array
     */
    public function isSameProvider()
    {
        return [
            [ // #0
                'from' => $this->getDimensionCollection(['target.a', 'target.b']),
                'to' => $this->getDimensionCollection(['target.b', 'target.a']),
                'expected' => true
            ],
            [ // #1
                'from' => $this->getDimensionCollection(['target.c', 'target.a', 'target.b']),
                'to' => $this->getDimensionCollection(['target.b', 'target.c', 'target.a']),
                'expected' => true
            ],
            [ // #2
                'from' => $this->getDimensionCollection(['target.a', 'target.b']),
                'to' => $this->getDimensionCollection(['target.b', 'target.c', 'target.a']),
                'expected' => false
            ],
        ];
    }

    /**
     * @dataProvider isSameProvider
     *
     * @covers \Tornado\Analyze\Dimension\Collection::isSame
     *
     * @param \Tornado\Analyze\Dimension\Collection $from
     * @param \Tornado\Analyze\Dimension\Collection $to
     *
     * @param boolean $expected
     */
    public function testIsSame(Collection $from, Collection $to, $expected)
    {
        $this->assertEquals($expected, $from->isSame($to));
    }

    /**
     * DataProvider for testIsSubset
     *
     * @return array
     */
    public function isSubsetProvider()
    {
        return [
            [ // #0
                'from' => $this->getDimensionCollection(['target.b', 'target.a']),
                'to' => $this->getDimensionCollection(['target.a', 'target.b']),
                'expected' => true
            ],
            [ // #1
                'from' => $this->getDimensionCollection(['target.b', 'target.a']),
                'to' => $this->getDimensionCollection(['target.c', 'target.a', 'target.b']),
                'expected' => true
            ],
            [ // #2
                'from' => $this->getDimensionCollection(['target.b', 'target.c', 'target.a']),
                'to' => $this->getDimensionCollection(['target.a', 'target.b']),
                'expected' => false
            ],
            [ // #3
                'from' => $this->getDimensionCollection(['target.b', 'target.c']),
                'to' => $this->getDimensionCollection(['target.d', 'target.b']),
                'expected' => false
            ],
        ];
    }

    /**
     * @dataProvider isSubsetProvider
     *
     * @covers \Tornado\Analyze\Dimension\Collection::isSubset
     *
     * @param \Tornado\Analyze\Dimension\Collection $from
     * @param \Tornado\Analyze\Dimension\Collection $to
     *
     * @param boolean $expected
     */
    public function testIsSubset(Collection $from, Collection $to, $expected)
    {
        $this->assertEquals($expected, $from->isSubset($to));
    }

    /**
     * DataProvider for testGetOrderedSubset
     *
     * @return array
     */
    public function getOrderedSubsetProvider()
    {
        return [
            [ // #0
                'from' => $this->getDimensionCollection(['target.a', 'target.b']),
                'to' => $this->getDimensionCollection(['target.b', 'target.a', 'target.c']),
                'expected' => $this->getDimensionCollection(['target.b', 'target.a'])
            ],
            [ // #1
                'from' => $this->getDimensionCollection(['target.a', 'target.d']),
                'to' => $this->getDimensionCollection(['target.b', 'target.a', 'target.c']),
                'expected' => $this->getDimensionCollection(['target.b', 'target.a']),
                'expectedException' => 'Tornado\Analyze\DataSet\IncompatibleDimensionsException',
            ]
        ];
    }

    /**
     * @dataProvider getOrderedSubsetProvider
     *
     * @covers \Tornado\Analyze\Dimension\Collection::getOrderedSubset
     *
     * @param \Tornado\Analyze\Dimension\Collection $from
     * @param \Tornado\Analyze\Dimension\Collection $to
     * @param \Tornado\Analyze\Dimension\Collection $expected
     * @param bool                                  $expectedException
     */
    public function testGetOrderedSubset(
        Collection $from,
        Collection $to,
        Collection $expected,
        $expectedException = false
    ) {
        if ($expectedException) {
            $this->setExpectedException($expectedException);
        }
        $out = $from->getOrderedSubset($to);

        $this->assertTrue($expected->isSame($out));
        $outDimensions = $out->getDimensions();
        $this->assertEquals(count($expected->getDimensions()), count($outDimensions));
        foreach ($expected->getDimensions() as $idx => $dimension) {
            $this->assertEquals($dimension->getTarget(), $outDimensions[$idx]->getTarget());
        }
    }

    /**
     * @covers \Tornado\Analyze\Dimension\Collection::removeElement
     */
    public function testRemoveElement()
    {
        $dimA = new Dimension('test.a', 1);
        $dimB = new Dimension('test.b', 2);
        $dimC = new Dimension('test.c', 3);

        $col = new Collection([$dimA, $dimB, $dimC]);
        $this->assertCount(3, $col->getDimensions());
        $this->assertEquals([$dimA, $dimB, $dimC], $col->getDimensions());

        $this->assertEquals(false, $col->removeElement(-1));
        $this->assertEquals(false, $col->removeElement(10));

        $this->assertEquals(true, $col->removeElement(0));
        $this->assertCount(2, $col->getDimensions());
        $this->assertEquals([$dimB, $dimC], $col->getDimensions());

        $col = new Collection([$dimA, $dimB, $dimC]);

        $col->removeElement(1);
        $this->assertCount(2, $col->getDimensions());
        $this->assertEquals([$dimA, $dimC], $col->getDimensions());
    }

    /**
     * Gets a Dimension\Collection of the passed targets
     *
     * @param array $targets
     *
     * @return \Tornado\Analyze\Dimension\Collection
     */
    private function getDimensionCollection(array $targets)
    {
        $dimensionCollection = new Collection(array());
        foreach ($targets as $target) {
            $dim = Mockery::Mock('\Tornado\Analyze\Dimension');
            $dim->shouldReceive('getTarget')
                ->andReturn($target);
            $dimensionCollection->addDimension($dim);
        }
        return $dimensionCollection;
    }
}
