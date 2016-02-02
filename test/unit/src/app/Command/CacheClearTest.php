<?php

namespace Test\Command;

use Mockery;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Doctrine\Common\Cache\Cache;

use Test\DataSift\ReflectionAccess;

use Command\CacheClear;

/**
 * CacheClearTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Command
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass  \Command\CacheClear
 */
class CacheClearTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess;

    public function tearDown()
    {
        Mockery::close();
    }

    protected function provideCommandTester(Cache $cache, Filesystem $filesystem, $cacheDir)
    {
        $command = new CacheClear($cache, $filesystem, $cacheDir);
        return new CommandTester($command);
    }

    /**
     * @covers ::__construct
     * @covers ::configure
     */
    public function testConfigure()
    {
        $cache = Mockery::mock('Doctrine\Common\Cache\Cache');
        $filesystem = Mockery::mock('Symfony\Component\Filesystem\Filesystem');
        $cacheDir = './cache';

        $command = new CacheClear($cache, $filesystem, $cacheDir);
        $this->invokeMethod($command, 'configure');

        $this->assertEquals('cache:clear', $command->getName());
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * @expectedException \RuntimeException
     *
     * @covers ::__construct
     * @covers ::execute
     */
    public function testExecutingWithNonClearableCache()
    {
        $cache = Mockery::mock('Doctrine\Common\Cache\Cache');
        $filesystem = Mockery::mock('Symfony\Component\Filesystem\Filesystem');
        $cacheDir = './cache';

        $commandTester = $this->provideCommandTester($cache, $filesystem, $cacheDir);
        $commandTester->execute([]);
    }

    /**
     * @covers ::__construct
     * @covers ::execute
     */
    public function testExecuting()
    {
        $cache = Mockery::mock('Doctrine\Common\Cache\Cache,Doctrine\Common\Cache\ClearableCache');
        $filesystem = Mockery::mock('Symfony\Component\Filesystem\Filesystem');
        $cacheDir = './cache';

        $cache->shouldReceive('deleteAll')
            ->andReturn(true)
            ->once();

        $filesystem->shouldReceive('remove')
            ->with($cacheDir);

        $commandTester = $this->provideCommandTester($cache, $filesystem, $cacheDir);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        $this->assertContains('Cleared the application cache.', $display);
        $this->assertContains('Cleared the file cache', $display);
    }

    /**
     * @covers ::__construct
     * @covers ::execute
     */
    public function testExecutingWithProblems()
    {
        $cache = Mockery::mock('Doctrine\Common\Cache\Cache,Doctrine\Common\Cache\ClearableCache');
        $filesystem = Mockery::mock('Symfony\Component\Filesystem\Filesystem');
        $cacheDir = './cache';

        $cache->shouldReceive('deleteAll')
            ->andReturn(false)
            ->once();

        $filesystem->shouldReceive('remove')
            ->with($cacheDir);

        $commandTester = $this->provideCommandTester($cache, $filesystem, $cacheDir);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        $this->assertContains('There was a problem clearing the application cache.', $display);
        $this->assertContains('Cleared the file cache', $display);
    }
}
