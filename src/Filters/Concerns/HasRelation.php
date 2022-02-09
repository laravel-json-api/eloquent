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

namespace LaravelJsonApi\Eloquent\Filters\Concerns;

use LaravelJsonApi\Eloquent\Fields\Relations\Relation;
use LaravelJsonApi\Eloquent\Schema;

trait HasRelation
{
    /**
     * @var Schema
     */
    private Schema $schema;

    /**
     * The JSON:API relationship field name.
     *
     * @var string
     */
    private string $fieldName;

    /**
     * The JSON:API filter name.
     *
     * @var string|null
     */
    private ?string $key;

    /**
     * @var Relation|null
     */
    private ?Relation $relation = null;

    /**
     * Get the JSON:API key for the filter.
     *
     * @return string
     */
    public function key(): string
    {
        return $this->key ?? $this->fieldName();
    }

    /**
     * Get the JSON:API relationship field name.
     *
     * @return string
     */
    public function fieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * Get the Eloquent relation name.
     *
     * @return string
     */
    public function relationName(): string
    {
        return $this->relation()->relationName();
    }

    /**
     * Get the relationship used for this filter.
     *
     * @return Relation
     */
    protected function relation(): Relation
    {
        if ($this->relation) {
            return $this->relation;
        }

        return $this->relation = $this->schema->relationship($this->fieldName);
    }
}
