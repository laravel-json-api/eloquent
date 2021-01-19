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

namespace LaravelJsonApi\Eloquent\Pagination\Cursor;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use InvalidArgumentException;
use LogicException;
use OutOfRangeException;
use function in_array;

class CursorBuilder
{

    /**
     * @var Builder|Relation
     */
    private $query;

    /**
     * @var string
     */
    private string $column;

    /**
     * @var string
     */
    private string $keyName;

    /**
     * @var string
     */
    private string $direction;

    /**
     * @var int|null
     */
    private ?int $defaultPerPage = null;

    /**
     * CursorBuilder constructor.
     *
     * @param Builder|Relation $query
     * @param string|null $column
     *      the column to use for the cursor.
     * @param string|null $key
     *      the key column that the before/after cursors related to.
     */
    public function __construct($query, string $column = null, string $key = null)
    {
        if (!$query instanceof Builder && !$query instanceof Relation) {
            throw new InvalidArgumentException('Expecting an Eloquent query builder or relation.');
        }

        if (!empty($query->orders)) {
            throw new LogicException('Cursor queries must not have an order applied.');
        }

        $this->query = $query;
        $this->column = $column ?: $this->guessColumn();
        $this->keyName = $key ?: $this->guessKey();
        $this->direction = 'desc';
    }

    /**
     * Set the query direction.
     *
     * @param string $direction
     * @return $this
     */
    public function withDirection(string $direction): self
    {
        if (in_array($direction, ['asc', 'desc'])) {
            $this->direction = $direction;
            return $this;
        }

        throw new InvalidArgumentException('Unexpected query direction.');
    }

    /**
     * Set the default number of items per-page.
     *
     * If null, the default from the `Model::getPage()` method will be used.
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
     * @param Cursor $cursor
     * @param array $columns
     * @return CursorPaginator
     */
    public function paginate(Cursor $cursor, $columns = ['*']): CursorPaginator
    {
        $cursor = $cursor->withDefaultLimit($this->getDefaultPerPage());

        if ($cursor->isBefore()) {
            $paginator = $this->previous($cursor, $columns);
        } else {
            $paginator = $this->next($cursor, $columns);
        }

        return $paginator->withCurrentPath();
    }

    /**
     * @return int
     */
    private function getDefaultPerPage(): int
    {
        if (is_int($this->defaultPerPage)) {
            return $this->defaultPerPage;
        }

        return $this->query->getModel()->getPerPage();
    }

    /**
     * @return string
     */
    private function getQualifiedColumn(): string
    {
        return $this->query->getModel()->qualifyColumn(
            $this->column
        );
    }

    /**
     * @return string
     */
    private function getQualifiedKeyName(): string
    {
        return $this->query->getModel()->qualifyColumn(
            $this->keyName
        );
    }

    /**
     * Get the next page.
     *
     * To calculate if there are more items in the list, we ask
     * for one more item than we actually need for the limit. We then
     * slice the results to remove this extra item.
     *
     * @param Cursor $cursor
     * @param array|string $columns
     * @return CursorPaginator
     * @throws OutOfRangeException
     *      if the cursor contains a before/after id that does not exist.
     */
    private function next(Cursor $cursor, $columns): CursorPaginator
    {
        if ($cursor->isAfter()) {
            $this->whereId($cursor->getAfter(), $this->isDescending() ? '<' : '>');
        }

        $items = $this
            ->orderForNext()
            ->get($cursor->getLimit() + 1, $columns);

        $more = $items->count() > $cursor->getLimit();

        return new CursorPaginator(
            $items->slice(0, $cursor->getLimit()),
            $more,
            $cursor,
            $this->getUnqualifiedKey()
        );
    }

    /**
     * Get the previous page.
     *
     * To get the previous page, we need to sort in the opposite direction
     * (i.e. ascending rather than descending), then reverse the results
     * so that they are in the correct page order.
     *
     * The previous page always has-more items, because we know there is
     * at least one object ahead in the table - i.e. the one that was
     * provided as the before cursor.
     *
     * @param Cursor $cursor
     * @param array|string $columns
     * @return CursorPaginator
     */
    private function previous(Cursor $cursor, $columns): CursorPaginator
    {
        $items = $this
            ->whereId($cursor->getBefore(), $this->isDescending() ? '>' : '<')
            ->orderForPrevious()
            ->get($cursor->getLimit(), $columns)
            ->reverse()
            ->values();

        return new CursorPaginator($items, true, $cursor, $this->getUnqualifiedKey());
    }

