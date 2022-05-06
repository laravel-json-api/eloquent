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

namespace LaravelJsonApi\Eloquent\Filters;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;

class WhereLike implements Filter
{
    use Concerns\IsSingular;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var array|string[]
     */
    private array $columns;

    /**
     * @var string|null
     */
    private ?string $position = null;

    /**
     * Create a new filter.
     *
     * @param string $name
     * @param array $columns
     * @return static
     */
    public static function make(string $name, array $columns = []): self
    {
        return new static($name, $columns);
    }

    /**
     * WhereSearch constructor.
     *
     * @param string $name
     * @param array $columns
     */
    public function __construct(string $name, array $columns = [])
    {
        $this->name = $name;
        $this->columns = empty($columns) ? [$name] : Arr::wrap($columns);
    }

    /**
     * @inheritDoc
     */
    public function key(): string
    {
        return $this->name;
    }

    /**
     * Apply the "WhereLike" filter to the query builder.
     *
     * @param $query
     * @param $value
     * @return Builder
     */
    public function apply($query, $value): Builder
    {
        $pattern = $this->pattern($value);

        if (1 === count($this->columns)) {
            $query->where(
                $query->qualifyColumn($this->columns[0]),
                'LIKE',
                $pattern
            );

            return $query;
        }

        $query->where(function (Builder $q) use ($pattern) {
            foreach ($this->columns as $column) {
                $q->orWhere($q->qualifyColumn($column), 'LIKE', $pattern);
            }
        });

        return $query;
    }

    /**
     * Set the pattern to query the start of a dataset.
     *
     * @return $this
     */
    public function startsWith(): self
    {
        $this->position = 'start';

        return $this;
    }

    /**
     * Set the patter to query the end of a dataset.
     *
     * @return $this
     */
    public function endsWith(): self
    {
        $this->position = 'end';

        return $this;
    }

    /**
     * Get the pattern for the LIKE query.
     *
     * @param string $value
     * @return string
     */
    private function pattern(string $value): string
    {
        if ($this->position === 'start') {
            return $value . '%';
        }

        if ($this->position === 'end') {
            return '%' . $value;
        }

        return '%' . $value . '%';
    }
}
