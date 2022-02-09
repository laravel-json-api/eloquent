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

use LaravelJsonApi\Eloquent\Contracts\SortField;
use LaravelJsonApi\Eloquent\Schema;

class SortCountable implements SortField
{

    /**
     * @var Schema
     */
    private Schema $schema;

    /**
     * @var string
     */
    private string $fieldName;

    /**
     * @var string
     */
    private string $key;

    /**
     * Create a new sortable field.
     *
     * @param Schema $schema
     * @param string $fieldName
     * @param string|null $key
     * @return static
     */
    public static function make(Schema $schema, string $fieldName, string $key = null): self
    {
        return new self($schema, $fieldName, $key);
    }

    /**
     * SortCount constructor.
     *
     * @param Schema $schema
     * @param string $fieldName
     * @param string|null $key
     */
    public function __construct(Schema $schema, string $fieldName, string $key = null)
    {
        $this->schema = $schema;
        $this->fieldName = $fieldName;
        $this->key = $key ?? $fieldName;
    }

    /**
     * @inheritDoc
     */
    public function sortField(): string
    {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    public function sort($query, string $direction = 'asc')
    {
        $relation = $this->schema->toMany($this->fieldName);

        return $query
            ->withCount($relation->withCountName())
            ->orderBy($relation->keyForCount(), $direction);
    }

}
