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

namespace LaravelJsonApi\Eloquent\Filters;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WherePivotNotIn extends WhereIn
{

    /**
     * @inheritDoc
     */
    public function apply($query, $value)
    {
        if ($query instanceof BelongsToMany) {
            return $query->wherePivotNotIn(
                $this->column(),
                $this->deserialize($value)
            );
        }

        /**
         * If we haven't got a belongs-to-many, then we'll use a standard `whereNotIn()` and
         * hope that our column is qualified enough to be unique in the query so the
         * database knows we mean the pivot table.
         */
        return $query->whereNotIn(
            $this->qualifiedColumn(),
            $this->deserialize($value)
        );
    }

}
