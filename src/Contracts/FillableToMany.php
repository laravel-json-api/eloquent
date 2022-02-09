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

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use LaravelJsonApi\Eloquent\Polymorphism\MorphMany;

interface FillableToMany extends IsReadOnly
{

    /**
     * Fill the model with the value of the JSON:API to-many relation.
     *
     * @param Model $model
     * @param array $identifiers
     */
    public function fill(Model $model, array $identifiers): void;

    /**
     * Completely replace every member of the relationship with the specified members.
     *
     * @param Model $model
     * @param array $identifiers
     * @return EloquentCollection|MorphMany
     */
    public function sync(Model $model, array $identifiers): iterable;

    /**
     * Add the specified members to the relationship unless they are already present.
     *
     * @param Model $model
     * @param array $identifiers
     * @return EloquentCollection|MorphMany
     */
    public function attach(Model $model, array $identifiers): iterable;

    /**
     * Remove the specified members from the relationship.
     *
     * @param Model $model
     * @param array $identifiers
     * @return EloquentCollection|MorphMany
     */
    public function detach(Model $model, array $identifiers): iterable;
}
