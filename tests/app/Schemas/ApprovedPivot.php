<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
