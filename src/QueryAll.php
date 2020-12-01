<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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

namespace LaravelJsonApi\Eloquent;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Contracts\Query\QueryParameters as QueryParametersContract;
use LaravelJsonApi\Contracts\Store\QueryAllBuilder;

class QueryAll implements QueryAllBuilder
{

    /**
     * @var Schema
     */
    private Schema $schema;

    /**
     * @var Builder
     */
    private Builder $query;

    /**
     * QueryAll constructor.
     *
     * @param Schema $schema
     * @param Builder $query
     */
    public function __construct(Schema $schema, Builder $query)
    {
        $this->schema = $schema;
        $this->query = $query;
    }

    /**
     * @inheritDoc
     */
    public function using(QueryParametersContract $query): self
    {
        return $this
            ->with($query->includePaths())
            ->filter($query->filter())
            ->sort($query->sortFields());
    }

    /**
     * @inheritDoc
     */
    public function filter(?array $filters): self
    {
        $this->query->filter($filters);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function sort($fields): self
    {
        $this->query->sort($fields);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function with($includePaths): self
    {
        $this->query->with($includePaths);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function first(): ?object
    {
        return $this->query->first();
    }

    /**
     * @inheritDoc
     */
    public function firstOrMany()
    {
        if ($this->query->isSingular()) {
            return $this->first();
        }

        return $this->cursor();
    }

    /**
     * @inheritDoc
     */
    public function get(): Collection
    {
        return $this->query->get();
    }

    /**
     * @inheritDoc
     */
    public function cursor(): LazyCollection
    {
        return $this->query->cursor();
    }

    /**
     * @inheritDoc
     */
    public function paginate(array $page): Page
    {
        return $this->query->paginate($page);
    }

    /**
     * @inheritDoc
     */
    public function getOrPaginate(?array $page): iterable
    {
        if (is_null($page)) {
            $page = $this->schema->defaultPagination();
        }

        if (is_null($page)) {
            return $this->get();
        }

        return $this->paginate($page);
    }

    /**
     * @inheritDoc
     */
    public function firstOrPaginate(?array $page)
    {
        /**
         * If page is `null`, then we need to use the schema's default
         * pagination - UNLESS a singular filter has been used.
         * That's because if we add default pagination when a singular
         * filter has been used, they'll get a page when they're
         * expecting zero-to-one resource.
         */
        if (is_null($page) && $this->query->isNotSingular()) {
            $page = $this->schema->defaultPagination();
        }

        if (is_null($page)) {
            return $this->firstOrMany();
        }

        return $this->paginate($page);
    }

}
