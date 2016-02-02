<?php

namespace Test\Tornado\Project\Workbook;

use Mockery;

use Tornado\Project\Workbook\DataMapper;

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
 * @coversDefaultClass \Tornado\Project\Workbook\DataMapper
 */
class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::find
     */
    public function testDefaultSortBy()
    {
        $dbName = 'workbook';
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
                'project_id' => 1,
                'name' => 'Workbook 1',
                'recording_id' => 5
            ],
            [
                'id' => 2,
                'project_id' => 1,
                'name' => 'Workbook 2',
                'recording_id' => 6
            ],
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
            'Tornado\Project\Workbook',
            $dbName
        );

        $objects = $repository->find();

        $this->assertInternalType('array', $objects, 'DoctrineRepository::find() did not return an array.');

        foreach ($objects as $object) {
            $this->assertInstanceOf('Tornado\DataMapper\DataObjectInterface', $object);
            $this->assertInstanceOf('Tornado\Project\Workbook', $object);
        }
    }

    /**
     * @covers ::findByProject
     */
    public function testFindByProject()
    {
        $projectId = 20;
        $project = Mockery::mock('Tornado\Project\Project[getPrimaryKey]');
        $project->shouldReceive('getPrimaryKey')->andReturn($projectId);

        $filter = ['a' => 'b'];
        $expectedFilter = array_merge($filter, ['project_id' => $projectId]);

        $response = [
            'project_id' => $projectId,
            'c' => 'd',
            'e' => 'f'
        ];

        $sortBy = ['name' => 'ASC'];
        $limit = 20;
        $offset = 30;

        $repository = Mockery::mock(
            'Tornado\Project\Workbook\DataMapper[find]',
            [Mockery::mock('Doctrine\DBAL\Connection'), 'stdObject', 'project']
        );

        $repository->shouldReceive('find')
            ->with($expectedFilter, $sortBy, $limit, $offset)
            ->andReturn($response);

        $this->assertEquals($response, $repository->findByProject(
            $project,
            $filter,
            $sortBy,
            $limit,
            $offset
        ));
    }

    /**
     * @covers ::findOneByProject
     */
    public function testFindOneByProject()
    {
        $projectId = 20;
        $project = Mockery::mock('Tornado\Project\Project[getPrimaryKey]');
        $project->shouldReceive('getPrimaryKey')->andReturn($projectId);

        $workbookId = 3;

        $expectedFilter = [
            'id' => $workbookId,
            'project_id' => $projectId
        ];

        $response = [
            'workbook' => $workbookId
        ];

        $repository = Mockery::mock(
            'Tornado\Project\Workbook\DataMapper[findOne]',
            [Mockery::mock('Doctrine\DBAL\Connection'), 'stdObject', 'project']
        );

        $repository->shouldReceive('findOne')
            ->with($expectedFilter)
            ->andReturn($response);

        $this->assertEquals($response, $repository->findOneByProject($workbookId, $project));
    }

    /**
     * @covers ::findOneByWorksheet
     */
    public function testFindOneByWorksheet()
    {
        $workbookId = 13;
        $worksheet = Mockery::mock('Tornado\Project\Worksheet', [
            'getWorkbookId' => $workbookId
        ]);

        $expectedFilter = [
            'id' => $workbookId
        ];

        $response = [
            'workbook' => $workbookId
        ];

        $repository = Mockery::mock(
            'Tornado\Project\Workbook\DataMapper[findOne]',
            [Mockery::mock('Doctrine\DBAL\Connection'), 'stdObject', 'project']
        );

        $repository->shouldReceive('findOne')
            ->with($expectedFilter)
            ->andReturn($response);

        $this->assertEquals($response, $repository->findOneByWorksheet($worksheet));
    }
}
