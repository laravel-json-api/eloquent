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

namespace LaravelJsonApi\Eloquent\Filters;

use LaravelJsonApi\Core\Support\Str;
use LaravelJsonApi\Eloquent\Contracts\Filter;

class Where implements Filter
{

    use Concerns\DeserializesValue;
    use Concerns\IsSingular;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var string
     */
    private string $column;

    /**
     * @var string
     */
    private string $operator;

    /**
     * Create a new filter.
     *
     * @param string $name
     * @param string|null $column
     * @return static
     */
    public static function make(string $name, string $column = null): self
    {
        return new static($name, $column);
    }

    /**
     * Where constructor.
     *
     * @param string $name
     * @param string|null $column
     */
    public function __construct(string $name, string $column = null)
    {
        $this->name = $name;
        $this->column = $column ?: $this->guessColumn();
        $this->operator = '=';
    }

    /**
     * @return $this
     */
    public function eq(): self
    {
        return $this->using('=');
    }

    /**
     * @return $this
     */
    public function gt(): self
    {
        return $this->using('>');
    }

    /**
     * @return $this
     */
    public function gte(): self
    {
        return $this->using('>=');
    }

    /**
     * @return $this
     */
    public function lt(): self
    {
        return $this->using('<');
    }

    /**
     * @return $this
     */
    public function lte(): self
    {
        return $this->using('<=');
    }

    /**
     * Use the provided operator for the filter.
     *
     * @param string $operator
     * @return $this
     */
    public function using(string $operator): self
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function key(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function apply($query, $value)
    {
        return $query->where(
            $query->getModel()->qualifyColumn($this->column()),
            $this->operator(),
            $this->deserialize($value)
        );
    }

    /**
     * @return string
     */
    protected function column(): string
    {
        return $this->column;
    }

    /**
     * @return string
     */
    protected function operator(): string
    {
        return $this->operator;
    }

    /**
     * @return string
     */
    private function guessColumn(): string
    {
        return Str::underscore($this->name);
    }
}
