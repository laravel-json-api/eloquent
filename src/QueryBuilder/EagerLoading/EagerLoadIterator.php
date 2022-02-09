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

namespace LaravelJsonApi\Eloquent\QueryBuilder\EagerLoading;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use IteratorAggregate;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Eloquent\Schema;
use Traversable;

/**
 * Class EagerLoadIterator
 *
 * @internal
 */
class EagerLoadIterator implements IteratorAggregate
{

    /**
     * @var Schema
     */
    private Schema $schema;

    /**
     * @var IncludePaths
     */
    private IncludePaths $paths;

    /**
     * Fluent constructor.
     *
     * @param Schema $schema
     * @param mixed $paths
     * @return static
     */
    public static function make(Schema $schema, IncludePaths $paths): self
    {
        return new self($schema, $paths);
    }

    /**
     * EagerLoadIterator constructor.
     *
     * @param Schema $schema
     * @param IncludePaths $paths
     */
    public function __construct(Schema $schema, IncludePaths $paths)
    {
        $this->schema = $schema;
        $this->paths = $paths;
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
    public function getIterator(): Traversable
    {
        /**
         * We always need to yield the default paths on the base schema.
         */
        foreach ($this->schema->with() as $relation) {
            yield $relation;
        }

        /**
         * Next we iterate over the include paths, using the EagerLoadPathList
         * class to work out what the eager load path(s) are for each include
         * path. (One JSON:API include path can map to one-to-many Eloquent
         * eager load paths.)
         */
        foreach ($this->paths as $path) {
            foreach (new EagerLoadPathList($this->schema, $path) as $eagerLoadPath) {
                yield $eagerLoadPath;
            }
        }
    }

}
