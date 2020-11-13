<?php
/*
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

namespace LaravelJsonApi\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as EloquentBelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany as EloquentHasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough as EloquentHasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany as EloquentMorphMany;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Contracts\Store\QueryManyBuilder;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Query\SortFields;
use LaravelJsonApi\Eloquent\Fields\Relations\ToMany;
use LogicException;
use function get_class;
use function sprintf;

class QueryToMany implements QueryManyBuilder
{

    /**
     * @var Model
     */
    private Model $model;

    /**
     * @var ToMany
     */
    private ToMany $relation;

    /**
     * @var array|null
     */
    private ?array $filters = null;

    /**
     * @var SortFields|null
     */
    private ?SortFields $sort = null;

    /**
     * @var IncludePaths|null
     */
    private ?IncludePaths $includePaths = null;

    /**
     * QueryToMany constructor.
     *
     * @param Model $model
     * @param ToMany $relation
     */
    public function __construct(Model $model, ToMany $relation)
    {
        $this->model = $model;
        $this->relation = $relation;
    }

    /**
     * @inheritDoc
     */
    public function using(QueryParameters $query): QueryManyBuilder
    {
        return $this
            ->with($query->includePaths())
            ->filter($query->filter())
            ->sort($query->sortFields());
    }

    /**
     * @inheritDoc
     */
    public function filter(?array $filters): QueryManyBuilder
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function sort($fields): QueryManyBuilder
    {
        $this->sort = SortFields::nullable($fields);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function with($includePaths): QueryManyBuilder
    {
        $this->includePaths = IncludePaths::nullable($includePaths);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get(): Collection
    {
        return $this->prepareQuery()->get();
    }

    /**
     * @inheritDoc
     */
    public function cursor(): LazyCollection
    {
        return $this->prepareQuery()->cursor();
    }

    /**
     * @inheritDoc
     */
    public function paginate(array $page): Page
    {
        return $this->prepareQuery()->paginate($page);
    }

    /**
     * @inheritDoc
     */
    public function getOrPaginate(?array $page): iterable
    {
        if (empty($page)) {
            return $this->get();
        }

        return $this->paginate($page);
    }

    /**
     * @return Builder
     */
    public function query(): Builder
    {
        return new Builder(
            $this->relation->schema(),
            $this->getRelation(),
            $this->relation
        );
    }

    /**
     * @return Builder
     */
    private function prepareQuery(): Builder
    {
        return $this->query()
            ->with($this->includePaths)
            ->filter($this->filters)
            ->sort($this->sort);
    }

    /**
     * @return EloquentRelation
     */
    private function getRelation(): EloquentRelation
    {
        $relation = $this->model->{$this->relation->name()}();

        if ($relation instanceof EloquentHasMany ||
            $relation instanceof EloquentBelongsToMany ||
            $relation instanceof EloquentHasManyThrough ||
            $relation instanceof EloquentMorphMany
        ) {
            return $relation;
        }

        if ($relation instanceof EloquentRelation) {
            throw new LogicException(sprintf(
                'Eloquent relation %s on model %s returned a %s relation, which is not a to-many relation.',
                $this->relation->name(),
                get_class($this->model),
                get_class($relation)
            ));
        }

        throw new LogicException(sprintf(
            'Expecting method %s on model %s to return an Eloquent relation.',
            $this->relation->name(),
            get_class($this->model)
        ));
    }

}
