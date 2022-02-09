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

namespace LaravelJsonApi\Eloquent\Sorting;

use Closure;
use LaravelJsonApi\Core\Support\Str;
use LaravelJsonApi\Eloquent\Contracts\SortField;

class SortWithCount implements SortField
{

    /**
     * @var string
     */
    private string $relationName;

    /**
     * @var string
     */
    private string $key;

    /**
     * @var string|null
     */
    private ?string $countAs = null;

    /**
     * @var Closure|null
     */
    private ?Closure $callback = null;

    /**
     * Create a new sortable field.
     *
     * @param string $fieldName
     * @param string|null $key
     * @return static
     */
    public static function make(string $fieldName, string $key = null): self
    {
        return new self($fieldName, $key);
    }

    /**
     * SortCountRelation constructor.
     *
     * @param string $relationName
     * @param string|null $key
     */
    public function __construct(string $relationName, string $key = null)
    {
        $this->relationName = $relationName;
        $this->key = $key ?? $relationName;
    }

    /**
     * @inheritDoc
     */
    public function sortField(): string
    {
        return $this->key;
    }

    /**
     * Set an alias for the relationship count.
     *
     * @param string $alias
     * @return $this
     */
    public function countAs(string $alias): self
    {
        $this->countAs = $alias;

        return $this;
    }

    /**
     * @param Closure $callback
     * @return $this
     */
    public function using(Closure $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function sort($query, string $direction = 'asc')
    {
        $callback = $this->callback ?? static fn($query) => $query;

        return $query
            ->withCount([$this->withCountName() => $callback])
            ->orderBy($this->keyForCount(), $direction);
    }

    /**
     * @return string
     */
    protected function withCountName(): string
    {
        if ($this->countAs) {
            return "{$this->relationName} as {$this->countAs}";
        }

        return $this->relationName;
    }

    /**
     * @return string
     */
    protected function keyForCount(): string
    {
        if ($this->countAs) {
            return $this->countAs;
        }

        return Str::snake($this->relationName) . '_count';
    }

}
