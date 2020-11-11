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

use Generator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use InvalidArgumentException;
use LaravelJsonApi\Contracts\Schema\Container;
use LaravelJsonApi\Core\Query\IncludePaths;
use LogicException;
use function iterator_to_array;

class EagerLoader
{

    /**
     * @var Container
     */
    private Container $schemas;

    /**
     * @var Schema
     */
    private Schema $schema;

    /**
     * @var Model|null
     */
    private ?Model $model = null;

    /**
     * @var EloquentCollection|null
     */
    private ?EloquentCollection $models = null;

    /**
     * @var EloquentBuilder|EloquentRelation|null
     */
    private $query;

    /**
     * EagerLoader constructor.
     *
     * @param Container $schemas
     * @param Schema $schema
     */
    public function __construct(Container $schemas, Schema $schema)
    {
        $this->schemas = $schemas;
        $this->schema = $schema;
    }

    /**
     * @param Model $model
     * @return $this
     */
    public function forModel(Model $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @param EloquentCollection $models
     * @return $this
     */
    public function forModels(EloquentCollection $models): self
    {
        $this->models = $models;

        return $this;
    }

    /**
     * @param EloquentBuilder|EloquentRelation $query
     * @return $this
     */
    public function using($query): self
    {
        if ($query instanceof EloquentBuilder || $query instanceof EloquentRelation) {
            $this->query = $query;
            return $this;
        }

        throw new InvalidArgumentException('Expecting an Eloquent builder or relation.');
    }

    /**
     * @param $includePaths
     * @return EloquentBuilder
     */
    public function with($includePaths)
    {
        if ($this->query) {
            return $this->query->with(
                $this->toRelations($includePaths)
            );
        }

        throw new LogicException('No query to load relations on.');
    }

    /**
     * @param $includePaths
     * @return void
     */
    public function load($includePaths): void
    {
        $relations = $this->toRelations($includePaths);

        if ($this->models) {
            $this->models->load($relations);
            return;
        }

        if ($this->model) {
            $this->model->load($relations);
            return;
        }

        throw new LogicException('No model or models to load relations on.');
    }

    /**
     * @param $includePaths
     * @return void
     */
    public function loadMissing($includePaths): void
    {
        $relations = $this->toRelations($includePaths);

        if ($this->models) {
            $this->models->loadMissing($relations);
            return;
        }

        if ($this->model) {
            $this->model->loadMissing($relations);
            return;
        }

        throw new LogicException('No model or models to load relations on.');
    }

    /**
     * @param $includePaths
     * @return array
     */
    public function toRelations($includePaths): array
    {
        return iterator_to_array($this->cursor($includePaths));
    }

    /**
     * @param mixed $includePaths
     * @return Generator
     */
    public function cursor($includePaths): Generator
    {
        foreach (IncludePaths::cast($includePaths) as $path) {
            yield (string) new EagerLoadPath($this->schemas, $this->schema, $path);
        }
    }

}
