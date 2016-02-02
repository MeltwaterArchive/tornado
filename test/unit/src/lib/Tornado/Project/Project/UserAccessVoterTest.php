<?php

namespace Test\Tornado\Project\Project;

use \Mockery;

use Tornado\Project\Project\UserAccessVoter;

/**
 * UserAccessVoterTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Project\Project
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Project\Project\UserAccessVoter
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
                'class' => 'Tornado\Project\Project',
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
        $session = Mockery::mock('\Symfony\Component\HttpFoundation\Session\SessionInterface', [
            'get' => true
        ]);
        $brandRepo = Mockery::mock('\Tornado\Organization\Brand\DataMapper');
        $voter = new UserAccessVoter($session, $brandRepo);

        $this->assertEquals($expected, $voter->supportsClass($class));
    }

    /**
     * @covers ::__construct
     * @covers ::vote
     */
    public function testVote()
    {
        $user = Mockery::mock('\Tornado\Organization\User', [
            'getId' => 1
        ]);
        $project = Mockery::mock('\Tornado\Project\Project', [
            'getBrandId' => 1
        ]);
        $brand = Mockery::mock('\Tornado\Organization\Brand', [
            'getId' => 1
        ]);
        $session = Mockery::mock('\Symfony\Component\HttpFoundation\Session\SessionInterface');
        $session->shouldReceive('get')
            ->once()
            ->with('user')
            ->andReturn($user);
        $brandRepo = Mockery::mock('\Tornado\Organization\Brand\DataMapper');
        $brandRepo->shouldReceive('findOne')
            ->once()
            ->with(['id' => 1])
            ->andReturn($brand);
        $brandRepo->shouldReceive('isUserAllowed')
            ->once()
            ->with($user, $brand)
            ->andReturn(true);
        $voter = new UserAccessVoter($session, $brandRepo);

        $this->assertTrue($voter->vote($project));
    }

    /**
     * @covers ::__construct
     * @covers ::vote
     */
    public function testVoteUnlessMissingSessionUser()
    {
        $project = Mockery::mock('\Tornado\Project\Project', [
            'getBrandId' => 1
        ]);
        $session = Mockery::mock('\Symfony\Component\HttpFoundation\Session\SessionInterface');
        $session->shouldReceive('get')
            ->once()
            ->with('user')
            ->andReturn(null);
        $brandRepo = Mockery::mock('\Tornado\Organization\Brand\DataMapper');
        $brandRepo->shouldReceive('isUserAllowed')
            ->never();
        $brandRepo->shouldReceive('findOne')
            ->never();
        $voter = new UserAccessVoter($session, $brandRepo);

        $this->assertFalse($voter->vote($project));
    }
}
