<?php

namespace Test\DataSift\Pylon\Schema;

use DataSift\Pylon\Schema\Provider;

/**
 * ProviderTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\DataSift\Pylon\Schema
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers      \DataSift\Pylon\Schema\Provider
 */
class ProviderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers \DataSift\Pylon\Schema\Provider::__construct
     *
     * @expectedException \InvalidArgumentException
     */
    public function testThrowExceptionUnlessAtLeastOneLoaderGiven()
    {
        new Provider([], $this->getCacheClientMock());
    }

    /**
     * @covers \DataSift\Pylon\Schema\Provider::__construct
     * @covers \DataSift\Pylon\Schema\Provider::getSchema
     */
    public function testLoadingSchemaFromLoaderUnlessStoredInCache()
    {
        list($loaderObjects, $providerSchema) = $this->getSchemaMockObjects();

        $loaderMock = $this->getMockObject('\DataSift\Pylon\Schema\LoaderInterface');
        $loaderMock->expects($this->once())
            ->method('load')
            ->willReturn($loaderObjects);

        $cacheClientMock = $this->getCacheClientMock();
        $cacheClientMock->expects($this->once())
            ->method('contains')
            ->with(Provider::PYLON_SCHEMA_CACHE_ID)
            ->willReturn(false);
        $cacheClientMock->expects($this->never())
            ->method('fetch');
        $cacheClientMock->expects($this->once())
            ->method('save')
            ->with(Provider::PYLON_SCHEMA_CACHE_ID, $providerSchema);

        $provider = new Provider([$loaderMock], $cacheClientMock);
        $this->assertInstanceOf('\DataSift\Pylon\Schema\Schema', $provider->getSchema());
        $this->assertCount(count($loaderObjects), $provider->getSchema()->getObjects());
        $this->assertEquals($providerSchema, $provider->getSchema()->getObjects());
    }

    /**
     * @covers \DataSift\Pylon\Schema\Provider::__construct
     * @covers \DataSift\Pylon\Schema\Provider::getSchema
     */
    public function testMergingSchemaFromMultipleLoaders()
    {
        list($loaderObjects) = $this->getSchemaMockObjects();

        $loaderMock = $this->getMockObject('\DataSift\Pylon\Schema\LoaderInterface');
        $loaderMock->expects($this->once())
            ->method('load')
            ->willReturn($loaderObjects);
        $loaderMock2 = $this->getMockObject('\DataSift\Pylon\Schema\Loader\Json');
        $loaderMock2->expects($this->once())
            ->method('load')
            ->willReturn([
                [
                    'target' => 'fb.author.id',
                    'label' => 'Author ID'
                ],
                [
                    'target' => 'fb.author.age',
                    'label' => 'Author Age'
                ]
            ]);

        $provider = new Provider([$loaderMock, $loaderMock2], $this->getCacheClientMock());

        $expected = [
            'fb.author.id' => [
                'target' => 'fb.author.id',
                'perms' => [],
                'is_mandatory' => true,
                'label' => 'Author ID'
            ],
            'fb.author.age' => [
                'target' => 'fb.author.age',
                'cardinality' => 7,
                'description' => "One of '18-24'",
                'label' => 'Author Age'
            ],
            'fb.author.gender' => [
                'target' => 'fb.author.gender',
                'perms' => [],
                'is_mandatory' => false
            ],
            'fb.author.country' => [
                'target' => 'fb.author.country',
                'cardinality' => 249,
            ]
        ];

        $this->assertInstanceOf('\DataSift\Pylon\Schema\Schema', $provider->getSchema());
        $this->assertCount(count($expected), $provider->getSchema()->getObjects());
        $this->assertEquals($expected, $provider->getSchema()->getObjects());
        $this->assertArrayHasKey('label', $provider->getSchema()->getObjects()['fb.author.id']);
        $this->assertArrayHasKey('label', $provider->getSchema()->getObjects()['fb.author.age']);
    }

    /**
     * @covers \DataSift\Pylon\Schema\Provider::__construct
     * @covers \DataSift\Pylon\Schema\Provider::getSchema
     */
    public function testReadingDefinitionUnlessItMissesTarget()
    {
        $definition = [
            [
            'label' => 'Author ID'
            ]
        ];

        $monologLogger = $this->getMockBuilder('\Monolog\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $recording = null;

        $loaderMock = $this->getMockObject('\DataSift\Pylon\Schema\LoaderInterface');
        $loaderMock->expects($this->once())
            ->method('load')
            ->with($recording)
            ->willReturn($definition);

        $monologLogger->expects($this->once())
            ->method('alert')
            ->with(sprintf(
                '%s: Pylon Schema definition object does not contain required "target" data. Object: "%s".',
                'DataSift\Pylon\Schema\Provider::readLoaders',
                json_encode($definition[0])
            ));

        $provider = new Provider([$loaderMock], $this->getCacheClientMock(), $monologLogger);
        $provider->getSchema($recording);
    }

    /**
     * @covers \DataSift\Pylon\Schema\Provider::__construct
     * @covers \DataSift\Pylon\Schema\Provider::getSchema
     */
    public function testLoadingSchemaFromCache()
    {
        list($loaderObjects, $providerSchema) = $this->getSchemaMockObjects();

        $loaderMock = $this->getMockObject('\DataSift\Pylon\Schema\LoaderInterface');
        $loaderMock->expects($this->never())
            ->method('load')
            ->willReturn($loaderObjects);

        $cacheClientMock = $this->getCacheClientMock();
        $cacheClientMock->expects($this->once())
            ->method('contains')
            ->with(Provider::PYLON_SCHEMA_CACHE_ID)
            ->willReturn(true);
        $cacheClientMock->expects($this->once())
            ->method('fetch')
            ->with(Provider::PYLON_SCHEMA_CACHE_ID)
            ->willReturn($providerSchema);
        $cacheClientMock->expects($this->never())
            ->method('save');

        $provider = new Provider([$loaderMock], $cacheClientMock);
        $this->assertInstanceOf('\DataSift\Pylon\Schema\Schema', $provider->getSchema());
        $this->assertCount(count($providerSchema), $provider->getSchema()->getObjects());
        $this->assertEquals($providerSchema, $provider->getSchema()->getObjects());
    }

    /**
     * @covers \DataSift\Pylon\Schema\Provider::__construct
     * @covers \DataSift\Pylon\Schema\Provider::reload
     * @covers \DataSift\Pylon\Schema\Provider::getSchema
     */
    public function testReload()
    {
        list($loaderObjects, $providerSchema) = $this->getSchemaMockObjects();

        //
        // CACHE MOCK
        //
        $cacheClientMock = $this->getCacheClientMock();
        $cacheSchemaObjects = [
            [
                'target' => 'fb.author.id',
                'perms' => [],
                'is_mandatory' => true
            ]
        ];
        // contains method should be called x2, no 0
        $cacheClientMock->expects($this->at(0))
            ->method('contains')
            ->with(Provider::PYLON_SCHEMA_CACHE_ID)
            ->willReturn(true);
        // no 3
        $cacheClientMock->expects($this->at(3))
            ->method('contains')
            ->with(Provider::PYLON_SCHEMA_CACHE_ID)
            ->willReturn(false);
        // fetch method should be called x1, no 1
        $cacheClientMock->expects($this->once())
            ->method('fetch')
            ->with(Provider::PYLON_SCHEMA_CACHE_ID)
            ->willReturn($cacheSchemaObjects);
        // delete method should be called x1, no 2
        $cacheClientMock->expects($this->once())
            ->method('delete')
            ->with(Provider::PYLON_SCHEMA_CACHE_ID);
        // save method should be called x1, no 4
        $cacheClientMock->expects($this->once())
            ->method('save')
            ->with(Provider::PYLON_SCHEMA_CACHE_ID, $providerSchema);

        //
        // LoaderMock
        //
        $loaderMock = $this->getMockObject('\DataSift\Pylon\Schema\LoaderInterface');
        // load method should be called x1
        $loaderMock
            ->expects($this->once())
            ->method('load')
            ->willReturn($loaderObjects);

        // calls: delete(x0), contains(true), fetch(x1), load(x0), save(x0)
        $provider = new Provider([$loaderMock], $cacheClientMock);

        $this->assertInstanceOf('\DataSift\Pylon\Schema\Schema', $provider->getSchema());
        $this->assertCount(count($cacheSchemaObjects), $provider->getSchema()->getObjects());
        $this->assertEquals($cacheSchemaObjects, $provider->getSchema()->getObjects());

        // reload schema which means remove cache, and load once again from loaders
        // calls: delete(x1), contains(false), fetch(x0), load(x1), save(x1)
        $provider->reload();

        $this->assertInstanceOf('\DataSift\Pylon\Schema\Schema', $provider->getSchema());
        $this->assertCount(count($providerSchema), $provider->getSchema()->getObjects());
        $this->assertEquals($providerSchema, $provider->getSchema()->getObjects());
    }

    /**
     * @param string $class
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockObject($class)
    {
        return $this->getMockBuilder($class)
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCacheClientMock()
    {
        return $this->getMockObject('\Doctrine\Common\Cache\Cache');
    }

    /**
     * Provides Pylon Loader schema data and Provider parsed and transformed objects
     *
     * @return array
     */
    protected function getSchemaMockObjects()
    {
        $loaderObjects = [
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

        $providerSchema = [
            'fb.author.id' => [
                'target' => 'fb.author.id',
                'perms' => [],
                'is_mandatory' => true
            ],
            'fb.author.age' => [
                'target' => 'fb.author.age',
                'cardinality' => 7,
                'description' => "One of '18-24'"

            ],
            'fb.author.gender' => [
                'target' => 'fb.author.gender',
                'perms' => [],
                'is_mandatory' => false

            ],
            'fb.author.country' => [
                'target' => 'fb.author.country',
                'cardinality' => 249,
            ]
        ];

        return [$loaderObjects, $providerSchema];
    }
}
