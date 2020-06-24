<?php

namespace Butschster\Tests;

use Butschster\Cycle\Paginator;
use Illuminate\Support\Collection;

class PaginatorTest extends TestCase
{
    function test_gets_total_items()
    {
        $this->assertEquals(11, $this->getPaginator()->total());
    }

    function test_gets_last_page()
    {
        $this->assertEquals(3, $this->getPaginator()->lastPage());
    }

    function test_gets_next_page_url_when_it_exists()
    {
        $this->assertEquals('/?test_page=3', $this->getPaginator()->nextPageUrl());
    }

    function test_gets_next_page_url_when_it_not_exists()
    {
        $this->assertNull(
            $this->getPaginator(1, 1, 1)->nextPageUrl()
        );
    }

    function test_gets_previous_page_url_when_it_exists()
    {
        $this->assertEquals('/?test_page=1', $this->getPaginator()->previousPageUrl());
    }

    function test_gets_previous_page_url_when_it_not_exists()
    {
        $this->assertNull(
            $this->getPaginator(1, 1, 1)->previousPageUrl()
        );
    }

    function test_gets_items()
    {
        $this->assertEquals([1, 2, 3, 4, 5], $this->getPaginator()->items());
    }

    function test_gets_first_item()
    {
        $this->assertEquals(6, $this->getPaginator()->firstItem());
    }

    function test_gets_last_item()
    {
        $this->assertEquals(10, $this->getPaginator()->lastItem());
    }

    function test_gets_per_page()
    {
        $this->assertEquals(5, $this->getPaginator()->perPage());
    }

    function test_gets_current_page()
    {
        $this->assertEquals(2, $this->getPaginator()->currentPage());
    }

    function test_gets_page_name()
    {
        $this->assertEquals('test_page', $this->getPaginator()->getPageName());
    }

    function test_gets_count()
    {
        $this->assertEquals(5, $this->getPaginator()->count());
    }

    function test_gets_collection()
    {
        $this->assertInstanceOf(Collection::class, $this->getPaginator()->getCollection());
    }

    /**
     * @param int $totalPages
     * @param int $perPage
     * @param int $currentPage
     * @return Paginator
     */
    protected function getPaginator(int $totalPages = 11, int $perPage = 5, int $currentPage = 2): Paginator
    {
        return new Paginator(

            (new \Spiral\Pagination\Paginator($perPage))
                ->withPage($currentPage)
                ->withCount($totalPages),

            new Collection(range(1, $perPage)),
            'test_page'
        );
    }
}
