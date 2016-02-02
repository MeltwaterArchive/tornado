<?php

namespace Test\Tornado\Project\Workbook;

use \Mockery;

use Tornado\Organization\User;
use Tornado\Project\Workbook;
use Tornado\Project\Workbook\Locker;

/**
 * LockerTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Project\Workbook
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers \Tornado\Project\Workbook\Locker
 */
class LockerTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testConstructWithDefaultData()
    {
        $mocks = $this->getMocks();
        $locker = new Locker($mocks['cache']);

        $this->assertEquals(Locker::LOCKING_TTL, $locker->getTtl());
        $this->assertEquals(Locker::TTL_RESET_LIMIT, $locker->getTtlResetLimit());
        $this->assertNull($locker->getLockingUser());
    }

    public function testConstruct()
    {
        $mocks = $this->getMocks();
        $locker = new Locker($mocks['cache'], 10, 20);

        $this->assertEquals(10, $locker->getTtl());
        $this->assertEquals(20, $locker->getTtlResetLimit());
        $this->assertNull($locker->getLockingUser());
    }

    public function testIsLocked()
    {
        $mocks = $this->getMocks();
        $mocks['cache']->shouldReceive('contains')
            ->once()
            ->with($mocks['lockKey'])
            ->andReturn(true);

        $locker = $this->getLocker($mocks);

        $this->assertTrue($locker->isLocked($mocks['workbook']));
    }

    public function testFetch()
    {
        $mocks = $this->getMocks();
        $mocks['cache']->shouldReceive('fetch')
            ->once()
            ->with($mocks['lockKey'])
            ->andReturn($mocks['lockData']);

        $locker = $this->getLocker($mocks);

        $this->assertNotNull($locker->fetch($mocks['workbook']));
    }

    public function testLock()
    {
        $mocks = $this->getMocks();
        $mocks['cache']->shouldReceive('save')
            ->once()
            ->with(
                $mocks['lockKey'],
                array_merge($mocks['lockData'], ['reset_limit' => Locker::TTL_RESET_LIMIT]),
                Locker::LOCKING_TTL
            )
            ->andReturn(true);

        $locker = $this->getLocker($mocks);

        $this->assertTrue($locker->lock($mocks['workbook'], $mocks['user']));
    }

    public function testFetchLockingUser()
    {
        $mocks = $this->getMocks();
        $mocks['cache']->shouldReceive('fetch')
            ->once()
            ->with($mocks['lockKey'])
            ->andReturn($mocks['lockData']);

        $locker = $this->getLocker($mocks);
        $result = $locker->fetchLockingUser($mocks['workbook']);

        $this->assertInstanceOf('\Tornado\Organization\User', $result);
        $this->assertSame($mocks['user'], $result);
        $this->assertSame($mocks['userId'], $result->getId());
    }

    public function testFetchLockingUserUnlessNoCacheData()
    {
        $mocks = $this->getMocks();
        $mocks['cache']->shouldReceive('fetch')
            ->once()
            ->with($mocks['lockKey'])
            ->andReturn(null);

        $locker = $this->getLocker($mocks);
        $this->assertNull($locker->fetchLockingUser($mocks['workbook']));
    }

    public function testFetchLockingUserUnlessUserDataMissing()
    {
        $mocks = $this->getMocks();
        $mocks['cache']->shouldReceive('fetch')
            ->once()
            ->with($mocks['lockKey'])
            ->andReturn(['workbook' => $mocks['workbook']]);

        $locker = $this->getLocker($mocks);
        $this->assertNull($locker->fetchLockingUser($mocks['workbook']));
    }

    public function testFetchLockingUserUnlessUserIsInvalidType()
    {
        $mocks = $this->getMocks();
        $mocks['cache']->shouldReceive('fetch')
            ->once()
            ->with($mocks['lockKey'])
            ->andReturn(['user' => 'string']);

        $locker = $this->getLocker($mocks);
        $this->assertNull($locker->fetchLockingUser($mocks['workbook']));
    }

    public function testIsNotGranted()
    {
        $mocks = $this->getMocks();
        $mocks['cache']->shouldReceive('fetch')
            ->once()
            ->with($mocks['lockKey'])
            ->andReturn(['user' => 'string']);

        $locker = $this->getLocker($mocks);
        $this->assertFalse($locker->isGranted($mocks['workbook'], $mocks['user']));
    }

    public function testIsNotGrantedForDifferentUser()
    {
        $mocks = $this->getMocks();
        $mocks['cache']->shouldReceive('fetch')
            ->once()
            ->with($mocks['lockKey'])
            ->andReturn($mocks['lockData']);

        $locker = $this->getLocker($mocks);

        $user = new User();
        $user->setId(50);

        $this->assertFalse($locker->isGranted($mocks['workbook'], $user));
    }

    public function testIsGranted()
    {
        $mocks = $this->getMocks();
        $mocks['cache']->shouldReceive('fetch')
            ->once()
            ->with($mocks['lockKey'])
            ->andReturn($mocks['lockData']);

        $locker = $this->getLocker($mocks);
        $this->assertTrue($locker->isGranted($mocks['workbook'], $mocks['user']));
    }

    public function testResetTtl()
    {
        $mocks = $this->getMocks();
        $mocks['cache']->shouldReceive('fetch')
            ->once()
            ->with($mocks['lockKey'])
            ->andReturn(array_merge($mocks['lockData'], ['reset_limit' => Locker::TTL_RESET_LIMIT]));
        $mocks['cache']->shouldReceive('save')
            ->once()
            ->with(
                $mocks['lockKey'],
                array_merge($mocks['lockData'], ['reset_limit' => Locker::TTL_RESET_LIMIT - 1]),
                Locker::LOCKING_TTL
            )
            ->andReturn(true);

        $locker = $this->getLocker($mocks);
        $result = $locker->resetTtl($mocks['workbook'], $mocks['user']);

        $this->assertEquals(Locker::TTL_RESET_LIMIT - 1, $result);
    }

    public function testResetTtlUnlessResetLimitExceeded()
    {
        $mocks = $this->getMocks();
        $mocks['cache']->shouldReceive('fetch')
            ->once()
            ->with($mocks['lockKey'])
            ->andReturn(array_merge($mocks['lockData'], ['reset_limit' => 0]));

        $locker = $this->getLocker($mocks);

        $this->assertFalse($locker->resetTtl($mocks['workbook'], $mocks['user']));
    }

    public function testUnlock()
    {
        $mocks = $this->getMocks();
        $mocks['cache']->shouldReceive('delete')
            ->once()
            ->with($mocks['lockKey'])
            ->andReturn(true);

        $locker = $this->getLocker($mocks);

        $this->assertTrue($locker->unlock($mocks['workbook']));
    }

    /**
     * @return array
     */
    protected function getMocks()
    {
        $mocks['workbookId'] = 10;
        $mocks['userId'] = 20;

        $mocks['workbook'] = new Workbook();
        $mocks['workbook']->setId($mocks['workbookId']);

        $mocks['user'] = new User();
        $mocks['user']->setId($mocks['userId']);

        $mocks['lockKey'] = sprintf('workbook:lock:%d', $mocks['workbookId']);

        $mocks['lockData'] = [
            'workbook' => $mocks['workbook'],
            'user' => $mocks['user'],
        ];
        $mocks['cache'] = Mockery::mock('\Doctrine\Common\Cache\Cache');

        return $mocks;
    }

    /**
     * @param array $mocks
     *
     * @return \Tornado\Project\Workbook\Locker
     */
    protected function getLocker(array $mocks)
    {
        return new Locker(
            $mocks['cache']
        );
    }
}
