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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as EloquentBelongsToMany;
use InvalidArgumentException;
use LaravelJsonApi\Eloquent\Contracts\FillableToMany;
use LaravelJsonApi\Eloquent\Fields\Concerns\IsReadOnly;
use LogicException;
use function get_class;
use function sprintf;

class BelongsToMany extends ToMany implements FillableToMany
{

    use IsReadOnly;

    /**
     * Create a belongs-to-many relation.
     *
     * @param string $fieldName
     * @param string|null $relation
     * @return BelongsToMany
     */
    public static function make(string $fieldName, string $relation = null): BelongsToMany
    {
        return new self($fieldName, $relation);
    }

    /**
     * @var callable|array|null
     */
    private $pivot;

    /**
     * Set the values or callback to use for pivot attributes.
     *
     * @param callable|array|null $pivot
     * @return $this
     */
    public function fields($pivot): self
    {
        if (!is_array($pivot) && !is_callable($pivot)) {
            throw new InvalidArgumentException('Expecting an array or a callable value.');
        }

        $this->pivot = $pivot;

        return $this;
    }

    /**
     * @return iterable
     */
    public function filters(): iterable
    {
        foreach (parent::filters() as $filter) {
            yield $filter;
        }

        if (is_object($this->pivot) && method_exists($this->pivot, 'filters')) {
            foreach ($this->pivot->filters() as $filter) {
                yield $filter;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function fill(Model $model, array $identifiers): void
    {
        $this->sync($model, $identifiers);
    }

    /**
     * @inheritDoc
     */
    public function sync(Model $model, array $identifiers): iterable
    {
        $related = $this->findMany($identifiers);
        $relation = $this->getRelation($model);

        $relation->sync($this->formatAttachRecords(
            $model,
            $relation,
            $related
        ));

        $model->setRelation($this->relationName(), $related);

        return $related;
    }

    /**
     * @inheritDoc
     */
    public function attach(Model $model, array $identifiers): iterable
    {
        $related = $this->findMany($identifiers);
        $relation = $this->getRelation($model);

        /**
         * Note that the spec says that duplicates MUST NOT be added. The default Laravel
         * behaviour for `saveMany` is to add duplicates, therefore we need to do some
         * work to ensure that we only add the records that are not already in the relationship.
         */
        $existing = $relation
            ->whereKey($related->modelKeys())
            ->get();

        $relation->attach($this->formatAttachRecords(
            $model,
            $relation,
            $related->diff($existing)
        ));

        $model->unsetRelation($this->relationName());

        return $related;
    }

    /**
     * @inheritDoc
     */
    public function detach(Model $model, array $identifiers): iterable
    {
        $related = $this->findMany($identifiers);

        $this->getRelation($model)->detach($related);
        $model->unsetRelation($this->relationName());

        return $related;
    }

    /**
     * @param Model $model
     * @return EloquentBelongsToMany
     */
    private function getRelation(Model $model): EloquentBelongsToMany
    {
        $relation = $model->{$this->relationName()}();

        if ($relation instanceof EloquentBelongsToMany) {
            return $relation;
        }

        throw new LogicException(sprintf(
            'Expecting relation %s on model %s to be a has-many or morph-many relation.',
            $this->relationName(),
            get_class($model)
        ));
    }

    /**
     * @param Model $parent
     * @param EloquentBelongsToMany $relation
     * @param EloquentCollection $models
     * @return array
     */
    private function formatAttachRecords(
        Model $parent,
        EloquentBelongsToMany $relation,
        EloquentCollection $models
    ): array
    {
        return $models
            ->keyBy(static fn($related) => $related->{$relation->getRelatedKeyName()})
            ->map(fn($related) => $this->getPivotAttributes($parent, $related))
            ->all();
    }

    /**
     * @param Model $parent
     * @param Model $related
     * @return array
     */
    private function getPivotAttributes(Model $parent, Model $related): array
    {
        if (is_array($this->pivot)) {
            return $this->pivot;
        }

        if (is_callable($this->pivot)) {
            return ($this->pivot)($parent, $related);
        }

        return [];
    }

}
