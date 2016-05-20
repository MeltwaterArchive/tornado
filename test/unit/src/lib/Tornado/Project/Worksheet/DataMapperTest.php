<?php

namespace Test\Tornado\Project\Worksheet;

use Mockery;

use Tornado\Analyze\Analysis;
use Tornado\Project\Chart;
use Tornado\Project\Chart\Generator;
use Tornado\Project\Workbook;
use Tornado\Project\Worksheet;
use Tornado\Project\Worksheet\DataMapper;

use \Tornado\DataMapper\DataMapperInterface;

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
 * @coversDefaultClass \Tornado\Project\Worksheet\DataMapper
 */
class DataMapperTest extends \PHPUnit_Framework_TestCase
{

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @covers \Tornado\Project\Worksheet\DataMapper::find
     */
    public function testDefaultSortBy()
    {
        $dbName = 'worksheet';
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
                'workbook_id' => 53,
                'name' => 'Demographics',
                'rank' => 0,
                'comparison' => Generator::MODE_BASELINE,
                'measurement' => Generator::MEASURE_UNIQUE_AUTHORS,
                'chart_type' => Chart::TYPE_TORNADO,
                'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                'secondary_recording_id' => null,
                'secondary_recording_filters' => null,
                'baseline_dataset_id' => null,
                'filters' => null,
                'dimensions' => null,
                'start' => null,
                'end' => null,
                'parent_worksheet_id' => null,
                'created_at' => null,
                'updated_at' => null
            ],
            [
                'id' => 3,
                'workbook_id' => 53,
                'name' => 'Volume',
                'rank' => 1,
                'comparison' => Generator::MODE_BASELINE,
                'measurement' => Generator::MEASURE_UNIQUE_AUTHORS,
                'chart_type' => Chart::TYPE_TORNADO,
                'analysis_type' => Analysis::TYPE_FREQUENCY_DISTRIBUTION,
                'secondary_recording_id' => null,
                'secondary_recording_filters' => null,
                'baseline_dataset_id' => null,
                'filters' => null,
                'dimensions' => null,
                'start' => null,
                'end' => null,
                'parent_worksheet_id' => null,
                'created_at' => null,
                'updated_at' => null
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
            'Tornado\Project\Worksheet',
            $dbName
        );

        $objects = $repository->find();

        $this->assertInternalType('array', $objects, 'DoctrineRepository::find() did not return an array.');

