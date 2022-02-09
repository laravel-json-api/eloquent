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
use Illuminate\Database\Eloquent\Model;

interface Driver
{

    /**
     * Return a new query instance for querying specific resources.
     *
     * @return Builder
     */
    public function query(): Builder;

    /**
     * Return a new query instance for querying all resources.
     *
     * @return Builder
     */
    public function queryAll(): Builder;

    /**
     * Create a new model instance.
     *
     * @return Model
     */
    public function newInstance(): Model;

    /**
     * Persist the model to the database.
     *
     * @param Model $model
     * @return bool
     *      whether the storage operation was successful.
     */
    public function persist(Model $model): bool;

    /**
     * Remove the model from the database.
     *
     * @param Model $model
     * @return bool
     *      whether the removal operation was successful.
     */
    public function destroy(Model $model): bool;
}
