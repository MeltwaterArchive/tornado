<?php

namespace Test\DataSift\Cache;

use Mockery;

use DataSift\Cache\CacheProvider;

/**
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\DataSift\Cache
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers \DataSift\Cache\CacheProvider
 */
class CacheProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testProvidingConfiguredCache()
    {
        $cacheServiceName = 'cache.memcache';
        $cacheMock = Mockery::mock('Doctrine\Common\Cache\Cache');

        $container = Mockery::mock('Symfony\Component\DependencyInjection\ContainerInterface');

        $container->shouldReceive('get')
            ->with($cacheServiceName)
            ->andReturn($cacheMock)
            ->once();

        $cacheProvider = new CacheProvider($container, $cacheServiceName);
        $cache = $cacheProvider->provideCache();
        $this->assertSame($cacheMock, $cache);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testThrowingExceptionOnInvalidCacheService()
    {
        $cacheServiceName = 'cache.memcache';
        $invalidCacheMock = new \StdClass();

        $container = Mockery::mock('Symfony\Component\DependencyInjection\ContainerInterface');

        $container->shouldReceive('get')
            ->with($cacheServiceName)
            ->andReturn($invalidCacheMock)
            ->once();

        $cacheProvider = new CacheProvider($container, $cacheServiceName);
        $cacheProvider->provideCache();
    }
}
