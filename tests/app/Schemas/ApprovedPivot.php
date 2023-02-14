<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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

namespace App\Schemas;

use Illuminate\Support\Facades\Auth;
use LaravelJsonApi\Eloquent\Filters\WherePivot;
use function boolval;
use function optional;

class ApprovedPivot
{

    /**
     * Get the pivot attributes.
     *
     * @param $parent
     * @param $related
     * @return array
     */
    public function __invoke($parent, $related): array
    {
        return [
            'approved' => boolval(optional(Auth::user())->admin),
        ];
    }

    /**
     * Get filters for the pivot table.
     *
     * @return array
     */
    public function filters(): array
    {
        return [
            WherePivot::make('approved')->asBoolean(),
        ];
    }
}
