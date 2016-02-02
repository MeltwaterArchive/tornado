<?php

namespace Test\Tornado\Project\Chart;

use Mockery;

use Tornado\Project\Chart\DataMapper;

/**
 * RepositoryTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\Project
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\Project\Chart\DataMapper
 */
class RepositoryTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers ::find
     */
    public function testDefaultSortBy()
    {
        $dbName = 'chart';
        $expressionBuilder = Mockery::mock('Doctrine\DBAL\Query\Expression\ExpressionBuilder');

        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('select')->with('*')->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')->with($dbName)->andReturn($queryBuilder);

        $queryBuilder->shouldReceive('expr')->andReturn($expressionBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        // make sure that default order is "rant ASC" when no order defined
        $queryBuilder->shouldReceive('addOrderBy')
            ->with('rank', 'ASC')
            ->once();

        $queryBuilder->shouldNotReceive('andWhere');
        $queryBuilder->shouldNotReceive('setMaxResults');
        $queryBuilder->shouldNotReceive('setFirstResult');

        $results = [
            [
                'id' => 1,
                'worksheet_id' => 53,
                'name' => 'Demographics',
                'type' => 'tornado',
                'rank' => 0,
                'data' => new \stdClass()
            ],
            [
                'id' => 3,
                'worksheet_id' => 53,
                'name' => 'Volume',
                'type' => 'tornado',
                'rank' => 1,
                'data' => new \stdClass()
            ],
            [
                'id' => 2,
                'worksheet_id' => 53,
                'name' => 'Topics',
                'type' => 'tornado',
                'rank' => 2,
                'data' => new \stdClass()
            ]
        ];

        $resultStatement = Mockery::mock('Doctrine\DBAL\Driver\ResultStatement');
        $resultStatement->shouldReceive('fetch')
            ->andReturn($results[0], $results[1], $results[2], null);

        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($resultStatement);

        // do the test
        $repository = new DataMapper(
            $connection,
            'Tornado\Project\Chart',
            $dbName
        );

        $objects = $repository->find();

        $this->assertInternalType('array', $objects, 'DoctrineRepository::find() did not return an array.');

        foreach ($objects as $i => $object) {
            $this->assertInstanceOf('Tornado\DataMapper\DataObjectInterface', $object);
            $this->assertInstanceOf('Tornado\Project\Chart', $object);

            $objectArray = $object->toArray();
            $this->assertEquals($results[$i], $objectArray);
        }
    }

    /**
     * @covers ::findByWorksheet
     */
    public function testFindByWorksheet()
    {
        $worksheetId = 20;
        $worksheet = Mockery::mock('Tornado\Project\Worksheet[getPrimaryKey]');
        $worksheet->shouldReceive('getPrimaryKey')->andReturn($worksheetId);

        $filter = ['a' => 'b'];
        $expectedFilter = array_merge($filter, ['worksheet_id' => $worksheetId]);

        $response = [
            'worksheet_id' => $worksheetId,
            'c' => 'd',
            'e' => 'f'
        ];

        $sortBy = ['name' => 'ASC'];
        $limit = 20;
        $offset = 30;

        $repository = Mockery::mock(
            'Tornado\Project\Chart\DataMapper[find]',
            [Mockery::mock('Doctrine\DBAL\Connection'), 'stdClass', 'chart']
        );

        $repository->shouldReceive('find')
            ->with($expectedFilter, $sortBy, $limit, $offset)
            ->andReturn($response);

        $this->assertEquals($response, $repository->findByWorksheet(
            $worksheet,
            $filter,
            $sortBy,
            $limit,
            $offset
        ));
    }

    /**
     * @covers ::deleteByWorksheet
     */
    public function testDeleteByWorksheet()
    {
        $dbName = 'chart';
        $worksheet = Mockery::mock('\Tornado\Project\Worksheet', [
            'getId' => 1
        ]);
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('delete')
            ->with($dbName)
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('where')
            ->with('worksheet_id = :worksheetId')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('setParameter')
            ->with('worksheetId', 1)
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
            'Tornado\Organization\Chart',
            $dbName
        );

        $results = $repository->deleteByWorksheet($worksheet);

        $this->assertInternalType('integer', $results);
        $this->assertEquals($removed, $results);
    }
}
