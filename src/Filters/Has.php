<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Filters;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Query\Input\Query;
use LaravelJsonApi\Eloquent\Contracts\Filter;
use LaravelJsonApi\Eloquent\Filters\Concerns\HasRelation;
use LaravelJsonApi\Eloquent\Filters\Concerns\IsSingular;
use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Validation\Filters\Validated;
use LaravelJsonApi\Validation\Rules\JsonBoolean;
use function filter_var;

class Has implements Filter
{
    use HasRelation;
    use IsSingular;
    use Validated;

    /**
     * Create a new filter.
     *
     * @param Schema $schema
     * @param string $fieldName
     * @param string|null $key
     * @return self
     */
    public static function make(Schema $schema, string $fieldName, string $key = null): self
    {
        return new static($schema, $fieldName, $key);
    }

    /**
     * Has constructor.
     *
     * @param Schema $schema
     * @param string $fieldName
     * @param string|null $key
     */
    public function __construct(Schema $schema, string $fieldName, string $key = null)
    {
        $this->schema = $schema;
        $this->fieldName = $fieldName;
        $this->key = $key;
    }

    /**
     * @inheritDoc
     */
    public function apply($query, $value)
    {
        $value = $this->deserialize($value);
        $relationName = $this->relationName();

        if (true === $value) {
            return $query->has($relationName);
        }

        return $query->doesntHave($relationName);
    }

    /**
     * @inheritDoc
     */
    public function validationRules(?Request $request, Query $query): array
    {
        return [(new JsonBoolean())->asString()];
    }

    /**
     * Deserialize the value.
     *
     * @param mixed $value
     * @return bool
     */
    protected function deserialize(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL);
    }
}
