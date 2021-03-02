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

namespace App\Schemas;

use App\Models\Post;
use LaravelJsonApi\Contracts\Pagination\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOne;
use LaravelJsonApi\Eloquent\Fields\SoftDelete;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\OnlyTrashed;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Filters\WhereTrashed;
use LaravelJsonApi\Eloquent\Filters\WithTrashed;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\SoftDeletes;

class PostSchema extends Schema
{

    use SoftDeletes;

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Post::class;

    /**
     * @var array|null
     */
    protected ?array $defaultPagination = ['number' => '1'];

    /**
     * @inheritDoc
     */
    public function fields(): iterable
    {
        return [
            ID::make(),
            BelongsTo::make('author', 'user'),
            DateTime::make('createdAt')->sortable()->readOnly(),
            HasMany::make('comments'),
            Str::make('content'),
            SoftDelete::make('deletedAt')->sortable(),
            HasOne::make('image'),
            Str::make('slug')->sortable(),
            BelongsToMany::make('tags')->fields(new ApprovedPivot()),
            Str::make('title'),
            DateTime::make('updatedAt')->sortable()->readOnly(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function filters(): iterable
    {
        return [
            WhereIn::make('id', $this->idColumn()),
            OnlyTrashed::make('onlyTrashed'),
            Where::make('slug')->singular(),
            WhereIn::make('slugs')->delimiter(','),
            WhereTrashed::make('trashed'),
            WithTrashed::make('withTrashed'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

}
