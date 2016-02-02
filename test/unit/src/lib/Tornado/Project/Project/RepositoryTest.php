<?php

namespace Test\Tornado\Project\Project;

use \Mockery;

use Tornado\Project\Project\DataMapper;

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
 * @coversDefaultClass \Tornado\Project\Project\DataMapper
 */
class RepositoryTest extends \PHPUnit_Framework_TestCase
{
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

        $repositoryMock = $this->getMockBuilder('Tornado\Project\Project\DataMapper')
            ->setConstructorArgs([
                $this->getMockObject('\Doctrine\DBAL\Connection'),
                'stdObject',
                'project'
            ])
            ->setMethods(['find'])
            ->getMock();
        $repositoryMock->expects($this->once())
            ->method('find')
            ->with($expectedFilter, $sortBy, $limit, $offset)
            ->willReturn($response);

        $this->assertEquals(
            $response,
            $repositoryMock->findByBrand(
                $brandMock,
                $filter,
                $sortBy,
                $limit,
                $offset
            )
        );
    }

    /**
     * @covers ::deleteProjectsByBrand
     */
    public function testDeleteProjectsByBrand()
    {
        $dbName = 'brand';
        $brandId = 1;
        $brand = Mockery::mock('\Tornado\Organization\Brand', [
            'getId' => $brandId
        ]);
        $ids = [1,2];
        $queryBuilder = Mockery::mock('Doctrine\DBAL\Query\QueryBuilder');
        $expressionBuilder = Mockery::mock('Doctrine\DBAL\Query\Expression\ExpressionBuilder');
        $expressionBuilder->shouldReceive('in')
            ->once()
            ->with('id', $ids)
            ->andReturn('id IN (1,2)');
        $queryBuilder->shouldReceive('delete')
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

        $removed = 2;
        $queryBuilder->shouldReceive('execute')
            ->once()
            ->andReturn($removed);

        // do the test
        $repository = new DataMapper(
            $connection,
            'Tornado\Organization\Brand',
            $dbName
        );

        $result = $repository->deleteProjectsByBrand($brand, $ids);

        $this->assertEquals($removed, $result);
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
}
