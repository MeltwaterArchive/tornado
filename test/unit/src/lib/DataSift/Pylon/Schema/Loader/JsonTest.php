<?php

namespace Test\DataSift\Pylon\Schema\Loader;

use DataSift\Pylon\Schema\Loader\Json;

use Test\DataSift\FixtureLoader;
use Test\DataSift\ReflectionAccess;

/**
 * JsonTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\DataSift\Pylon\Schema\Loader
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers      \DataSift\Pylon\Schema\Loader\Json
 */
class JsonTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess,
        FixtureLoader;

    /**
     * DataProvider for testConstruct
     */
    public function constructDataProvider()
    {
        return [
            [
                'paths' => 'path1',
                'logger' => null,
                'expectedPaths' => ['path1'],
                'expectedLogger' => '\Psr\Log\NullLogger'
            ],
            [
                'paths' => ['path1', 'path2'],
                'logger' => $this->getMockBuilder('\Monolog\Logger')
                    ->disableOriginalConstructor()
                    ->getMock(),
                'expectedPaths' => ['path1', 'path2'],
                'expectedLogger' => '\Monolog\Logger'
            ]
        ];
    }

    /**
     * @dataProvider constructDataProvider
     *
     * @covers       \DataSift\Pylon\Schema\Loader\Json::__construct
     *
     * @param string|string[] $paths
     * @param mixed           $logger
     * @param                 string []
     * @param string          $expectedLogger
     */
    public function testConstruct($paths, $logger, $expected, $expectedLogger)
    {
        $jsonLoader = new Json($paths, $logger);
        $this->assertInstanceOf('\Psr\Log\LoggerAwareInterface', $jsonLoader);

        $this->assertInstanceOf('\Symfony\Component\Config\FileLocator', $jsonLoader);
        $this->assertInstanceOf('\DataSift\Loader\LoaderInterface', $jsonLoader);

        $this->assertEquals($expected, $this->getPropertyValue($jsonLoader, 'paths'));
        $this->assertInstanceOf($expectedLogger, $this->getPropertyValue($jsonLoader, 'logger'));
    }

    /**
     * @covers \DataSift\Pylon\Schema\Loader\Json::load
     */
    public function testLoadSchemaUnlessItIsMissingInJson()
    {
        $path = $this->getDirPath() . '/fixtures/noSchema.json';

        $monologLogger = $this->getMockBuilder('\Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $jsonLoader = new Json($path, $monologLogger);

        $monologLogger->expects($this->once())
            ->method('alert')
            ->with(sprintf(
                '%s: Pylon Schema file "%s" does not contain required "schema" key.',
                get_class($jsonLoader) . '::load',
                $path
            ));

        $this->assertEquals([], $jsonLoader->load());
    }

    /**
     * @covers \DataSift\Pylon\Schema\Loader\Json::load
     */
    public function testLoadSchema()
    {
        $monologLogger = $this->getMockBuilder('\Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();
        $monologLogger->expects($this->never())
            ->method('alert');

        $jsonLoader = new Json(
            [
                $this->getDirPath() . '/fixtures/dummy.json',
                $this->getDirPath() . '/fixtures/dummy2.json'
            ],
            $monologLogger
        );

        $this->assertEquals($this->schemaProvider(), $jsonLoader->load());
    }

    /**
     * @covers \DataSift\Pylon\Schema\Loader\Json::load
     */
    public function testLoadSchemaFromSchemaAndSchemaLessFiles()
    {
        $monologLogger = $this->getMockBuilder('\Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $jsonLoader = new Json(
            [
                $this->getDirPath() . '/fixtures/dummy.json',
                $this->getDirPath() . '/fixtures/dummy2.json',
                $this->getDirPath() . '/fixtures/noSchema.json',
            ],
            $monologLogger
        );

        $monologLogger->expects($this->once())
            ->method('alert')
            ->with(sprintf(
                '%s: Pylon Schema file "%s" does not contain required "schema" key.',
                get_class($jsonLoader) . '::load',
                $this->getDirPath() . '/fixtures/noSchema.json'
            ));

        $this->assertEquals($this->schemaProvider(), $jsonLoader->load());
    }

    /**
     * Providers schema
     *
     * @return array
     */
    protected function schemaProvider()
    {
        return [
            [
                'target' => 'fb.author.id',
                'perms' => [],
                'is_mandatory' => true
            ],
            [
                'target' => 'fb.author.age',
                'cardinality' => 7,
                'description' => "One of '18-24'"
            ],
            [
                'target' => 'fb.author.gender',
                'perms' => [],
                'is_mandatory' => false
            ],
            [
                'target' => 'fb.author.country',
                'cardinality' => 249,
            ]
        ];
    }
}
