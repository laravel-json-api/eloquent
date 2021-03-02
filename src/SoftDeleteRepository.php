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

namespace LaravelJsonApi\Eloquent;

use Illuminate\Database\Eloquent\Model;
use LaravelJsonApi\Contracts\Store\ResourceBuilder;
use LaravelJsonApi\Eloquent\Hydrators\SoftDeleteHydrator;

class SoftDeleteRepository extends Repository
{

    /**
     * @inheritDoc
     */
    public function create(): ResourceBuilder
    {
        return new SoftDeleteHydrator(
            $this->schema,
            $this->model->newInstance(),
        );
    }

    /**
     * @inheritDoc
     */
    public function update($modelOrResourceId): ResourceBuilder
    {
        return new SoftDeleteHydrator(
            $this->schema,
            $this->retrieve($modelOrResourceId),
        );
    }

    /**
     * @param string $resourceId
     * @return JsonApiBuilder
     */
    protected function findQuery(string $resourceId): JsonApiBuilder
    {
        return parent::findQuery($resourceId)->withTrashed();
    }

    /**
     * @param array $resourceIds
     * @return JsonApiBuilder
     */
    protected function findManyQuery(array $resourceIds): JsonApiBuilder
    {
        return parent::findManyQuery($resourceIds)->withTrashed();
    }

    /**
     * @inheritDoc
     */
    protected function destroy(Model $model): bool
    {
        return (bool) $model->forceDelete();
    }
}
