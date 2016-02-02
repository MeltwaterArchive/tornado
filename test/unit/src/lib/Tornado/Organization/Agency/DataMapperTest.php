<?php

namespace Test\Tornado\Organization\Agency;

use Mockery;
use Tornado\Organization\Agency\DataMapper;
use Tornado\Organization\User;

/**
 * Agency Repository
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\Organization\Brand
 * @author      Christopher Hoult <chris.hoult@datasift.com>
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Organization\Agency\DataMapper
 */
class DataMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @covers ::findByOrganization
     */
    public function testFindByOrganization()
    {
        $orgId = 10;
        $organization = Mockery::mock('Tornado\Organization\Organization[getPrimaryKey]');
        $organization->shouldReceive('getPrimaryKey')->andReturn($orgId);
        $filter = ['a' => 'b'];
        $expectedFilter = array_merge($filter, ['organization_id' => $orgId]);
        $response = ['c' => 'd', 'e' => 'f'];
        $sortBy = ['name' => 'ASC'];
        $limit = 20;
        $offset = 30;
        $repository = Mockery::mock(
            'Tornado\Organization\Agency\DataMapper[find]',
            [Mockery::mock('Doctrine\DBAL\Connection'), 'stdObject', 'agency']
        );
        $repository->shouldReceive('find')
            ->with($expectedFilter, $sortBy, $limit, $offset)
            ->andReturn($response);

        $this->assertEquals($response, $repository->findByOrganization(
            $organization,
            $filter,
            $sortBy,
            $limit,
            $offset
        ));
    }

    /**
     * @covers ::findUserAssigned
     */
    public function testFindUserAssignedAgencies()
    {
        $dbName = 'agency';
        $user = Mockery::mock('\Tornado\Organization\User', [
            'getId' => 1
        ]);
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('select')
            ->with('a.*')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')
            ->with($dbName, 'a')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('leftJoin')
            ->with('a', User::RELATION_TABLE_AGENCY, 'ua', 'ua.agency_id = a.id')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('where')
            ->with('ua.user_id = :userId')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('setParameter')
            ->with('userId', 1)
            ->andReturn($queryBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $results = [
            [
                'id' => 1,
                'organization_id' => 1,
                'name' => 'test',
                'datasift_username' => 'ds1',
                'datasift_apikey' => null
            ],
            [
                'id' => 2,
                'organization_id' => 1,
                'name' => 'test2',
                'datasift_username' => 'ds2',
                'datasift_apikey' => null
            ]
        ];

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldReceive('fetch')
            ->andReturn($results[0], $results[1], null);

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($resultStatement);

        // do the test
        $repository = new DataMapper(
            $connection,
            'Tornado\Organization\Agency',
            $dbName
        );

        $objects = $repository->findUserAssigned($user);

        $this->assertInternalType('array', $objects);
        $this->assertCount(2, $objects);

        foreach ($objects as $index => $object) {
            $this->assertInstanceOf('\Tornado\Organization\Agency', $object);
            $this->assertEquals($results[$index]['id'], $object->getId());
            $this->assertEquals($results[$index]['name'], $object->getName());
            $this->assertEquals($results[$index]['organization_id'], $object->getOrganizationId());
            $this->assertEquals($results[$index]['datasift_apikey'], $object->getDatasiftApiKey());
        }
    }

    /**
     * @covers ::findUserAllowed
     */
    public function testFindUserAllowedAgencies()
    {
        $dbName = 'agency';
        $user = Mockery::mock('\Tornado\Organization\User', [
            'getId' => 1,
            'getOrganizationId' => 1
        ]);
        // user agencies QB
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('select')
            ->with('*')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')
            ->with($dbName, 'a')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('where')
            ->with('a.organization_id = :organizationId')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('setParameter')
            ->with('organizationId', 1)
            ->andReturn($queryBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection');
        $connection->shouldReceive('createQueryBuilder')
            ->once()
            ->andReturn($queryBuilder);

        $results = [
            [
                'id' => 1,
                'organization_id' => 1,
                'name' => 'test',
                'datasift_username' => 'ds1',
                'datasift_apikey' => null
            ],
            [
                'id' => 2,
                'organization_id' => 1,
                'name' => 'test2',
                'datasift_username' => 'ds2',
                'datasift_apikey' => null
            ]
        ];

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldReceive('fetch')
            ->andReturn($results[0], $results[1], null);

        $queryBuilder->shouldReceive('execute')
            ->andReturn($resultStatement);

        // do the test
        $repository = new DataMapper(
            $connection,
            'Tornado\Organization\Agency',
            $dbName
        );

        $objects = $repository->findUserAllowed($user);

        $this->assertInternalType('array', $objects);
        $this->assertCount(2, $objects);

        foreach ($objects as $index => $object) {
            $this->assertInstanceOf('\Tornado\Organization\Agency', $object);
            $this->assertEquals($results[$index]['id'], $object->getId());
            $this->assertEquals($results[$index]['name'], $object->getName());
            $this->assertEquals($results[$index]['organization_id'], $object->getOrganizationId());
            $this->assertEquals($results[$index]['datasift_apikey'], $object->getDatasiftApiKey());
        }
    }

    /**
     * @covers ::findByIds
     */
    public function testFindByIds()
    {
        $dbName = 'agency';
        $ids = [1,2];
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $expressionBuilder = Mockery::mock('\Doctrine\DBAL\Query\Expression\ExpressionBuilder');
        $expressionBuilder->shouldReceive('in')
            ->with('id', $ids)
            ->andReturn('id IN (1,2)');
        $queryBuilder->shouldReceive('expr')
            ->withNoArgs()
            ->andReturn($expressionBuilder);
        $queryBuilder->shouldReceive('select')
            ->with('*')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')
            ->with($dbName)
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('add')
            ->with('where', 'id IN (1,2)')
            ->andReturn($queryBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $results = [
            [
                'id' => 1,
                'organization_id' => 1,
                'name' => 'test',
                'datasift_username' => 'ds1',
                'datasift_apikey' => null
            ],
            [
                'id' => 2,
                'organization_id' => 1,
                'name' => 'test2',
                'datasift_username' => 'ds2',
                'datasift_apikey' => null
            ]
        ];

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldReceive('fetch')
            ->andReturn($results[0], $results[1], null);

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($resultStatement);

        // do the test
        $repository = new DataMapper(
            $connection,
            'Tornado\Organization\Agency',
            $dbName
        );

        $objects = $repository->findByIds($ids);

        $this->assertInternalType('array', $objects);
        $this->assertCount(2, $objects);

        foreach ($objects as $index => $object) {
            $this->assertInstanceOf('\Tornado\Organization\Agency', $object);
            $this->assertEquals($results[$index]['id'], $object->getId());
            $this->assertEquals($results[$index]['name'], $object->getName());
            $this->assertEquals($results[$index]['organization_id'], $object->getOrganizationId());
            $this->assertEquals($results[$index]['datasift_apikey'], $object->getDatasiftApiKey());
        }
    }
}
