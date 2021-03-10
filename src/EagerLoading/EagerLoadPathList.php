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

use IteratorAggregate;
use LaravelJsonApi\Core\Query\RelationshipPath;
use LaravelJsonApi\Eloquent\Fields\Relations\Relation;
use LaravelJsonApi\Eloquent\Schema;
use LogicException;

class EagerLoadPathList implements IteratorAggregate
{

    /**
     * @var Schema
     */
    private Schema $schema;

    /**
     * @var RelationshipPath
     */
    private RelationshipPath $path;

    /**
     * @var array|null
     */
    private ?array $paths = null;

    /**
     * EagerLoadPathList constructor.
     *
     * @param Schema $schema
     * @param RelationshipPath $path
     */
    public function __construct(Schema $schema, RelationshipPath $path)
    {
        $this->schema = $schema;
        $this->path = $path;
    }

    /**
     * Get the default eager load paths.
     *
     * @return iterable
     */
    public function defaults(): iterable
    {
        foreach ($this->cachedPaths() as $path) {
            foreach ($path->defaults() as $default) {
                yield $default;
            }
        }
    }

    /**
     * Get the eager load paths for the relationship path.
     *
     * @return array
     */
    public function paths(): iterable
    {
        foreach ($this->cachedPaths() as $path) {
            yield $path->toString();
        }
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        foreach ($this->defaults() as $default) {
            yield $default;
        }

        foreach ($this->paths() as $path) {
            yield $path;
        }
    }

    /**
     * Get the first relationship.
     *
     * @return Relation
     */
    private function relation(): Relation
    {
        $relation = $this->schema->relationship(
            $this->path->first()
        );

        if ($relation instanceof Relation) {
            return $relation;
        }

        throw new LogicException('Expecting an Eloquent relationship.');
    }

    /**
     * @return EagerLoadPath[]
     */
    private function cachedPaths(): array
    {
        if (is_array($this->paths)) {
            return $this->paths;
        }

        return $this->paths = $this->compute();
    }

    /**
     * Calculate the eager load paths for the provided relationship path.
     *
     * Due to polymorphic to-many relationships, one JSON:API include path
     * can be mapped to one or many Eloquent eager load paths.
     *
     * @return array
     */
    private function compute(): array
    {
        $paths = EagerLoadPath::make($this->relation());
        $terminated = [];

        if ($path = $this->path->skip(1)) {
            foreach ($path as $idx => $name) {
                $retain = [];
                foreach ($paths as $path) {
                    if (is_array($next = $path->next($name))) {
                        $retain = array_merge($retain, $next);
                        continue;
                    }

                    $terminated[] = $path;
                }

                $paths = $retain;
            }
        }

        return array_merge($paths, $terminated);
    }

}
