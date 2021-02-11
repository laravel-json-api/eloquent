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

namespace LaravelJsonApi\Eloquent;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Contracts\Store\QueryAllBuilder;
use LaravelJsonApi\Core\Query\QueryParameters;

class QueryAll implements QueryAllBuilder
{

    use HasQueryParameters;

    /**
     * @var Schema
     */
    private Schema $schema;

    /**
     * QueryAll constructor.
     *
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
        $this->queryParameters = new QueryParameters();
    }

    /**
     * @inheritDoc
     */
    public function filter(?array $filters): self
    {
        $this->queryParameters->setFilters($filters);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function sort($fields): self
    {
        $this->queryParameters->setSortFields($fields);

        return $this;
    }

    /**
     * @return Builder
     */
    public function query(): Builder
    {
        $base = $this->schema->newInstance()->newQuery();

        $query = new Builder(
            $this->schema,
            $this->schema->indexQuery($this->request, $base)
        );

        return $query->withQueryParameters(
            $this->queryParameters
        );
    }

    /**
     * @inheritDoc
     */
    public function first(): ?object
    {
        return $this->query()->first();
    }

    /**
     * @inheritDoc
     */
    public function firstOrMany()
    {
        $query = $this->query();

        if ($query->isSingular()) {
            return $query->first();
        }

        return $query->cursor();
    }

    /**
     * @inheritDoc
     */
    public function get(): Collection
    {
        return $this->query()->get();
    }

    /**
     * @inheritDoc
     */
    public function cursor(): LazyCollection
    {
        return $this->query()->cursor();
    }

    /**
     * @inheritDoc
     */
    public function paginate(array $page): Page
    {
        return $this->query()->paginate($page);
    }

    /**
     * @inheritDoc
     */
    public function getOrPaginate(?array $page): iterable
    {
        $query = $this->query();

        if (is_null($page)) {
            $page = $this->schema->defaultPagination();
        }

        if (is_null($page)) {
            return $query->get();
        }

        return $query->paginate($page);
    }

    /**
     * @inheritDoc
     */
    public function firstOrPaginate(?array $page)
    {
        $query = $this->query();

        /**
         * If page is `null` and a singular filter has been used,
         * we return the first matching record as the client is expecting
         * a zero-to-one response.
         *
         * If a singular filter has been used but the page parameter
         * is not null, then the client has explicitly asked for a page so
         * we want to return a page regardless of the fact that a singular
         * filter has been used.
         */
        if (is_null($page) && $query->isSingular()) {
            return $query->first();
        }

        $page = $page ?? $this->schema->defaultPagination();

        if (is_null($page)) {
            return $query->cursor();
        }

        return $query->paginate($page);
    }
}
