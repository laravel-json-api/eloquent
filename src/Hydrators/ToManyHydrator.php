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

namespace LaravelJsonApi\Eloquent\Hydrators;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Contracts\Store\ToManyBuilder;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Support\Str;
use LaravelJsonApi\Eloquent\Contracts\FillableToMany;
use LaravelJsonApi\Eloquent\Fields\Relations\ToMany;
use UnexpectedValueException;

class ToManyHydrator implements ToManyBuilder
{

    /**
     * @var Model
     */
    private Model $model;

    /**
     * @var ToMany|FillableToMany
     */
    private ToMany $relation;

    /**
     * @var IncludePaths|null
     */
    private ?IncludePaths $includePaths = null;

    /**
     * ToManyHydrator constructor.
     *
     * @param Model $model
     * @param ToMany $relation
     */
    public function __construct(Model $model, ToMany $relation)
    {
        if (!$relation instanceof FillableToMany) {
            throw new UnexpectedValueException(sprintf(
                'Relation %s cannot be hydrated.',
                Str::dasherize(class_basename($relation))
            ));
        }

        $this->model = $model;
        $this->relation = $relation;
    }

    /**
     * @inheritDoc
     */
    public function using(QueryParameters $query): ToManyBuilder
    {
        $this->with($query);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function with($includePaths): ToManyBuilder
    {
        $this->includePaths = IncludePaths::nullable($includePaths);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function replace(array $identifiers): iterable
    {
        $related = $this->model->getConnection()->transaction(
            fn() => $this->relation->replace($this->model, $identifiers)
        );

        return $this->prepareResult($related);
    }

    /**
     * @inheritDoc
     */
    public function add(array $identifiers): iterable
    {
        $related = $this->model->getConnection()->transaction(
            fn() => $this->relation->add($this->model, $identifiers)
        );

        return $this->prepareResult($related);
    }

    /**
     * @inheritDoc
     */
    public function remove(array $identifiers): iterable
    {
        $related = $this->model->getConnection()->transaction(
            fn() => $this->relation->remove($this->model, $identifiers)
        );

        return $this->prepareResult($related);
    }

    /**
     * @param EloquentCollection $related
     * @return EloquentCollection
     */
    private function prepareResult(EloquentCollection $related): EloquentCollection
    {
        if ($this->includePaths && $related->isNotEmpty()) {
            $this->relation->schema()->loader()->forModels($related)->loadMissing(
                $this->includePaths
            );
        }

        return $related;
    }

}
