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

use Closure;
use LaravelJsonApi\Core\Json\Hash;
use LaravelJsonApi\Core\Support\Arr;
use LaravelJsonApi\Validation\Fields\IsValidated;
use LaravelJsonApi\Validation\Fields\ValidatedWithArrayKeys;
use LaravelJsonApi\Validation\Rules\JsonObject;

class ArrayHash extends Attribute implements IsValidated
{
    use ValidatedWithArrayKeys;

    /**
     * @var Closure|null
     */
    private ?Closure $keys = null;

    /**
     * @var int|null
     */
    private ?int $sortValues = null;

    /**
     * @var int|null
     */
    private ?int $sortKeys = null;

    /**
     * The JSON:API field case.
     *
     * @var string|null
     */
    private ?string $fieldCase = null;

    /**
     * The database key-case.
     *
     * @var string|null
     */
    private ?string $keyCase = null;

    /**
     * Whether an empty array is allowed as the value.
     *
     * @var bool
     */
    private bool $allowEmpty = false;

    /**
     * Create an array attribute.
     *
     * @param string $fieldName
     * @param string|null $column
     * @return ArrayHash
     */
    public static function make(string $fieldName, string $column = null): self
    {
        return new self($fieldName, $column);
    }

    /**
     * Sort values when deserializing the array.
     *
     * @param int $flags
     * @return $this
     */
    public function sorted(int $flags = SORT_REGULAR): self
    {
        $this->sortKeys = null;
        $this->sortValues = $flags;

        return $this;
    }

    /**
     * Sort values by their keys when deserializing the array.
     *
     * @param int $flags
     * @return $this
     */
    public function sortKeys(int $flags = SORT_REGULAR): self
    {
        $this->sortKeys = $flags;
        $this->sortValues = null;

        return $this;
    }

    /**
     * Use camel-case fields when serializing to JSON.
     *
     * @return $this
     */
    public function camelizeFields(): self
    {
        $this->fieldCase = 'camelize';

        return $this;
    }

    /**
     * Use camel-case fields when storing values in the database.
     *
     * @return $this
     */
    public function camelizeKeys(): self
    {
        $this->keyCase = 'camelize';

        return $this;
    }

    /**
     * Use snake-case fields when serializing to JSON.
     *
     * @return $this
     */
    public function snakeFields(): self
    {
        $this->fieldCase = 'snake';

        return $this;
    }

    /**
     * Use snake-case fields when storing values in the database.
     *
     * @return $this
     */
    public function snakeKeys(): self
    {
        $this->keyCase = 'snake';

        return $this;
    }

    /**
     * Use underscore fields when serializing to JSON.
     *
     * @return $this
     */
    public function underscoreFields(): self
    {
        $this->fieldCase = 'underscore';

        return $this;
    }

    /**
     * Use underscore keys when storing values in the database.
     *
     * @return $this
     */
    public function underscoreKeys(): self
    {
        $this->keyCase = 'underscore';

        return $this;
    }

    /**
     * Use dash-case fields when serializing to JSON.
     *
     * @return $this
     */
    public function dasherizeFields(): self
    {
        $this->fieldCase = 'dasherize';

        return $this;
    }

    /**
     * Use dash-case keys when storing values in the database.
     *
     * @return $this
     */
    public function dasherizeKeys(): self
    {
        $this->keyCase = 'dasherize';

        return $this;
    }

    /**
     * Whether an empty array is allowed as the value.
     *
     * @param bool $allowEmpty
     * @return self
     */
    public function allowEmpty(bool $allowEmpty = true): self
    {
        $this->allowEmpty = $allowEmpty;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function serialize(object $model)
    {
        $value = parent::serialize($model);

        return Hash::cast($value)
            ->maybeSorted($this->sortValues)
            ->maybeSortKeys($this->sortKeys)
            ->useCase($this->fieldCase);
    }

    /**
     * @inheritDoc
     */
    protected function deserialize($value)
    {
        $value = parent::deserialize($value);

        if ($value && $this->keys) {
            $value = ($this->keys)($value);
        }

        if ($value === null) {
            return null;
        }

        return Hash::cast($value)
            ->maybeSorted($this->sortValues)
            ->maybeSortKeys($this->sortKeys)
            ->useCase($this->keyCase)
            ->all();
    }

    /**
     * @inheritDoc
     */
    protected function assertValue($value): void
    {
        if (($value !== null && !is_array($value)) || (!empty($value) && !Arr::isAssoc($value))) {
            throw new \UnexpectedValueException(sprintf(
                'Expecting the value of attribute %s to be an associative array.',
                $this->name()
            ));
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultRules(): array
    {
        return ['.' => (new JsonObject())->allowEmpty($this->allowEmpty)];
    }
}
