<?php
/*
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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo as EloquentBelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne as EloquentHasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough as EloquentHasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne as EloquentMorphOne;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Contracts\Query\QueryParameters as QueryParametersContract;
use LaravelJsonApi\Contracts\Store\QueryOneBuilder;
use LaravelJsonApi\Contracts\Store\QueryOneBuilder as QueryOneBuilderContract;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Eloquent\Fields\Relations\ToOne;
use LogicException;
use function sprintf;

class QueryToOne implements QueryOneBuilder
{

    /**
     * @var Model
     */
    private Model $model;

    /**
     * @var ToOne
     */
    private ToOne $relation;

    /**
     * @var array|null
     */
    private ?array $filters = null;

    /**
     * @var IncludePaths|null
     */
    private ?IncludePaths $includePaths = null;

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
    }

    /**
     * @inheritDoc
     */
    public function using(QueryParametersContract $query): QueryOneBuilderContract
    {
        return $this
            ->filter($query->filter())
            ->with($query->includePaths());
    }

    /**
     * @inheritDoc
     */
    public function filter(?array $filters): QueryOneBuilderContract
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function with($includePaths): QueryOneBuilderContract
    {
        $this->includePaths = IncludePaths::nullable($includePaths);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function first(): ?object
    {
        if ($this->model->relationLoaded($this->relation->name()) && empty($this->filters)) {
            return $this->related();
        }

        return $this->prepareQuery()->first();
    }

    /**
     * @return Builder
     */
    public function query(): Builder
    {
        return new Builder(
            $this->relation->schema(),
            $this->getRelation(),
            $this->relation
        );
    }

    /**
     * @return Builder
     */
    private function prepareQuery(): Builder
    {
        return $this->query()
            ->filter($this->filters)
            ->with($this->includePaths);
    }

    /**
     * @return EloquentRelation
     */
    private function getRelation(): EloquentRelation
    {
        $relation = $this->model->{$this->relation->name()}();

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
                $this->relation->name(),
                get_class($this->model),
                get_class($relation)
            ));
        }

        throw new LogicException(sprintf(
            'Expecting method %s on model %s to return an Eloquent relation.',
            $this->relation->name(),
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
            $this->relation->schema()
                ->loader()
                ->forModel($related)
                ->loadMissing($this->includePaths);

            return $related;
        }

        return null;
    }

}
