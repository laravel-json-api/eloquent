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

use Illuminate\Database\Eloquent\Model;
use LaravelJsonApi\Contracts\Store\CreatesResources;
use LaravelJsonApi\Contracts\Store\DeletesResources;
use LaravelJsonApi\Contracts\Store\ModifiesToMany;
use LaravelJsonApi\Contracts\Store\ModifiesToOne;
use LaravelJsonApi\Contracts\Store\QueriesAll;
use LaravelJsonApi\Contracts\Store\QueriesOne;
use LaravelJsonApi\Contracts\Store\QueriesToMany;
use LaravelJsonApi\Contracts\Store\QueriesToOne;
use LaravelJsonApi\Contracts\Store\QueryAllBuilder;
use LaravelJsonApi\Contracts\Store\QueryManyBuilder;
use LaravelJsonApi\Contracts\Store\QueryOneBuilder;
use LaravelJsonApi\Contracts\Store\Repository as RepositoryContract;
use LaravelJsonApi\Contracts\Store\ResourceBuilder;
use LaravelJsonApi\Contracts\Store\ToManyBuilder;
use LaravelJsonApi\Contracts\Store\ToOneBuilder;
use LaravelJsonApi\Contracts\Store\UpdatesResources;
use LaravelJsonApi\Eloquent\Fields\Relations\MorphTo;
use LaravelJsonApi\Eloquent\Hydrators\ModelHydrator;
use LaravelJsonApi\Eloquent\Hydrators\ToManyHydrator;
use LaravelJsonApi\Eloquent\Hydrators\ToOneHydrator;
use LogicException;
use RuntimeException;
use function is_string;
use function sprintf;

class Repository implements
    RepositoryContract,
    QueriesAll,
    QueriesOne,
    QueriesToOne,
    QueriesToMany,
    CreatesResources,
    UpdatesResources,
    DeletesResources,
    ModifiesToOne,
    ModifiesToMany
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
     * Repository constructor.
     *
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
        $this->model = $schema->newInstance();
    }

    /**
     * @inheritDoc
     */
    public function find(string $resourceId): ?object
    {
        if ($this->schema->id()->match($resourceId)) {
            return $this->query()->whereResourceId($resourceId)->first();
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function findOrFail(string $resourceId): object
    {
        if ($model = $this->find($resourceId)) {
            return $model;
        }

        throw new RuntimeException(sprintf(
            'Resource %s with id %s does not exist.',
            $this->schema->type(),
            $resourceId
        ));
    }

    /**
     * @inheritDoc
     */
    public function findMany(array $resourceIds): iterable
    {
        $field = $this->schema->id();

        $ids = collect($resourceIds)
            ->filter(fn($resourceId) => $field->match($resourceId))
            ->all();

        return $this->query()->whereResourceId($ids)->get();
    }

    /**
     * @inheritDoc
     */
    public function exists(string $resourceId): bool
    {
        if ($this->schema->id()->match($resourceId)) {
            return $this->query()->whereResourceId($resourceId)->exists();
        }

        return false;
    }

    /**
     * @return Builder
     */
    public function query(): Builder
    {
        return new Builder($this->schema, $this->model->newQuery());
    }

    /**
     * @inheritDoc
     */
    public function queryAll(): QueryAllBuilder
    {
        return new QueryAll($this->schema, $this->query());
    }

    /**
     * @inheritDoc
     */
    public function queryOne($modelOrResourceId): QueryOneBuilder
    {
        if ($modelOrResourceId instanceof Model) {
            return new QueryOne(
                $this->schema,
                $this->query(),
                $modelOrResourceId,
                strval($modelOrResourceId->{$this->schema->idColumn()})
            );
        }

        if (is_string($modelOrResourceId) && !empty($modelOrResourceId)) {
            return new QueryOne(
                $this->schema,
                $this->query(),
                null,
                $modelOrResourceId
            );
        }

        throw new LogicException('Expecting a model or non-empty string resource id.');
    }

    /**
     * @inheritDoc
     */
    public function queryToOne($modelOrResourceId, string $fieldName): QueryOneBuilder
    {
        $model = $this->retrieve($modelOrResourceId);
        $relation = $this->schema->toOne($fieldName);

        if ($relation instanceof MorphTo) {
            return new QueryMorphTo($model, $relation);
        }

        return new QueryToOne($model, $relation);
    }

    /**
     * @inheritDoc
     */
    public function queryToMany($modelOrResourceId, string $fieldName): QueryManyBuilder
    {
        return new QueryToMany(
            $this->retrieve($modelOrResourceId),
            $this->schema->toMany($fieldName)
        );
    }

    /**
     * @inheritDoc
     */
    public function create(): ResourceBuilder
    {
        return new ModelHydrator(
            $this->schema,
            $this->schema->newInstance()
        );
    }

    /**
     * @inheritDoc
     */
    public function update($modelOrResourceId): ResourceBuilder
    {
        return new ModelHydrator(
            $this->schema,
            $this->retrieve($modelOrResourceId)
        );
    }

    /**
     * @inheritDoc
     */
    public function delete($modelOrResourceId): void
    {
        $model = $this->retrieve($modelOrResourceId);

        if (true !== $model->getConnection()->transaction(fn() => $model->forceDelete())) {
            throw new RuntimeException('Failed to delete resource.');
        }
    }

    /**
     * @inheritDoc
     */
    public function modifyToOne($modelOrResourceId, string $fieldName): ToOneBuilder
    {
        return new ToOneHydrator(
            $this->retrieve($modelOrResourceId),
            $this->schema->toOne($fieldName)
        );
    }

    /**
     * @inheritDoc
     */
    public function modifyToMany($modelOrResourceId, string $fieldName): ToManyBuilder
    {
        return new ToManyHydrator(
            $this->retrieve($modelOrResourceId),
            $this->schema->toMany($fieldName)
        );
    }

    /**
     * @param Model|string $modelOrResourceId
     * @return Model
     */
    private function retrieve($modelOrResourceId): Model
    {
        if ($modelOrResourceId instanceof $this->model) {
            return $modelOrResourceId;
        }

        if (is_string($modelOrResourceId)) {
            return $this
                ->query()
                ->whereResourceId($modelOrResourceId)
                ->firstOrFail();
        }

        throw new LogicException(sprintf(
            'Expecting a %s instance or a string resource id.',
            get_class($this->model)
        ));
    }

}
