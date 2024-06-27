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

use App\Models\Post;
use LaravelJsonApi\Contracts\Pagination\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsToMany;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOne;
use LaravelJsonApi\Eloquent\Fields\Relations\MorphToMany;
use LaravelJsonApi\Eloquent\Fields\SoftDelete;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\OnlyTrashed;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereAll;
use LaravelJsonApi\Eloquent\Filters\WhereAny;
use LaravelJsonApi\Eloquent\Filters\WhereDoesntHave;
use LaravelJsonApi\Eloquent\Filters\WhereHas;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Filters\WhereIn;
use LaravelJsonApi\Eloquent\Filters\WhereNotNull;
use LaravelJsonApi\Eloquent\Filters\WhereNull;
use LaravelJsonApi\Eloquent\Filters\WithTrashed;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\SoftDeletes;
use LaravelJsonApi\Eloquent\Sorting\SortCountable;

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
            HasMany::make('comments')->canCount(),
            Str::make('content'),
            SoftDelete::make('deletedAt')->sortable(),
            HasOne::make('image'),
            MorphToMany::make('media', [
                BelongsToMany::make('images'),
                BelongsToMany::make('videos'),
            ])->canCount(),
            DateTime::make('publishedAt'),
            Str::make('slug')->sortable(),
            BelongsToMany::make('tags')
                ->fields(new ApprovedPivot())
                ->canCount(),
            Str::make('title')->sortable(),
            DateTime::make('updatedAt')->sortable()->readOnly(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function filters(): iterable
    {
        return [
            WhereIdIn::make($this),
            WhereNull::make('draft', 'published_at'),
            WhereDoesntHave::make($this, 'tags', 'notTags'),
            WhereNotNull::make('published', 'published_at'),
            WhereHas::make($this, 'tags'),
            OnlyTrashed::make('trashed'),
            Where::make('slug')->singular(),
            WhereIn::make('slugs')->delimiter(','),
            WithTrashed::make('withTrashed'),
            WhereAll::make('all', ['title','content'])->withColumn('slug')->using('like'),
            WhereAny::make('any', ['title','content'])->withColumn('slug')->using('like'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function sortables(): iterable
    {
        return [
            SortCountable::make($this, 'comments'),
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
