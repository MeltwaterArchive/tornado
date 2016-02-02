<?php

namespace Test\Tornado\DataMapper;

use \Mockery;

use Tornado\DataMapper\DataMapperInterface;
use Tornado\DataMapper\Paginator;

/**
 * PaginatorTest
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Test\Tornado\DataMapper
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 *
 * @coversDefaultClass \Tornado\DataMapper\Paginator
 */
class PaginatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     *
     * @expectedException \LogicException
     */
    public function testThrowExceptionUnlessPositivePerPageParamGiven()
    {
        $provider = Mockery::mock('\Tornado\DataMapper\PaginatorProviderInterface');
        new Paginator($provider, 1, null, -1);
    }

    /**
     * @covers ::__construct
     * @covers ::paginate
     * @covers ::getFirstPage
     * @covers ::getCurrentPage
     * @covers ::getTotalPages
     * @covers ::getNextPage
     * @covers ::getPreviousPage
     * @covers ::getCurrentItems
     * @covers ::getTotalItemsCount
     * @covers ::getPerPage
     * @covers ::getSortBy
     * @covers ::getOrder
     * @covers ::toArray
     */
    public function testPaginate()
    {
        $resultItems = $this->getResultsData(5);
        $provider = Mockery::mock('\Tornado\DataMapper\PaginatorProviderInterface', [
            'count' => 24,
            'find' => $resultItems
        ]);
        $paginator = new Paginator($provider, 3, 'id');
        $paginationResults = $paginator->paginate();

        $this->assertArrayHasKey('firstPage', $paginationResults);
        $this->assertArrayHasKey('currentPage', $paginationResults);
        $this->assertArrayHasKey('totalPages', $paginationResults);
        $this->assertArrayHasKey('nextPage', $paginationResults);
        $this->assertArrayHasKey('previousPage', $paginationResults);
        $this->assertArrayHasKey('totalItemsCount', $paginationResults);
        $this->assertArrayHasKey('perPage', $paginationResults);
        $this->assertArrayHasKey('sortBy', $paginationResults);
        $this->assertArrayHasKey('order', $paginationResults);

        $this->assertEquals(1, $paginator->getFirstPage());
        $this->assertEquals(3, $paginator->getCurrentPage());
        $this->assertEquals(5, $paginator->getTotalPages());
        $this->assertEquals(4, $paginator->getNextPage());
        $this->assertEquals(2, $paginator->getPreviousPage());
        $this->assertEquals($resultItems, $paginator->getCurrentItems());
        $this->assertEquals(5, $paginator->getPerPage());
        $this->assertEquals(24, $paginator->getTotalItemsCount());
        $this->assertEquals('id', $paginator->getSortBy());
        $this->assertEquals(DataMapperInterface::ORDER_DESCENDING, $paginator->getOrder());
    }

    /**
     * @covers ::__construct
     * @covers ::paginate
     * @covers ::getFirstPage
     * @covers ::getCurrentPage
     * @covers ::getTotalPages
     * @covers ::getNextPage
     * @covers ::getPreviousPage
     * @covers ::getCurrentItems
     * @covers ::getTotalItemsCount
     * @covers ::getPerPage
     * @covers ::getSortBy
     * @covers ::getOrder
     * @covers ::toArray
     */
    public function testPaginateWhenNoDataExists()
    {
        $resultItems = [];
        $provider = Mockery::mock('\Tornado\DataMapper\PaginatorProviderInterface', [
            'count' => 0,
            'find' => $resultItems
        ]);
        $paginator = new Paginator($provider, 1, 'id');
        $paginationResults = $paginator->paginate();

        $this->assertArrayHasKey('firstPage', $paginationResults);
        $this->assertArrayHasKey('currentPage', $paginationResults);
        $this->assertArrayHasKey('totalPages', $paginationResults);
        $this->assertArrayHasKey('nextPage', $paginationResults);
        $this->assertArrayHasKey('previousPage', $paginationResults);
        $this->assertArrayHasKey('totalItemsCount', $paginationResults);
        $this->assertArrayHasKey('perPage', $paginationResults);
        $this->assertArrayHasKey('sortBy', $paginationResults);
        $this->assertArrayHasKey('order', $paginationResults);

        $this->assertEquals(0, $paginator->getFirstPage());
        $this->assertEquals(0, $paginator->getCurrentPage());
        $this->assertEquals(0, $paginator->getTotalPages());
        $this->assertEquals(null, $paginator->getNextPage());
        $this->assertEquals(null, $paginator->getPreviousPage());
        $this->assertEquals([], $paginator->getCurrentItems());
        $this->assertEquals(5, $paginator->getPerPage());
        $this->assertEquals(0, $paginator->getTotalItemsCount());
        $this->assertEquals('id', $paginator->getSortBy());
        $this->assertEquals(DataMapperInterface::ORDER_DESCENDING, $paginator->getOrder());
    }


    /**
     * @covers ::__construct
     * @covers ::paginate
     * @covers ::getFirstPage
     * @covers ::getCurrentPage
     * @covers ::getTotalPages
     * @covers ::getNextPage
     * @covers ::getPreviousPage
     * @covers ::getCurrentItems
     * @covers ::getTotalItemsCount
     * @covers ::getPerPage
     * @covers ::getSortBy
     * @covers ::getOrder
     * @covers ::toArray
     */
    public function testPaginateWhenOnlyOnePageExists()
    {
        $resultItems = $this->getResultsData(3);
        $provider = Mockery::mock('\Tornado\DataMapper\PaginatorProviderInterface', [
            'count' => 3,
            'find' => $resultItems
        ]);
        $paginator = new Paginator($provider, 1, 'id');
        $paginationResults = $paginator->paginate();

        $this->assertArrayHasKey('firstPage', $paginationResults);
        $this->assertArrayHasKey('currentPage', $paginationResults);
        $this->assertArrayHasKey('totalPages', $paginationResults);
        $this->assertArrayHasKey('nextPage', $paginationResults);
        $this->assertArrayHasKey('previousPage', $paginationResults);
        $this->assertArrayHasKey('totalItemsCount', $paginationResults);
        $this->assertArrayHasKey('perPage', $paginationResults);
        $this->assertArrayHasKey('sortBy', $paginationResults);
        $this->assertArrayHasKey('order', $paginationResults);

        $this->assertEquals(1, $paginator->getFirstPage());
        $this->assertEquals(1, $paginator->getCurrentPage());
        $this->assertEquals(1, $paginator->getTotalPages());
        $this->assertEquals(null, $paginator->getNextPage());
        $this->assertEquals(null, $paginator->getPreviousPage());
        $this->assertEquals($resultItems, $paginator->getCurrentItems());
        $this->assertEquals(5, $paginator->getPerPage());
        $this->assertEquals(3, $paginator->getTotalItemsCount());
        $this->assertEquals('id', $paginator->getSortBy());
        $this->assertEquals(DataMapperInterface::ORDER_DESCENDING, $paginator->getOrder());
    }

    /**
     * @covers ::__construct
     * @covers ::paginate
     * @covers ::getFirstPage
     * @covers ::getCurrentPage
     * @covers ::getTotalPages
     * @covers ::getNextPage
     * @covers ::getPreviousPage
     * @covers ::getCurrentItems
     * @covers ::getTotalItemsCount
     * @covers ::getPerPage
     * @covers ::toArray
     */
    public function testPaginateWhenLastPageGiven()
    {
        $resultItems = $this->getResultsData(5);
        $provider = Mockery::mock('\Tornado\DataMapper\PaginatorProviderInterface', [
            'count' => 24,
        ]);
        $provider->shouldReceive('find')
            ->once()
            ->with([], [], 5, 20)
            ->andReturn($resultItems);

        $paginator = new Paginator($provider, 5, null);
        $paginationResults = $paginator->paginate();

        $this->assertArrayHasKey('firstPage', $paginationResults);
        $this->assertArrayHasKey('currentPage', $paginationResults);
        $this->assertArrayHasKey('totalPages', $paginationResults);
        $this->assertArrayHasKey('nextPage', $paginationResults);
        $this->assertArrayHasKey('previousPage', $paginationResults);
        $this->assertArrayHasKey('totalItemsCount', $paginationResults);
        $this->assertArrayHasKey('perPage', $paginationResults);
        $this->assertArrayHasKey('sortBy', $paginationResults);
        $this->assertArrayHasKey('order', $paginationResults);

        $this->assertEquals(1, $paginator->getFirstPage());
        $this->assertEquals(5, $paginator->getCurrentPage());
        $this->assertEquals(5, $paginator->getTotalPages());
        $this->assertEquals(null, $paginator->getNextPage());
        $this->assertEquals(4, $paginator->getPreviousPage());
        $this->assertEquals($resultItems, $paginator->getCurrentItems());
        $this->assertEquals(5, $paginator->getPerPage());
        $this->assertEquals(24, $paginator->getTotalItemsCount());
        $this->assertEquals(null, $paginator->getSortBy());
        $this->assertEquals(DataMapperInterface::ORDER_DESCENDING, $paginator->getOrder());
    }

    /**
     * @covers ::__construct
     * @covers ::paginate
     * @covers ::getFirstPage
     * @covers ::getCurrentPage
     * @covers ::getTotalPages
     * @covers ::getNextPage
     * @covers ::getPreviousPage
     * @covers ::getCurrentItems
     * @covers ::getTotalItemsCount
     * @covers ::getPerPage
     * @covers ::toArray
     */
    public function testPaginateWhenFirstPageGiven()
    {
        $resultItems = $this->getResultsData(5);
        $provider = Mockery::mock('\Tornado\DataMapper\PaginatorProviderInterface', [
            'count' => 24,
            'find' => $resultItems
        ]);
        $paginator = new Paginator($provider, 1, null);
        $paginationResults = $paginator->paginate();

        $this->assertArrayHasKey('firstPage', $paginationResults);
        $this->assertArrayHasKey('currentPage', $paginationResults);
        $this->assertArrayHasKey('totalPages', $paginationResults);
        $this->assertArrayHasKey('nextPage', $paginationResults);
        $this->assertArrayHasKey('previousPage', $paginationResults);
        $this->assertArrayHasKey('totalItemsCount', $paginationResults);
        $this->assertArrayHasKey('perPage', $paginationResults);

        $this->assertEquals(1, $paginator->getFirstPage());
        $this->assertEquals(1, $paginator->getCurrentPage());
        $this->assertEquals(5, $paginator->getTotalPages());
        $this->assertEquals(2, $paginator->getNextPage());
        $this->assertEquals(null, $paginator->getPreviousPage());
        $this->assertEquals($resultItems, $paginator->getCurrentItems());
        $this->assertEquals(5, $paginator->getPerPage());
        $this->assertEquals(24, $paginator->getTotalItemsCount());
    }

    /**
     * @covers ::__construct
     * @covers ::paginate
     * @covers ::getFirstPage
     * @covers ::getCurrentPage
     * @covers ::getTotalPages
     * @covers ::getNextPage
     * @covers ::getPreviousPage
     * @covers ::getCurrentItems
     * @covers ::getTotalItemsCount
     * @covers ::getPerPage
     * @covers ::toArray
     */
    public function testPaginateWhenMoreThanLastPageGiven()
    {
        $resultItems = $this->getResultsData(5);
        $provider = Mockery::mock('\Tornado\DataMapper\PaginatorProviderInterface', [
            'count' => 24,
            'find' => $resultItems
        ]);
        $paginator = new Paginator($provider, 10, null);
        $paginationResults = $paginator->paginate();

        $this->assertArrayHasKey('firstPage', $paginationResults);
        $this->assertArrayHasKey('currentPage', $paginationResults);
        $this->assertArrayHasKey('totalPages', $paginationResults);
        $this->assertArrayHasKey('nextPage', $paginationResults);
        $this->assertArrayHasKey('previousPage', $paginationResults);
        $this->assertArrayHasKey('totalItemsCount', $paginationResults);
        $this->assertArrayHasKey('perPage', $paginationResults);

        $this->assertEquals(1, $paginator->getFirstPage());
        $this->assertEquals(5, $paginator->getCurrentPage());
        $this->assertEquals(5, $paginator->getTotalPages());
        $this->assertEquals(5, $paginator->getNextPage());
        $this->assertEquals(4, $paginator->getPreviousPage());
        $this->assertEquals($resultItems, $paginator->getCurrentItems());
        $this->assertEquals(5, $paginator->getPerPage());
        $this->assertEquals(24, $paginator->getTotalItemsCount());
    }

    /**
     * @covers ::__construct
     * @covers ::paginate
     * @covers ::getFirstPage
     * @covers ::getCurrentPage
     * @covers ::getTotalPages
     * @covers ::getNextPage
     * @covers ::getPreviousPage
     * @covers ::getCurrentItems
     * @covers ::getTotalItemsCount
     * @covers ::getPerPage
     * @covers ::toArray
     */
    public function testPaginateWhenLessThanFirstPageGiven()
    {
        $resultItems = $this->getResultsData(5);
        $provider = Mockery::mock('\Tornado\DataMapper\PaginatorProviderInterface', [
            'count' => 24,
            'find' => $resultItems
        ]);
        $paginator = new Paginator($provider, 0, null);
        $paginationResults = $paginator->paginate();

        $this->assertArrayHasKey('firstPage', $paginationResults);
        $this->assertArrayHasKey('currentPage', $paginationResults);
        $this->assertArrayHasKey('totalPages', $paginationResults);
        $this->assertArrayHasKey('nextPage', $paginationResults);
        $this->assertArrayHasKey('previousPage', $paginationResults);
        $this->assertArrayHasKey('totalItemsCount', $paginationResults);
        $this->assertArrayHasKey('perPage', $paginationResults);

        $this->assertEquals(5, $paginator->getPerPage());
        $this->assertEquals(1, $paginator->getFirstPage());
        $this->assertEquals(1, $paginator->getCurrentPage());
        $this->assertEquals(5, $paginator->getTotalPages());
        $this->assertEquals(2, $paginator->getNextPage());
        $this->assertEquals(1, $paginator->getPreviousPage());
        $this->assertEquals($resultItems, $paginator->getCurrentItems());
        $this->assertEquals(5, $paginator->getPerPage());
        $this->assertEquals(24, $paginator->getTotalItemsCount());
    }

    /**
     * @covers ::__construct
     * @covers ::paginate
     * @covers ::getFirstPage
     * @covers ::getCurrentPage
     * @covers ::getTotalPages
     * @covers ::getNextPage
     * @covers ::getPreviousPage
     * @covers ::getCurrentItems
     * @covers ::getTotalItemsCount
     * @covers ::getPerPage
     * @covers ::toArray
     */
    public function testPaginateWithCustomPerPageCount()
    {
        $resultItems = $this->getResultsData(3);
        $provider = Mockery::mock('\Tornado\DataMapper\PaginatorProviderInterface', [
            'count' => 26,
            'find' => $resultItems
        ]);
        $paginator = new Paginator($provider, 4, null, 3);
        $paginationResults = $paginator->paginate();

        $this->assertArrayHasKey('firstPage', $paginationResults);
        $this->assertArrayHasKey('currentPage', $paginationResults);
        $this->assertArrayHasKey('totalPages', $paginationResults);
        $this->assertArrayHasKey('nextPage', $paginationResults);
        $this->assertArrayHasKey('previousPage', $paginationResults);
        $this->assertArrayHasKey('totalItemsCount', $paginationResults);
        $this->assertArrayHasKey('perPage', $paginationResults);

        $this->assertEquals(1, $paginator->getFirstPage());
        $this->assertEquals(4, $paginator->getCurrentPage());
        $this->assertEquals(9, $paginator->getTotalPages());
        $this->assertEquals(5, $paginator->getNextPage());
        $this->assertEquals(3, $paginator->getPreviousPage());
        $this->assertEquals($resultItems, $paginator->getCurrentItems());
        $this->assertEquals(3, $paginator->getPerPage());
        $this->assertEquals(26, $paginator->getTotalItemsCount());
    }

    /**
     * @covers ::__construct
     * @covers ::paginate
     * @covers ::getOffset
     * @covers ::getLimit
     */
    public function testOffsetLimit()
    {
        $resultItems = $this->getResultsData(3);
        $provider = Mockery::mock('\Tornado\DataMapper\PaginatorProviderInterface', [
            'count' => 26,
            'find' => $resultItems
        ]);

        $paginator = new Paginator($provider, 4, null, 3);
        $paginator->paginate();
        $this->assertEquals(3, $paginator->getLimit());
        $this->assertEquals(9, $paginator->getOffset());

        $paginator = new Paginator($provider, 1, null);
        $paginator->paginate();
        $this->assertEquals(5, $paginator->getLimit());
        $this->assertEquals(0, $paginator->getOffset());

        $paginator = new Paginator($provider, 9, null, 3);
        $paginator->paginate();
        $this->assertEquals(3, $paginator->getLimit());
        $this->assertEquals(24, $paginator->getOffset());
    }

    /**
     * @covers ::jsonSerialize
     * @covers ::toArray
     */
    public function testJsonSerialization()
    {
        $resultItems = $this->getResultsData(5);
        $provider = Mockery::mock('\Tornado\DataMapper\PaginatorProviderInterface', [
            'count' => 24,
            'find' => $resultItems
        ]);
        $paginator = new Paginator($provider, 0, null);
        $paginator->paginate();

        $this->assertInstanceOf('\JsonSerializable', $paginator);
        $this->assertEquals(json_encode($paginator->toArray()), json_encode($paginator));
    }

    /**
     * @param int $onPage
     *
     * @return array
     */
    protected function getResultsData($onPage)
    {
        $results = [];
        for ($i = 1; $i <= $onPage; $i++) {
            $obj = Mockery::mock('\Tornado\DataMapper\DataObjectInterface', [
                'getId' => $i,
                'jsonSerialize' => ['id' => $i]
            ]);
            $results[] = $obj;
        }

        return $results;
    }
}
