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
use LaravelJsonApi\Contracts\Store\Repository as RepositoryContract;
use LaravelJsonApi\Core\Schema\Schema as BaseSchema;
use LaravelJsonApi\Eloquent\Fields\Relations\ToMany;
use LaravelJsonApi\Eloquent\Fields\Relations\ToOne;
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
     * @return Builder
     */
    public static function query(): Builder
    {
        return app(static::class)->newQuery();
    }

    /**
     * @inheritDoc
     */
    public function repository(): RepositoryContract
    {
        return new Repository($this);
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
     * @return Builder
     */
    public function newQuery(): Builder
    {
        return new Builder($this, $this->newInstance()->newQuery());
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
        return new EagerLoader($this->schemas, $this);
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

}
