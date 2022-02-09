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

namespace LaravelJsonApi\Eloquent\Filters;

use Closure;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\DeserializesToArray;
use LaravelJsonApi\Eloquent\Filters\Concerns\HasRelation;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;
use LaravelJsonApi\Eloquent\QueryBuilder\Applicators\FilterApplicator;
use LaravelJsonApi\Eloquent\Schema;

class WhereHas implements Filter
{
    use DeserializesToArray;
    use HasRelation;
    use IsSingular;

    /**
     * Create a new filter.
     *
     * @param Schema $schema
     * @param string $fieldName
     * @param string|null $key
     * @return static
     */
    public static function make(Schema $schema, string $fieldName, string $key = null)
    {
        return new static($schema, $fieldName, $key);
    }

    /**
     * WhereHas constructor.
     *
     * @param Schema $schema
     * @param string $fieldName
     * @param string|null $key
     */
    public function __construct(Schema $schema, string $fieldName, string $key = null)
    {
        $this->schema = $schema;
        $this->fieldName = $fieldName;
        $this->key = $key;
    }

    /**
     * @inheritDoc
     */
    public function apply($query, $value)
    {
        return $query->whereHas(
            $this->relationName(),
            $this->callback($value),
        );
    }

    /**
     * Get the relation query callback.
     *
     * @param mixed $value
     * @return Closure
     */
    protected function callback($value): Closure
    {
        return function($query) use ($value) {
            $relation = $this->relation();
            FilterApplicator::make($relation->schema(), $relation)
                ->apply($query, $this->toArray($value));
        };
    }
}
