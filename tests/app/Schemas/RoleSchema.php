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

use App\Models\Role;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Schema;

class RoleSchema extends Schema
{

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Role::class;

    /**
     * @inheritDoc
     */
    public function fields(): array
    {
        return [
            ID::make(),
            DateTime::make('createdAt')->readOnly(),
            Str::make('name'),
            DateTime::make('updatedAt')->readOnly(),
            BelongsToMany::make('users')->fields(new ApprovedPivot())->readOnly(),
            BelongsToMany::make('userAccounts', 'users')
                ->type('user-accounts')
                ->fields(new ApprovedPivot())
                ->readOnly(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
        ];
    }

    /**
     * @inheritDoc
     */
    public function pagination(): ?Paginator
    {
        return null;
    }

}
