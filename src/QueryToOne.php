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
use Illuminate\Database\Eloquent\Relations\BelongsTo as EloquentBelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne as EloquentHasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough as EloquentHasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne as EloquentMorphOne;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use LaravelJsonApi\Contracts\Store\QueryOneBuilder;
use LaravelJsonApi\Contracts\Store\QueryOneBuilder as QueryOneBuilderContract;
use LaravelJsonApi\Core\Query\Custom\ExtendedQueryParameters;
use LaravelJsonApi\Eloquent\Fields\Relations\ToOne;
use LaravelJsonApi\Eloquent\QueryBuilder\JsonApiBuilder;
use LogicException;
use function sprintf;

class QueryToOne implements QueryOneBuilder
{

    use HasQueryParameters;

    /**
     * @var Model
     */
    private Model $model;

    /**
     * @var ToOne
     */
    private ToOne $relation;

    /**
     * QueryToOne constructor.
     *
     * @param Model $model
     * @param ToOne $relation
     */
    public function __construct(Model $model, ToOne $relation)
    {
        $this->model = $model;
        $this->relation = $relation;
        $this->queryParameters = new ExtendedQueryParameters();
    }

    /**
     * @inheritDoc
     */
    public function filter(?array $filters): QueryOneBuilderContract
    {
        $this->queryParameters->setFilters($filters);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function first(): ?object
    {
        if ($this->model->relationLoaded($this->relation->name()) && empty($this->queryParameters->filter())) {
            return $this->relation->parse(
                $this->related()
            );
        }

        return $this->relation->parse(
            $this->query()->first()
        );
    }

    /**
     * @return JsonApiBuilder
     */
    public function query(): JsonApiBuilder
    {
        return $this->relation
            ->newQuery($this->getRelation())
            ->withQueryParameters($this->queryParameters);
    }

    /**
     * @return EloquentRelation
     */
    private function getRelation(): EloquentRelation
    {
        $name = $this->relation->relationName();
        $relation = $this->model->{$name}();

        if ($relation instanceof EloquentHasOne ||
            $relation instanceof EloquentBelongsTo ||
            $relation instanceof EloquentHasOneThrough ||
            $relation instanceof EloquentMorphOne
        ) {
            return $relation;
        }

        if ($relation instanceof EloquentRelation) {
            throw new LogicException(sprintf(
                'Eloquent relation %s on model %s returned a %s relation, which is not a to-one relation.',
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
     * Return the already loaded related model.
     *
     * @return Model|null
     */
    private function related(): ?Model
    {
        if ($related = $this->model->getRelation($this->relation->relationName())) {
            $this->relation
                ->schema()
                ->loaderFor($related)
                ->loadMissing($this->queryParameters->includePaths())
                ->loadCount($this->queryParameters->countable());

            return $related;
        }

        return null;
    }

}
