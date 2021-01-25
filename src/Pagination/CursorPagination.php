<?php
/*
 * Copyright 2021 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Pagination;

use InvalidArgumentException;
use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Pagination\Cursor\Cursor;
use LaravelJsonApi\Eloquent\Pagination\Cursor\CursorBuilder;

class CursorPagination implements Paginator
{

    use Concerns\HasPageMeta;

    /**
     * @var string
     */
    private string $before;

    /**
     * @var string
     */
    private string $after;

    /**
     * @var string
     */
    private string $limit;

    /**
     * @var string
     */
    private string $direction;

    /**
     * @var string|null
     */
    private ?string $cursorColumn = null;

    /**
     * @var string|null
     */
    private ?string $primaryKey = null;

    /**
     * @var string|array|null
     */
    private $columns = null;

    /**
     * @var int|null
     */
    private ?int $defaultPerPage = null;

    /**
     * Fluent constructor.
     *
     * @return CursorPagination
     */
    public static function make(): self
    {
        return new static();
    }

    /**
     * CursorStrategy constructor.
     */
    public function __construct()
    {
        $this->before = 'before';
        $this->after = 'after';
        $this->limit = 'limit';
        $this->metaKey = 'page';
        $this->direction = 'desc';
    }

    /**
     * Set the "after" key.
     *
     * @param string $key
     * @return $this
     */
    public function withAfterKey(string $key): self
    {
        if (empty($key)) {
            throw new InvalidArgumentException('Expecting a non-empty string.');
        }

        $this->after = $key;

        return $this;
    }

    /**
     * Set the "before" key.
     *
     * @param string $key
     * @return $this
     */
    public function withBeforeKey(string $key): self
    {
        if (empty($key)) {
            throw new InvalidArgumentException('Expecting a non-empty string.');
        }

        $this->before = $key;

        return $this;
    }

    /**
     * Set the "limit" key.
     *
     * @param string $key
     * @return $this
     */
    public function withLimitKey(string $key): self
    {
        if (empty($key)) {
            throw new InvalidArgumentException('Expecting a non-empty string.');
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
     * Set the cursor column.
     *
     * @param string $column
     * @return $this
     */
    public function withCursorColumn(string $column): self
    {
        $this->cursorColumn = $column;

        return $this;
    }

    /**
     * Use the provided number as the default items per-page.
     *
     * If null, the default per-page set on the model class will be used.
     *
     * @param int|null $perPage
     * @return $this
     */
    public function withDefaultPerPage(?int $perPage): self
    {
        $this->defaultPerPage = $perPage;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withKeyName(string $column): self
    {
        $this->primaryKey = $column;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withColumns($columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function paginate($query, array $page): Page
    {
        $paginator = $this
            ->query($query)
            ->withDirection($this->direction)
            ->withDefaultPerPage($this->defaultPerPage)
            ->paginate($this->cursor($page), $this->columns ?: ['*']);

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
     *
     * @param mixed $query
     * @return CursorBuilder
     */
    private function query($query): CursorBuilder
    {
        return new CursorBuilder($query, $this->cursorColumn, $this->primaryKey);
    }

    /**
     * Extract the cursor from the provided paging parameters.
     *
     * @param array $page
     * @return Cursor
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
