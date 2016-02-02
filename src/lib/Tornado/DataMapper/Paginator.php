<?php

namespace Tornado\DataMapper;

/**
 * Paginator
 *
 * LICENSE: This software is the intellectual property of MediaSift Ltd.,
 * and is covered by retained intellectual property rights, including
 * copyright. Distribution of this software is strictly forbidden under
 * the terms of this license.
 *
 * @category    Applications
 * @package     \Tornado\DataMapper
 * @copyright   2015-2016 MediaSift Ltd.
 * @license     http://mediasift.com/licenses/internal MediaSift Internal License
 * @link        https://github.com/datasift/tornado
 */
class Paginator implements \JsonSerializable
{
    /**
     * @var PaginatorProviderInterface
     */
    protected $provider;

    /**
     * Number of the first page
     */
    protected $firstPage = 1;

    /**
     * @var int Number of the current page
     */
    protected $currentPage;

    /**
     * @var int Total pages
     */
    protected $totalPages = 0;

    /**
     * @var int Number of the next page
     */
    protected $nextPage;

    /**
     * @var int Number of the previous page
     */
    protected $previousPage;

    /**
     * @var int Items per page
     */
    protected $perPage = 5;

    /**
     * @var string data property by which data should be sorted
     */
    protected $sortBy;

    /**
     * @var string data sorting order
     */
    protected $order = DataMapperInterface::ORDER_DESCENDING;

    /**
     * @var int Total Items count
     */
    protected $totalItemsCount;

    /**
     * @var array Current Items received from the pagination provider
     */
    protected $currentItems = [];

    /**
     * @param \Tornado\DataMapper\PaginatorProviderInterface $provider
     * @param int                                            $currentPage
     * @param string                                         $sortBy
     * @param int                                            $perPage
     * @param string                                         $order
     */
    public function __construct(
        PaginatorProviderInterface $provider,
        $currentPage,
        $sortBy,
        $perPage = 5,
        $order = DataMapperInterface::ORDER_DESCENDING
    ) {
        $this->provider = $provider;
        $this->currentPage = (int)$currentPage;
        $this->perPage = (int)$perPage;
        $this->sortBy = $sortBy;
        $this->order = $order;

        if ($this->perPage <= 0) {
            throw new \LogicException('Items per page must be at least 1');
        }
    }

    /**
     * Paginates Provider results data based on the given filters and sort params
     *
     * @param array $filter
     *
     * @return array pagination data ready for use
     */
    public function paginate(array $filter = [])
    {
        $this->totalItemsCount = (int)$this->provider->count($filter);

        if (!$this->totalItemsCount > 0) {
            $this->firstPage = 0;
            $this->currentPage = 0;

            return $this->toArray();
        }

        // set current page
        if ($this->currentPage < $this->firstPage) {
            $this->currentPage = $this->firstPage;
            $this->previousPage = $this->firstPage;
        }

        $this->totalPages = (int)ceil($this->totalItemsCount / $this->perPage);
        if ($this->currentPage > $this->totalPages) {
            $this->currentPage = $this->totalPages;
            $this->nextPage = $this->totalPages;
        }

        // set previous
        if ($this->currentPage > 1) {
            $this->previousPage = $this->currentPage - 1;
        }

        // set next
        if ($this->currentPage < $this->totalPages) {
            $this->nextPage = $this->currentPage + 1;
        }

        $this->currentItems = $this->provider->find(
            $filter,
            $this->getSortBy() ? [$this->getSortBy() => $this->getOrder()] : [],
            $this->getLimit(),
            $this->getOffset()
        );

        return $this->toArray();
    }

    /**
     * Returns the number of the first page
     *
     * @return int
     */
    public function getFirstPage()
    {
        return $this->firstPage;
    }

    /**
     * Returns number of the current page
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * Returns number of the last page
     *
     * @return int
     */
    public function getTotalPages()
    {
        return $this->totalPages;
    }

    /**
     * Returns the number of the next page
     *
     * @return int
     */
    public function getNextPage()
    {
        return $this->nextPage;
    }

    /**
     * Returns the number of the previous page
     *
     * @return int
     */
    public function getPreviousPage()
    {
        return $this->previousPage;
    }

    /**
     * Returns the number of the items per page
     *
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * Returns the data property by which data should be sorted
     *
     * @return string
     */
    public function getSortBy()
    {
        return $this->sortBy;
    }

    /**
     * Returns the sorting order
     *
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Returns the number of all items which PaginatorProvider can return for the applied filters
     *
     * @return int
     */
    public function getTotalItemsCount()
    {
        return $this->totalItemsCount;
    }

    /**
     * Returns the collection of the items which PaginationProvider returns for the current page
     *
     * @return array
     */
    public function getCurrentItems()
    {
        return $this->currentItems;
    }

    /**
     * Counts Paginator found items
     *
     * @return int
     */
    public function getCurrentItemsCount()
    {
        return count($this->currentItems);
    }

    /**
     * Returns the offset for the PaginatorProvider search
     *
     * @return int
     */
    public function getOffset()
    {
        return ($this->getCurrentPage() - 1) * $this->getPerPage();
    }

    /**
     * Returns the limit for the PaginatorProvider search
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->getPerPage();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'firstPage' => $this->getFirstPage(),
            'currentPage' => $this->getCurrentPage(),
            'totalPages' => $this->getTotalPages(),
            'nextPage' => $this->getNextPage(),
            'previousPage' => $this->getPreviousPage(),
            'totalItemsCount' => $this->getTotalItemsCount(),
            'perPage' => $this->getPerPage(),
            'sortBy' => $this->getSortBy(),
            'order' => $this->getOrder()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
