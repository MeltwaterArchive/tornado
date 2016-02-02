<?php

namespace Test\Tornado\Analyze\Dimension;

use \Mockery;

use DataSift\Pylon\Schema\Schema;

use Tornado\Analyze\Dimension\Factory;

use Test\DataSift\ReflectionAccess;

/**
 * FactoryTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Analyze\Dimension
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Analyze\Dimension\Factory
 */
class FactoryTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess;

    /**
     * @covers ::__construct
     * @covers ::getDimensionDefinition
     * @covers ::getDimensionCollection
     */
    public function testGetDimensionCollection()
    {
        $permissions = array();
        $objs = $this->getSchemaObjects();
        $schema = $this->getMock(
            '\DataSift\Pylon\Schema\Schema',
            ['findObjectByTarget'],
            [$objs]
        );

        $schema->expects($this->any())
            ->method('findObjectByTarget')
            ->with($this->anything(), $permissions)
            ->will(
                $this->returnCallback(
                    function ($target) use ($objs) {
                        if (isset($objs[$target])) {
                            return $objs[$target];
                        }

                        throw new \InvalidArgumentException('Unknown target!');
                    }
                )
            );

        $factory = new Factory($this->getProviderMock($schema));

        $dimensionCollection = $factory->getDimensionCollection(
            [
                ['target' => 'fb.author.id'],
                ['target' => 'fb.author.age', 'threshold' => 1000]
            ],
            null,
            $permissions
        );
        $this->assertInstanceOf('\Tornado\Analyze\Dimension\Collection', $dimensionCollection);
        $this->assertCount(2, $dimensionCollection->getDimensions());

        $dimsA = $dimensionCollection->getDimensions()[0];
        $this->assertInstanceOf('\Tornado\Analyze\Dimension', $dimsA);
        $this->assertNull($dimsA->getThreshold());
        $this->assertEquals('Author ID', $dimsA->getLabel());
        $this->assertNull($dimsA->getCardinality());

        $dimsB = $dimensionCollection->getDimensions()[1];
        $this->assertInstanceOf('\Tornado\Analyze\Dimension', $dimsB);
        $this->assertEquals(7, $dimsB->getThreshold());
        $this->assertEquals('fb.author.age', $dimsB->getTarget());
        $this->assertEquals('Author Age', $dimsB->getLabel());
        $this->assertEquals(7, $dimsB->getCardinality());
    }

    /**
     * @covers ::__construct
     * @covers ::getDimensionDefinition
     * @covers ::getDimensionCollection
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionUnlessValidDimensionDataGiven()
    {
        $factory = new Factory($this->getProviderMock());

        $factory->getDimensionCollection([
            ['target' => 'fb.author.id'],
            ['notarget' => 'fb.author.age', 'threshold' => 1000]
        ]);
    }

    /**
     * @covers ::__construct
     * @covers ::getDimensionDefinition
     * @covers ::getDimensionCollection
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionUnlessDimensionExists()
    {
        $factory = new Factory($this->getProviderMock());

        $factory->getDimensionCollection([
            ['target' => 'blablabla'],
            ['target' => 'fb.author.age', 'threshold' => 1000]
        ]);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getProviderMock(Schema $schema = null)
    {
        if (!$schema) {
            $schema = new Schema($this->getSchemaObjects());
        }

        $providerMock = $this->getMockBuilder('\DataSift\Pylon\Schema\Provider')
            ->disableOriginalConstructor()
            ->getMock();
        $providerMock->expects($this->once())
            ->method('getSchema')
            ->willReturn($schema);

        return $providerMock;
    }

    /**
     * @return array
     */
    protected function getSchemaObjects()
    {
        return [
            'fb.author.id' => [
                'target' => 'fb.author.id',
                'perms' => [],
                'label' => 'Author ID',
                'is_mandatory' => true
            ],
            'fb.author.age' => [
                'target' => 'fb.author.age',
                'cardinality' => 7,
                'label' => 'Author Age',
                'description' => "One of '18-24'"
            ],
            'fb.author.gender' => [
                'target' => 'fb.author.gender',
                'perms' => [],
                'label' => 'Author Gender',
                'is_mandatory' => false
            ],
            'fb.author.country' => [
                'target' => 'fb.author.country',
                'cardinality' => 249,
            ]
        ];
    }
}
