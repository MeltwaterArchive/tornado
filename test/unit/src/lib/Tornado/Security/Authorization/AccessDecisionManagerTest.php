<?php

namespace Test\Tornado\Security\Authorization;

use \Mockery;

use Test\DataSift\ReflectionAccess;
use Tornado\Security\Authorization\AccessDecisionManager;

/**
 * AccessDecisionManagerTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Security\Authorization
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Security\Authorization\AccessDecisionManager
 */
class AccessDecisionManagerTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionAccess;

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @covers ::addVoter
     */
    public function testAddVoter()
    {
        $manager = new AccessDecisionManager();
        $manager->addVoter(Mockery::mock('\Tornado\Security\Authorization\VoterInterface'));
        $manager->addVoter(Mockery::mock('\Tornado\Security\Authorization\VoterInterface'));
        $manager->addVoter(Mockery::mock('\Tornado\Security\Authorization\VoterInterface'));

        $this->assertCount(3, $this->getPropertyValue($manager, 'voters'));
    }

    /**
     * @covers ::isGranted
     */
    public function testIsGranted()
    {
        $object = new \StdClass();
        $voter = Mockery::mock('\Tornado\Security\Authorization\VoterInterface');
        $voter->shouldReceive('supportsClass')
            ->once()
            ->with(get_class($object))
            ->andReturn(false);
        $voter->shouldReceive('vote')
            ->never();
        $voter2 = Mockery::mock('\Tornado\Security\Authorization\VoterInterface');
        $voter2->shouldReceive('supportsClass')
            ->once()
            ->with(get_class($object))
            ->andReturn(true);
        $voter2->shouldReceive('vote')
            ->once()
            ->with($object, null)
            ->andReturn(true);
        $manager = new AccessDecisionManager();
        $manager->addVoter($voter);
        $manager->addVoter($voter2);

        $result = $manager->isGranted($object);
        $this->assertTrue($result);
    }

    /**
     * @nvers ::isGranted
     */
    public function testIsNotGrantedUnlessOneVoterSupportsObject()
    {
        $object = new \StdClass();
        $voter = Mockery::mock('\Tornado\Security\Authorization\VoterInterface');
        $voter->shouldReceive('supportsClass')
            ->once()
            ->with(get_class($object))
            ->andReturn(false);
        $voter->shouldReceive('vote')
            ->never();

        $voter2 = Mockery::mock('\Tornado\Security\Authorization\VoterInterface');
        $voter2->shouldReceive('supportsClass')
            ->once()
            ->with(get_class($object))
            ->andReturn(false);
        $voter2->shouldReceive('vote')
            ->never();

        $manager = new AccessDecisionManager();
        $manager->addVoter($voter);
        $manager->addVoter($voter2);

        $result = $manager->isGranted($object);
        $this->assertTrue($result);
    }

    /**
     * @covers ::isGranted
     */
    public function testIsGrantedUnlessOneVoterRefuse()
    {
        $object = new \StdClass();
        $voter = Mockery::mock('\Tornado\Security\Authorization\VoterInterface');
        $voter->shouldReceive('supportsClass')
            ->once()
            ->with(get_class($object))
            ->andReturn(false);
        $voter->shouldReceive('vote')
            ->never();
        $voter2 = Mockery::mock('\Tornado\Security\Authorization\VoterInterface');
        $voter2->shouldReceive('supportsClass')
            ->once()
            ->with(get_class($object))
            ->andReturn(true);
        $voter2->shouldReceive('vote')
            ->once()
            ->with($object, null)
            ->andReturn(false);
        $voter3 = Mockery::mock('\Tornado\Security\Authorization\VoterInterface');
        $voter3->shouldReceive('supportsClass')
            ->never();
        $voter3->shouldReceive('vote')
            ->never();

        $manager = new AccessDecisionManager();
        $manager->addVoter($voter);
        $manager->addVoter($voter2);
        $manager->addVoter($voter3);

        $result = $manager->isGranted($object);
        $this->assertFalse($result);
    }
}
