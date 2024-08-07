<?php

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Pagination;

use LaravelJsonApi\Eloquent\Pagination\Cursor\CursorBuilder;
use LaravelJsonApi\Eloquent\Pagination\Cursor\CursorPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Contracts\Schema\ID;
use LaravelJsonApi\Core\Pagination\Concerns\HasPageMeta;
use LaravelJsonApi\Eloquent\Pagination\Cursor\Cursor;
use LaravelJsonApi\Eloquent\Contracts\Paginator;

class CursorPagination implements Paginator
{
    use HasPageMeta;

    private string $before;

    private string $after;

    private string $limit;

    private string $direction;

    private ?string $primaryKey = null;

    /** @var string|array<string>|null */
    private string|array|null $columns = null;

    private ?int $defaultPerPage = null;

    private bool $withTotal;

    private bool $withTotalOnFirstPage;

    private bool $keySort = true;

    /**
     * CursorPagination constructor.
     */
    public function __construct(private readonly ID $id)
    {
        $this->before = 'before';
        $this->after = 'after';
        $this->limit = 'limit';
        $this->metaKey = 'page';
        $this->direction = 'desc';
        $this->withTotal = false;
        $this->withTotalOnFirstPage = false;
    }

    /**
     * Fluent constructor.
     */
    public static function make(ID $id): self
    {
        return new self($id);
    }

    /**
     * Set the "after" key.
     *
     * @return $this
     */
    public function withAfterKey(string $key): self
    {
        if (empty($key)) {
            throw new \InvalidArgumentException('Expecting a non-empty string.');
        }

        $this->after = $key;

        return $this;
    }

    /**
     * Set the "before" key.
     *
     * @return $this
     */
    public function withBeforeKey(string $key): self
    {
        if (empty($key)) {
            throw new \InvalidArgumentException('Expecting a non-empty string.');
        }

        $this->before = $key;

        return $this;
    }

    /**
     * Set the "limit" key.
     *
     * @return $this
     */
    public function withLimitKey(string $key): self
    {
        if (empty($key)) {
            throw new \InvalidArgumentException('Expecting a non-empty string.');
        }

        $this->limit = $key;

        return $this;
    }

    /**
     * Use an ascending order.
     *
     * @return $this
     */
    public function withAscending(): self
    {
        $this->direction = 'asc';

        return $this;
    }

    /**
     * Use the provided number as the default items per-page.
     *
     * If null, the default per-page set on the model class will be used.
     *
     * @return $this
     */
    public function withDefaultPerPage(?int $perPage): self
    {
        $this->defaultPerPage = $perPage;

        return $this;
    }

    public function withTotal(bool $withTotal = true): self
    {
        $this->withTotal = $withTotal;

        return $this;
    }

    public function withTotalOnFirstPage(bool $withTotal = true): self
    {
        $this->withTotalOnFirstPage = $withTotal;

        return $this;
    }

    public function withKeyName(string $column): self
    {
        $this->primaryKey = $column;

        return $this;
    }

    /**
     * @param string|array<string> $columns
     * @return $this
     */
    public function withColumns($columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    public function withKeySort(bool $keySort = true): self
    {
        $this->keySort = $keySort;

        return $this;
    }

    public function withoutKeySort(): self
    {
        return $this->withKeySort(false);
    }

    /**
     * @return array<string>
     */
    public function keys(): array
    {
        return [
            $this->before,
            $this->after,
            $this->limit,
        ];
    }

    /**
     * @param Builder|Relation $query
     * @param array<string, mixed> $page
     */
    public function paginate($query, array $page): Page
    {
        $cursor = $this->cursor($page);

        $withTotal = $this->withTotal
            || ($this->withTotalOnFirstPage
            && !$cursor->isBefore()
            && !$cursor->isAfter());

        $paginator = $this
            ->query($query)
            ->withDirection($this->direction)
            ->withKeySort($this->keySort)
            ->withDefaultPerPage($this->defaultPerPage)
            ->withTotal($withTotal)
            ->paginate($cursor, $this->columns ?? ['*']);

        return CursorPage::make($paginator)
            ->withBeforeParam($this->before)
            ->withAfterParam($this->after)
            ->withLimitParam($this->limit)
            ->withMeta($this->hasMeta)
            ->withNestedMeta($this->metaKey)
            ->withMetaCase($this->metaCase);
    }

    /**
     * Create a new cursor query.
     */
    private function query(Builder|Relation $query): CursorBuilder
    {
        return new CursorBuilder($query, $this->id, $this->primaryKey);
    }

    /**
     * Extract the cursor from the provided paging parameters.
     * @param array<string,mixed> $page
     */
    private function cursor(array $page): Cursor
    {
        $before = $page[$this->before] ?? null;
        $after = $page[$this->after] ?? null;
        $limit = $page[$this->limit] ?? null;

        return new Cursor(
            !is_null($before) ? strval($before) : null,
            !is_null($after) ? strval($after) : null,
            !is_null($limit) ? intval($limit) : null,
        );
    }
}
