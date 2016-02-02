<?php

namespace Test\DataSift\Cache;

use DataSift\Cache\NullCache;

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
 * @coversDefaultClass \DataSift\Cache\NullCache
 */
class NullCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::fetch
     * @covers ::contains
     * @covers ::save
     * @covers ::delete
     * @covers ::getStats
     */
    public function testGetters()
    {
        $cache = new NullCache();

        $this->assertFalse($cache->fetch(1));
        $this->assertFalse($cache->contains(1));
        $this->assertFalse($cache->save(1, []));
        $this->assertFalse($cache->delete(1));
        $this->assertNull($cache->getStats());
    }
}
