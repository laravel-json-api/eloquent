<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Fields;

use LaravelJsonApi\Validation\Fields\IsValidated;
use LaravelJsonApi\Validation\Fields\ValidatedWithListOfRules;

class Str extends Attribute implements IsValidated
{
    use ValidatedWithListOfRules;

    /**
     * Create a string attribute.
     *
     * @param string $fieldName
     * @param string|null $column
     * @return Str
     */
    public static function make(string $fieldName, string $column = null): self
    {
        return new self($fieldName, $column);
    }

    /**
     * @return array
     */
    protected function defaultRules(): array
    {
        return ['string'];
    }

    /**
     * @inheritDoc
     */
    protected function assertValue($value): void
    {
        if (!is_null($value) && !is_string($value)) {
            throw new \UnexpectedValueException(sprintf(
                'Expecting the value of attribute %s to be a string.',
                $this->name()
            ));
        }
    }

}
