<?php

namespace Test\DataSift\Loader;

use DataSift\Loader\Json;

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
 * @package     \Test\DataSift\Loader
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers      \DataSift\Loader\Json
 */
class JsonTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess,
        FixtureLoader;

    /**
     * DataProvider for testSupports
     */
    public function supportsDataProvider()
    {
        return [
            [
                'resource' => 'jsonFile.json',
                'type' => null,
                'expected' => true
            ],
            [
                'resource' => 'jsonFile.yml',
                'type' => 'json',
                'expected' => true
            ],
            [
                'resource' => 'jsonFile.yml',
                'type' => null,
                'expected' => false
            ],
            [
                'resource' => [],
                'type' => null,
                'expected' => false
            ],
            [
                'resource' => [],
                'type' => 'yml',
                'expected' => false
            ]
        ];
    }

    /**
     * DataProvider for testConstruct
     */
    public function constructDataProvider()
    {
        return [
            [
                'paths' => 'path1',
                'expected' => ['path1']
            ],
            [
                'paths' => ['path1', 'path2'],
                'expected' => ['path1', 'path2']
            ]
        ];
    }

    /**
     * @dataProvider constructDataProvider
     *
     * @covers       \DataSift\Loader\Json::__construct
     *
     * @param string|string[] $paths
     * @param                 string []
     */
    public function testConstruct($paths, $expected)
    {
        $jsonLoader = new Json($paths);

        $this->assertInstanceOf('\Symfony\Component\Config\FileLocator', $jsonLoader);
        $this->assertInstanceOf('\DataSift\Loader\LoaderInterface', $jsonLoader);

        $this->assertEquals($expected, $this->getPropertyValue($jsonLoader, 'paths'));
    }

    /**
     * @dataProvider supportsDataProvider
     *
     * @covers       \DataSift\Loader\Json::supports
     *
     * @param mixed   $resource
     * @param mixed   $type
     * @param boolean $expected
     */
    public function testSupports($resource, $type, $expected)
    {
        $jsonLoader = new Json();
        $this->assertEquals($expected, $jsonLoader->supports($resource, $type));
    }

    /**
     * @covers \DataSift\Loader\Json::load
     *
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFileContentUnlessNotSupportedFileGiven()
    {
        $jsonLoader = new Json($this->getDirPath() . '/fixtures/dummy.notjsonext');
        $jsonLoader->load();
    }

    /**
     * @covers \DataSift\Loader\Json::load
     *
     * @expectedException \RuntimeException
     */
    public function testLoadFileContentUnlessCorruptedFileGiven()
    {
        $jsonLoader = new Json($this->getDirPath() . '/fixtures/corrupted.json');
        $jsonLoader->load();
    }

    /**
     * @covers \DataSift\Loader\Json::load
     */
    public function testLoadFileContent()
    {
        $path = $this->getDirPath() . '/fixtures/dummy.json';
        $jsonLoader = new Json($path);
        $this->assertEquals(
            [
                $path => [
                    'source' => 'facebook',
                    'schema' => [
                        [
                            'target' => 'fb.author.id',
                            'perms' => [],
                            'is_mandatory' => true
                        ],
                        [
                            'target' => 'fb.author.age',
                            'cardinality' => 7,
                            'description' => "One of '18-24'"
                        ]
                    ]
                ]
            ],
            $jsonLoader->load()
        );

    }
}
