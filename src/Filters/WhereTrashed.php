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

namespace LaravelJsonApi\Eloquent\Filters;

use Illuminate\Database\Eloquent\SoftDeletingScope;
use LogicException;

class WhereTrashed extends WithTrashed
{

    /**
     * @inheritDoc
     */
    public function apply($query, $value)
    {
        $model = $query->getModel();

        if (!method_exists($model, 'getQualifiedDeletedAtColumn')) {
            throw new LogicException('Expecting a model that has the soft deletes trait.');
        }

        $value = $this->deserialize($value);
        $column = $model->getQualifiedDeletedAtColumn();

        if (true === $value) {
            return $query
                ->withoutGlobalScope(SoftDeletingScope::class)
                ->whereNotNull($column);
        }

        return $query
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->whereNull($column);
    }

}
