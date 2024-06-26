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
use LaravelJsonApi\Validation\Fields\ValidatedWithRules;
use LaravelJsonApi\Validation\Rules\JsonNumber;
use UnexpectedValueException;

class Integer extends Attribute implements IsValidated
{
    use ValidatedWithRules;

    /**
     * @var bool
     */
    private bool $acceptStrings = false;

    /**
     * Create a number attribute.
     *
     * @param string $fieldName
     * @param string|null $column
     * @return self
     */
    public static function make(string $fieldName, string $column = null): self
    {
        return new self($fieldName, $column);
    }

    /**
     * @return $this
     */
    public function acceptStrings(): self
    {
        $this->acceptStrings = true;

        return $this;
    }

    /**
     * @return array
     */
    protected function defaultRules(): array
    {
        if ($this->acceptStrings) {
            return ['numeric', 'integer'];
        }

        return [(new JsonNumber())->onlyIntegers()];
    }

    /**
     * @inheritDoc
     */
    protected function assertValue($value): void
    {
        if (!$this->isInt($value)) {
            $expected = $this->acceptStrings ?
                'an integer or a numeric string that is an integer.' :
                'an integer.';

            throw new UnexpectedValueException(sprintf(
                'Expecting the value of attribute %s to be ' . $expected,
                $this->name(),
            ));
        }
    }

    /**
     * Is the value a numeric value that this field accepts?
     *
     * @param mixed $value
     * @return bool
     */
    private function isInt(mixed $value): bool
    {
        if ($this->acceptStrings && is_string($value) && is_numeric($value)) {
            $value = filter_var($value, FILTER_VALIDATE_INT);
        }

        if ($value === null || is_int($value)) {
            return true;
        }

        return false;
    }
}
