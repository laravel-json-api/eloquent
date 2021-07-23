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

use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;
use LaravelJsonApi\Eloquent\Filters\Concerns\DeserializesValue;

class WhereDoesntHave implements Filter
{
    use DeserializesValue;
    use IsSingular;
    use Having;

    /**
     * Apply the filter to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply($query, $value)
    {
        $deserializedValues = $this->deserialize($value);

        $relation = $this->schema->relationship($this->relationName());

        $availableFilters = collect($relation->schema()->filters())->merge($relation->filters());

        $keyedFilters = collect($availableFilters)->keyBy(function ($filter) {
            return $filter->key();
        })->all();

        return $query->whereDoesntHave($this->relationName(), function ($query) use ($deserializedValues, $keyedFilters) {
            foreach ($deserializedValues as $key => $value) {
                if (isset($keyedFilters[$key])) {
                    $keyedFilters[$key]->apply($query, $value);
                }
            }
        });
    }
}
