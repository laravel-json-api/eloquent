<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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

use App\Models\Comment;
use LaravelJsonApi\Contracts\Pagination\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\MorphTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Pagination\StandardPaginator;
use LaravelJsonApi\Eloquent\Schema;

class CommentSchema extends Schema
{

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Comment::class;

    /**
     * @inheritDoc
     */
    public function fields(): iterable
    {
        return [
            ID::make(),
            DateTime::make('createdAt')->readOnly(),
            MorphTo::make('commentable'),
            Str::make('content'),
            DateTime::make('updatedAt')->readOnly(),
            BelongsTo::make('user'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function filters(): iterable
    {
        return [
            WhereIn::make('id', $this->idColumn()),
        ];
    }

    /**
     * @inheritDoc
     */
    public function pagination(): ?Paginator
    {
        return StandardPaginator::make();
    }

}
