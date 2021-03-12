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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Store\Repository as RepositoryContract;
use LaravelJsonApi\Core\Query\RelationshipPath;
use LaravelJsonApi\Core\Schema\Schema as BaseSchema;
use LaravelJsonApi\Eloquent\Contracts\Driver;
use LaravelJsonApi\Eloquent\Contracts\Parser;
use LaravelJsonApi\Eloquent\Drivers\StandardDriver;
use LaravelJsonApi\Eloquent\EagerLoading\EagerLoader;
use LaravelJsonApi\Eloquent\Fields\Relations\ToMany;
use LaravelJsonApi\Eloquent\Fields\Relations\ToOne;
use LaravelJsonApi\Eloquent\Parsers\StandardParser;
use LogicException;

abstract class Schema extends BaseSchema
{

    /**
     * The relationships that should always be eager loaded.
     *
     * @var array
     */
    protected array $with = [];

    /**
     * The default paging parameters to use if the client supplies none.
     *
     * @var array|null
     */
    protected ?array $defaultPagination = null;

    /**
     * The cached parser instance.
     *
     * @var Parser|null
     */
    protected ?Parser $parser = null;

    /**
     * @var string|null
     */
    private ?string $idColumn = null;

    /**
     * @inheritDoc
     */
    public static function model(): string
    {
        if (isset(static::$model)) {
            return static::$model;
        }

        throw new LogicException('The model class name must be set.');
    }

    /**
     * @inheritDoc
     */
    public function repository(): RepositoryContract
    {
        return new Repository(
            $this,
            $this->driver(),
            $this->parser(),
        );
    }

    /**
     * @return Model
     */
    public function newInstance(): Model
    {
        $modelClass = $this->model();

        return new $modelClass;
    }

    /**
     * Does the schema handle the provided model?
     *
     * @param Model|string $model
     * @return bool
     */
    public function isModel($model): bool
    {
        $expected = $this->model();

        return ($model instanceof $expected) || $model === $expected;
    }

    /**
     * Build an index query for this resource.
     *
     * Allows the developer to implement resource specific filtering
     * when querying all resources (an "index" query). This is required
     * for authorization implementation - i.e. to remove certain resources
     * that the user is not allowed to access.
     *
     * The request will be `null` if querying the resource outside of a HTTP
     * request - for example, queued broadcasting.
     *
     * @param Request|null $request
     * @param Builder $query
     * @return Builder
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        return $query;
    }

    /**
     * Build a "relatable" query for this resource.
     *
     * @param Request|null $request
     * @param Relation $query
     * @return Relation
     */
    public function relatableQuery(?Request $request, Relation $query): Relation
    {
        return $query;
    }

    /**
     * @return string|null
     */
    public function idKeyName(): ?string
    {
        return $this->idColumn();
    }

    /**
     * @return string
     */
    public function idColumn(): string
    {
        if ($this->idColumn) {
            return $this->idColumn;
        }

        if ($key = $this->id()->key()) {
            return $this->idColumn = $key;
        }

        return $this->idColumn = $this->newInstance()->getRouteKeyName();
    }

    /**
     * Get an Eloquent belongs-to field.
     *
     * @param string $fieldName
     * @return ToOne
     */
    public function toOne(string $fieldName): ToOne
    {
        $relation = $this->relationship($fieldName);

        if ($relation instanceof ToOne) {
            return $relation;
        }

        throw new LogicException(sprintf(
            'Expecting relationships %s to be an Eloquent JSON API to-one relationship.',
            $fieldName
        ));
    }

    /**
     * Get an Eloquent has-many field.
     *
     * @param string $fieldName
     * @return ToMany
     */
    public function toMany(string $fieldName): ToMany
    {
        $relation = $this->relationship($fieldName);

        if ($relation instanceof ToMany) {
            return $relation;
        }

        throw new LogicException(sprintf(
            'Expecting relationships %s to be an Eloquent JSON API to-many relationship.',
            $fieldName
        ));
    }

    /**
     * @return EagerLoader
     */
    public function loader(): EagerLoader
    {
        return new EagerLoader($this->server->schemas(), $this);
    }

    /**
     * The relationships that should always be eager loaded.
     *
     * @return array
     */
    public function with(): array
    {
        return $this->with;
    }

    /**
     * Get the default pagination parameters.
     *
     * The default pagination parameters are used if the client supplies no page
     * parameters. Returning `null` from this method indicates that there
     * should be no default pagination - i.e. if the client supplies no page
     * parameters, they receive ALL resources.
     *
     * @return array|null
     */
    public function defaultPagination(): ?array
    {
        return $this->defaultPagination;
    }

    /**
     * Will the set of filters result in zero-to-one resource?
     *
     * While individual filters can be marked as singular, there may be instances
     * where the combination of filters should result in a singular response
     * (zero-to-one resource instead of zero-to-many). Developers can use this
     * hook to add complex logic for working out if a set of filters should
     * return a singular resource.
     *
     * @param array $filters
     * @return bool
     */
    public function isSingular(array $filters): bool
    {
        return false;
    }

    /**
     * Get the parser for this resource type.
     *
     * @return Parser
     */
    public function parser(): Parser
    {
        if ($this->parser) {
            return $this->parser;
        }

        return $this->parser = new StandardParser();
    }

    /**
     * @param RelationshipPath $path
     * @return bool
     */
    public function isIncludePath(RelationshipPath $path): bool
    {
        if (!$this->isRelationship($path->first())) {
            return false;
        }

        return $this
            ->relationship($path->first())
            ->isIncludePath();
    }

    /**
     * @return Driver
     */
    protected function driver(): Driver
    {
        return new StandardDriver($this->newInstance());
    }

}
