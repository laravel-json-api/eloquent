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

use Illuminate\Support\Traits\Conditionable;
use LaravelJsonApi\Eloquent\Contracts\Filter;

class WhereAny implements Filter
{

    use Concerns\DeserializesValue;
    use Concerns\HasColumns;
    use Concerns\HasOperator;
    use Concerns\IsSingular;
    use Conditionable;

    /**
     * @var string
     */
    private string $name;

    /**
     * Create a new filter.
     *
     * @param string $name
     * @param array<string> $columns
     * @return static
     */
    public static function make(string $name, array $columns = null): self
    {
        return new static($name, $columns);
    }

    /**
     * WhereAny constructor.
     *
     * @param string $name
     * @param array<string> $columns
    */
    public function __construct(string $name, array $columns = null)
    {
        $this->name = $name;
        $this->columns = $columns ?? [];
        $this->operator = '=';
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
    public function apply($query, $value)
    {
        if (!$this->isQualified()){
            $this->qualifyAs($query->getModel()->getTable());
        }

        return $query->whereAny(
            $this->qualifiedColumns(),
            $this->operator(),
            $this->deserialize($value)
        );
    }
}
