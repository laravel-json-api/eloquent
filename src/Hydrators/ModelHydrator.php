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
use Illuminate\Support\Collection;
use LaravelJsonApi\Contracts\Schema\Attribute;
use LaravelJsonApi\Contracts\Schema\Field;
use LaravelJsonApi\Contracts\Schema\Relation as RelationContract;
use LaravelJsonApi\Contracts\Store\ResourceBuilder;
use LaravelJsonApi\Core\Query\Custom\ExtendedQueryParameters;
use LaravelJsonApi\Eloquent\Contracts\Driver;
use LaravelJsonApi\Eloquent\Contracts\Fillable;
use LaravelJsonApi\Eloquent\Contracts\FillableToMany;
use LaravelJsonApi\Eloquent\Contracts\FillableToOne;
use LaravelJsonApi\Eloquent\Contracts\Parser;
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
    private Schema $schema;

    /**
     * @var Driver
     */
    private Driver $driver;

    /**
     * @var Parser
     */
    private Parser $parser;

    /**
     * @var Model
     */
    private Model $model;

    /**
     * ModelHydrator constructor.
     *
     * @param Schema $schema
     * @param Driver $driver
     * @param Parser $parser
     * @param Model $model
     */
    public function __construct(
        Schema $schema,
        Driver $driver,
        Parser $parser,
        Model $model
    ) {
        $this->schema = $schema;
        $this->driver = $driver;
        $this->parser = $parser;
        $this->model = $model;
        $this->queryParameters = new ExtendedQueryParameters();
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
        $this->schema
            ->loaderFor($model)
            ->loadMissing($this->queryParameters->includePaths())
            ->loadCount($this->queryParameters->countable());

        return $this->parser->parseOne($model);
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
            $deferredAttributes = $this->fillAttributes($validatedData);
            $deferredRelations = $this->fillRelationships($validatedData);
            $this->persist();
            $this->fillDeferredAttributes($deferredAttributes, $validatedData);
            $this->persistAfterDeferredAttributes();
            $this->fillDeferredRelationships($deferredRelations, $validatedData);
        });

        return $this->model;
    }

    /**
     * Hydrate the JSON:API resource id, if provided.
     *
     * @param array $validatedData
     * @return void
     */
    private function fillId(array $validatedData): void
    {
        $field = $this->schema->id();

        if ($this->mustFillIdOrAttribute($field, $validatedData)) {
            $field->fill($this->model, $validatedData[$field->name()], $validatedData);
        }
    }

    /**
     * Hydrate JSON API attributes into the model.
     *
     * @param array $validatedData
     * @return array
     *      attributes that must be filled after the model is saved.
     */
    private function fillAttributes(array $validatedData): array
    {
        $defer = [];

        /** @var Attribute|Fillable $attribute */
        foreach ($this->schema->attributes() as $attribute) {
            if ($this->mustDeferAttribute($attribute)) {
                $defer[] = $attribute;
                continue;
            }

            if ($this->mustFillIdOrAttribute($attribute, $validatedData)) {
                $attribute->fill($this->model, $validatedData[$attribute->name()], $validatedData);
            }
        }

        return $defer;
    }

    /**
     * Fill attributes that were deferred until after the model was saved.
     *
     * @param array $validatedData
     * @return void
     */
    private function fillDeferredAttributes(iterable $deferred, array $validatedData): void
    {
        /** @var Attribute|Fillable $attribute */
        foreach ($deferred as $attribute) {
            if ($this->mustFillIdOrAttribute($attribute, $validatedData)) {
                $attribute->fill($this->model, $validatedData[$attribute->name()], $validatedData);
            }
        }
    }

    /**
     * Should a value be filled into the supplied field?
     *
     * @param Field $field
     * @param array $validatedData
     * @return bool
     */
    private function mustFillIdOrAttribute(Field $field, array $validatedData): bool
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
     * Does filling the relation need to be deferred until after the model is persisted?
     *
     * Attributes *never* need to be deferred if the primary model already exists. This
     * is because any relationships that use the `withDefault()` method will work if
     * the primary model is already persisted (because the related key is properly
     * set). So if the model already exists, we can just allow all attributes to be
     * filled at once regardless of whether they are filled into the primary model or
     * a related model.
     *
     * If the model is being created, a relationship that uses the `withDefault()` method
     * cannot be filled *before* the primary model is saved. This is because the related
     * key will not be set properly, as the primary model's primary key will be `null`
     * before being created. In this scenario, we need to defer any attributes that
     * need the primary model to exist, and fill them in a second pass.
     *
     * @param Field $field
     * @return bool
     */
    private function mustDeferAttribute(Field $field): bool
    {
        if (true === $this->model->exists) {
            return false;
        }

        if ($field instanceof Fillable) {
            return $field->mustExist();
        }

        return false;
    }

    /**
     * Hydrate JSON:API relationships into the model.
     *
     * @param array $validatedData
     * @return array
     *      relationships that have to be filled after the model is saved.
     */
    private function fillRelationships(array $validatedData): array
    {
        $defer = [];

        /** @var Relation|FillableToOne|FillableToMany $field */
        foreach ($this->schema->relationships() as $field) {
            if ($this->mustDeferRelation($field)) {
                $defer[] = $field;
                continue;
            }

            if ($this->mustFillRelation($field, $validatedData)) {
                $field->fill($this->model, $validatedData[$field->name()]);
            }
        }

        return $defer;
    }

    /**
     * Should a value be filled into the supplied field?
     *
     * @param RelationContract $field
     * @param array $validatedData
     * @return bool
     */
    private function mustFillRelation(RelationContract $field, array $validatedData): bool
    {
        if (!$field instanceof FillableToOne && !$field instanceof FillableToMany) {
            return false;
        }

        if ($field->isReadOnly($this->request)) {
            return false;
        }

        return array_key_exists($field->name(), $validatedData);
    }

    /**
     * Does filling the relation need to be deferred until after the model is persisted?
     *
     * @param $relation
     * @return bool
     */
    private function mustDeferRelation($relation): bool
    {
        if ($relation instanceof FillableToMany) {
            return true;
        }

        if ($relation instanceof FillableToOne) {
            return $relation->mustExist();
        }

        return false;
    }

    /**
     * Fill relationships that were deferred until after the model was persisted.
     *
     * @param iterable $deferred
     * @param array $validatedData
     */
    private function fillDeferredRelationships(iterable $deferred, array $validatedData): void
    {
        /** @var Relation|FillableToOne|FillableToMany $field */
        foreach ($deferred as $field) {
            if ($this->mustFillRelation($field, $validatedData)) {
                $field->fill($this->model, $validatedData[$field->name()]);
            }
        }
    }

    /**
     * Store the model.
     *
     * @return void
     */
    private function persist(): void
    {
        if (true !== $this->driver->persist($this->model)) {
            throw new RuntimeException('Failed to save resource.');
        }
    }

    /**
     * Store any related models that are dirty as a result of filling attributes.
     *
     * @return void
     */
    private function persistAfterDeferredAttributes(): void
    {
        /**
         * If deferred attributes have caused the model to become dirty, we will need to
         * save it again. There are only very limited circumstances where this might occur.
         * Primarily, if the `Map` field contains a mixture of attributes on the primary
         * model and on related models, it will be deferred and will cause the primary model
         * to become dirty when it is later filled. This will only occur on create -
         * because the model hydrator *never* defers attributes if the model already exists.
         *
         * In the vast majority of cases, the primary model will not be dirty here, so we
         * won't get another save.
         */
        if ($this->model->isDirty()) {
            $this->persist();
        }

        foreach ($this->model->getRelations() as $key => $related) {
            /** @var Model $model */
            foreach (Collection::wrap($related) as $model) {
                if ($model->isDirty() && true !== $model->save()) {
                    throw new RuntimeException(sprintf(
                        'Failed to save related model %s on relation %s.',
                        get_class($model),
                        $key,
                    ));
                }
            }
        }
    }
}
