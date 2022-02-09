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

use Illuminate\Database\Eloquent\Model;
use LaravelJsonApi\Contracts\Pagination\Page;

interface Parser
{

    /**
     * Parse a single model.
     *
     * @param Model $model
     * @return object
     */
    public function parseOne(Model $model): object;

    /**
     * Parse a single model that may be null.
     *
     * @param Model|null $model
     * @return object|null
     */
    public function parseNullable(?Model $model): ?object;

    /**
     * Parse many models.
     *
     * @param $models
     * @return iterable
     */
    public function parseMany($models): iterable;

    /**
     * Parse a page of models.
     *
     * @param Page $page
     * @return Page
     */
    public function parsePage(Page $page): Page;
}
