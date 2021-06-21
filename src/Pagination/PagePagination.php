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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\AbstractPaginator;
use LaravelJsonApi\Contracts\Pagination\Page as PageContract;
use LaravelJsonApi\Core\Pagination\Concerns\HasPageMeta;
use LaravelJsonApi\Core\Pagination\Concerns\HasPageNumbers;
use LaravelJsonApi\Core\Pagination\Page;
use LaravelJsonApi\Eloquent\Contracts\Paginator;

class PagePagination implements Paginator
{

    use HasPageMeta;
    use HasPageNumbers;

    /**
     * @var array|null
     */
    private ?array $columns = null;

    /**
     * @var bool|null
     */
    private ?bool $simplePagination = null;

    /**
     * @var string|null
     */
    private ?string $primaryKey = null;

    /**
     * Fluent constructor.
     *
     * @return PagePagination
     */
    public static function make(): self
    {
        return new static();
    }

    /**
     * Override the page resolver to read the page parameter from the JSON:API request.
     *
     * @return void
     */
    public static function bindPageResolver(): void
    {
        AbstractPaginator::currentPageResolver(static function ($pageName) {
            $pagination = \request()->query($pageName);
            return $pagination['number'] ?? null;
        });
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
     * Use simple pagination.
     *
     * @return $this
     */
    public function withSimplePagination(): self
    {
        $this->simplePagination = true;

        return $this;
    }

    /**
     * Use length-aware pagination.
     *
     * @return $this
     */
    public function withLengthAwarePagination(): self
    {
        $this->simplePagination = false;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function paginate($query, array $page): PageContract
    {
        $paginator = $this
            ->defaultOrder($query)
            ->query($query, $page);

        return Page::make($paginator)
            ->withPageParam($this->pageKey)
            ->withPerPageParam($this->perPageKey)
            ->withMeta($this->hasMeta)
            ->withNestedMeta($this->metaKey)
            ->withMetaCase($this->metaCase);
    }

    /**
     * Get the number of items to return per-page.
     *
     * If this method returns zero, the default per-page will be used.
     *
     * @param array $page
     * @return int
     */
    protected function getPerPage(array $page): int
    {
        $perPage = $page[$this->perPageKey] ?? 0;

        return (int) $perPage;
    }

    /**
     * Get the default per-page value for the query.
     *
     * If the query is an Eloquent builder, we can pass in `null` as the default,
     * which then delegates to the model to get the default. Otherwise the Laravel
     * standard default is 15.
     *
     * @return int|null
     */
    protected function getDefaultPerPage(): ?int
    {
        return $this->defaultPerPage;
    }

    /**
     * @return array
     */
    protected function getColumns(): array
    {
        return $this->columns ?: ['*'];
    }

    /**
     * @return bool
     */
    protected function isSimplePagination(): bool
    {
        return (bool) $this->simplePagination;
    }

    /**
     * @param Builder|Relation $query
     * @return bool
     */
    private function willSimplePaginate($query): bool
    {
        return $this->isSimplePagination() && method_exists($query, 'simplePaginate');
    }

    /**
     * Apply a deterministic order to the page.
     *
     * @param Builder|Relation $query
     * @return $this
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/313
     */
    private function defaultOrder($query): self
    {
        if ($this->doesRequireOrdering($query)) {
            $query->orderBy(
                $query->getModel()->qualifyColumn($this->primaryKey)
            );
        }

        return $this;
    }

    /**
     * Do we need to apply a deterministic order to the query?
     *
     * If the primary key has not been used for a sort order already, we use it
     * to ensure the page has a deterministic order.
     *
     * @param Builder|Relation $query
     * @return bool
     */
    private function doesRequireOrdering($query): bool
    {
        if (!$this->primaryKey) {
            return false;
        }

        $query = $query->toBase();

        return !collect($query->orders ?: [])->contains(function (array $order) {
            $col = $order['column'] ?? '';
            return $this->primaryKey === $col;
        });
    }

    /**
     * @param Builder|Relation $query
     * @param array $page
     * @return mixed
     */
    private function query($query, array $page)
    {
        $size = $this->getPerPage($page) ?: $this->getDefaultPerPage();
        $cols = $this->getColumns();
        $pageNumber = $page[$this->pageKey] ?? null;

        return $this->willSimplePaginate($query) ?
            $query->simplePaginate($size, $cols, $this->pageKey, $pageNumber) :
            $query->paginate($size, $cols, $this->pageKey, $pageNumber);
    }
}
