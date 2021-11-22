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

namespace LaravelJsonApi\Eloquent\Contracts;

use Illuminate\Database\Eloquent\Model;

interface FillableToOne extends IsReadOnly
{

    /**
     * Does the model need to exist in the database before the relation is filled?
     *
     * @return bool
     */
    public function mustExist(): bool;

    /**
     * Fill the model with the value of the JSON:API to-one relation.
     *
     * @param Model $model
     * @param mixed $identifier
     */
    public function fill(Model $model, ?array $identifier): void;

    /**
     * Replace the relationship.
     *
     * @param Model $model
     * @param array|null $identifier
     * @return Model|null
     */
    public function associate(Model $model, ?array $identifier): ?Model;
}
