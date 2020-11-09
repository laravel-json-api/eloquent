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

use Illuminate\Database\Eloquent\Model;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Store\ToOneBuilder;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Schema;
use LogicException;

class ToOneHydrator implements ToOneBuilder
{

    /**
     * @var SchemaContainer
     */
    private SchemaContainer $schemas;

    /**
     * @var Model
     */
    private Model $model;

    /**
     * @var BelongsTo
     */
    private BelongsTo $relation;

    /**
     * @var IncludePaths|null
     */
    private ?IncludePaths $includePaths = null;

    /**
     * ToOneHydrator constructor.
     *
     * @param SchemaContainer $schemas
     * @param Model $model
     * @param BelongsTo $relation
     */
    public function __construct(SchemaContainer $schemas, Model $model, BelongsTo $relation)
    {
        $this->schemas = $schemas;
        $this->model = $model;
        $this->relation = $relation;
    }

    /**
     * @inheritDoc
     */
    public function using(QueryParameters $query): ToOneBuilder
    {
        $this->with($query->includePaths());

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function with($includePaths): ToOneBuilder
    {
        $this->includePaths = IncludePaths::cast($includePaths);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function replace(?array $identifier): ?object
    {
        $related = $this->relation->replace($this->model, $identifier);

        if ($this->includePaths && $related) {
            $this->inverse()->loader()->using($related)->loadMissing(
                $this->includePaths
            );
        }

        return $related;
    }

    /**
     * @return Schema
     */
    private function inverse(): Schema
    {
        $schema = $this->schemas->schemaFor(
            $this->relation->inverse()
        );

        if ($schema instanceof Schema) {
            return $schema;
        }

        throw new LogicException(sprintf(
            'Expecting inverse schema for resource type %s to be an Eloquent schema.',
            $this->relation->inverse()
        ));
    }

}
