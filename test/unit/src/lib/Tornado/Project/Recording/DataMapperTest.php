<?php

namespace Test\Tornado\Project\Recording;

use \Mockery;

use Tornado\Project\Project;
use Tornado\Project\Recording;
use Tornado\Project\Recording\DataMapper;

/**
 * DataMapperTest
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
 * @coversDefaultClass \Tornado\Project\Recording\DataMapper
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
     * @covers ::findByBrand
     */
    public function testFindByBrand()
    {
        $brandId = 10;
        $brandMock = $this->getMockObject('Tornado\Organization\Brand');
        $brandMock->expects($this->once())
            ->method('getPrimaryKey')
            ->willReturn($brandId);

        $filter = ['a' => 'b'];
        $expectedFilter = array_merge($filter, ['brand_id' => $brandId]);

        $response = [
            'brand_id' => $brandId,
            'c' => 'd',
            'e' => 'f'
        ];

        $sortBy = ['name' => 'ASC'];
        $limit = 20;
        $offset = 30;

        $repositoryMock = $this->getMockBuilder('Tornado\Project\Recording\DataMapper')
            ->setConstructorArgs([
                $this->getMockObject('\Doctrine\DBAL\Connection'),
                'stdObject',
                'recording'
            ])
            ->setMethods(['find'])
            ->getMock();
        $repositoryMock->expects($this->once())
            ->method('find')
            ->with($expectedFilter, $sortBy, $limit, $offset)
            ->willReturn($response);

        $this->assertEquals($response, $repositoryMock->findByBrand(
            $brandMock,
            $filter,
            $sortBy,
            $limit,
            $offset
        ));
    }

    /**
     * @covers ::findOneByWorkbook
     */
    public function testFindOneByWorkbook()
    {
        $recordingId = 1235;

        $workbookMock = $this->getMockObject('Tornado\Project\Workbook');
        $workbookMock->expects($this->once())
            ->method('getRecordingId')
            ->willReturn($recordingId);

        $repositoryMock = $this->getMockBuilder('Tornado\Project\Recording\DataMapper')
            ->setConstructorArgs([
                $this->getMockObject('\Doctrine\DBAL\Connection'),
                'stdObject',
                'recording'
            ])
            ->setMethods(['findOne'])
            ->getMock();

        $response = [
            'recording_id' => $recordingId
        ];

        $repositoryMock->expects($this->once())
            ->method('findOne')
            ->with([
                'id' => $recordingId
            ])
            ->willReturn($response);

        $this->assertEquals($response, $repositoryMock->findOneByWorkbook($workbookMock));
    }

    /**
     * @covers ::findRecordingsByBrand
     */
    public function testFindRecordingsByBrand()
    {
        $dbName = 'recording';
        $brandId = 1;
        $brand = Mockery::mock('\Tornado\Organization\Brand', [
            'getId' => $brandId
        ]);
        $ids = [1, 2];
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $expressionBuilder = Mockery::mock('Doctrine\DBAL\Query\Expression\ExpressionBuilder');
        $expressionBuilder->shouldReceive('in')
            ->once()
            ->with('id', $ids)
            ->andReturn('id IN (1,2)');
        $queryBuilder->shouldReceive('select')
            ->with('*')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')
            ->with($dbName)
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('expr')
            ->once()
            ->withNoArgs()
            ->andReturn($expressionBuilder);
        $queryBuilder->shouldReceive('add')
            ->once()
            ->with('where', 'id IN (1,2)')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('andWhere')
            ->with('brand_id = :brandId')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('setParameter')
            ->with('brandId', $brandId)
            ->andReturn($queryBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $results = [
            [
                'id' => 1,
                'brand_id' => $brandId,
                'name' => 'test'
            ],
            [
                'id' => 2,
                'brand_id' => $brandId,
                'name' => 'test2'
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
            'Tornado\Project\Recording',
            $dbName
        );

        $objects = $repository->findRecordingsByBrand($brand, $ids);

        $this->assertInternalType('array', $objects);
        $this->assertCount(2, $objects);

        foreach ($objects as $object) {
            $this->assertInstanceOf('Tornado\DataMapper\DataObjectInterface', $object);
            $this->assertInstanceOf('Tornado\Project\Recording', $object);
        }
    }

    /**
     * @covers ::deleteRecordings
     */
    public function testDeleteRecordings()
    {
        $dbName = 'recording';
        $recordings = [];
        for ($i = 1; $i < 5; $i++) {
            $rec = new Recording();
            $rec->setId($i);

            $recordings[] = $rec;
        }

        $ids = [1, 2, 3, 4];
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $expressionBuilder = Mockery::mock('Doctrine\DBAL\Query\Expression\ExpressionBuilder');
        $expressionBuilder->shouldReceive('in')
            ->once()
            ->with('id', $ids)
            ->andReturn('id IN (1,2,3,4)');
        $queryBuilder->shouldReceive('delete')
            ->with($dbName)
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('expr')
            ->once()
            ->withNoArgs()
            ->andReturn($expressionBuilder);
        $queryBuilder->shouldReceive('add')
            ->once()
            ->with('where', 'id IN (1,2,3,4)')
            ->andReturn($queryBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $removed = 4;
        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($removed);

        // do the test
        $repository = new DataMapper(
            $connection,
            'Tornado\Project\Recording',
            $dbName
        );

        $result = $repository->deleteRecordings($recordings);

        $this->assertEquals($removed, $result);
    }

    /**
     * @covers ::findByProject
     */
    public function testFindByProject()
    {
        $dbName = 'recording';
        $projectId = 1;
        $project = new Project();
        $project->setId($projectId);
        $project->setRecordingFilter(Project::RECORDING_FILTER_API);

        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('select')
            ->once()
            ->with('*')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')
            ->once()
            ->with($dbName)
            ->andReturn($queryBuilder);

        $expressionBuilder = Mockery::mock('Doctrine\DBAL\Query\Expression\ExpressionBuilder');
        $expressionBuilder->shouldReceive('eq')
            ->once()
            ->with('project_id', ':project_id')
            ->andReturn('project_id = :project_id');
        $queryBuilder->shouldReceive('expr')
            ->withNoArgs()
            ->andReturn($expressionBuilder);
        $queryBuilder->shouldReceive('where')
            ->once()
            ->with('project_id = :project_id')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('setParameter')
            ->with('project_id', $projectId)
            ->andReturn($queryBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $results = [
            [
                'id' => 1,
                'project_id' => $projectId,
                'name' => 'test rec'
            ],
            [
                'id' => 2,
                'project_id' => $projectId,
                'name' => 'test rec 12'
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
            'Tornado\Project\Recording',
            $dbName
        );

        $objects = $repository->findByProject($project);

        $this->assertInternalType('array', $objects);
        $this->assertCount(2, $objects);

        foreach ($objects as $index => $object) {
            $this->assertInstanceOf('\Tornado\Project\Recording', $object);
            $this->assertEquals($results[$index]['id'], $object->getId());
        }
    }

    /**
     * @covers ::findByProject
     */
    public function testFindByProjectIncludingBrand()
    {
        $dbName = 'recording';
        $projectId = 1;
        $brandId = 20;
        $project = new Project();
        $project->setId($projectId);
        $project->setBrandId($brandId);

        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $queryBuilder->shouldReceive('select')
            ->once()
            ->with('*')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('from')
            ->once()
            ->with($dbName)
            ->andReturn($queryBuilder);

        $expressionBuilder = Mockery::mock('Doctrine\DBAL\Query\Expression\ExpressionBuilder');
        $expressionBuilder->shouldReceive('eq')
            ->once()
            ->with('project_id', ':project_id')
            ->andReturn('project_id = :project_id');
        $queryBuilder->shouldReceive('expr')
            ->withNoArgs()
            ->andReturn($expressionBuilder);
        $queryBuilder->shouldReceive('where')
            ->once()
            ->with('project_id = :project_id')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('setParameter')
            ->with('project_id', $projectId)
            ->andReturn($queryBuilder);

        $expressionBuilder->shouldReceive('eq')
            ->once()
            ->with('brand_id', ':brand_id')
            ->andReturn('brand_id = :brand_id');
        $queryBuilder->shouldReceive('orWhere')
            ->once()
            ->with('brand_id = :brand_id')
            ->andReturn($queryBuilder);
        $queryBuilder->shouldReceive('setParameter')
            ->with('brand_id', $brandId)
            ->andReturn($queryBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $results = [
            [
                'id' => 1,
                'project_id' => $projectId,
                'brand_id' => 13,
                'name' => 'test rec'
            ],
            [
                'id' => 2,
                'project_id' => 88,
                'brand_id' => $brandId,
                'name' => 'test rec 12'
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
            'Tornado\Project\Recording',
            $dbName
        );

        $objects = $repository->findByProject($project);

        $this->assertInternalType('array', $objects);
        $this->assertCount(2, $objects);

        foreach ($objects as $index => $object) {
            $this->assertInstanceOf('\Tornado\Project\Recording', $object);
            $this->assertEquals($results[$index]['id'], $object->getId());
        }
    }

    /**
     * @covers ::findByProjectIds
     */
    public function testFindByProjectIds()
    {
        $dbName = 'recording';
        $ids = [1,2];
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $expressionBuilder = Mockery::mock('\Doctrine\DBAL\Query\Expression\ExpressionBuilder');
        $expressionBuilder->shouldReceive('in')
            ->with('project_id', $ids)
            ->andReturn('project_id IN (1,2)');
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
            ->with('where', 'project_id IN (1,2)')
            ->andReturn($queryBuilder);

        $connection = Mockery::mock('Doctrine\DBAL\Connection', [
            'createQueryBuilder' => $queryBuilder
        ]);

        $results = [
            [
                'id' => 1,
                'project_id' => 1,
                'name' => 'test',
            ],
            [
                'id' => 2,
                'project_id' => 2,
                'name' => 'test2',
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
            'Tornado\Project\Recording',
            $dbName
        );

        $objects = $repository->findByProjectIds($ids);

        $this->assertInternalType('array', $objects);
        $this->assertCount(2, $objects);

        foreach ($objects as $index => $object) {
            $this->assertInstanceOf('\Tornado\Project\Recording', $object);
            $this->assertEquals($results[$index]['id'], $object->getId());
            $this->assertEquals($results[$index]['name'], $object->getName());
            $this->assertEquals($results[$index]['project_id'], $object->getProjectId());
        }
    }

    /**
     * DataProvider for testImportRecording
     *
     * @return array
     */
    public function importRecordingProvider()
    {
        return [
            'Happy path' => [
                'pylon' => $this->getPylon(
                    [
                        'hash' => 'abc123abc123abc123abc123abc123ab',
                        'status' => 'stopped',
                        'volume' => 123456,
                        'name' => 'Test Name',
                        'start' => 2345678
                    ]
                ),
                'id' => 'abc123abc123abc123abc123abc123ab',
                'expectedGetters' => [
                    'getDataSiftRecordingId' => 'abc123abc123abc123abc123abc123ab',
                    'getHash' => 'abc123abc123abc123abc123abc123ab',
                    'getStatus' => Recording::STATUS_STOPPED,
                    'getVolume' => 123456,
                    'getName' => 'Test Name',
                    'getCreatedAt' => 2345678
                ]
            ],
            'Null path' => [
                'pylon' => $this->getPylon(
                    [
                        'hash' => null,
                        'status' => 'stopped',
                        'volume' => 123456,
                        'name' => 'Test Name',
                        'start' => 2345678
                    ]
                ),
                'id' => 'abc123abc123abc123abc123abc123ab',
                'expectedGetters' => [],
                'nullExpected' => true
            ],
            'Not found' => [
                'pylon' => $this->getPylon([]),
                'id' => 'abc123abc123abc123abc123abc123ab',
                'expectedGetters' => [],
                'nullExpected' => true,
                'expectedException' => '',
                'clientException' => new \DataSift_Exception_APIError('Not found', 404)
            ],
            'Internal server error' => [
                'pylon' => $this->getPylon([]),
                'id' => 'abc123abc123abc123abc123abc123ab',
                'expectedGetters' => [],
                'nullExpected' => true,
                'expectedException' => 'RuntimeException',
                'clientException' => new \DataSift_Exception_APIError('Internal server error', 500)
            ],
        ];
    }

    /**
     * @dataProvider importRecordingProvider
     *
     * @covers ::importRecording
     *
     * @param \DataSift_Pylon $pylon
     * @param string $id
     * @param array $expectedGetters
     * @param boolean $nullExpected
     * @param string $expectedException
     * @param \Exception $clientException
     *
     * @return boolean
     */
    public function testImportRecording(
        \DataSift_Pylon $pylon,
        $id,
        array $expectedGetters,
        $nullExpected = false,
        $expectedException = '',
        \Exception $clientException = null
    ) {

        $client = Mockery::mock('DataSift_Pylon');
        $and = $client->shouldReceive('find')
            ->once()
            ->with($id);

        if ($clientException) {
            $and->andThrow($clientException);
        } else {
            $and->andReturn($pylon);
        }

        if ($expectedException) {
            $this->setExpectedException($expectedException);
        }

        $mapper = new DataMapper(
            Mockery::mock('Doctrine\DBAL\Connection'),
            'Tornado\Project\Recording',
            'recording'
        );

        $recording = $mapper->importRecording($client, $id);
        if ($nullExpected) {
            $this->assertNull($recording);
            return true;
        }

        $this->assertInstanceOf('\Tornado\Project\Recording', $recording);
        foreach ($expectedGetters as $getter => $expected) {
            $this->assertEquals($expected, $recording->{$getter}());
        }
    }

    /**
     * Prepares a mock object for given class
     *
     * @param string $class
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockObject($class)
    {
        return $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Gets a Pylon client for testing
     *
     * @param array $data
     *
     * @return \DataSift_Pylon
     */
    protected function getPylon(array $data)
    {
        return new \DataSift_Pylon(
            Mockery::mock('\DataSift_User'),
            $data
        );
    }
}
