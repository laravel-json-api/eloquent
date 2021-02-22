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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as EloquentBelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany as EloquentHasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough as EloquentHasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany as EloquentMorphMany;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Contracts\Store\QueryManyBuilder;
use LaravelJsonApi\Core\Query\QueryParameters;
use LaravelJsonApi\Eloquent\Fields\Relations\ToMany;
use LogicException;
use function get_class;
use function sprintf;

class QueryToMany implements QueryManyBuilder
{

    use HasQueryParameters;

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
     * @param Model $model
     * @param ToMany $relation
     */
    public function __construct(Model $model, ToMany $relation)
    {
        $this->model = $model;
        $this->relation = $relation;
        $this->queryParameters = new QueryParameters();
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
        $schema = $this->relation->schema();

        $query = new JsonApiBuilder(
            $schema,
            $schema->relatableQuery($this->request, $this->getRelation()),
            $this->relation
        );

        return $query->withQueryParameters(
            $this->queryParameters
        );
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

}