    /**
     * Add a where clause for the supplied id and operator.
     *
     * If we are paging on the key, then we only need one where clause - i.e.
     * on the key column.
     *
     * If we are paging on a column that is different than the key, we do not
     * assume that the column is unique. Therefore we add where clauses for
     * both the column plus then use the key column (which we expect to be
     * unique) to differentiate between any items that have the same value for
     * the non-unique column.
     *
     * @param int|string $id
     * @param string $operator
     * @return $this
     * @see https://stackoverflow.com/questions/38017054/mysql-cursor-based-pagination-with-multiple-columns
     */
    private function whereId($id, string $operator): self
    {
        /** If we are paging on the key, we only need one where clause. */
        if ($this->isPagingOnKey()) {
            $this->query->where($this->getQualifiedKeyName(), $operator, $id);
            return $this;
        }

        $value = $this->getColumnValue($id);

        $this->query->where(
            $this->getQualifiedColumn(), $operator . '=', $value
        )->where(function ($query) use ($id, $value, $operator) {
            /** @var QueryBuilder $query */
            $query->where($this->getQualifiedColumn(), $operator, $value)
                ->orWhere($this->getQualifiedKeyName(), $operator, $id);
        });

        return $this;
    }

    /**
     * Order items for a previous page query.
     *
     * A previous page query needs to retrieve items in the opposite
     * order from the desired order.
     *
     * @return $this
     */
    private function orderForPrevious(): self
    {
        if ($this->isDescending()) {
            $this->orderAsc();
        } else {
            $this->orderDesc();
        }

        return $this;
    }

    /**
     * Order items for a next page query.
     *
     * @return $this
     */
    private function orderForNext(): self
    {
        if ($this->isDescending()) {
            $this->orderDesc();
        } else {
            $this->orderAsc();
        }

        return $this;
    }

    /**
     * Order items in descending order.
     *
     * @return $this
     */
    private function orderDesc(): self
    {
        $this->query->orderByDesc($this->getQualifiedColumn());

        if ($this->isNotPagingOnKey()) {
            $this->query->orderByDesc($this->getQualifiedKeyName());
        }

        return $this;
    }

    /**
     * Order items in ascending order.
     *
     * @return $this
     */
    private function orderAsc(): self
    {
        $this->query->orderBy($this->getQualifiedColumn());

        if ($this->isNotPagingOnKey()) {
            $this->query->orderBy($this->getQualifiedKeyName());
        }

        return $this;
    }

    /**
     * @param int $limit
     * @param array|string $columns
     * @return EloquentCollection
     */
    private function get(int $limit, $columns): EloquentCollection
    {
        return $this->query->take($limit)->get($columns);
    }

    /**
     * Get the column value for the provided id.
     *
     * @param string|int $id
     * @return mixed
     * @throws OutOfRangeException
     *      if the id does not exist.
     */
    private function getColumnValue($id)
    {
        // we want the raw DB value, not the Model value as that can be mutated.
        $query = clone $this->query->toBase();

        $value = $query
            ->where($this->getQualifiedKeyName(), $id)
            ->value($this->getQualifiedColumn());

        if (is_null($value)) {
            throw new OutOfRangeException("Cursor key {$id} does not exist or has a null value.");
        }

        return $value;
    }

    /**
     * @return bool
     */
    private function isDescending(): bool
    {
        return 'desc' === $this->direction;
    }

    /**
     * Are we paging using the key column?
     *
     * @return bool
     */
    private function isPagingOnKey(): bool
    {
        return $this->column === $this->keyName;
    }

    /**
     * Are we not paging on the key column?
     *
     * @return bool
     */
    private function isNotPagingOnKey(): bool
    {
        return !$this->isPagingOnKey();
    }

    /**
     * @return string
     */
    private function getUnqualifiedKey(): string
    {
        $parsed = explode('.', $this->keyName);

        return $parsed[1] ?? $parsed[0];
    }

    /**
     * Guess the column to use for the cursor.
     *
     * @return string
     */
    private function guessColumn(): string
    {
        return $this->query->getModel()->getCreatedAtColumn();
    }

    /**
     * Guess the key to use for the cursor.
     *
     * @return string
     */
    private function guessKey(): string
    {
        return $this->query->getModel()->getRouteKeyName();
    }
}
