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

namespace LaravelJsonApi\Eloquent\Query;

use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Core\Query\FieldSets;
use LaravelJsonApi\Core\Query\FilterParameters;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Query\QueryParameters;
use LaravelJsonApi\Core\Query\SortFields;

class ExtendedQueryParameters extends QueryParameters
{

    /**
     * @var string
     */
    private static string $withCount = 'withCount';

    /**
     * @var CountablePaths|null
     */
    private ?CountablePaths $countable;

    /**
     * Set the `withCount` parameter key.
     *
     * @param string $key
     * @return void
     */
    public static function withCount(string $key): void
    {
        self::$withCount = $key;
    }

    /**
     * ExtendedQueryParameters constructor.
     *
     * @param IncludePaths|null $includePaths
     * @param FieldSets|null $fieldSets
     * @param SortFields|null $sortFields
     * @param array|null $page
     * @param FilterParameters|null $filters
     * @param array|null $unrecognised
     */
    public function __construct(
        IncludePaths $includePaths = null,
        FieldSets $fieldSets = null,
        SortFields $sortFields = null,
        array $page = null,
        FilterParameters $filters = null,
        array $unrecognised = null
    ) {
        parent::__construct(
            $includePaths,
            $fieldSets,
            $sortFields,
            $page,
            $filters,
            collect($unrecognised)->forget(self::$withCount)->all() ?: null,
        );

        $this->countable = CountablePaths::nullable($unrecognised[self::$withCount] ?? null);
    }

    /**
     * Get the countable relationships.
     *
     * @return CountablePaths|null
     */
    public function countable(): ?CountablePaths
    {
        return $this->countable;
    }

    /**
     * Set the countable relationships.
     *
     * @param mixed $countable
     * @return $this
     */
    public function setCountable($countable): self
    {
        $this->countable = CountablePaths::nullable($countable);

        return $this;
    }

    /**
     * Remove countable paths.
     *
     * @return $this
     */
    public function withoutCountable(): self
    {
        $this->countable = null;

        return $this;
    }

    /**
     * @return array
     */
    public function unrecognisedParameters(): array
    {
        $parameters = parent::unrecognisedParameters();
        $countable = $this->countable();

        if ($countable && $countable->isNotEmpty()) {
            $parameters[self::$withCount] = $countable->toString();
        }

        return $parameters;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $values = parent::toArray();

        if (isset($values[self::$withCount])) {
            $values[self::$withCount] = $this->countable()->toArray();
        }

        return $values;
    }

    /**
     * @param Schema $schema
     * @return static
     */
    public function forSchema(Schema $schema): QueryParameters
    {
        $copy = parent::forSchema($schema);
        $countable = CountablePaths::cast($this->countable)->forSchema($schema);

        $copy->setCountable(
            $countable->isNotEmpty() ? $countable : null
        );

        return $copy;
    }
}
