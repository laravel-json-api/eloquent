<?php

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Pagination\Cursor;

use Illuminate\Contracts\Pagination\CursorPaginator as LaravelCursorPaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

class CursorPaginator implements \IteratorAggregate, \Countable
{
    private Collection $items;

    private ?string $path;

    /**
     * CursorPaginator constructor.
     */
    public function __construct(private readonly CursorParser $parser, private readonly LaravelCursorPaginator $laravelPaginator, private readonly Cursor $cursor, private readonly int|null $total = null)
    {
        $this->items = Collection::make($this->laravelPaginator->items());
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function firstItem(): ?string
    {

        if ($this->laravelPaginator->isEmpty()) {
            return null;
        }

        return $this->parser->encode($this->laravelPaginator->getCursorForItem($this->items->first(), false));
    }

    public function lastItem(): ?string
    {
        if ($this->laravelPaginator->isEmpty()) {
            return null;
        }

        return $this->parser->encode($this->laravelPaginator->getCursorForItem($this->items->last()));
    }

    public function hasMorePages(): bool
    {
        return ($this->cursor->isBefore() && !$this->laravelPaginator->onFirstPage()) || $this->laravelPaginator->hasMorePages();
    }

    public function hasNext()
    {
        return ((!$this->cursor->isAfter() && !$this->cursor->isBefore()) || $this->cursor->isAfter()) && $this->hasMorePages();
    }

    public function hasPrev()
    {
        return ($this->cursor->isBefore() && $this->hasMorePages()) || $this->cursor->isAfter();
    }

    public function hasNoMorePages(): bool
    {
        return !$this->hasMorePages();
    }

    public function getPerPage(): int
    {
        return $this->laravelPaginator->perPage();
    }

    public function getFrom(): ?string
    {
        return $this->firstItem();
    }

    public function getTo(): ?string
    {
        return $this->lastItem();
    }

    public function getTotal(): ?int
    {
        return $this->total;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function count(): int
    {
        return $this->items->count();
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function withCurrentPath(): self
    {
        $this->path = Paginator::resolveCurrentPath();

        return $this;
    }

    public function withPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get the base path for paginator generated URLs.
     */
    public function path(): ?string
    {
        return $this->path;
    }
}
