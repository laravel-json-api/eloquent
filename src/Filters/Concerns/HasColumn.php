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

trait HasColumn
{

    /**
     * @var string|null
     */
    private ?string $table = null;

    /**
     * @var string
     */
    private string $column;

    /**
     * @return string
     */
    public function column(): string
    {
        return $this->column;
    }

    /**
     * Force the table name when qualifying the column.
     *
     * This allows the developer to force the table that the column is qualified as.
     *
     * @param string $table
     * @return $this
     */
    public function qualifyAs(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Get the qualified column.
     *
     * @return string
     */
    protected function qualifiedColumn(): string
    {
        if ($this->table) {
            return $this->table . '.' . $this->column;
        }

        return $this->column;
    }
}
