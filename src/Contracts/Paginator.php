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

namespace LaravelJsonApi\Eloquent\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Contracts\Pagination\Paginator as BasePaginator;

interface Paginator extends BasePaginator
{

    /**
     * Set the key column.
     *
     * The key column must be used to ensure the paginator
     * has a deterministic order. Typically this will be set to
     * either the model's key name or the model's route key name.
     *
     * @param string $column
     * @return $this
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/313
     */
    public function withKeyName(string $column): self;

    /**
     * Set the columns to select when querying the database.
     *
     * @param string|array $columns
     * @return $this
     */
    public function withColumns($columns): self;

    /**
     * Execute the query and return a JSON API page.
     *
     * @param Builder|Relation $query
     * @param array $page
     * @return Page
     */
    public function paginate($query, array $page): Page;
}
