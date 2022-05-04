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

use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\DeserializesValue;

class WhereSearch implements Filter
{
    use DeserializesValue;
    use Concerns\IsSingular;

    /**
     * @var string
     */
    private string $name;

    /**
     * Create a new filter.
     *
     * @param string $name
     * @param string|null $column
     * @return static
     */
    public static function make(string $name, string $columns): self
    {
        return new static($name, $columns);
    }

    /**
     * WhereSearch constructor.
     *
     * @param string $name
     * @param string $column
     */
    public function __construct(string $name, string $columns)
    {
        $this->name = $name;
        $this->column = $columns;
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
        $searchableColumns = explode('|', $this->column);
        $value = $this->deserialize($value);

        foreach ($searchableColumns as $searchableColumn) {
            $query->orWhere($searchableColumn, 'LIKE', '%'.$value.'%');
        }

        return $query;
    }
}
