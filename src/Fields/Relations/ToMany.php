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

namespace LaravelJsonApi\Eloquent\Fields\Relations;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use LaravelJsonApi\Core\Support\Str;

abstract class ToMany extends Relation
{

    /**
     * Should the relationship use the schema's default pagination?
     *
     * @var bool
     */
    private bool $defaultPagination = true;

    /**
     * @inheritDoc
     */
    public function toOne(): bool
    {
        return false;
    }

    /**
     * Mark the relation as not using default-pagination.
     *
     * @return $this
     */
    public function withoutDefaultPagination(): self
    {
        $this->defaultPagination = false;

        return $this;
    }

    /**
     * Get the default pagination for the relation.
     *
     * @return array|null
     */
    public function defaultPagination(): ?array
    {
        if (true === $this->defaultPagination) {
            return $this->schema()->defaultPagination();
        }

        return null;
    }

    /**
     * @param array $identifiers
     * @return EloquentCollection
     */
    protected function findMany(array $identifiers): EloquentCollection
    {
        $schemas = $this->schemas();

        $items = collect($identifiers)->groupBy('type')->map(function(Collection $ids, $type) use ($schemas) {
            $this->assertInverseType($type);

            return collect($schemas->schemaFor($type)->repository()->findMany(
                $ids->pluck('id')->unique()->all()
            ));
        })->flatten();

        return new EloquentCollection($items);
    }

    /**
     * @inheritDoc
     */
    protected function guessInverse(): string
    {
        return Str::dasherize($this->relationName());
    }
}
