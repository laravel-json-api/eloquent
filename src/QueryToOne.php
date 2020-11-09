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
use Illuminate\Database\Eloquent\Relations\HasOne as EloquentHasOne;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use Illuminate\Database\Eloquent\Relations\BelongsTo as EloquentBelongsTo;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Contracts\Query\QueryParameters as QueryParametersContract;
use LaravelJsonApi\Contracts\Schema\Container;
use LaravelJsonApi\Contracts\Store\QueryOneBuilder;
use LaravelJsonApi\Contracts\Store\QueryOneBuilder as QueryOneBuilderContract;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LogicException;
use function sprintf;

class QueryToOne implements QueryOneBuilder
{

    /**
     * @var Container
     */
    private Container $schemas;

    /**
     * @var Model
     */
    private Model $model;

    /**
     * @var BelongsTo
     */
    private BelongsTo $relation;

    /**
     * @var array|null
     */
    private ?array $filters = null;

    /**
     * @var mixed|null
     */
    private $includePaths = null;

    /**
     * QueryToOne constructor.
     *
     * @param Container $schemas
     * @param Model $model
     * @param BelongsTo $relation
     */
    public function __construct(Container $schemas, Model $model, BelongsTo $relation)
    {
        $this->schemas = $schemas;
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
        $this->includePaths = $includePaths;

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

        return $this
            ->query()
            ->filter($this->filters)
            ->with($this->includePaths)
            ->first();
    }

    /**
     * @return Builder
     */
    public function query(): Builder
    {
        return new Builder($this->inverse(), $this->relation());
    }

    /**
     * @return EloquentRelation
     */
    private function relation(): EloquentRelation
    {
        $relation = $this->model->{$this->relation->name()}();

        if ($relation instanceof EloquentHasOne || $relation instanceof EloquentBelongsTo) {
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
     * @return Schema
     */
    private function inverse(): Schema
    {
        $schema = $this->schemas->schemaFor(
            $this->relation->inverse()
        );

        if ($schema instanceof Schema) {
            return $schema;
        }

        throw new LogicException(sprintf(
            'Expecting inverse schema for resource type %s to be an Eloquent schema.',
            $this->relation->inverse()
        ));
    }

    /**
     * Return the already loaded related model.
     *
     * @return Model|null
     */
    private function related(): ?Model
    {
        if ($related = $this->model->getRelation($this->relation->name())) {
            return $this
                ->inverse()
                ->loader()
                ->using($related)
                ->loadMissing($this->includePaths);
        }

        return null;
    }

}
