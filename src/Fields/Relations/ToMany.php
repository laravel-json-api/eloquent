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

namespace LaravelJsonApi\Eloquent\Fields\Relations;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Core\Support\Str;
use LaravelJsonApi\Eloquent\Contracts\Countable as CountableContract;
use LaravelJsonApi\Eloquent\Contracts\Proxy;
use LaravelJsonApi\Eloquent\Fields\Concerns\Countable;

abstract class ToMany extends Relation implements CountableContract
{

    use Countable;

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
     * Parse models for the relationship.
     *
     * @param mixed $models
     * @return iterable
     */
    public function parse($models): iterable
    {
        return $this->schema()->parser()->parseMany(
            $models
        );
    }

    /**
     * Parse a page for the relationship.
     *
     * @param Page $page
     * @return Page
     */
    public function parsePage(Page $page): Page
    {
        return $this->schema()->parser()->parsePage(
            $page
        );
    }

    /**
     * Find many models using the provided JSON:API identifiers.
     *
     * @param array $identifiers
     * @return EloquentCollection
     */
    protected function findMany(array $identifiers): EloquentCollection
    {
        $items = collect($identifiers)->groupBy('type')->map(function(Collection $ids, $type) {
            return collect($this->findManyByType($type, $ids))
                ->map(fn($model) => ($model instanceof Proxy) ? $model->toBase() : $model);
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

    /**
     * @param string $type
     * @param Collection $identifiers
     * @return iterable
     */
    private function findManyByType(string $type, Collection $identifiers): iterable
    {
        $this->assertInverseType($type);

        return $this->schemas()->schemaFor($type)->repository()->findMany(
            $identifiers->pluck('id')->unique()->all()
        );
    }
}
