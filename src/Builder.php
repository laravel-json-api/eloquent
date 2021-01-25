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

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Traits\ForwardsCalls;
use InvalidArgumentException;
use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Contracts\Schema\Relation as SchemaRelation;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Query\QueryParameters;
use LaravelJsonApi\Core\Query\RelationshipPath;
use LaravelJsonApi\Core\Query\SortField;
use LaravelJsonApi\Core\Query\SortFields;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Contracts\Sortable;
use LogicException;
use RuntimeException;

/**
 * Class Builder
 *
 * @mixin EloquentBuilder
 */
class Builder
{

    use ForwardsCalls;

    /**
     * @var Schema
     */
    private Schema $schema;

    /**
     * @var EloquentBuilder|EloquentRelation
     */
    private $query;

    /**
     * @var SchemaRelation|null
     */
    private ?SchemaRelation $relation;

    /**
     * @var QueryParameters
     */
    private QueryParameters $parameters;

    /**
     * @var bool
     */
    private bool $singular = false;

    /**
     * @var bool
     */
    private bool $eagerLoading = false;

    /**
     * Builder constructor.
     *
     * @param Schema $schema
     * @param EloquentBuilder|EloquentRelation|Model $query
     * @param SchemaRelation|null $relation
     */
    public function __construct(Schema $schema, $query, SchemaRelation $relation = null)
    {
        if ($query instanceof Model) {
            $query = $query->newQuery();
        }

        if ($query instanceof EloquentRelation && !$relation) {
            throw new InvalidArgumentException('Expecting a schema relation when querying an Eloquent relation.');
        }

        if ($relation && !$query instanceof EloquentRelation) {
            throw new InvalidArgumentException('Expecting an Eloquent relation when querying a schema relation.');
        }

        $this->schema = $schema;
        $this->query = $query;
        $this->relation = $relation;
        $this->parameters = new QueryParameters();
    }

    /**
     * @param $name
     * @param $arguments
     * @return $this
     */
    public function __call($name, $arguments)
    {
        $result = $this->forwardCallTo($this->query, $name, $arguments);

        if ($result === $this->query) {
            return $this;
        }

        return $result;
    }

    /**
     * Apply the supplied JSON API filters.
     *
     * @param array|null $filters
     * @return $this
     */
    public function filter(?array $filters): self
    {
        if (is_null($filters)) {
            $this->parameters->withoutFilters();
            return $this;
        }

        $keys = [];

        foreach ($this->filters() as $filter) {
            if ($filter instanceof Filter) {
                $keys[] = $key = $filter->key();

                if (array_key_exists($key, $filters)) {
                    $filter->apply($this->query, $value = $filters[$key]);
                    $actual[$key] = $value;

                    if ($filter->isSingular()) {
                        $this->singular = true;
                    }
                }
                continue;
            }

            throw new RuntimeException(sprintf(
                'Schema %s has a filter that does not implement the Eloquent filter contract.',
                $this->schema->type()
            ));
        }

        $unrecognised = collect($filters)->keys()->diff($keys);

        if ($unrecognised->isNotEmpty()) {
            throw new RuntimeException(sprintf(
                'Encountered filters that are not defined on the %s schema: %s',
                $this->schema->type(),
                $unrecognised->implode(', ')
            ));
        }

        $this->parameters->setFilters($filters);

        return $this;
    }

    /**
     * Sort models using JSON API sort fields.
     *
     * @param SortFields|SortField|array|string|null $fields
     * @return $this
     */
    public function sort($fields): self
    {
        if (is_null($fields)) {
            $this->parameters->withoutSortFields();
            return $this;
        }

        $fields = SortFields::cast($fields);

        /** @var SortField $sort */
        foreach ($fields as $sort) {
            if ('id' === $sort->name()) {
                $this->orderByResourceId($sort->getDirection());
                continue;
            }

            $field = $this->schema->attribute($sort->name());

            if ($field->isSortable() && $field instanceof Sortable) {
                $field->sort($this->query, $sort->getDirection());
                continue;
            }

            throw new LogicException(sprintf(
                'Field %s is not sortable on resource type %s.',
                $sort->name(),
                $this->schema->type()
            ));
        }

        $this->parameters->setSortFields($fields);

        return $this;
    }

