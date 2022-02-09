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

use LaravelJsonApi\Core\Support\Str;
use LaravelJsonApi\Eloquent\Contracts\SortField;

class SortColumn implements SortField
{

    /**
     * @var string
     */
    private string $fieldName;

    /**
     * @var string
     */
    private string $column;

    /**
     * Create a new sortable field.
     *
     * @param string $fieldName
     * @param string|null $column
     * @return static
     */
    public static function make(string $fieldName, string $column = null): self
    {
        return new self($fieldName, $column);
    }

    /**
     * SortColumn constructor.
     *
     * @param string $fieldName
     * @param string|null $column
     */
    public function __construct(string $fieldName, string $column = null)
    {
        $this->fieldName = $fieldName;
        $this->column = $column ?? $this->guessColumn();
    }

    /**
     * @inheritDoc
     */
    public function sortField(): string
    {
        return $this->fieldName;
    }

    /**
     * @inheritDoc
     */
    public function sort($query, string $direction = 'asc')
    {
        return $query->orderBy(
            $query->getModel()->qualifyColumn($this->column),
            $direction,
        );
    }

    /**
     * @return string
     */
    private function guessColumn(): string
    {
        return Str::underscore($this->fieldName);
    }
}
