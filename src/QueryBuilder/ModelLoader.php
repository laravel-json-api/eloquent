<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

namespace LaravelJsonApi\Eloquent\QueryBuilder;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use LaravelJsonApi\Contracts\Schema\Container;
use LaravelJsonApi\Core\Query\Custom\CountablePaths;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Eloquent\QueryBuilder\Aggregates\CountableLoader;
use LaravelJsonApi\Eloquent\QueryBuilder\EagerLoading\EagerLoader;
use LaravelJsonApi\Eloquent\Schema;

class ModelLoader
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
     * @var Model|EloquentCollection
     */
    private $target;

    /**
     * ModelLoader constructor.
     *
     * @param Container $schemas
     * @param Schema $schema
     * @param EloquentCollection|Model $target
     */
    public function __construct(Container $schemas, Schema $schema, $target)
    {
        if (!$target instanceof Model && !$target instanceof EloquentCollection) {
            throw new InvalidArgumentException('Expecting a model or collection of models.');
        }

        $this->schemas = $schemas;
        $this->schema = $schema;
        $this->target = $target;
    }

    /**
     * Eager load relations using JSON:API include paths.
     *
     * @param $includePaths
     * @return $this
     */
    public function load($includePaths): self
    {
        $loader = new EagerLoader(
            $this->schemas,
            $this->schema,
            IncludePaths::cast($includePaths),
        );

        $this->target->load(
            $loader->getRelations()
        );

        foreach ($loader->getMorphs() as $relation => $map) {
            $this->target->loadMorph($relation, $map);
        }

        return $this;
    }

    /**
     * Eager load relations using JSON:API include paths, if they are not already loaded.
     *
     * @param $includePaths
     * @return $this
     */
    public function loadMissing($includePaths): self
    {
        $loader = new EagerLoader(
            $this->schemas,
            $this->schema,
            IncludePaths::cast($includePaths),
        );

        $this->target->loadMissing(
            $loader->getRelations()
        );

        foreach ($loader->getMorphs() as $relation => $map) {
            $this->target->loadMorph($relation, $map);
        }

        return $this;
    }

    /**
     * Eager load relation counts.
     *
     * @param $countable
     * @return $this
     */
    public function loadCount($countable): self
    {
        $counter = new CountableLoader(
            $this->schema,
            CountablePaths::cast($countable)
        );

        $this->target->loadCount($counter->getRelations());

        return $this;
    }

}
