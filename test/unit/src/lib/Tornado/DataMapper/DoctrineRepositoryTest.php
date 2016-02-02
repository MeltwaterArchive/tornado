<?php
namespace Test\Tornado\DataMapper;

use Mockery;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\DoctrineRepository;

use Test\Tornado\DataMapper\Fixtures\TestObject;

/**
 * @coversDefaultClass \Tornado\DataMapper\DoctrineRepository
 */
class DoctrineRepositoryTest extends \PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @covers ::__construct
     */
    public function testImplementingInterface()
    {
        $repository = new DoctrineRepository(Mockery::mock('Doctrine\DBAL\Connection'), 'stdObject', 'test_mocks');
        $this->assertInstanceOf(
            'Tornado\DataMapper\DataMapperInterface',
            $repository,
            'DoctrineRepository does not implement required DataMapperInterface'
        );
    }

    /**
     * @expectedException \Tornado\DataMapper\Exceptions\InvalidPrimaryKeyException
     *
     * @covers ::verifyPrimaryKey
     */
    public function testInvalidObjectPrimaryKeyNameType()
    {
        $object = Mockery::mock('Tornado\DataMapper\DataObjectInterface', [
            'getPrimaryKeyName' => new \stdClass(),
            'getPrimaryKey' => 234
        ]);

        $repository = new DoctrineRepository(Mockery::mock('Doctrine\DBAL\Connection'), 'stdObject', 'test_mocks');
        $repository->delete($object);
    }

    /**
     * @expectedException \Tornado\DataMapper\Exceptions\InvalidPrimaryKeyException
     *
     * @covers ::verifyPrimaryKey
     */
    public function testInvalidObjectPrimaryKeyTypeMismatch()
    {
        $object = Mockery::mock('Tornado\DataMapper\DataObjectInterface', [
            'getPrimaryKeyName' => 'id',
            'getPrimaryKey' => [234, 'organization']
        ]);

        $repository = new DoctrineRepository(Mockery::mock('Doctrine\DBAL\Connection'), 'stdObject', 'test_mocks');
        $repository->delete($object);
    }

    /**
     * @expectedException \Tornado\DataMapper\Exceptions\InvalidPrimaryKeyException
     *
     * @covers ::verifyPrimaryKey
     */
    public function testInvalidObjectPrimaryKeyEmptyName()
    {
        $object = Mockery::mock('Tornado\DataMapper\DataObjectInterface', [
            'getPrimaryKeyName' => '',
            'getPrimaryKey' => 'johndoe'
        ]);

        $repository = new DoctrineRepository(Mockery::mock('Doctrine\DBAL\Connection'), 'stdObject', 'test_mocks');
        $repository->delete($object);
    }

    /**
     * @expectedException \Tornado\DataMapper\Exceptions\InvalidPrimaryKeyException
     *
     * @covers ::verifyPrimaryKey
     */
    public function testInvalidObjectCompoundPrimaryKey()
    {
        $object = Mockery::mock('Tornado\DataMapper\DataObjectInterface', [
            'getPrimaryKeyName' => ['organization_id', 'id'],
            'getPrimaryKey' => 45
        ]);

        $repository = new DoctrineRepository(Mockery::mock('Doctrine\DBAL\Connection'), 'stdObject', 'test_mocks');
        $repository->delete($object);
    }

    /**
     * @expectedException \Tornado\DataMapper\Exceptions\InvalidPrimaryKeyException
     *
     * @covers ::verifyPrimaryKey
     */
    public function testInvalidObjectCompoundPrimaryKeyEmpty()
    {
        $object = Mockery::mock('Tornado\DataMapper\DataObjectInterface', [
            'getPrimaryKeyName' => [],
            'getPrimaryKey' => []
        ]);

        $repository = new DoctrineRepository(Mockery::mock('Doctrine\DBAL\Connection'), 'stdObject', 'test_mocks');
        $repository->delete($object);
    }

    /**
     * @expectedException \Tornado\DataMapper\Exceptions\InvalidPrimaryKeyException
     *
     * @covers ::verifyPrimaryKey
     */
    public function testInvalidObjectCompoundPrimaryKeyCountMismatch()
    {
        $object = Mockery::mock('Tornado\DataMapper\DataObjectInterface', [
            'getPrimaryKeyName' => ['organization_id', 'id'],
            'getPrimaryKey' => [456]
        ]);

        $repository = new DoctrineRepository(Mockery::mock('Doctrine\DBAL\Connection'), 'stdObject', 'test_mocks');
        $repository->delete($object);
    }

    /**
     * @covers ::__construct
     * @covers ::createQueryBuilder
     * @covers ::create
     */
    public function testCreate()
    {
        $object = new TestObject();
        $object->loadFromArray([
            'id' => null,
            'name' => 'John Doe',
            'email' => 'john.doe@gmail.com',
            'password' => 'jane123'
        ]);

        // configure the query builder
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');

        $queryBuilder->shouldReceive('insert')
            ->with('test_mocks')
            ->andReturn(Mockery::self())
            ->once()
            ->ordered();

        $objectArray = [
            'name' => 'John Doe',
            'email' => 'john.doe@gmail.com',
            'password' => 'jane123'
        ];
        foreach ($objectArray as $key => $value) {
            $queryBuilder->shouldReceive('setValue')
                ->with($key, Mockery::mustBe(':'. $key))
                ->andReturn(Mockery::self())
                ->once();
            $queryBuilder->shouldReceive('setParameter')
                ->with($key, $value)
                ->andReturn(Mockery::self())
                ->once();
        }

        $queryBuilder->shouldReceive('execute')
            ->andReturn(1)
            ->once()
            ->ordered();

        // configure the connection
        $connection = Mockery::mock('Doctrine\DBAL\Connection');
        $connection->shouldReceive('createQueryBuilder')
            ->andReturn($queryBuilder)
            ->once();

        $connection->shouldReceive('lastInsertId')
            ->andReturn(5)
            ->once();

        // do the test
        $repository = new DoctrineRepository($connection, 'Test\Tornado\DataMapper\Fixtures\TestObject', 'test_mocks');

        $createdObject = $repository->create($object);

        $this->assertSame($object, $createdObject, 'DoctrineRepository::create() did not return the same object.');
        $this->assertEquals(
            5,
            $object->getPrimaryKey(),
            'DoctrineRepository::create() did not set primary key on the object.'
        );
    }

    /**
     * @expectedException \RuntimeException
     *
     * @covers ::__construct
     * @covers ::createQueryBuilder
     * @covers ::create
     */
    public function testCreateNotInserting()
    {
        $object = new TestObject();
        $object->loadFromArray([
            'id' => null,
            'name' => 'John Doe',
            'email' => 'john.doe@gmail.com',
            'password' => 'jane123'
        ]);

        // configure the query builder
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');

        $queryBuilder->shouldReceive('insert')
            ->with('test_mocks')
            ->andReturn(Mockery::self())
            ->once()
            ->ordered();

        $objectArray = [
            'name' => 'John Doe',
            'email' => 'john.doe@gmail.com',
            'password' => 'jane123'
        ];
        foreach ($objectArray as $key => $value) {
            $queryBuilder->shouldReceive('setValue')
                ->with($key, Mockery::mustBe(':'. $key))
                ->andReturn(Mockery::self())
                ->once();
            $queryBuilder->shouldReceive('setParameter')
                ->with($key, $value)
                ->andReturn(Mockery::self())
                ->once();
        }

        // no affected rows
        $queryBuilder->shouldReceive('execute')
            ->andReturn(0)
            ->once()
            ->ordered();

        // configure the connection
        $connection = Mockery::mock('Doctrine\DBAL\Connection');
        $connection->shouldReceive('createQueryBuilder')
            ->andReturn($queryBuilder)
            ->once();

        // do the test
        $repository = new DoctrineRepository($connection, 'Test\Tornado\DataMapper\Fixtures\TestObject', 'test_mocks');

        $repository->create($object);
    }

    /**
     * @expectedException \Tornado\DataMapper\Exceptions\AlreadySavedObjectException
     *
     * @covers ::__construct
     * @covers ::createQueryBuilder
     * @covers ::create
     */
    public function testCreateAlreadySavedObject()
    {
        // configure the test object
        $objectArray = [
            'id' => 45,
            'name' => 'John',
            'surname' => 'Doe',
            'password' => 'qwerty123',
            'email' => 'john@doe.com',
            'created_at' => 1234567890,
            'deleted_at' => null
        ];
        $object = Mockery::namedMock('DataClass', 'Tornado\DataMapper\DataObjectInterface', [
            'getPrimaryKeyName' => 'id',
            'getPrimaryKey' => $objectArray['id'],
            'toArray' => $objectArray
        ]);

        // do the test
        $repository = new DoctrineRepository(Mockery::mock('Doctrine\DBAL\Connection'), 'DataClass', 'test_mocks');
        $repository->create($object);
    }

    /**
     * @covers ::__construct
     * @covers ::createQueryBuilder
     * @covers ::addFilterToQueryBuilder
     * @covers ::verifyPrimaryKey
     * @covers ::update
     */
    public function testUpdate()
    {
        // configure the test object
        $objectArray = [
            'user_id' => 12,
            'name' => 'John',
            'surname' => 'Doe',
            'password' => 'qwerty123',
            'email' => 'john@doe.com',
            'created_at' => 1234567890,
            'deleted_at' => null
        ];
        $object = Mockery::namedMock('DataClass', 'Tornado\DataMapper\DataObjectInterface', [
            'getPrimaryKeyName' => 'user_id',
            'getPrimaryKey' => $objectArray['user_id'],
            'toArray' => $objectArray
        ]);

        // configure the query builder
        $expressionBuilder = Mockery::mock('Doctrine\DBAL\Query\Expression\ExpressionBuilder');
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');

        $queryBuilder->shouldReceive('update')
            ->with('test_mocks')
            ->andReturn(Mockery::self())
            ->once()
            ->ordered();

        foreach ($objectArray as $column => $value) {
            $queryBuilder->shouldReceive('set')
                ->with($column, Mockery::mustBe(':'. $column))
                ->andReturn(Mockery::self())
                ->once();
            $queryBuilder->shouldReceive('setParameter')
                ->with($column, $value)
                ->andReturn(Mockery::self())
                ->once();
        }

        $queryBuilder->shouldReceive('expr')->andReturn($expressionBuilder);

        $expressionBuilder->shouldReceive('eq')
            ->with('user_id', Mockery::mustBe(':'. DoctrineRepository::FILTER_PARAMETER_PREFIX .'user_id'))
            ->andReturn('expr_eq_result')
            ->once();

        $queryBuilder->shouldReceive('setParameter')
            ->with(DoctrineRepository::FILTER_PARAMETER_PREFIX .'user_id', 12)
            ->andReturn(Mockery::self())
            ->once();

        $queryBuilder->shouldReceive('andWhere')
            ->with('expr_eq_result')
            ->andReturn(Mockery::self())
            ->once();

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->ordered();

        // configure the connection
        $connection = Mockery::mock('Doctrine\DBAL\Connection');
        $connection->shouldReceive('createQueryBuilder')
            ->andReturn($queryBuilder)
            ->once();

        // do the test
        $repository = new DoctrineRepository($connection, 'DataClass', 'test_mocks');

        $repository->update($object);
    }

    /**
     * @covers ::__construct
     * @covers ::createQueryBuilder
     * @covers ::addFilterToQueryBuilder
     * @covers ::verifyPrimaryKey
     * @covers ::update
     */
    public function testUpdateWithCompoundKey()
    {
        // configure the test object
        $objectArray = [
            'user_id' => 12,
            'organization_id' => 345,
            'name' => 'John',
            'surname' => 'Doe',
            'password' => 'qwerty123',
            'email' => 'john@doe.com',
            'created_at' => 1234567890,
            'deleted_at' => null
        ];
        $object = Mockery::namedMock('DataClass', 'Tornado\DataMapper\DataObjectInterface', [
            'getPrimaryKeyName' => ['organization_id', 'user_id'],
            'getPrimaryKey' => [$objectArray['organization_id'], $objectArray['user_id']],
            'toArray' => $objectArray
        ]);

        // configure the query builder
        $expressionBuilder = Mockery::mock('Doctrine\DBAL\Query\Expression\ExpressionBuilder');
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');

        $queryBuilder->shouldReceive('update')
            ->with('test_mocks')
            ->andReturn(Mockery::self())
            ->once()
            ->ordered();

        foreach ($objectArray as $column => $value) {
            $queryBuilder->shouldReceive('set')
                ->with($column, Mockery::mustBe(':'. $column))
                ->andReturn(Mockery::self())
                ->once();
            $queryBuilder->shouldReceive('setParameter')
                ->with($column, $value)
                ->andReturn(Mockery::self())
                ->once();
        }

        $queryBuilder->shouldReceive('expr')->andReturn($expressionBuilder);

        $expressionBuilder->shouldReceive('eq')
            ->with(
                'organization_id',
                Mockery::mustBe(':'. DoctrineRepository::FILTER_PARAMETER_PREFIX .'organization_id')
            )
            ->andReturn('expr_eq_org_result')
            ->once();

        $queryBuilder->shouldReceive('setParameter')
            ->with(DoctrineRepository::FILTER_PARAMETER_PREFIX .'organization_id', 345)
            ->andReturn(Mockery::self())
            ->once();

        $expressionBuilder->shouldReceive('eq')
            ->with('user_id', Mockery::mustBe(':'. DoctrineRepository::FILTER_PARAMETER_PREFIX .'user_id'))
            ->andReturn('expr_eq_user_result')
            ->once();

        $queryBuilder->shouldReceive('setParameter')
            ->with(DoctrineRepository::FILTER_PARAMETER_PREFIX .'user_id', 12)
            ->andReturn(Mockery::self())
            ->once();

        $expressionBuilder->shouldReceive('andX')
            ->with('expr_eq_org_result', 'expr_eq_user_result')
            ->andReturn('expr_andX_result')
            ->once();

        $queryBuilder->shouldReceive('andWhere')
            ->with('expr_andX_result')
            ->andReturn(Mockery::self())
            ->once();

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->ordered();

        // configure the connection
        $connection = Mockery::mock('Doctrine\DBAL\Connection');
        $connection->shouldReceive('createQueryBuilder')
            ->andReturn($queryBuilder)
            ->once();

        // do the test
        $repository = new DoctrineRepository($connection, 'DataClass', 'test_mocks');

        $repository->update($object);
    }

    /**
     * @expectedException \Tornado\DataMapper\Exceptions\UnsavedObjectException
     *
     * @covers ::__construct
     * @covers ::createQueryBuilder
     * @covers ::addFilterToQueryBuilder
     * @covers ::verifyPrimaryKey
     * @covers ::update
     */
    public function testUpdateWithoutPrimaryKeySet()
    {
        // configure the test object
        $objectArray = [
            'name' => 'John',
            'surname' => 'Doe',
            'password' => 'qwerty123',
            'email' => 'john@doe.com',
            'created_at' => 1234567890,
            'deleted_at' => null
        ];
        $object = Mockery::namedMock('DataClass', 'Tornado\DataMapper\DataObjectInterface', [
            'getPrimaryKeyName' => 'user_id',
            'getPrimaryKey' => null,
            'toArray' => $objectArray
        ]);

        // do the test
        $repository = new DoctrineRepository(Mockery::mock('Doctrine\DBAL\Connection'), 'DataClass', 'test_mocks');

        $repository->update($object);
    }

    /**
     * @covers ::__construct
     * @covers ::createQueryBuilder
     * @covers ::addFilterToQueryBuilder
     * @covers ::delete
     */
    public function testDelete()
    {
        $object = Mockery::namedMock('DataClass', 'Tornado\DataMapper\DataObjectInterface', [
            'getPrimaryKeyName' => 'user_id',
            'getPrimaryKey' => 436
        ]);

        // configure the query builder
        $expressionBuilder = Mockery::mock('Doctrine\DBAL\Query\Expression\ExpressionBuilder');
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');

        $queryBuilder->shouldReceive('delete')
            ->with('test_mocks')
            ->andReturn(Mockery::self())
            ->once();

        $queryBuilder->shouldReceive('expr')->andReturn($expressionBuilder);

        $expressionBuilder->shouldReceive('eq')
            ->with('user_id', Mockery::mustBe(':'. DoctrineRepository::FILTER_PARAMETER_PREFIX .'user_id'))
            ->andReturn('expr_eq_result')
            ->once();

        $queryBuilder->shouldReceive('setParameter')
            ->with(DoctrineRepository::FILTER_PARAMETER_PREFIX .'user_id', 436)
            ->andReturn(Mockery::self())
            ->once();

        $queryBuilder->shouldReceive('andWhere')
            ->with('expr_eq_result')
            ->andReturn(Mockery::self())
            ->once();

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->ordered();

        // configure the connection
        $connection = Mockery::mock('Doctrine\DBAL\Connection');
        $connection->shouldReceive('createQueryBuilder')
            ->andReturn($queryBuilder)
            ->once();

        // do the test
        $repository = new DoctrineRepository($connection, 'DataClass', 'test_mocks');

        $repository->delete($object);
    }

    /**
     * @covers ::__construct
     * @covers ::createQueryBuilder
     * @covers ::addFilterToQueryBuilder
     * @covers ::verifyPrimaryKey
     * @covers ::delete
     */
    public function testDeleteWithCompoundKey()
    {
        $object = Mockery::namedMock('DataClass', 'Tornado\DataMapper\DataObjectInterface', [
            'getPrimaryKeyName' => array('organization_id', 'user_id'),
            'getPrimaryKey' => array(1345, 436)
        ]);

        // configure the query builder
        $expressionBuilder = Mockery::mock('Doctrine\DBAL\Query\Expression\ExpressionBuilder');
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');

        $queryBuilder->shouldReceive('delete')
            ->with('test_mocks')
            ->andReturn(Mockery::self())
            ->once();

        $queryBuilder->shouldReceive('expr')->andReturn($expressionBuilder);

        $expressionBuilder->shouldReceive('eq')
            ->with(
                'organization_id',
                Mockery::mustBe(':'. DoctrineRepository::FILTER_PARAMETER_PREFIX .'organization_id')
            )
            ->andReturn('expr_eq_org_result')
            ->once();

        $queryBuilder->shouldReceive('setParameter')
            ->with(DoctrineRepository::FILTER_PARAMETER_PREFIX .'organization_id', 1345)
            ->andReturn(Mockery::self())
            ->once();

        $expressionBuilder->shouldReceive('eq')
            ->with('user_id', Mockery::mustBe(':'. DoctrineRepository::FILTER_PARAMETER_PREFIX .'user_id'))
            ->andReturn('expr_eq_user_result')
            ->once();

        $queryBuilder->shouldReceive('setParameter')
            ->with(DoctrineRepository::FILTER_PARAMETER_PREFIX .'user_id', 436)
            ->andReturn(Mockery::self())
            ->once();

        $expressionBuilder->shouldReceive('andX')
            ->with('expr_eq_org_result', 'expr_eq_user_result')
            ->andReturn('expr_andX_result')
            ->once();

        $queryBuilder->shouldReceive('andWhere')
            ->with('expr_andX_result')
            ->andReturn(Mockery::self())
            ->once();

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->ordered();

        // configure the connection
        $connection = Mockery::mock('Doctrine\DBAL\Connection');
        $connection->shouldReceive('createQueryBuilder')
            ->andReturn($queryBuilder)
            ->once();

        // do the test
        $repository = new DoctrineRepository($connection, 'DataClass', 'test_mocks');

        $repository->delete($object);
    }

    /**
     * @expectedException \Tornado\DataMapper\Exceptions\UnsavedObjectException
     *
     * @covers ::__construct
     * @covers ::createQueryBuilder
     * @covers ::verifyPrimaryKey
     * @covers ::delete
     */
    public function testDeleteWithoutPrimaryKeySet()
    {
        // configure the test object
        $objectArray = [
            'name' => 'John',
            'surname' => 'Doe',
            'password' => 'qwerty123',
            'email' => 'john@doe.com',
            'created_at' => 1234567890,
            'deleted_at' => null
        ];
        $object = Mockery::namedMock('DataClass', 'Tornado\DataMapper\DataObjectInterface', [
            'getPrimaryKeyName' => 'user_id',
            'getPrimaryKey' => null,
            'toArray' => $objectArray
        ]);

        // do the test
        $repository = new DoctrineRepository(Mockery::mock('Doctrine\DBAL\Connection'), 'DataClass', 'test_mocks');

        $repository->delete($object);
    }

    /**
     * @covers ::__construct
     * @covers ::createQueryBuilder
     * @covers ::addFilterToQueryBuilder
     * @covers ::addRangeStatements
     * @covers ::find
     * @covers ::mapResult
     * @covers ::mapResults
     */
    public function testFindAll()
    {
        $dbName = 'test_objects';
        $expressionBuilder = Mockery::mock('Doctrine\DBAL\Query\Expression\ExpressionBuilder');

        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('select')->with('*')->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')->with($dbName)->andReturn($queryBuilder);

        $queryBuilder->shouldReceive('expr')->andReturn($expressionBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $queryBuilder->shouldNotReceive('andWhere');
        $queryBuilder->shouldNotReceive('orderBy');
        $queryBuilder->shouldNotReceive('addOrderBy');
        $queryBuilder->shouldNotReceive('setMaxResults');
        $queryBuilder->shouldNotReceive('setFirstResult');

        $results = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john.doe@gmail.com', 'password' => 'jane123'],
            ['id' => 5, 'name' => 'Jane Doe', 'email' => 'jane.doe@gmail.com', 'password' => 'john123'],
            ['id' => 8, 'name' => 'Jon  Snow', 'email' => 'jon@winteriscoming.com', 'password' => 'knownothing'],
            ['id' => 15, 'name' => 'Tyrion Lannister', 'email' => 'tyrion@gmail.com', 'password' => 'wine'],
            ['id' => 16, 'name' => 'Dani Targaryen', 'email' => 'mother@dragons.com', 'password' => 'drogon']
        ];

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldReceive('fetch')
            ->andReturn($results[0], $results[1], $results[2], $results[3], $results[4], null);

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($resultStatement);

        // do the test
        $repository = new DoctrineRepository(
            $connection,
            'Test\Tornado\DataMapper\Fixtures\TestObject',
            $dbName
        );

        $objects = $repository->find();

        $this->assertInternalType('array', $objects, 'DoctrineRepository::find() did not return an array.');

        foreach ($objects as $i => $object) {
            $this->assertInstanceOf('Tornado\DataMapper\DataObjectInterface', $object);
            $this->assertInstanceOf('Test\Tornado\DataMapper\Fixtures\TestObject', $object);

            $objectArray = $object->toArray();
            $this->assertEquals($results[$i], $objectArray);
        }
    }

    /**
     * @covers ::__construct
     * @covers ::createQueryBuilder
     * @covers ::addFilterToQueryBuilder
     * @covers ::count
     */
    public function testCountAll()
    {
        $dbName = 'test_objects';

        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('select')
            ->with('COUNT(id)')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')
            ->with($dbName)
            ->andReturn($queryBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $queryBuilder->shouldNotReceive('andWhere');
        $queryBuilder->shouldNotReceive('orderBy');
        $queryBuilder->shouldNotReceive('addOrderBy');
        $queryBuilder->shouldNotReceive('setMaxResults');
        $queryBuilder->shouldNotReceive('setFirstResult');

        $results = ['COUNT(id)' => 10];

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldReceive('fetch')
            ->andReturn($results);

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($resultStatement);

        // do the test
        $repository = new DoctrineRepository(
            $connection,
            'Test\Tornado\DataMapper\Fixtures\TestObject',
            $dbName
        );

        $quantity = $repository->count();
        $this->assertEquals(10, $quantity);
    }

    /**
     * @covers ::__construct
     * @covers ::createQueryBuilder
     * @covers ::addFilterToQueryBuilder
     * @covers ::addRangeStatements
     * @covers ::find
     * @covers ::mapResult
     * @covers ::mapResults
     */
    public function testFindFiltered()
    {
        $dbName = 'test_objects';
        $expressionBuilder = Mockery::mock('Doctrine\DBAL\Query\Expression\ExpressionBuilder');

        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('select')->with('*')->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')->with($dbName)->andReturn($queryBuilder);

        $queryBuilder->shouldReceive('expr')->andReturn($expressionBuilder);

        $queryBuilder->shouldNotReceive('orderBy');
        $queryBuilder->shouldNotReceive('addOrderBy');
        $queryBuilder->shouldNotReceive('setMaxResults');
        $queryBuilder->shouldNotReceive('setFirstResult');

        $expressionBuilder->shouldReceive('eq')
            ->with('password', Mockery::mustBe(':'. DoctrineRepository::FILTER_PARAMETER_PREFIX .'password'))
            ->andReturn('expr_eq_result')
            ->once();

        $queryBuilder->shouldReceive('setParameter')
            ->with(DoctrineRepository::FILTER_PARAMETER_PREFIX .'password', 'got')
            ->andReturn(Mockery::self())
            ->once();

        $queryBuilder->shouldReceive('andWhere')
            ->with('expr_eq_result')
            ->andReturn(Mockery::self())
            ->once();

        $results = [
            ['id' => 8, 'name' => 'Jon  Snow', 'email' => 'jon@winteriscoming.com', 'password' => 'got'],
            ['id' => 15, 'name' => 'Tyrion Lannister', 'email' => 'tyrion@gmail.com', 'password' => 'got'],
            ['id' => 16, 'name' => 'Dani Targaryen', 'email' => 'mother@dragons.com', 'password' => 'got']
        ];

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldReceive('fetch')
            ->andReturn($results[0], $results[1], $results[2], null);

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($resultStatement);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        // do the test
        $repository = new DoctrineRepository(
            $connection,
            'Test\Tornado\DataMapper\Fixtures\TestObject',
            $dbName
        );

        $objects = $repository->find([
            'password' => 'got'
        ]);

        $this->assertInternalType('array', $objects, 'DoctrineRepository::find() did not return an array.');

        foreach ($objects as $i => $object) {
            $this->assertInstanceOf('Tornado\DataMapper\DataObjectInterface', $object);
            $this->assertInstanceOf('Test\Tornado\DataMapper\Fixtures\TestObject', $object);

            $objectArray = $object->toArray();
            $this->assertEquals($results[$i], $objectArray);
        }
    }

    /**
     * @covers ::__construct
     * @covers ::createQueryBuilder
     * @covers ::addFilterToQueryBuilder
     * @covers ::addRangeStatements
     * @covers ::find
     * @covers ::mapResult
     * @covers ::mapResults
     */
    public function testFindFilteredWithInStmt()
    {
        $dbName = 'test_objects';
        $expressionBuilder = Mockery::mock('Doctrine\DBAL\Query\Expression\ExpressionBuilder');

        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('select')->with('*')->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')->with($dbName)->andReturn($queryBuilder);

        $queryBuilder->shouldReceive('expr')->andReturn($expressionBuilder);

        $queryBuilder->shouldNotReceive('orderBy');
        $queryBuilder->shouldNotReceive('addOrderBy');
        $queryBuilder->shouldNotReceive('setMaxResults');
        $queryBuilder->shouldNotReceive('setFirstResult');

        $ids = [1,2];
        $expressionBuilder->shouldReceive('in')
            ->once()
            ->with('id', $ids)
            ->andReturn('id IN (1,2)');

        $queryBuilder->shouldReceive('andWhere')
            ->with('id IN (1,2)')
            ->andReturn(Mockery::self())
            ->once();

        $results = [
            ['id' => 8, 'name' => 'Jon  Snow', 'email' => 'jon@winteriscoming.com', 'password' => 'got'],
            ['id' => 15, 'name' => 'Tyrion Lannister', 'email' => 'tyrion@gmail.com', 'password' => 'got'],
            ['id' => 16, 'name' => 'Dani Targaryen', 'email' => 'mother@dragons.com', 'password' => 'got']
        ];

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldReceive('fetch')
            ->andReturn($results[0], $results[1], $results[2], null);

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($resultStatement);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        // do the test
        $repository = new DoctrineRepository(
            $connection,
            'Test\Tornado\DataMapper\Fixtures\TestObject',
            $dbName
        );

        $objects = $repository->find([
            'id' => $ids
        ]);

        $this->assertInternalType('array', $objects, 'DoctrineRepository::find() did not return an array.');

        foreach ($objects as $i => $object) {
            $this->assertInstanceOf('Tornado\DataMapper\DataObjectInterface', $object);
            $this->assertInstanceOf('Test\Tornado\DataMapper\Fixtures\TestObject', $object);

            $objectArray = $object->toArray();
            $this->assertEquals($results[$i], $objectArray);
        }
    }

    /**
     * @covers ::__construct
     * @covers ::createQueryBuilder
     * @covers ::addFilterToQueryBuilder
     * @covers ::count
     */
    public function testCountFiltered()
    {
        $dbName = 'test_objects';
        $expressionBuilder = Mockery::mock('Doctrine\DBAL\Query\Expression\ExpressionBuilder');

        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('select')
            ->with('COUNT(id)')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')
            ->with($dbName)
            ->andReturn($queryBuilder);

        $queryBuilder->shouldReceive('expr')->andReturn($expressionBuilder);

        $queryBuilder->shouldNotReceive('orderBy');
        $queryBuilder->shouldNotReceive('addOrderBy');
        $queryBuilder->shouldNotReceive('setMaxResults');
        $queryBuilder->shouldNotReceive('setFirstResult');

        $expressionBuilder->shouldReceive('eq')
            ->with('password', Mockery::mustBe(':'. DoctrineRepository::FILTER_PARAMETER_PREFIX .'password'))
            ->andReturn('expr_eq_result')
            ->once();

        $queryBuilder->shouldReceive('setParameter')
            ->with(DoctrineRepository::FILTER_PARAMETER_PREFIX .'password', 'got')
            ->andReturn(Mockery::self())
            ->once();

        $queryBuilder->shouldReceive('andWhere')
            ->with('expr_eq_result')
            ->andReturn(Mockery::self())
            ->once();

        $results = ['COUNT(id)' => 10];

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldReceive('fetch')
            ->andReturn($results);

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($resultStatement);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        // do the test
        $repository = new DoctrineRepository(
            $connection,
            'Test\Tornado\DataMapper\Fixtures\TestObject',
            $dbName
        );

        $quantity = $repository->count([
            'password' => 'got'
        ]);
        $this->assertEquals(10, $quantity);
    }

    /**
     * @covers ::__construct
     * @covers ::createQueryBuilder
     * @covers ::addFilterToQueryBuilder
     * @covers ::addRangeStatements
     * @covers ::find
     * @covers ::mapResult
     * @covers ::mapResults
     */
    public function testFindSorted()
    {
        $dbName = 'test_objects';
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');

        $queryBuilder->shouldReceive('select')->with('*')->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')->with($dbName)->andReturn($queryBuilder);

        $queryBuilder->shouldNotReceive('setMaxResults');
        $queryBuilder->shouldNotReceive('setFirstResult');

        $queryBuilder->shouldReceive('addOrderBy')
            ->with('id', 'ASC')
            ->andReturn(Mockery::self())
            ->once();

        $queryBuilder->shouldReceive('addOrderBy')
            ->with('name', 'DESC')
            ->andReturn(Mockery::self())
            ->once();

        $results = [
            ['id' => 8, 'name' => 'Jon  Snow', 'email' => 'jon@winteriscoming.com', 'password' => 'got'],
            ['id' => 15, 'name' => 'Tyrion Lannister', 'email' => 'tyrion@gmail.com', 'password' => 'got'],
            ['id' => 16, 'name' => 'Dani Targaryen', 'email' => 'mother@dragons.com', 'password' => 'got']
        ];

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldReceive('fetch')
            ->andReturn($results[0], $results[1], $results[2], null);

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($resultStatement);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        // do the test
        $repository = new DoctrineRepository(
            $connection,
            'Test\Tornado\DataMapper\Fixtures\TestObject',
            $dbName
        );

        $objects = $repository->find([], [
            'id' => DataMapperInterface::ORDER_ASCENDING,
            'name' => DataMapperInterface::ORDER_DESCENDING
        ]);

        $this->assertInternalType('array', $objects, 'DoctrineRepository::find() did not return an array.');

        foreach ($objects as $i => $object) {
            $this->assertInstanceOf('Tornado\DataMapper\DataObjectInterface', $object);
            $this->assertInstanceOf('Test\Tornado\DataMapper\Fixtures\TestObject', $object);

            $objectArray = $object->toArray();
            $this->assertEquals($results[$i], $objectArray);
        }
    }

    /**
     * @covers ::__construct
     * @covers ::createQueryBuilder
     * @covers ::addFilterToQueryBuilder
     * @covers ::addRangeStatements
     * @covers ::find
     * @covers ::mapResult
     * @covers ::mapResults
     */
    public function testFindWithLimit()
    {
        $dbName = 'test_objects';
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');

        $queryBuilder->shouldReceive('select')->with('*')->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')->with($dbName)->andReturn($queryBuilder);

        $queryBuilder->shouldNotReceive('setFirstResult');

        $queryBuilder->shouldReceive('setMaxResults')
            ->with(2)
            ->andReturn(Mockery::self())
            ->once();

        $results = [
            ['id' => 15, 'name' => 'Tyrion Lannister', 'email' => 'tyrion@gmail.com', 'password' => 'got'],
            ['id' => 16, 'name' => 'Dani Targaryen', 'email' => 'mother@dragons.com', 'password' => 'got']
        ];

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldReceive('fetch')
            ->andReturn($results[0], $results[1], null);

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($resultStatement);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        // do the test
        $repository = new DoctrineRepository(
            $connection,
            'Test\Tornado\DataMapper\Fixtures\TestObject',
            $dbName
        );

        $objects = $repository->find([], [], 2);

        $this->assertCount(2, $objects);
        $this->assertInternalType('array', $objects, 'DoctrineRepository::find() did not return an array.');

        foreach ($objects as $i => $object) {
            $this->assertInstanceOf('Tornado\DataMapper\DataObjectInterface', $object);
            $this->assertInstanceOf('Test\Tornado\DataMapper\Fixtures\TestObject', $object);

            $objectArray = $object->toArray();
            $this->assertEquals($results[$i], $objectArray);
        }
    }

    /**
     * @covers ::__construct
     * @covers ::createQueryBuilder
     * @covers ::addFilterToQueryBuilder
     * @covers ::addRangeStatements
     * @covers ::find
     * @covers ::mapResult
     * @covers ::mapResults
     */
    public function testFindWithOffset()
    {
        $dbName = 'test_objects';

        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');

        $queryBuilder->shouldReceive('select')->with('*')->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')->with($dbName)->andReturn($queryBuilder);

        $queryBuilder->shouldReceive('setFirstResult')
            ->with(10)
            ->andReturn(Mockery::self())
            ->once();

        $results = [
            ['id' => 15, 'name' => 'Tyrion Lannister', 'email' => 'tyrion@gmail.com', 'password' => 'got'],
            ['id' => 16, 'name' => 'Dani Targaryen', 'email' => 'mother@dragons.com', 'password' => 'got']
        ];

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldReceive('fetch')
            ->andReturn($results[0], $results[1], null);

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($resultStatement);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        // do the test
        $repository = new DoctrineRepository(
            $connection,
            'Test\Tornado\DataMapper\Fixtures\TestObject',
            $dbName
        );

        $objects = $repository->find([], [], 0, 10);

        $this->assertInternalType('array', $objects, 'DoctrineRepository::find() did not return an array.');

        foreach ($objects as $i => $object) {
            $this->assertInstanceOf('Tornado\DataMapper\DataObjectInterface', $object);
            $this->assertInstanceOf('Test\Tornado\DataMapper\Fixtures\TestObject', $object);

            $objectArray = $object->toArray();
            $this->assertEquals($results[$i], $objectArray);
        }
    }

    /**
     * @covers ::__construct
     * @covers ::createQueryBuilder
     * @covers ::addFilterToQueryBuilder
     * @covers ::addRangeStatements
     * @covers ::find
     * @covers ::findOne
     * @covers ::mapResult
     * @covers ::mapResults
     */
    public function testFindOne()
    {
        $dbName = 'test_objects';
        $expressionBuilder = Mockery::mock('Doctrine\DBAL\Query\Expression\ExpressionBuilder');

        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');

        $queryBuilder->shouldReceive('select')->with('*')->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')->with($dbName)->andReturn($queryBuilder);

        $queryBuilder->shouldReceive('expr')->andReturn($expressionBuilder);

        $queryBuilder->shouldNotReceive('orderBy');
        $queryBuilder->shouldNotReceive('addOrderBy');
        $queryBuilder->shouldNotReceive('setFirstResult');

        $queryBuilder->shouldReceive('setMaxResults')
            ->with(1)
            ->andReturn(Mockery::self())
            ->once();

        $expressionBuilder->shouldReceive('eq')
            ->with('id', Mockery::mustBe(':'. DoctrineRepository::FILTER_PARAMETER_PREFIX .'id'))
            ->andReturn('expr_eq_result')
            ->once();

        $queryBuilder->shouldReceive('setParameter')
            ->with(DoctrineRepository::FILTER_PARAMETER_PREFIX .'id', 15)
            ->andReturn(Mockery::self())
            ->once();

        $queryBuilder->shouldReceive('andWhere')
            ->with('expr_eq_result')
            ->andReturn(Mockery::self())
            ->once();

        $results = [
            ['id' => 15, 'name' => 'Tyrion Lannister', 'email' => 'tyrion@gmail.com', 'password' => 'got']
        ];

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldReceive('fetch')
            ->andReturn($results[0], null);

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($resultStatement);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        // do the test
        $repository = new DoctrineRepository(
            $connection,
            'Test\Tornado\DataMapper\Fixtures\TestObject',
            $dbName
        );

        $object = $repository->findOne(['id' => 15]);

        $this->assertInstanceOf('Tornado\DataMapper\DataObjectInterface', $object);
        $this->assertInstanceOf('Test\Tornado\DataMapper\Fixtures\TestObject', $object);

        $objectArray = $object->toArray();
        $this->assertEquals($results[0], $objectArray);
    }

    /**
     * @covers ::__construct
     * @covers ::createQueryBuilder
     * @covers ::addFilterToQueryBuilder
     * @covers ::addRangeStatements
     * @covers ::find
     * @covers ::findOne
     * @covers ::mapResult
     * @covers ::mapResults
     */
    public function testFindOneReturnsNullWhenNoResult()
    {
        $dbName = 'test_objects';
        $expressionBuilder = Mockery::mock('Doctrine\DBAL\Query\Expression\ExpressionBuilder');

        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('select')->with('*')->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')->with($dbName)->andReturn($queryBuilder);

        $queryBuilder->shouldReceive('expr')->andReturn($expressionBuilder);

        $queryBuilder->shouldNotReceive('orderBy');
        $queryBuilder->shouldNotReceive('addOrderBy');
        $queryBuilder->shouldNotReceive('setFirstResult');

        $queryBuilder->shouldReceive('setMaxResults')
            ->with(1)
            ->andReturn(Mockery::self())
            ->once();

        $expressionBuilder->shouldReceive('eq')
            ->with('id', Mockery::mustBe(':'. DoctrineRepository::FILTER_PARAMETER_PREFIX .'id'))
            ->andReturn('expr_eq_result')
            ->once();

        $queryBuilder->shouldReceive('setParameter')
            ->with(DoctrineRepository::FILTER_PARAMETER_PREFIX .'id', 55)
            ->andReturn(Mockery::self())
            ->once();

        $queryBuilder->shouldReceive('andWhere')
            ->with('expr_eq_result')
            ->andReturn(Mockery::self())
            ->once();

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldReceive('fetch')
            ->andReturn(null);

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($resultStatement);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        // do the test
        $repository = new DoctrineRepository(
            $connection,
            'Test\Tornado\DataMapper\Fixtures\TestObject',
            $dbName
        );

        $object = $repository->findOne(['id' => 55]);

        $this->assertNull($object);
    }
}
