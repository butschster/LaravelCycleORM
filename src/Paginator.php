<?php

namespace Butschster\Cycle;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Spiral\Pagination\PaginatorInterface;

class Paginator extends AbstractPaginator implements LengthAwarePaginator
{
    protected LazyCollection $results;
    protected PaginatorInterface $paginator;

    public function __construct(PaginatorInterface $paginator, Collection $items, string $pageName = 'page')
    {
        $this->paginator = $paginator;
        $this->perPage = $paginator->getLimit();
        $this->currentPage = $paginator->getPage();
        $this->pageName = $pageName;
        $this->items = $items;
    }

    public function total(): int
    {
        return $this->paginator->count();
    }

    public function lastPage(): int
    {
        return $this->paginator->countPages();
    }

    public function nextPageUrl(): ?string
    {
        if ($this->hasMorePages()) {
            return $this->url($this->currentPage() + 1);
        }

        return null;
    }

    public function hasMorePages(): bool
    {
        return $this->currentPage() < $this->lastPage();
    }

    public function render($view = null, $data = [])
    {
        // TODO: Implement render() method.
    }
}
