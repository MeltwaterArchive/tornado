<?php

namespace Test\Tornado\Organization\Brand;

use Mockery;

use Tornado\Organization\Agency;
use Tornado\Organization\Brand\DataMapper;
use Tornado\Organization\User;

/**
 * Brand Repository
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
 * @coversDefaultClass \Tornado\Organization\Brand\DataMapper
 */
class DataMapperyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @covers ::findByAgency
     */
    public function testFindByAgency()
    {
        $agencyId = 10;
        $agency = Mockery::mock('Tornado\Organization\Agency[getPrimaryKey]');
        $agency->shouldReceive('getPrimaryKey')->andReturn($agencyId);

        $filter = ['a' => 'b'];
        $expectedFilter = array_merge($filter, ['agency_id' => $agencyId]);

        $response = ['c' => 'd', 'e' => 'f'];
        $sortBy = ['name' => 'ASC'];
        $limit = 20;
        $offset = 30;

        $repository = Mockery::mock(
            'Tornado\Organization\Brand\DataMapper[find]',
            [Mockery::mock('Doctrine\DBAL\Connection'), 'stdObject', 'brand']
        );
        $repository->shouldReceive('find')
            ->with($expectedFilter, $sortBy, $limit, $offset)
            ->andReturn($response);

        $this->assertEquals($response, $repository->findByAgency(
            $agency,
            $filter,
            $sortBy,
            $limit,
            $offset
        ));
    }

    /**
     * @covers ::findUserAssigned
     */
    public function testFindUserAssignedBrands()
    {
        $dbName = 'brand';
        $user = Mockery::mock('\Tornado\Organization\User', [
            'getId' => 1
        ]);
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('select')
            ->with('b.*')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')
            ->with($dbName, 'b')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('leftJoin')
            ->with('b', User::RELATION_TABLE_BRAND, 'ub', 'ub.brand_id = b.id')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('where')
            ->with('ub.user_id = :userId')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('setParameter')
            ->with('userId', 1)
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('addOrderBy')
            ->once()
            ->with('name', 'ASC')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldNotReceive('setMaxResults');
        $queryBuilder->shouldNotReceive('setFirstResult');

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $results = [
            [
                'id' => 1,
                'agency_id' => 1,
                'name' => 'test',
                'datasift_identity_id' => 'identity',
                'datasift_apikey' => null,
                'target_permissions' => null,
            ],
            [
                'id' => 2,
                'agency_id' => 1,
                'name' => 'test2',
                'datasift_identity_id' => 'identity2',
                'datasift_apikey' => null,
                'target_permissions' => null,
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
            'Tornado\Organization\Brand',
            $dbName
        );

        $objects = $repository->findUserAssigned($user);

        $this->assertInternalType('array', $objects);
        $this->assertCount(2, $objects);

        foreach ($objects as $index => $object) {
            $this->assertInstanceOf('\Tornado\Organization\Brand', $object);
            $this->assertEquals($results[$index]['id'], $object->getId());
            $this->assertEquals($results[$index]['agency_id'], $object->getAgencyId());
            $this->assertEquals($results[$index]['datasift_apikey'], $object->getDatasiftApiKey());
            $this->assertEquals([], $object->getTargetPermissions());
        }
    }

    /**
     * @covers ::findUserAllowed
     */
    public function testFindUserAllowedBrands()
    {
        $dbName = 'brand';
        $user = Mockery::mock('\Tornado\Organization\User', [
            'getId' => 1
        ]);
        // user agencies QB
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('select')
            ->with('ua.agency_id')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')
            ->with(User::RELATION_TABLE_AGENCY, 'ua')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('where')
            ->with('ua.user_id = :userId')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('setParameter')
            ->with('userId', 1)
            ->andReturn($queryBuilder);
        $agenciesIds = [['agency_id' => 1], ['agency_id' => 2]];
        // allowed brand QB
        $expressionBuilder = Mockery::mock('\Doctrine\DBAL\Query\Expression\ExpressionBuilder');
        $expressionBuilder->shouldReceive('in')
            ->with('agency_id', [1, 2])
            ->andReturn('agency_id IN (1,2)');
        $queryBuilder2 = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder2->shouldReceive('expr')
            ->withNoArgs()
            ->andReturn($expressionBuilder);
        $queryBuilder2->shouldReceive('select')
            ->with('*')
            ->andReturn($queryBuilder2);
        $queryBuilder2->shouldReceive('from')
            ->with($dbName, 'b')
            ->andReturn($queryBuilder2);
        $queryBuilder2->shouldReceive('add')
            ->with('where', 'agency_id IN (1,2)')
            ->andReturn($queryBuilder2);
        $queryBuilder2->shouldReceive('setParameter')
            ->with('userId', 1)
            ->andReturn($queryBuilder2);

        $connection = Mockery::mock('Doctrine\DBAL\Connection');
        $connection->shouldReceive('createQueryBuilder')
            ->twice()
            ->andReturn($queryBuilder, $queryBuilder2);

        $results = [
            [
                'id' => 1,
                'agency_id' => 1,
                'name' => 'test',
                'datasift_identity_id' => 'identity',
                'datasift_apikey' => null,
                'target_permissions' => null,
            ],
            [
                'id' => 2,
                'agency_id' => 1,
                'name' => 'test2',
                'datasift_identity_id' => 'identity2',
                'datasift_apikey' => null,
                'target_permissions' => null,
            ]
        ];

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldReceive('fetch')
            ->andReturn($results[0], $results[1], null);
        $resultStatement->shouldReceive('fetchAll')
            ->andReturn($agenciesIds);

        $queryBuilder->shouldReceive('execute')
            ->andReturn($resultStatement);
        $queryBuilder2->shouldReceive('execute')
            ->andReturn($resultStatement);

        // do the test
        $repository = new DataMapper(
            $connection,
            'Tornado\Organization\Brand',
            $dbName
        );

        $objects = $repository->findUserAllowed($user);

        $this->assertInternalType('array', $objects);
        $this->assertCount(2, $objects);

        foreach ($objects as $index => $object) {
            $this->assertInstanceOf('\Tornado\Organization\Brand', $object);
            $this->assertInstanceOf('\Tornado\Organization\Brand', $object);
            $this->assertEquals($results[$index]['id'], $object->getId());
            $this->assertEquals($results[$index]['agency_id'], $object->getAgencyId());
            $this->assertEquals($results[$index]['datasift_apikey'], $object->getDatasiftApiKey());
            $this->assertEquals([], $object->getTargetPermissions());
        }
    }

    /**
     * @covers ::isUserAllowed
     */
    public function testIsUserAllowed()
    {
        $dbName = 'brand';
        $user = Mockery::mock('\Tornado\Organization\User', [
            'getId' => 1
        ]);
        $brand = Mockery::mock('\Tornado\Organization\Brand', [
            'getId' => 1
        ]);
        // user agencies QB
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('select')
            ->with('*')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')
            ->with(User::RELATION_TABLE_BRAND)
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('where')
            ->with('user_id = :userId')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('andWhere')
            ->with('brand_id = :brandId')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('setParameters')
            ->with(['userId' => 1, 'brandId' => 1])
            ->andReturn($queryBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection');
        $connection->shouldReceive('createQueryBuilder')
            ->once()
            ->andReturn($queryBuilder);

        $results = ['user_id' => 1, 'brand_id' => 1];

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldReceive('fetch')
            ->andReturn($results);

        $queryBuilder->shouldReceive('execute')
            ->andReturn($resultStatement);

        // do the test
        $repository = new DataMapper(
            $connection,
            'Tornado\Organization\Brand',
            $dbName
        );

        $result = $repository->isUserAllowed($user, $brand);

        $this->assertInternalType('boolean', $result);
        $this->assertTrue($result);
    }

    /**
     * @covers ::isUserAllowed
     */
    public function testIsUserNotAllowed()
    {
        $dbName = 'brand';
        $user = Mockery::mock('\Tornado\Organization\User', [
            'getId' => 1
        ]);
        $brand = Mockery::mock('\Tornado\Organization\Brand', [
            'getId' => 1
        ]);
        // user agencies QB
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('select')
            ->with('*')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')
            ->with(User::RELATION_TABLE_BRAND)
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('where')
            ->with('user_id = :userId')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('andWhere')
            ->with('brand_id = :brandId')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('setParameters')
            ->with(['userId' => 1, 'brandId' => 1])
            ->andReturn($queryBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection');
        $connection->shouldReceive('createQueryBuilder')
            ->once()
            ->andReturn($queryBuilder);

        $results = false;

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldReceive('fetch')
            ->andReturn($results);

        $queryBuilder->shouldReceive('execute')
            ->andReturn($resultStatement);

        // do the test
        $repository = new DataMapper(
            $connection,
            'Tornado\Organization\Brand',
            $dbName
        );

        $result = $repository->isUserAllowed($user, $brand);

        $this->assertInternalType('boolean', $result);
        $this->assertFalse($result);
    }

    /**
     * @covers ::findUserAllowed
     */
    public function testFindUserAllowedBrandsIfNotAssignedToAnyAgency()
    {
        $dbName = 'brand';
        $user = Mockery::mock('\Tornado\Organization\User', [
            'getId' => 1
        ]);
        // user agencies QB
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('select')
            ->with('ua.agency_id')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')
            ->with(User::RELATION_TABLE_AGENCY, 'ua')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('where')
            ->with('ua.user_id = :userId')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('setParameter')
            ->with('userId', 1)
            ->andReturn($queryBuilder);
        $agenciesIds = [];

        $connection = Mockery::mock('Doctrine\DBAL\Connection');
        $connection->shouldReceive('createQueryBuilder')
            ->once()
            ->andReturn($queryBuilder);

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldNotReceive('fetch');
        $resultStatement->shouldReceive('fetchAll')
            ->andReturn($agenciesIds);

        $queryBuilder->shouldReceive('execute')
            ->andReturn($resultStatement);

        // do the test
        $repository = new DataMapper(
            $connection,
            'Tornado\Organization\Brand',
            $dbName
        );

        $objects = $repository->findUserAllowed($user);
        $this->assertNull($objects);
    }

    /**
     * @covers ::findByIds
     */
    public function testFindByIds()
    {
        $dbName = 'brand';
        $ids = [1, 2];
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
                'agency_id' => 1,
                'name' => 'test',
                'datasift_identity_id' => 'identity',
                'datasift_apikey' => null,
                'target_permissions' => null,
            ],
            [
                'id' => 2,
                'agency_id' => 1,
                'name' => 'test2',
                'datasift_identity_id' => 'identity2',
                'datasift_apikey' => null,
                'target_permissions' => null,
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
            'Tornado\Organization\Brand',
            $dbName
        );

        $objects = $repository->findByIds($ids);

        $this->assertInternalType('array', $objects);
        $this->assertCount(2, $objects);

        foreach ($objects as $index => $object) {
            $this->assertInstanceOf('\Tornado\Organization\Brand', $object);
            $this->assertEquals($results[$index]['id'], $object->getId());
            $this->assertEquals($results[$index]['agency_id'], $object->getAgencyId());
            $this->assertEquals($results[$index]['datasift_apikey'], $object->getDatasiftApiKey());
            $this->assertEquals([], $object->getTargetPermissions());
        }
    }

    /**
     * @covers ::findOneByProject
     */
    public function testFindOneByProject()
    {
        $brandId = 20;

        $project = Mockery::mock('Tornado\Project\Project[getBrandId]');
        $project->shouldReceive('getBrandId')->andReturn($brandId);

        $brand = Mockery::mock('Tornado\Organization\Brand');

        $repository = Mockery::mock(
            'Tornado\Organization\Brand\DataMapper[findOne]',
            [Mockery::mock('Doctrine\DBAL\Connection'), 'stdObject', 'brand']
        );

        $repository->shouldReceive('findOne')
            ->with([
                'id' => $brandId
            ])
            ->andReturn($brand);

        $this->assertSame($brand, $repository->findOneByProject($project));
    }

    /**
     * DataProvider for testDeleteByIds
     *
     * @return array
     */
    public function deleteByIdsProvider()
    {
        return [
            'Happy path' => [
                'ids' => [1, 2, 3]
            ]
        ];
    }

    /**
     * @dataProvider deleteByIdsProvider
     *
     * @covers ::deleteByIds
     *
     * @param array $ids
     */
    public function testDeleteByIds(array $ids)
    {
        $class = 'Brand';
        $tableName = 'brand';

        $inExpr = 'IN EXPRE';

        $qb = Mockery::Mock('Doctrine\DBAL\Query\QueryBuilder');
        $qb->shouldReceive('expr')->once()->andReturn($qb);
        $qb->shouldReceive('in')->once()->with('id', $ids)->andReturn($inExpr);

        $qb->shouldReceive('delete')->once()->with($tableName)->andReturn($qb);
        $qb->shouldReceive('add')->once()->with('where', $inExpr)->andReturn($qb);
        $qb->shouldReceive('execute')->once()->andReturn($qb);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $qb
        ]);

        $repository = new DataMapper(
            $connection,
            $class,
            $tableName
        );

        $this->assertEquals($qb, $repository->deleteByIds($ids));
    }

    /**
     * DataProvider for testCountAgencyBrands
     *
     * @return array
     */
    public function countAgencyBrandsProvider()
    {
        return [
            'Happy path' => [
                'agency' => Mockery::mock('Tornado\Organization\Agency', ['getId' => 20]),
                'agencyId' => 20
            ]
        ];
    }

    /**
     * @dataProvider countAgencyBrandsProvider
     *
     * @covers ::countAgencyBrands
     *
     * @param Agency $agency
     * @param integer $agencyId
     */
    public function testCountAgencyBrands(Agency $agency, $agencyId)
    {
        $cnt = 23;

        $class = 'Brand';
        $tableName = 'brand';
        $qb = Mockery::Mock('Doctrine\DBAL\Query\QueryBuilder');
        $qb->shouldReceive('select')->once()->with('COUNT(id) AS cnt')->andReturn($qb);
        $qb->shouldReceive('from')->once()->with($tableName)->andReturn($qb);
        $qb->shouldReceive('where')->once()->with('agency_id = :agencyId')->andReturn($qb);
        $qb->shouldReceive('setParameter')->once()->with('agencyId', $agencyId)->andReturn($qb);
        $qb->shouldReceive('execute')->once()->andReturn($qb);
        $qb->shouldReceive('fetch')->once()->andReturn(['cnt' => $cnt]);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $qb
        ]);

        $repository = new DataMapper(
            $connection,
            $class,
            $tableName
        );

        $this->assertEquals($cnt, $repository->countAgencyBrands($agency));
    }
}
