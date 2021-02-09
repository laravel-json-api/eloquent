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

use Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use IteratorAggregate;
use LaravelJsonApi\Contracts\Schema\Container;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Query\RelationshipPath;
use LaravelJsonApi\Eloquent\Fields\Relations\MorphTo;
use LaravelJsonApi\Eloquent\Schema;

class EagerLoadIterator implements IteratorAggregate
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
     * @var IncludePaths
     */
    private IncludePaths $paths;

    /**
     * @var bool
     */
    private bool $skipMissingFields = false;

    /**
     * DefaultEagerLoadIterator constructor.
     *
     * @param Container $schemas
     * @param Schema $schema
     * @param mixed $paths
     */
    public function __construct(Container $schemas, Schema $schema, $paths)
    {
        $this->schemas = $schemas;
        $this->schema = $schema;
        $this->paths = IncludePaths::cast($paths);
    }

    /**
     * @param bool $skip
     * @return $this
     */
    public function skipMissingFields(bool $skip = true): self
    {
        $this->skipMissingFields = $skip;

        return $this;
    }

    /**
     * Get the paths as a collection.
     *
     * Before returning the paths, we filter out any duplicates. For example, if the iterator
     * yields `user` and `user.country`, we only want `user.country` to be in the collection.
     *
     * @return Collection
     */
    public function collect(): Collection
    {
        $values = collect($this);

        return $values->reject(
            fn($path) => $values->contains(fn($check) => $path !== $check && Str::startsWith($check, $path))
        )->sort()->values();
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->collect()->all();
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        /** We always need to yield the default paths on the base schema. */
        foreach ($this->schema->with() as $relation) {
            yield $relation;
        }

        /**
         * Next we need to make our way down the include paths, yielding
         * the default eager loading settings for each schema along that path.
         */
        foreach ($this->paths as $path) {
            foreach ($this->defaultsForPath($path) as $relation) {
                yield $relation;
            }
        }

        /**
         * Finally we need to convert each include path to an Eloquent eager
         * load path.
         */
        foreach ($this->paths as $path) {
            $path = new EagerLoadPath($this->schemas, $this->schema, $path);
            $path->skipMissingFields($this->skipMissingFields);

            if ($relationPath = $path->toString()) {
                yield $relationPath;
            }
        }
    }

    /**
     * @param RelationshipPath $path
     * @return Generator
     */
    private function defaultsForPath(RelationshipPath $path): Generator
    {
        $schema = $this->schema;
        $names = [];

        foreach ($path->names() as $name) {
            /** Ignore a relation that does not exist. */
            if (!$schema->isRelationship($name)) {
                break;
            }

            $names[] = $name;
            $relation = $schema->relationship($name);

            /** Morph to relations must be dealt with via a morph map. */
            if ($relation instanceof MorphTo) {
                break;
            }

            $schema = $this->schemas->schemaFor($relation->inverse());

            if ($schema instanceof Schema) {
                foreach ($schema->with() as $default) {
                    yield implode('.', array_merge($names, [$default]));
                }
            }
        }
    }
}
