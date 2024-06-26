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
use LaravelJsonApi\Validation\Filters\Validated;
use LaravelJsonApi\Validation\Rules\JsonBoolean;
use LogicException;
use function filter_var;

class WithTrashed implements Filter
{
    use Validated;

    /**
     * @var string
     */
    private string $name;

    /**
     * Create a new filter.
     *
     * @param string $name
     * @return static
     */
    public static function make(string $name): self
    {
        return new static($name);
    }

    /**
     * WithTrashed constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @inheritDoc
     */
    public function isSingular(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function apply($query, $value)
    {
        $value = $this->deserialize($value);

        if (is_callable([$query, 'withTrashed'])) {
            return $query->withTrashed($value);
        }

        throw new LogicException("Filter {$this->key()} expects query builder to have a `withTrashed` method.");
    }

    /**
     * @inheritDoc
     */
    public function key(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function validationRules(?Request $request, Query $query): array
    {
        return [(new JsonBoolean())->asString()];
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function deserialize(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL);
    }
}
