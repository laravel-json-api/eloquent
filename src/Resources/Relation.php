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

namespace LaravelJsonApi\Eloquent\Resources;

use LaravelJsonApi\Core\Resources\Relation as BaseRelation;
use LaravelJsonApi\Eloquent\Contracts\Countable;
use LaravelJsonApi\Eloquent\Fields\Relations\MorphToMany;
use LaravelJsonApi\Eloquent\Fields\Relations\Relation as SchemaRelation;
use LaravelJsonApi\Eloquent\Fields\Relations\ToMany;
use function intval;
use function is_null;

class Relation extends BaseRelation
{

    /**
     * The meta key for the `count` value.
     *
     * @var string
     */
    private static string $countAs = 'count';

    /**
     * Get or set the meta key for the count value.
     *
     * @param string|null $key
     * @return string
     */
    public static function withCount(string $key = null): string
    {
        if (empty($key)) {
            return self::$countAs;
        }

        return self::$countAs = $key;
    }

    /**
     * @var SchemaRelation
     */
    private SchemaRelation $field;

    /**
     * @var array|null
     */
    private ?array $cachedMeta = null;

    /**
     * Relation constructor.
     *
     * @param object $resource
     * @param string|null $baseUri
     * @param SchemaRelation $field
     */
    public function __construct(object $resource, ?string $baseUri, SchemaRelation $field)
    {
        parent::__construct(
            $resource,
            $baseUri,
            $field->name(),
            $field->relationName(),
            $field->uriName(),
        );

        $this->field = $field;
    }

    /**
     * @return array|null
     */
    public function meta(): ?array
    {
        if (is_array($this->cachedMeta)) {
            return $this->cachedMeta ?: null;
        }

        $this->cachedMeta = array_replace(
            $this->defaultMeta(),
            parent::meta() ?: [],
        );

        return $this->cachedMeta ?: null;
    }

    /**
     * @inheritDoc
     */
    protected function value()
    {
        if ($this->field instanceof MorphToMany) {
            return $this->field->value($this->resource);
        }

        return $this->field->parse(
            parent::value()
        );
    }

    /**
     * Get default relationship meta.
     *
     * @return array
     */
    private function defaultMeta(): array
    {
        if ($this->countable()) {
            return array_filter([
                self::withCount() => $this->count(),
            ], fn($value) => (null !== $value));
        }

        return [];
    }

    /**
     * Is the field countable?
     *
     * @return bool
     */
    private function countable(): bool
    {
        if ($this->field instanceof Countable) {
            return $this->field->isCountable();
        }

        return false;
    }

    /**
     * Get the relationship count.
     *
     * @return int|null
     */
    private function count(): ?int
    {
        if ($this->field instanceof MorphToMany) {
            return $this->field->count($this->resource);
        }

        if ($this->field instanceof ToMany) {
            $value = $this->resource->{$this->field->keyForCount()};
            return !is_null($value) ? intval($value) : null;
        }

        return null;
    }
}
