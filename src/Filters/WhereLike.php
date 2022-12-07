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

use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Core\Support\Str;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\DeserializesValue;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;

class WhereLike implements Filter
{
    use DeserializesValue;
    use IsSingular;

    /**
     * @var string
     */
    private string $key;

    /**
     * Create a new filter.
     *
     * @param string $key
     * @param string|null $column
     * @return self
     */
    public static function make(string $key, string $column = null): self
    {
        return new static($key, $column);
    }

    /**
     * WhereLike constructor.
     *
     * @param string|null $key
     * @param string|null $column
     */
    public function __construct(string $key, string $column = null)
    {
        $this->key = $key;
        $this->column = $column ?: $this->guessColumn();
    }

    /**
     * Get the key for the filter.
     *
     * @return string
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * @param string $value
     * @param string $char
     * @return string
     */
    public static function escapeLike(string $value, string $char = '\\'): string
    {
        return str_replace(
            [$char, '%', '_'],
            [$char.$char, $char.'%', $char.'_'],
            $value
        );
    }

    /**
     * {@inheritDoc}
     */
    public function apply($query, $value)
    {
        return $query->where(
            $query->getModel()->qualifyColumn($this->column()),
            'LIKE',
            '%'.self::escapeLike($value).'%'
        );
    }

    /**
     * @return string
     */
    private function guessColumn(): string
    {
        return Str::underscore($this->key);
    }
}
