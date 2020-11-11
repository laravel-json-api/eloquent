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
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Query\QueryParameters as QueryParametersContract;
use LaravelJsonApi\Contracts\Schema\Attribute;
use LaravelJsonApi\Contracts\Schema\Field;
use LaravelJsonApi\Contracts\Store\ResourceBuilder;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Eloquent\Contracts\Fillable;
use LaravelJsonApi\Eloquent\Contracts\FillableToMany;
use LaravelJsonApi\Eloquent\Contracts\FillableToOne;
use LaravelJsonApi\Eloquent\Fields\Relations\Relation;
use LaravelJsonApi\Eloquent\Schema;
use RuntimeException;

class ModelHydrator implements ResourceBuilder
{

    /**
     * @var Schema
     */
    private Schema $schema;

    /**
     * @var Model
     */
    private Model $model;

    /**
     * @var Request|mixed|null
     */
    private $request;

    /**
     * @var IncludePaths|null
     */
    private ?IncludePaths $includePaths = null;

    /**
     * Hydrator constructor.
     *
     * @param Schema $schema
     * @param Model $model
     */
    public function __construct(Schema $schema, Model $model)
    {
        $this->schema = $schema;
        $this->model = $model;
    }

    /**
     * @inheritDoc
     */
    public function using(QueryParametersContract $query): ResourceBuilder
    {
        if ($query instanceof Request) {
            $this->request = $query;
        }

        return $this->with($query->includePaths());
    }

    /**
     * @inheritDoc
     */
    public function with($includePaths): ResourceBuilder
    {
        $this->includePaths = IncludePaths::cast($includePaths);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function store(array $validatedData): object
    {
        if (!$this->request) {
            $this->request = \request();
        }

        $model = $this->hydrate($validatedData);

        if ($this->includePaths) {
            $this->schema->loader()
                ->forModel($model)
                ->loadMissing($this->includePaths);
        }

        return $model;
    }

    /**
     * @param array $validatedData
     * @return Model
     */
    public function hydrate(array $validatedData): Model
    {
        $this->model->getConnection()->transaction(function () use ($validatedData) {
            $this->fillAttributes($validatedData);
            $deferred = $this->fillRelationships($validatedData);
            $this->persist();
            $this->fillDeferredRelationships($deferred, $validatedData);
        });

        return $this->model;
    }

    /**
     * Hydrate JSON API attributes into the model.
     *
     * @param array $validatedData
     * @return void
     */
    private function fillAttributes(array $validatedData): void
    {
        /** @var Attribute|Fillable $attribute */
        foreach ($this->schema->attributes() as $attribute) {
            if ($this->mustFill($attribute, $validatedData)) {
                $attribute->fill($this->model, $validatedData[$attribute->name()]);
            }
        }
    }

    /**
     * @param Field $field
     * @param array $validatedData
     * @return bool
     */
    private function mustFill(Field $field, array $validatedData): bool
    {
        if ($field instanceof Fillable) {
            return $field->isNotReadOnly($this->request) && array_key_exists($field->name(), $validatedData);
        }

        return false;
    }

    /**
     * Hydrate JSON API relationships into the model.
     *
     * @param array $validatedData
     * @return array
     *      relationships that have to be filled after the model is saved.
     */
    private function fillRelationships(array $validatedData): array
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
    private function fillDeferredRelationships(iterable $deferred, array $validatedData): void
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
    private function persist(): void
    {
        if (true !== $this->model->save()) {
            throw new RuntimeException('Failed to save resource.');
        }
    }
}
