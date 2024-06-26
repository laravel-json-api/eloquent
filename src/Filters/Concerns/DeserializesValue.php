<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Filters\Concerns;

use Closure;
use function filter_var;

trait DeserializesValue
{
    /**
     * @var Closure|null
     */
    private ?Closure $deserializer = null;

    /**
     * @var bool
     */
    private bool $asBool = false;

    /**
     * Use the supplied callback to deserialize the value.
     *
     * @param Closure $deserializer
     * @return $this
     */
    public function deserializeUsing(Closure $deserializer): static
    {
        $this->deserializer = $deserializer;
        $this->asBool = false;

        return $this;
    }

    /**
     * Deserialize value as a boolean.
     *
     * @return $this
     */
    public function asBoolean(): static
    {
        $this->asBool = true;

        return $this;
    }

    /**
     * Deserialize the value.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function deserialize(mixed $value): mixed
    {
        if (true === $this->asBool) {
            return filter_var($value, FILTER_VALIDATE_BOOL);
        }

        if ($this->deserializer) {
            return ($this->deserializer)($value);
        }

        return $value;
    }
}