        foreach ($objects as $i => $object) {
            $this->assertInstanceOf('Tornado\DataMapper\DataObjectInterface', $object);
            $this->assertInstanceOf('Tornado\Project\Worksheet', $object);

            // expected value of Worksheet::toArray for null filters is {}
            $results[$i]['secondary_recording_filters'] = '{}';
            $results[$i]['filters'] = '{}';
            $results[$i]['display_options'] = '{}';

            $objectArray = $object->toArray();

            $this->assertEquals($results[$i], $objectArray);
        }
    }

    public function testFindByWorkbooks()
    {
        $dbName = 'worksheet';

        $workbookIds = [12, 34, 435, 2, 567];
        $workbooks = [];
        foreach ($workbookIds as $id) {
            $workbook = new Workbook();
            $workbook->setId($id);
            $workbooks[] = $workbook;
        }

        $inExpr = 'workbook_id IN ('. implode(',', $workbookIds) .')';

        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $expressionBuilder = Mockery::mock('\Doctrine\DBAL\Query\Expression\ExpressionBuilder');
        $expressionBuilder->shouldReceive('in')
            ->with('workbook_id', $workbookIds)
            ->andReturn($inExpr);
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
            ->with('where', $inExpr)
            ->andReturn($queryBuilder);

        $queryBuilder->shouldReceive('addOrderBy')
            ->with('rank', 'ASC')
            ->andReturn($queryBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $results = [
            [
                'id' => 1,
                'workbook_id' => 2,
                'name' => 'test worksheet'
            ],
            [
                'id' => 2,
                'workbook_id' => 12,
                'name' => 'test worksheet 12'
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
            'Tornado\Project\Worksheet',
            $dbName
        );

        $objects = $repository->findByWorkbooks($workbooks);

        $this->assertInternalType('array', $objects);
        $this->assertCount(2, $objects);

        foreach ($objects as $index => $object) {
            $this->assertInstanceOf('\Tornado\Project\Worksheet', $object);
            $this->assertEquals($results[$index]['id'], $object->getId());
        }
    }

    /**
     * @covers \Tornado\Project\Worksheet\DataMapper::findByWorkbooks
     */
    public function testFindByEmptyWorkbooks()
    {
        $repository = Mockery::mock(
            'Tornado\Project\Worksheet\DataMapper[find]',
            [Mockery::mock('Doctrine\DBAL\Connection'), 'stdObject', 'worksheet']
        );

        $worksheets = $repository->findByWorkbooks([]);
        $this->assertInternalType('array', $worksheets);
        $this->assertEmpty($worksheets);
    }

    /**
     * @covers \Tornado\Project\Worksheet\DataMapper::findByWorkbook
     */
    public function testFindByWorkbook()
    {
        $workbookId = 20;
        $workbook = new Workbook();
        $workbook->setId($workbookId);

        $filter = ['a' => 'b'];
        $expectedFilter = array_merge($filter, ['workbook_id' => $workbookId]);

        $response = [];
        for ($i = 1; $i < 5; $i++) {
            $worksheet = new Worksheet();
            $worksheet->setId($i);
            $worksheet->setWorkbookId($workbookId);
            $response[] = $worksheet;
        }

        $sortBy = ['name' => 'ASC'];
        $limit = 20;
        $offset = 30;

        $repository = Mockery::mock(
            'Tornado\Project\Worksheet\DataMapper[find]',
            [Mockery::mock('Doctrine\DBAL\Connection'), 'stdObject', 'worksheet']
        );

        $repository->shouldReceive('find')
            ->with($expectedFilter, $sortBy, $limit, $offset)
            ->andReturn($response);

        $this->assertEquals($response, $repository->findByWorkbook(
            $workbook,
            $filter,
            $sortBy,
            $limit,
            $offset
        ));
    }

    /**
     * @covers \Tornado\Project\Worksheet\DataMapper::findOneByWorkbook
     */
    public function testFindOneByWorkbook()
    {
        $workbookId = 20;
        $workbook = Mockery::mock('Tornado\Project\Workbook[getPrimaryKey]');
        $workbook->shouldReceive('getPrimaryKey')->andReturn($workbookId);

        $worksheetId = 14;
        $worksheet = Mockery::mock('Tornado\Project\Worksheet');

        $repository = Mockery::mock(
            'Tornado\Project\Worksheet\DataMapper[findOne]',
            [Mockery::mock('Doctrine\DBAL\Connection'), 'stdObject', 'worksheet']
        );

        $repository->shouldReceive('findOne')
            ->with([
                'id' => $worksheetId,
                'workbook_id' => $workbookId
            ])
            ->andReturn($worksheet);

        $this->assertSame($worksheet, $repository->findOneByWorkbook($worksheetId, $workbook));
    }

    /**
     * DataProvider for testGetNextRank
     *
     * @return array
     */
    public function getNextRankProvider()
    {
        return [
            'Existing' => [
                'worksheet' => Mockery::mock(
                    '\Tornado\Project\Worksheet',
                    [],
                    ['getWorkbookId' => 20, 'getId' => null]
                ),
                'workbookId' => 20,
                'lastWorksheetCollection' => [
                    Mockery::mock('\Tornado\Project\Worksheet', [], ['getRank' => 5])
                ],
                'expected' => 6
            ],
            'No worksheets existing' => [
                'worksheet' => Mockery::mock(
                    '\Tornado\Project\Worksheet',
                    [],
                    ['getWorkbookId' => 30, 'getId' => null]
                ),
                'workbookId' => 30,
                'lastWorksheetCollection' => [],
                'expected' => 1
            ],
            'Worksheet already saved' => [
                'worksheet' => Mockery::mock(
                    '\Tornado\Project\Worksheet',
                    [],
                    ['getWorkbookId' => 30, 'getId' => 10, 'getRank' => 5]
                ),
                'workbookId' => 30,
                'lastWorksheetCollection' => [],
                'expected' => 5
            ]
        ];
    }

    /**
     * @dataProvider getNextRankProvider
     *
     * @covers ::getNextRank
     *
     * @param \Tornado\Project\Worksheet $worksheet
     * @param integer $workbookId
     * @param array $lastWorksheetCollection
     * @param integer $expected
     */
    public function testGetNextRank(Worksheet $worksheet, $workbookId, array $lastWorksheetCollection, $expected)
    {
        $repo = Mockery::mock(
            'Tornado\Project\Worksheet\DataMapper[find]',
            [Mockery::mock('Doctrine\DBAL\Connection'), 'stdObject', 'worksheet']
        );

        $repo->shouldReceive('find')
            ->with(['workbook_id' => $workbookId], ['rank' => DataMapperInterface::ORDER_DESCENDING], 1)
            ->andReturn($lastWorksheetCollection);

        $this->assertEquals($expected, $repo->getNextRank($worksheet));
    }

    /**
     * DataProvider for testGetUniqueName
     *
     * @return array
     */
    public function getUniqueNameProvider()
    {
        return [
            'Nothing exists yet' => [
                'originalName' => 'My name',
                'worksheets' => [],
                'expected' => 'My name'
            ],
            'One exists' => [
                'originalName' => 'My name',
                'worksheets' => ['My name'],
                'expected' => 'My name (1)'
            ],
            'Two exist' => [
                'originalName' => 'My name',
                'worksheets' => ['My name', 'My name (1)'],
                'expected' => 'My name (2)'
            ],
        ];
    }

    /**
     * @dataProvider getUniqueNameProvider
     *
     * @covers ::getUniqueName
     *
     * @param string $originalName
     * @param array $worksheets
     * @param integer $expected
     */
    public function testGetUniqueName($originalName, array $worksheets, $expected)
    {
        $workbookId = 20;
        $worksheet = new Worksheet();
        $worksheet->setWorkbookId($workbookId);

        $repo = Mockery::mock(
            'Tornado\Project\Worksheet\DataMapper[findOne]',
            [Mockery::mock('Doctrine\DBAL\Connection'), 'stdObject', 'worksheet']
        );

        foreach ($worksheets as $existingName) {
            $repo->shouldReceive('findOne')
                ->with(['workbook_id' => $workbookId, 'name' => $existingName])
                ->andReturn(new Worksheet());
        }

        $repo->shouldReceive('findOne')
            ->with(['workbook_id' => $workbookId, 'name' => $expected])
            ->andReturn(false);

        $this->assertEquals($expected, $repo->getUniqueName($worksheet, $originalName));
    }
}
