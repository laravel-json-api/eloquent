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

namespace LaravelJsonApi\Eloquent\EagerLoading;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo as EloquentMorphTo;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use InvalidArgumentException;
use LaravelJsonApi\Contracts\Schema\Container;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Query\RelationshipPath;
use LaravelJsonApi\Eloquent\Fields\Relations\MorphTo;
use LaravelJsonApi\Eloquent\Schema;
use LogicException;

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
     * @var bool
     */
    private bool $skipMissingFields = false;

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
     * @return $this
     */
    public function skipMissingFields(): self
    {
        $this->skipMissingFields = true;

        return $this;
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
     * @return bool
     *      whether any eager load paths were applied.
     */
    public function with($includePaths): bool
    {
        if (!$this->query) {
            throw new LogicException('No query to load relations on.');
        }

        $paths = $this->toRelations($includePaths);
        $morphs = $this->toMorphs($includePaths);

        $this->query->with($paths);

        foreach ($morphs as $name => $map) {
            $this->query->with($name, static function(EloquentMorphTo $morphTo) use ($map) {
                $morphTo->morphWith($map);
            });
        }

        return !empty($paths) || !empty($morphs);
    }

    /**
     * @param mixed $includePaths
     * @return void
     */
    public function load($includePaths): void
    {
        foreach (array_filter([$this->models, $this->model]) as $target) {
            $target->load(
                $this->toRelations($includePaths)
            );

            foreach ($this->toMorphs($includePaths) as $relation => $map) {
                $target->loadMorph($relation, $map);
            }
        }
    }

    /**
     * @param mixed $includePaths
     * @return void
     */
    public function loadMissing($includePaths): void
    {
        foreach (array_filter([$this->models, $this->model]) as $target) {
            $target->loadMissing(
                $this->toRelations($includePaths)
            );

            foreach ($this->toMorphs($includePaths) as $relation => $map) {
                $target->loadMorph($relation, $map);
            }
        }
    }

    /**
     * @param mixed $includePaths
     * @return array
     */
    public function toRelations($includePaths): array
    {
        $paths = new EagerLoadIterator($this->schemas, $this->schema, $includePaths);
        $paths->skipMissingFields($this->skipMissingFields);

        return $paths->all();
    }

    /**
     * @param $includePaths
     * @return array
     */
    public function toMorphs($includePaths): array
    {
        return collect(IncludePaths::cast($includePaths)->all())
            ->filter(fn($path) => $this->isMorph($path))
            ->groupBy(fn(RelationshipPath $path) => $path->first())
            ->map(fn($paths, $name) => $this->morphs($name, $paths)->all())
            ->all();
    }

    /**
     * Does the relationship path need to be treated as a morph map?
     *
     * We create morph maps for any path where the first item in the path
     * is a morph-to relation, and:
     *
     * 1. there is more than one relation in the path and it is an include
     * path; OR
     * 2. at least one of the inverse resource types has default eager load
     * paths.
     *
     * @param RelationshipPath $path
     * @return bool
     */
    private function isMorph(RelationshipPath $path): bool
    {
        if (!$this->schema->isRelationship($path->first())) {
            return false;
        }

        $relation = $this->schema->relationship($path->first());

        if (!$relation instanceof MorphTo) {
            return false;
        }

        if (1 < $path->count() && $relation->isIncludePath()) {
            return true;
        }

        foreach ($relation->inverseTypes() as $type) {
            $schema = $this->schemas->schemaFor($type);

            if ($schema instanceof Schema && !empty($schema->with())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $fieldName
     * @param $paths
     * @return EagerLoadMorphs
     */
    private function morphs(string $fieldName, $paths): EagerLoadMorphs
    {
        /** @var MorphTo $relation */
        $relation = $this->schema->relationship($fieldName);

        return new EagerLoadMorphs(
            $this->schemas,
            $relation,
            IncludePaths::cast($paths)->skip(1)
        );
    }
}
