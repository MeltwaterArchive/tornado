<?php

namespace Test\Tornado\Organization\User;

use Mockery;

use Tornado\Organization\User;
use Tornado\Organization\User\DataMapper;

/**
 * UserDataMapper Test
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Organization\User
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass      \Tornado\Organization\User\DataMapper
 */
class DataMapperTest extends \PHPUnit_Framework_TestCase
{

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
            'Tornado\Organization\User\DataMapper[find]',
            [Mockery::mock('Doctrine\DBAL\Connection'), 'stdObject', 'user']
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
     * @covers ::findOneByUsernameOrEmail
     */
    public function testFindOneByUsernameOrEmailUnlessDoesNotExistInDb()
    {
        $dbName = 'user';
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('select')
            ->with('*')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')
            ->with($dbName, 'u')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('where')
            ->with('u.username = :username')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('orWhere')
            ->with('u.email = :email')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('setParameters')
            ->with(['username' => 'test', 'email' => 'test'])
            ->andReturn($queryBuilder);

        $queryBuilder->shouldNotReceive('setMaxResults');
        $queryBuilder->shouldNotReceive('setFirstResult');

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $result = [
            'id' => 1,
            'organization_id' => 1,
            'username' => 'test',
            'email' => 'email@test.pl',
            'password' => 'plain'
        ];

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldReceive('fetch')
            ->andReturn($result);

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($resultStatement);

        // do the test
        $repository = new DataMapper(
            $connection,
            'Tornado\Organization\User',
            $dbName
        );

        $object = $repository->findOneByUsernameOrEmail('test');

        $this->assertInstanceOf('\Tornado\Organization\User', $object);
        $this->assertEquals(1, $object->getId());
        $this->assertEquals(1, $object->getOrganizationId());
        $this->assertEquals('email@test.pl', $object->getEmail());
        $this->assertEquals('test', $object->getUsername());
        $this->assertEquals('plain', $object->getPassword());
    }

    /**
     * @covers ::findOneByUsernameOrEmail
     */
    public function testFindOneByUsernameOrEmailUnlessExistsInDb()
    {
        $dbName = 'user';
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('select')
            ->with('*')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')
            ->with($dbName, 'u')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('where')
            ->with('u.username = :username')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('orWhere')
            ->with('u.email = :email')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('setParameters')
            ->with(['username' => 'test', 'email' => 'test'])
            ->andReturn($queryBuilder);

        $queryBuilder->shouldNotReceive('setMaxResults');
        $queryBuilder->shouldNotReceive('setFirstResult');

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $result = null;

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldReceive('fetch')
            ->andReturn($result);

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($resultStatement);

        // do the test
        $repository = new DataMapper(
            $connection,
            'Tornado\Organization\User',
            $dbName
        );

        $object = $repository->findOneByUsernameOrEmail('test');

        $this->assertNotInstanceOf('\Tornado\Organization\User', $object);
        $this->assertNull($object);
    }

