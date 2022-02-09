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

namespace LaravelJsonApi\Eloquent\QueryBuilder\Applicators;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use LaravelJsonApi\Core\Query\SortField;
use LaravelJsonApi\Core\Query\SortFields;
use LaravelJsonApi\Eloquent\Contracts\Sortable;
use LaravelJsonApi\Eloquent\Schema;
use LogicException;
use function get_class;
use function sprintf;

class SortApplicator
{

    /**
     * @var Schema
     */
    private Schema $schema;

    /**
     * @var SortFields|null
     */
    private ?SortFields $fields = null;

    /**
     * Make a new sort applicator.
     *
     * @param Schema $schema
     * @return static
     */
    public static function make(Schema $schema): self
    {
        return new self($schema);
    }

    /**
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * Apply the JSON:API sort fields to the query builder.
     *
     * @param Builder|Relation $query
     * @param SortFields|SortField|array|string|null $fields
     * @return $this
     */
    public function apply($query, $fields): self
    {
        $fields = $this->fields = SortFields::nullable($fields);

        if (null === $fields || $fields->isEmpty()) {
            return $this;
        }

        /** @var SortField $sort */
        foreach ($fields as $sort) {
            if ('id' === $sort->name()) {
                $this->orderByResourceId($query, $sort);
                continue;
            }

            $field = $this->schema->sortField($sort->name());

            if ($field instanceof Sortable) {
                $field->sort($query, $sort->getDirection());
                continue;
            }

            throw new LogicException(sprintf(
                'Expecting sort field %s on schema %s to implement the Eloquent sortable interface.',
                $sort->name(),
                get_class($this->schema),
            ));
        }

        return $this;
    }

    /**
     * Get the applied sort fields.
     *
     * @return SortFields|null
     */
    public function applied(): ?SortFields
    {
        return $this->fields;
    }

    /**
     * @param Builder|Relation $query
     * @param SortField $sort
     * @return void
     */
    private function orderByResourceId($query, SortField $sort): void
    {
        $idColumn = $query->getModel()->qualifyColumn(
            $this->schema->idColumn()
        );

        $query->orderBy($idColumn, $sort->getDirection());
    }
}
