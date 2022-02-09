<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

use Illuminate\Database\Eloquent\Model;
use LaravelJsonApi\Contracts\Store\ToOneBuilder;
use LaravelJsonApi\Core\Query\Custom\ExtendedQueryParameters;
use LaravelJsonApi\Core\Support\Str;
use LaravelJsonApi\Eloquent\Contracts\FillableToOne;
use LaravelJsonApi\Eloquent\Fields\Relations\MorphTo;
use LaravelJsonApi\Eloquent\Fields\Relations\ToOne;
use LaravelJsonApi\Eloquent\HasQueryParameters;
use UnexpectedValueException;

class ToOneHydrator implements ToOneBuilder
{

    use HasQueryParameters;

    /**
     * @var Model
     */
    private Model $model;

    /**
     * @var ToOne|FillableToOne
     */
    private ToOne $relation;

    /**
     * ToOneHydrator constructor.
     *
     * @param Model $model
     * @param ToOne $relation
     */
    public function __construct(Model $model, ToOne $relation)
    {
        if (!$relation instanceof FillableToOne) {
            throw new UnexpectedValueException(sprintf(
                'Relation %s cannot be hydrated.',
                Str::dasherize(class_basename($relation))
            ));
        }

        $this->model = $model;
        $this->relation = $relation;
        $this->queryParameters = new ExtendedQueryParameters();
    }

    /**
     * @inheritDoc
     */
    public function associate(?array $identifier): ?object
    {
        $related = $this->model->getConnection()->transaction(
            fn() => $this->relation->associate($this->model, $identifier)
        );

        return $this->relation->parse(
            $this->prepareResult($related)
        );
    }

    /**
     * Prepare the related model.
     *
     * We always do eager loading, in case any default eager load paths
     * have been set on the schema.
     *
     * @param Model|null $related
     * @return Model|null
     */
    private function prepareResult(?Model $related): ?Model
    {
        if (is_null($related)) {
            return null;
        }

        $parameters = $this->queryParameters;

        if ($this->relation instanceof MorphTo) {
            $schema = $this->relation->schemaFor($related);
            $parameters = $parameters->forSchema($schema);
        } else {
            $schema = $this->relation->schema();
        }

        $schema
            ->loaderFor($related)
            ->loadMissing($parameters->includePaths())
            ->loadCount($parameters->countable());

        return $related;
    }

}