    /**
     * @covers ::findByOrganizationExcludingUser
     */
    public function testFindByOrganizationExcludingUser()
    {
        $dbName = 'user';
        $organization = Mockery::mock('\Tornado\Organization\Organization', [
            'getId' => 1
        ]);
        $user = Mockery::mock('\Tornado\Organization\User', [
            'getId' => 1
        ]);
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('select')
            ->with('*')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')
            ->with($dbName, 'u')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('where')
            ->with('u.organization_id = :organizationId')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('andWhere')
            ->with('u.id <> :userId')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('setParameters')
            ->with(['organizationId' => 1, 'userId' => 1])
            ->andReturn($queryBuilder);

        $queryBuilder->shouldNotReceive('setMaxResults');
        $queryBuilder->shouldNotReceive('setFirstResult');

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $results = [
            [
                'id' => 1,
                'organization_id' => 1,
                'username' => 'test',
                'email' => 'email@test.pl',
                'password' => 'plain'
            ],
            [
                'id' => 2,
                'organization_id' => 1,
                'username' => 'test2',
                'email' => 'email2@test.pl',
                'password' => 'plain'
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
            'Tornado\Organization\User',
            $dbName
        );

        $objects = $repository->findByOrganizationExcludingUser($organization, $user);

        $this->assertInternalType('array', $objects);
        $this->assertCount(2, $objects);

        foreach ($objects as $object) {
            $this->assertInstanceOf('Tornado\DataMapper\DataObjectInterface', $object);
            $this->assertInstanceOf('Tornado\Organization\User', $object);
        }
    }

    /**
     * @covers ::addBrands
     */
    public function testAddBrands()
    {
        $dbName = 'user';
        $brands = [
            Mockery::mock('\Tornado\Organization\Brand', [
                'getId' => 1
            ]),
            Mockery::mock('\Tornado\Organization\Brand', [
                'getId' => 2
            ])
        ];
        $user = Mockery::mock('\Tornado\Organization\User', [
            'getId' => 1
        ]);
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('insert')
            ->with(User::RELATION_TABLE_BRAND)
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('values')
            ->with(['user_id' => 1, 'brand_id' => 1])
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('values')
            ->with(['user_id' => 1, 'brand_id' => 2])
            ->andReturn($queryBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $queryBuilder->shouldReceive('execute')
            ->twice();

        // do the test
        $repository = new DataMapper(
            $connection,
            'Tornado\Organization\User',
            $dbName
        );

        $repository->addBrands($user, $brands);
    }

    /**
     * @covers ::removeBrands
     */
    public function testRemoveBrands()
    {
        $dbName = 'user';
        $user = Mockery::mock('\Tornado\Organization\User', [
            'getId' => 1
        ]);
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('delete')
            ->with(User::RELATION_TABLE_BRAND)
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('where')
            ->with('user_id = :userId')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('setParameter')
            ->with('userId', 1)
            ->andReturn($queryBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $removed = 2;

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($removed);

        // do the test
        $repository = new DataMapper(
            $connection,
            'Tornado\Organization\User',
            $dbName
        );

        $results = $repository->removeBrands($user);

        $this->assertInternalType('integer', $results);
        $this->assertEquals($removed, $results);
    }

    /**
     * @covers ::removeBrands
     */
    public function testRemoveBrandsById()
    {
        $brandIds = [1,4,6];
        $brands = [];
        foreach ($brandIds as $id) {
            $brands[] = Mockery::Mock('\Tornado\Organization\Brand', ['getId' => $id]);
        }

        $dbName = 'user';
        $inStr = 'testInStr';

        $user = Mockery::mock('\Tornado\Organization\User', [
            'getId' => 1
        ]);
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');

        $queryBuilder->shouldReceive('expr')->once()->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('in')->once()->with('brand_id', $brandIds)->andReturn($inStr);

        $queryBuilder->shouldReceive('delete')
            ->with(User::RELATION_TABLE_BRAND)
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('add')
            ->with('where', $inStr)
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('andWhere')
            ->once()
            ->with('user_id = :userId')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('setParameter')
            ->once()
            ->with('userId', 1)
            ->andReturn($queryBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $removed = count($brands);

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($removed);

        // do the test
        $repository = new DataMapper(
            $connection,
            'Tornado\Organization\User',
            $dbName
        );

        $results = $repository->removeBrands($user, $brands);

        $this->assertInternalType('integer', $results);
        $this->assertEquals($removed, $results);
    }

    /**
     * @covers ::addAgencies
     */
    public function testAddAgencies()
    {
        $dbName = 'user';
        $agencies = [
            Mockery::mock('\Tornado\Organization\Agency', [
                'getId' => 1
            ]),
            Mockery::mock('\Tornado\Organization\Agency', [
                'getId' => 2
            ])
        ];
        $user = Mockery::mock('\Tornado\Organization\User', [
            'getId' => 1
        ]);
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('insert')
            ->with(User::RELATION_TABLE_AGENCY)
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('values')
            ->with(['user_id' => 1, 'agency_id' => 1])
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('values')
            ->with(['user_id' => 1, 'agency_id' => 2])
            ->andReturn($queryBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $queryBuilder->shouldReceive('execute')
            ->twice();

        // do the test
        $repository = new DataMapper(
            $connection,
            'Tornado\Organization\User',
            $dbName
        );

        $repository->addAgencies($user, $agencies);
    }

    /**
     * @covers ::removeAgencies
     */
    public function testRemoveAgencies()
    {
        $dbName = 'user';
        $user = Mockery::mock('\Tornado\Organization\User', [
            'getId' => 1
        ]);
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('delete')
            ->with(User::RELATION_TABLE_AGENCY)
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('where')
            ->with('user_id = :userId')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('setParameter')
            ->with('userId', 1)
            ->andReturn($queryBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $removed = 2;

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($removed);

        // do the test
        $repository = new DataMapper(
            $connection,
            'Tornado\Organization\User',
            $dbName
        );

        $results = $repository->removeAgencies($user);

        $this->assertInternalType('integer', $results);
        $this->assertEquals($removed, $results);
    }

    /**
     * @covers ::removeAgencies
     */
    public function testRemoveAgenciesById()
    {
        $agencyIds = [1,4,6];
        $agencies = [];
        foreach ($agencyIds as $id) {
            $agencies[] = Mockery::Mock('\Tornado\Organization\Agency', ['getId' => $id]);
        }

        $dbName = 'user';
        $inStr = 'testInStr';

        $user = Mockery::mock('\Tornado\Organization\User', [
            'getId' => 1
        ]);
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');

        $queryBuilder->shouldReceive('expr')->once()->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('in')->once()->with('agency_id', $agencyIds)->andReturn($inStr);

        $queryBuilder->shouldReceive('delete')
            ->with(User::RELATION_TABLE_AGENCY)
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('add')
            ->with('where', $inStr)
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('andWhere')
            ->once()
            ->with('user_id = :userId')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('setParameter')
            ->once()
            ->with('userId', 1)
            ->andReturn($queryBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $removed = count($agencies);

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($removed);

        // do the test
        $repository = new DataMapper(
            $connection,
            'Tornado\Organization\User',
            $dbName
        );

        $results = $repository->removeAgencies($user, $agencies);

        $this->assertInternalType('integer', $results);
        $this->assertEquals($removed, $results);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        Mockery::close();
    }
}
