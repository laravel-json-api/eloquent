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

use App\Models\Video;
use LaravelJsonApi\Contracts\Pagination\Paginator;
use LaravelJsonApi\Core\Schema\Attributes\Model;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Schema;

#[Model(Video::class)]
class VideoSchema extends Schema
{
    /**
     * @inheritDoc
     */
    public function fields(): iterable
    {
        return [
            ID::make()->uuid()->sortable(),
            DateTime::make('createdAt')->sortable()->readOnly(),
            HasMany::make('comments')->canCount(),
            Str::make('slug'),
            BelongsToMany::make('tags')
                ->fields(new ApprovedPivot())
                ->canCount(),
            Str::make('title'),
            DateTime::make('updatedAt')->sortable()->readOnly(),
            Str::make('url'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function filters(): iterable
    {
        return [
            WhereIdIn::make($this),
            Where::make('slug')->singular(),
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
