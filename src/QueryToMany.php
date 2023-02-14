<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as EloquentBelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany as EloquentHasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough as EloquentHasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany as EloquentMorphMany;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use Illuminate\Support\LazyCollection;
use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Contracts\Store\HasPagination;
use LaravelJsonApi\Contracts\Store\QueryManyBuilder;
use LaravelJsonApi\Core\Query\Custom\ExtendedQueryParameters;
use LaravelJsonApi\Eloquent\Fields\Relations\ToMany;
use LaravelJsonApi\Eloquent\QueryBuilder\JsonApiBuilder;
use LogicException;
use function get_class;
use function sprintf;

class QueryToMany implements QueryManyBuilder, HasPagination
{

    use HasQueryParameters;

    /**
     * @var Schema
     */
    private Schema $schema;

    /**
     * @var Model
     */
    private Model $model;

    /**
     * @var ToMany
     */
    private ToMany $relation;

    /**
     * QueryToMany constructor.
     *
     * @param Schema $schema
     * @param Model $model
     * @param ToMany $relation
     */
    public function __construct(Schema $schema, Model $model, ToMany $relation)
    {
        $this->schema = $schema;
        $this->model = $model;
        $this->relation = $relation;
        $this->queryParameters = new ExtendedQueryParameters();
    }

    /**
     * @inheritDoc
     */
    public function filter(?array $filters): QueryManyBuilder
    {
        $this->queryParameters->setFilters($filters);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function sort($fields): QueryManyBuilder
    {
        $this->queryParameters->setSortFields($fields);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get(): iterable
    {
        return $this->relation->parse(
            $this->query()->get()
        );
    }

    /**
     * @return LazyCollection
     */
    public function cursor(): LazyCollection
    {
        $value = $this->relation->parse(
            $this->query()->cursor()
        );

        if ($value instanceof LazyCollection) {
            return $value;
        }

        return LazyCollection::make($value);
    }

    /**
     * @inheritDoc
     */
    public function paginate(array $page): Page
    {
        return $this->relation->parsePage(
            $this->query()->paginate($page)
        );
    }

    /**
     * @inheritDoc
     */
    public function getOrPaginate(?array $page): iterable
    {
        if (is_null($page)) {
            $page = $this->relation->defaultPagination();
        }

        if (is_null($page)) {
            return $this->get();
        }

        return $this->paginate($page);
    }

    /**
     * @return JsonApiBuilder
     */
    public function query(): JsonApiBuilder
    {
        $this->prepareModel();

        $base = $this->relation->schema()->relatableQuery(
            $this->request, $this->getRelation()
        );

        return $this->relation
            ->newQuery($base)
            ->withQueryParameters($this->queryParameters);
    }

    /**
     * @return EloquentRelation
     */
    private function getRelation(): EloquentRelation
    {
        $name = $this->relation->relationName();
        $relation = $this->model->{$name}();

        if ($relation instanceof EloquentHasMany ||
            $relation instanceof EloquentBelongsToMany ||
            $relation instanceof EloquentHasManyThrough ||
            $relation instanceof EloquentMorphMany
        ) {
            return $relation;
        }

        if ($relation instanceof EloquentRelation) {
            throw new LogicException(sprintf(
                'Eloquent relation %s on model %s returned a %s relation, which is not a to-many relation.',
                $name,
                get_class($this->model),
                get_class($relation)
            ));
        }

        throw new LogicException(sprintf(
            'Expecting method %s on model %s to return an Eloquent relation.',
            $name,
            get_class($this->model)
        ));
    }

    /**
     * @return $this
     */
    private function prepareModel(): self
    {
        if ($this->relation->isCountableInRelationship()) {
            $this->model->loadCount(
                $this->relation->withCountName(),
            );
        }

        return $this;
    }

}
