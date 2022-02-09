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

namespace LaravelJsonApi\Eloquent\Polymorphism;

use Countable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Enumerable;
use IteratorAggregate;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Eloquent\Fields\Relations\Relation;
use LaravelJsonApi\Eloquent\Fields\Relations\ToMany;
use LaravelJsonApi\Eloquent\Fields\Relations\ToOne;
use Traversable;

class MorphValue implements IteratorAggregate, Countable
{

    /**
     * @var Relation
     */
    private Relation $relation;

    /**
     * @var EloquentCollection|Model|null
     */
    private $value;

    /**
     * MorphValue constructor.
     *
     * @param Relation $relation
     * @param $value
     */
    public function __construct(Relation $relation, $value)
    {
        $this->relation = $relation;
        $this->value = $value;
    }

    /**
     * @param $includePaths
     * @return $this
     */
    public function load($includePaths): self
    {
        if ($this->isNotEmpty()) {
            $schema = $this->relation->schema();
            $includePaths = IncludePaths::cast($includePaths)->forSchema($schema);

            $schema
                ->loaderFor($this->value)
                ->load($includePaths);
        }

        return $this;
    }

    /**
     * @param $includePaths
     * @return $this
     */
    public function loadMissing($includePaths): self
    {
        if ($this->isNotEmpty()) {
            $schema = $this->relation->schema();
            $includePaths = IncludePaths::cast($includePaths)->forSchema($schema);

            $schema
                ->loaderFor($this->value)
                ->loadMissing($includePaths);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        if ($this->value instanceof Enumerable) {
            return $this->value->isEmpty();
        }

        return 0 === $this->count();
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        if ($this->value instanceof Model) {
            return 1;
        }

        if ($this->value instanceof Countable) {
            return $this->value->count();
        }

        return 0;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        if ($this->relation instanceof ToOne && $this->value) {
            yield $this->relation->parse($this->value);
            return;
        }

        if ($this->relation instanceof ToMany) {
            yield from $this->relation->parse($this->value);
        }
    }

}
