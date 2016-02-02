<?php

namespace Test\Tornado\Project\Recording;

use \Mockery;

use Tornado\Project\Recording\UserAccessVoter;

/**
 * UserAccessVoterTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Project\Recording
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Project\Recording\UserAccessVoter
 */
class UserAccessVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @return array
     */
    public function classProvider()
    {
        return [
            [
                'class' => '\Tornado\Organization\Organization',
                'expected' => false
            ],
            [
                'class' => null,
                'expected' => false
            ],
            [
                'class' => 20,
                'expected' => false
            ],
            [
                'class' => '\Tornado\Organization\Brand',
                'expected' => false
            ],
            [
                'class' => 'Tornado\Project\Recording',
                'expected' => true
            ]
        ];
    }

    /**
     * @dataProvider classProvider
     *
     * @covers ::__construct
     * @covers ::supportsClass
     */
    public function testSupportsClass($class, $expected)
    {
        $mocks = $this->getMocks();
        $voter = new UserAccessVoter($mocks['session'], $mocks['brandRepository']);

        $this->assertEquals($expected, $voter->supportsClass($class));
    }

    /**
     * @covers ::__construct
     * @covers ::vote
     */
    public function testVote()
    {
        $mocks = $this->getMocks();

        $mocks['session']->shouldReceive('get')
            ->once()
            ->with('user')
            ->andReturn($mocks['user']);
        $mocks['brandRepository']->shouldReceive('findOne')
            ->once()
            ->with(['id' => $mocks['brandId']])
            ->andReturn($mocks['brand']);
        $mocks['brandRepository']->shouldReceive('isUserAllowed')
            ->once()
            ->with($mocks['user'], $mocks['brand'])
            ->andReturn(true);

        $voter = new UserAccessVoter($mocks['session'], $mocks['brandRepository']);

        $this->assertTrue($voter->vote($mocks['recording']));
    }

    /**
     * @covers ::__construct
     * @covers ::vote
     */
    public function testVoteUnlessMissingSessionUser()
    {
        $mocks = $this->getMocks();

        $mocks['session']->shouldReceive('get')
            ->once()
            ->with('user')
            ->andReturn(null);
        $mocks['brandRepository']->shouldReceive('isUserAllowed')
            ->never();
        $mocks['brandRepository']->shouldReceive('findOne')
            ->never();
        $voter = new UserAccessVoter($mocks['session'], $mocks['brandRepository']);

        $this->assertFalse($voter->vote($mocks['recording']));
    }

    /**
     * Provides mocks for tests
     *
     * @return array
     */
    protected function getMocks()
    {
        $brandId = 1;
        $userId = 1;

        $session = Mockery::mock('\Symfony\Component\HttpFoundation\Session\SessionInterface', [
            'get' => true
        ]);
        $brandRepo = Mockery::mock('\Tornado\Organization\Brand\DataMapper');
        $user = Mockery::mock('\Tornado\Organization\User', [
            'getId' => $userId
        ]);
        $recording = Mockery::mock('\Tornado\Project\Recording', [
            'getBrandId' => $brandId
        ]);
        $brand = Mockery::mock('\Tornado\Organization\Brand', [
            'getId' => $brandId
        ]);

        return [
            'session' => $session,
            'brandId' => $brandId,
            'userId' => $userId,
            'brand' => $brand,
            'brandRepository' => $brandRepo,
            'user' => $user,
            'recording' => $recording
        ];
    }
}
