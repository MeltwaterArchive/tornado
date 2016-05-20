<?php

namespace Test\Tornado\Project\Workbook\Locker;

use \Mockery;

use Tornado\Organization\User;
use Tornado\Project\Workbook;
use Tornado\Project\Workbook\Locker\Dummy;

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
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @covers \Tornado\Project\Workbook\Locker\Dummy
 */
class DummyTest extends \PHPUnit_Framework_TestCase
{

    /**
     * DataProvider for testGetters
     *
     * @return array
     */
    public function gettersProvider()
    {
        return [
            [
                'getter' => 'getLockingUser',
                'expected' => null,
            ],
            [
                'getter' => 'getTtl',
                'expected' => 0,
            ],
            [
                'getter' => 'getTtlResetLimit',
                'expected' => 0,
            ],
            [
                'getter' => 'getRemainingLimit',
                'expected' => 0,
            ]
        ];
    }

    /**
     * @dataProvider gettersProvider
     *
     * @param string $getter
     * @param mixed $expected
     */
    public function testGetters($getter, $expected)
    {
        $locker = new Dummy();
        $this->assertEquals($expected, $locker->{$getter}());
    }

    /**
     * DataProvider for testWorkbookMethods
     *
     * @return array
     */
    public function workbookMethodsProvider()
    {
        return [
            [
                'method' => 'isLocked',
                'workbook' => Mockery::mock('Tornado\Project\Workbook'),
                'expected' => true,
            ],
            [
                'method' => 'fetch',
                'workbook' => Mockery::mock('Tornado\Project\Workbook'),
                'expected' => null,
            ],
            [
                'method' => 'fetchLockingUser',
                'workbook' => Mockery::mock('Tornado\Project\Workbook'),
                'expected' => null,
            ],
            [
                'method' => 'unlock',
                'workbook' => Mockery::mock('Tornado\Project\Workbook'),
                'expected' => true,
            ]
        ];
    }

    /**
     * @dataProvider workbookMethodsProvider
     *
     * @param string $method
     * @param \Tornado\Project\Workbook
     * @param mixed $expected
     */
    public function testWorkbookMethods($method, Workbook $workbook, $expected)
    {
        $locker = new Dummy();
        $this->assertEquals($expected, $locker->{$method}($workbook));
    }

    /**
     * DataProvider for testWorkbookUserMethods
     *
     * @return array
     */
    public function workbookUserMethodsProvider()
    {
        return [
            [
                'method' => 'lock',
                'workbook' => Mockery::mock('Tornado\Project\Workbook'),
                'user' => Mockery::mock('\Tornado\Organization\User'),
                'expected' => true,
            ],
            [
                'method' => 'isGranted',
                'workbook' => Mockery::mock('Tornado\Project\Workbook'),
                'user' => Mockery::mock('\Tornado\Organization\User'),
                'expected' => true,
            ],
            [
                'method' => 'resetTtl',
                'workbook' => Mockery::mock('Tornado\Project\Workbook'),
                'user' => Mockery::mock('\Tornado\Organization\User'),
                'expected' => 0,
            ],
        ];
    }

    /**
     * @dataProvider workbookUserMethodsProvider
     *
     * @param string $method
     * @param \Tornado\Project\Workbook
     * @param \Tornado\Organization\User
     * @param mixed $expected
     */
    public function testWorkbookUserMethods($method, Workbook $workbook, User $user, $expected)
    {
        $locker = new Dummy();
        $this->assertEquals($expected, $locker->{$method}($workbook, $user));
    }
}
