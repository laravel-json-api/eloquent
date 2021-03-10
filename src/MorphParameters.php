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

namespace LaravelJsonApi\Eloquent;

use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Query\FieldSets;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Query\RelationshipPath;
use LaravelJsonApi\Core\Query\SortField;
use LaravelJsonApi\Core\Query\SortFields;

class MorphParameters implements QueryParameters
{

    /**
     * @var Schema
     */
    private Schema $schema;

    /**
     * @var QueryParameters
     */
    private QueryParameters $parameters;

    /**
     * MorphParameters constructor.
     *
     * @param Schema $schema
     * @param QueryParameters $parameters
     */
    public function __construct(Schema $schema, QueryParameters $parameters)
    {
        $this->schema = $schema;
        $this->parameters = $parameters;
    }

    /**
     * @inheritDoc
     */
    public function includePaths(): ?IncludePaths
    {
        if ($paths = $this->parameters->includePaths()) {
            return new IncludePaths(...collect($paths->all())
                ->filter(fn($path) => $this->isRelationshipPath($path))
            );
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function sparseFieldSets(): ?FieldSets
    {
        return $this->parameters->sparseFieldSets();
    }

    /**
     * @inheritDoc
     */
    public function sortFields(): ?SortFields
    {
        if ($fields = $this->parameters->sortFields()) {
            return new SortFields(...collect($fields->all())
                ->filter(fn($field) => $this->isSortField($field))
            );
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function page(): ?array
    {
        return $this->parameters->page();
    }

    /**
     * @inheritDoc
     */
    public function filter(): ?array
    {
        if (is_array($filters = $this->parameters->filter())) {
            return collect($filters)
                ->filter(fn($value, $key) => $this->isFilter($key))
                ->all();
        }

        return null;
    }

    /**
     * @param RelationshipPath $path
     * @return bool
     */
    private function isRelationshipPath(RelationshipPath $path): bool
    {
        if (!$this->schema->isRelationship($path->first())) {
            return false;
        }

        return $this->schema->relationship($path->first())->isIncludePath();
    }

    /**
     * @param SortField $field
     * @return bool
     */
    private function isSortField(SortField $field): bool
    {
        foreach ($this->schema->sortable() as $name) {
            if ($name === $field->name()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $name
     * @return bool
     */
    private function isFilter(string $name): bool
    {
        foreach ($this->schema->filters() as $filter) {
            if ($filter->key() === $name) {
                return true;
            }
        }

        return false;
    }

}
