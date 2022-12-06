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

use LaravelJsonApi\Core\Support\Str;
use LaravelJsonApi\Eloquent\Contracts\Filter;

class WhereNull implements Filter
{
    use Concerns\HasColumn;
    use Concerns\IsSingular;

    /**
     * @var string
     */
    private string $key;

    /**
     * Create a new filter.
     *
     * @param string      $key
     * @param string|null $column
     *
     * @return static
     */
    public static function make(string $key, string $column = null): self
    {
        return new static($key, $column);
    }

    /**
     * WhereNull constructor.
     *
     * @param string|null $column
     */
    public function __construct(string $key, string $column = null)
    {
        $this->key = $key;
        $this->column = $column ?: $this->guessColumn();
    }

    /**
     * @return string
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * @return $this
     */
    public function not(): WhereNull
    {
        $this->not = true;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function apply($query, $value)
    {
        $value = filter_var($value, FILTER_VALIDATE_BOOL);
        $this->not = !$value ?? false;

        return $query->whereNull($query->getModel()->qualifyColumn($this->column()), 'and', $this->not);
    }

    /**
     * @return string
     */
    private function guessColumn(): string
    {
        return Str::underscore($this->key);
    }
}