    /**
     * Set the relations that should be eager loaded using JSON API include paths.
     *
     * @param IncludePaths|RelationshipPath|array|string|null $includePaths
     * @return $this
     */
    public function with($includePaths): self
    {
        if (is_null($includePaths)) {
            $this->parameters->withoutIncludePaths();
            return $this;
        }

        $includePaths = IncludePaths::cast($includePaths);

        if ($includePaths->isNotEmpty()) {
            $this->schema->loader()
                ->using($this->query)
                ->with($includePaths);

            $this->eagerLoading = true;
        }

        $this->parameters->setIncludePaths($includePaths);

        return $this;
    }

    /**
     * Add a where clause using the JSON API resource id.
     *
     * @param string|array|Arrayable $resourceId
     * @return $this
     */
    public function whereResourceId($resourceId): self
    {
        $column = $this->qualifiedIdColumn();

        if (is_string($resourceId)) {
            $this->query->where($column, '=', $resourceId);
            return $this;
        }

        if (is_array($resourceId) || $resourceId instanceof Arrayable) {
            $this->query->whereIn($column, $resourceId);
            return $this;
        }

        throw new InvalidArgumentException('Unexpected resource id value.');
    }

    /**
     * Add an "order by" clause to the query for the resource id column.
     *
     * @param string $direction
     * @return $this
     */
    public function orderByResourceId(string $direction = 'asc'): self
    {
        $column = $this->qualifiedIdColumn();

        $this->query->orderBy($column, $direction);

        return $this;
    }

    /**
     * Add a descending "order by" clause to the query for the resource id column.
     *
     * @return $this
     */
    public function orderByResourceIdDescending(): self
    {
        $this->orderByResourceId('desc');

        return $this;
    }

    /**
     * Has a singular filter been applied?
     *
     * @return bool
     */
    public function isSingular(): bool
    {
        return $this->singular;
    }

    /**
     * Has no singular filter been applied?
     *
     * @return bool
     */
    public function isNotSingular(): bool
    {
        return !$this->isSingular();
    }

    /**
     * Execute the query as a cursor.
     *
     * Eager loading does not work on a lazy collection. Therefore, if any include
     * paths have been used, we must call `get()` instead of `cursor()`.
     *
     * The advantage of using this method is that we get the memory performance
     * benefit if the client has not requested any eager loading.
     *
     * @return LazyCollection
     */
    public function cursor(): LazyCollection
    {
        if ($this->eagerLoading) {
            return new LazyCollection($this->get());
        }

        return $this->query->cursor();
    }

    /**
     * Return a page of models using JSON API page parameters.
     *
     * @param array $page
     * @return Page|mixed
     */
    public function paginate(array $page)
    {
        $paginator = $this->schema->pagination();

        if ($paginator instanceof Paginator) {
            return $paginator
                ->withKeyName($this->schema->idColumn())
                ->paginate($this->query, $page)
                ->withQuery($this->parameters->setPagination($page)->toArray());
        }

        if ($paginator) {
            throw new LogicException(sprintf(
                'Expecting paginator for resource %s to be an Eloquent paginator.',
                $this->schema->type()
            ));
        }

        throw new LogicException(sprintf(
            'Resource %s does not support pagination.',
            $this->schema->type()
        ));
    }

    /**
     * @return QueryParameters
     */
    public function getQueryParameters(): QueryParameters
    {
        return $this->parameters;
    }

    /**
     * @return EloquentBuilder
     */
    public function toBase(): EloquentBuilder
    {
        if ($this->query instanceof EloquentRelation) {
            return $this->query->getQuery();
        }

        return $this->query;
    }

    /**
     * @return iterable
     */
    private function filters(): iterable
    {
        yield from $this->schema->filters();

        if ($this->relation) {
            yield from $this->relation->filters();
        }
    }

    /**
     * Get the qualified id column.
     *
     * @return string
     */
    private function qualifiedIdColumn(): string
    {
        return $this->query->getModel()->qualifyColumn(
            $this->schema->idColumn()
        );
    }
}
