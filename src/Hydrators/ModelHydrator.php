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

namespace LaravelJsonApi\Eloquent\Hydrators;

use Illuminate\Database\Eloquent\Model;
use LaravelJsonApi\Contracts\Schema\Attribute;
use LaravelJsonApi\Contracts\Schema\Field;
use LaravelJsonApi\Contracts\Store\ResourceBuilder;
use LaravelJsonApi\Core\Query\QueryParameters;
use LaravelJsonApi\Eloquent\Contracts\Fillable;
use LaravelJsonApi\Eloquent\Contracts\FillableToMany;
use LaravelJsonApi\Eloquent\Contracts\FillableToOne;
use LaravelJsonApi\Eloquent\Fields\Relations\Relation;
use LaravelJsonApi\Eloquent\HasQueryParameters;
use LaravelJsonApi\Eloquent\Schema;
use LogicException;
use RuntimeException;
use function sprintf;

class ModelHydrator implements ResourceBuilder
{

    use HasQueryParameters;

    /**
     * @var Schema
     */
    protected Schema $schema;

    /**
     * @var Model
     */
    protected Model $model;

    /**
     * ModelHydrator constructor.
     *
     * @param Schema $schema
     * @param Model $model
     */
    public function __construct(Schema $schema, Model $model)
    {
        $this->schema = $schema;
        $this->model = $model;
        $this->queryParameters = new QueryParameters();
    }

    /**
     * @inheritDoc
     */
    public function store(array $validatedData): object
    {
        $model = $this->hydrate($validatedData);

        /**
         * Always do eager loading, as we may have default eager
         * load paths.
         */
        $this->schema->loader()->forModel($model)->loadMissing(
            $this->queryParameters->includePaths()
        );

        return $model;
    }

    /**
     * @param array $validatedData
     * @return Model
     */
    public function hydrate(array $validatedData): Model
    {
        $unrecognised = collect($validatedData)->keys()->diff(
            $this->schema->fieldNames()
        );

        if ($unrecognised->isNotEmpty()) {
            throw new LogicException(sprintf(
                'Validated data for resource type %s contains unrecognised fields: %s',
                $this->schema->type(),
                $unrecognised->implode(', ')
            ));
        }

        $this->model->getConnection()->transaction(function () use ($validatedData) {
            $this->fillId($validatedData);
            $this->fillAttributes($validatedData);
            $deferred = $this->fillRelationships($validatedData);
            $this->persist();
            $this->fillDeferredRelationships($deferred, $validatedData);
        });

        return $this->model;
    }

    /**
     * Hydrate the JSON:API resource id, if provided.
     *
     * @param array $validatedData
     * @return void
     */
    protected function fillId(array $validatedData): void
    {
        $field = $this->schema->id();

        if ($this->mustFill($field, $validatedData)) {
            $field->fill($this->model, $validatedData[$field->name()]);
        }
    }

    /**
     * Hydrate JSON API attributes into the model.
     *
     * @param array $validatedData
     * @return void
     */
    protected function fillAttributes(array $validatedData): void
    {
        /** @var Attribute|Fillable $attribute */
        foreach ($this->schema->attributes() as $attribute) {
            if ($this->mustFill($attribute, $validatedData)) {
                $attribute->fill($this->model, $validatedData[$attribute->name()]);
            }
        }
    }

    /**
     * Should a value be filled into the supplied field?
     *
     * Fields are only fillable if they implement the Eloquent fillable
     * interface. When that is true, if a request has been set on the
     * hydrator, the field is checked for whether it is read only.
     *
     * If no request has been set, we assume we are operating
     * outside the context of a HTTP request; i.e. that the developer
     * is passing through data intentionally. In these circumstances,
     * we don't need to check if the field is read only.
     *
     * @param Field $field
     * @param array $validatedData
     * @return bool
     */
    protected function mustFill(Field $field, array $validatedData): bool
    {
        if (!$field instanceof Fillable) {
            return false;
        }

        if ($field->isReadOnly($this->request)) {
            return false;
        }

        return array_key_exists($field->name(), $validatedData);
    }

    /**
     * Hydrate JSON API relationships into the model.
     *
     * @param array $validatedData
     * @return array
     *      relationships that have to be filled after the model is saved.
     */
    protected function fillRelationships(array $validatedData): array
    {
        $defer = [];

        /** @var Relation|Fillable $field */
        foreach ($this->schema->relationships() as $field) {
            if ($field instanceof FillableToMany || ($field instanceof FillableToOne && $field->mustExist())) {
                $defer[] = $field;
                continue;
            }

            if ($this->mustFill($field, $validatedData)) {
                $field->fill($this->model, $validatedData[$field->name()]);
            }
        }

        return $defer;
    }

    /**
     * Fill relationships that were deferred until after the model was persisted.
     *
     * @param iterable $deferred
     * @param array $validatedData
     */
    protected function fillDeferredRelationships(iterable $deferred, array $validatedData): void
    {
        /** @var Relation|Fillable $field */
        foreach ($deferred as $field) {
            if ($this->mustFill($field, $validatedData)) {
                $field->fill($this->model, $validatedData[$field->name()]);
            }
        }
    }

    /**
     * Store the model.
     *
     * @return void
     */
    protected function persist(): void
    {
        if (true !== $this->model->save()) {
            throw new RuntimeException('Failed to save resource.');
        }
    }
}
