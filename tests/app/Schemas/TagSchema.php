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

use App\Models\Tag;
use LaravelJsonApi\Contracts\Pagination\Paginator;
use LaravelJsonApi\Core\Schema\Attributes\Model;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Schema;

#[Model(Tag::class)]
class TagSchema extends Schema
{
    /**
     * @inheritDoc
     */
    public function fields(): iterable
    {
        return [
            ID::make(),
            DateTime::make('createdAt')->readOnly(),
            Str::make('name'),
            BelongsToMany::make('posts')
                ->fields(new ApprovedPivot())
                ->canCount(),
            DateTime::make('updatedAt')->readOnly(),
            BelongsToMany::make('videos')
                ->fields(new ApprovedPivot())
                ->canCount(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function filters(): iterable
    {
        return [
            WhereIdIn::make($this),
            Where::make('name'),
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
