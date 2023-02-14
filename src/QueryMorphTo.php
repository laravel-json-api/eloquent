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

namespace LaravelJsonApi\Eloquent;

use Illuminate\Database\Eloquent\Model;
use LaravelJsonApi\Contracts\Schema\Filter;
use LaravelJsonApi\Contracts\Store\QueryOneBuilder;
use LaravelJsonApi\Core\Query\Custom\ExtendedQueryParameters;
use LaravelJsonApi\Eloquent\Fields\Relations\MorphTo;
use function is_null;

class QueryMorphTo implements QueryOneBuilder
{

    use HasQueryParameters;

    /**
     * @var Model
     */
    private Model $model;

    /**
     * @var MorphTo
     */
    private MorphTo $relation;

    /**
     * QueryMorphOne constructor.
     *
     * @param Model $model
     * @param MorphTo $relation
     */
    public function __construct(Model $model, MorphTo $relation)
    {
        $this->model = $model;
        $this->relation = $relation;
        $this->queryParameters = new ExtendedQueryParameters();
    }

    /**
     * @inheritDoc
     */
    public function filter(?array $filters): QueryOneBuilder
    {
        $this->queryParameters->setFilters($filters);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function first(): ?object
    {
        /** @var Model|null $related */
        $related = $this->model->{$this->relation->relationName()};
        $filters = $this->queryParameters->filter();

        /**
         * If there are no filters, we can just return the related
         * model - loading any missing relations.
         */
        if (is_null($related) || empty($filters)) {
            return $this->relation->parse(
                $this->prepareResult($related)
            );
        }

        $schema = $this->relation->schemaFor($related);

        $expected = collect($schema->filters())
            ->map(fn(Filter $filter) => $filter->key())
            ->values();

        /**
         * If there are any filters that are not valid for this schema,
         * then we know the related model cannot match the filters. So
         * in this scenario, we return `null`.
         */
        if (collect($filters)->keys()->diff($expected)->isNotEmpty()) {
            return null;
        }

        /**
         * Otherwise we need to re-query this specific model to see if
         * it matches our filters or not.
         */
        $result = $schema
            ->newQuery($related->newQuery())
            ->whereKey($related->getKey())
            ->filter($filters)
            ->first();

        return $this->relation->parse(
            $this->prepareResult($result)
        );
    }

    /**
     * Prepare the model to be returned as the result of the query.
     *
     * @param Model|null $related
     * @return Model|null
     */
    private function prepareResult(?Model $related): ?Model
    {
        if ($related) {
            $schema = $this->relation->schemaFor($related);
            $parameters = $this->queryParameters->forSchema($schema);

            $schema
                ->loaderFor($related)
                ->loadMissing($parameters->includePaths())
                ->loadCount($parameters->countable());
        }

        return $related;
    }

}
