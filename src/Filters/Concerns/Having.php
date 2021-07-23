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

namespace LaravelJsonApi\Eloquent\Filters\Concerns;

use LaravelJsonApi\Contracts\Schema\Schema;

trait Having
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
    private string|null $key;

    /**
     * Create a new filter.
     *
     * @param Schema $schema
     * @param string|null $fieldName
     * @param string|null $key
     * @return WhereHas
     */
    public static function make(Schema $schema, string $fieldName = null, string $key = null): self
    {
        return new static($schema, $fieldName, $key);
    }

    /**
     * WhereDoesntHave constructor.
     *
     * @param Schema $schema
     * @param string|null $fieldName
     * @param string|null $key
     */
    public function __construct(Schema $schema, string $fieldName, string|null $key)
    {
        $this->schema = $schema;
        $this->fieldName = $fieldName;
        $this->key = $key;

        if (! $this->schema->isRelationship($fieldName)) {
            throw new \LogicException("Relationship with name $fieldName not defined in ". get_class($schema)." schema.");
        }
    }

    /**
     * Get the key for the filter.
     *
     * @return string
     */
    public function key(): string
    {
        return $this->key ?? $this->fieldName;
    }

    /**
     * @return string
     */
    protected function relationName(): string
    {
        return $this->schema->relationship($this->fieldName)->relationName();
    }
}
