<?php

namespace Test\Tornado\Organization\Organization;

use Mockery;

use Tornado\Organization\User;
use Tornado\Organization\User\DataMapper;

/**
 * Organization RepositoryTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Organization\User
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass      \Tornado\Organization\Organization\DataMapper
 */
class DataMapperTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::findOneByUser
     */
    public function testFindOneByUser()
    {
        $id = 101;
        $user = Mockery::mock('Tornado\Organization\User');
        $user->shouldReceive('getOrganizationId')
            ->once()
            ->andReturn($id);

        $organization = Mockery::Mock('Tornado\Organization\Organization');

        $mapper = Mockery::mock(
            'Tornado\Organization\Organization\DataMapper[findOne]',
            [
                Mockery::mock('Doctrine\DBAL\Connection'),
                'organization',
                'organization'
            ]
        );

        $mapper->shouldReceive('findOne')
            ->with(['id' => $id])
            ->andReturn($organization);

        $this->assertEquals($organization, $mapper->findOneByUser($user));
    }

    /**
     * DataProvider for testGetJwtKey
     *
     * @return array
     */
    public function getJwtKeyProvider()
    {
        return [
            'Org found' => [
                'key' => 1,
                'found' => true
            ],
            'Org not found' => [
                'key' => 2,
                'found' => false
            ]
        ];
    }

    /**
     * @dataProvider getJwtKeyProvider
     *
     * @covers ::getJwtKey
     */
    public function testGetJwtKey($keyId, $found)
    {
        $mapper = Mockery::mock(
            'Tornado\Organization\Organization\DataMapper[findOne]',
            [
                Mockery::mock('Doctrine\DBAL\Connection'),
                'organization',
                'organization'
            ]
        );

        $organization = Mockery::Mock('Tornado\Organization\Organization');
        $secret = 'My Secret';
        if ($found) {
            $organization->shouldReceive('getJwtSecret')
                ->andReturn($secret);
        }

        $mapper->shouldReceive('findOne')
            ->with(['id' => $keyId])
            ->andReturn(($found) ? $organization : false);

        $this->assertEquals(($found) ? $secret : null, $mapper->getJwtKey($keyId));
    }
}
