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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Schema\ID as IDContract;
use LaravelJsonApi\Core\Schema\Concerns\ClientIds;
use LaravelJsonApi\Core\Schema\Concerns\MatchesIds;
use LaravelJsonApi\Core\Schema\Concerns\Sortable;
use LaravelJsonApi\Eloquent\Contracts\Fillable;
use LaravelJsonApi\Validation\Fields\IsValidated;
use LaravelJsonApi\Validation\Rules\ClientId;

class ID implements IDContract, Fillable, IsValidated
{
    use ClientIds;
    use MatchesIds;
    use Sortable;

    /**
     * @var string|null
     */
    private ?string $column;

    /**
     * @var string
     */
    private string $validationModifier = 'required';

    /**
     * Create an id field.
     *
     * @param string|null $column
     * @return static
     */
    public static function make(string $column = null): self
    {
        return new static($column);
    }

    /**
     * IdField constructor.
     *
     * @param string|null $column
     */
    public function __construct(string $column = null)
    {
        $this->column = $column ?: null;
        $this->sortable();
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return 'id';
    }

    /**
     * @return string|null
     */
    public function column(): ?string
    {
        return $this->column;
    }

    /**
     * @inheritDoc
     */
    public function key(): ?string
    {
        return $this->column();
    }

    /**
     * @inheritDoc
     */
    public function isSparseField(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isReadOnly($request): bool
    {
        if ($this->acceptsClientIds()) {
            return !$request->isMethod('POST');
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function isNotReadOnly($request): bool
    {
        return !$this->isReadOnly($request);
    }

    /**
     * @return $this
     */
    public function nullable(): self
    {
        $this->validationModifier = 'nullable';

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function rulesForCreation(?Request $request): array|null
    {
        if ($this->acceptsClientIds()) {
            return [$this->validationModifier, new ClientId($this)];
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function rulesForUpdate(?Request $request, object $model): ?array
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function mustExist(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function fill(Model $model, $value, array $validatedData): void
    {
        $column = $this->column() ?: $model->getRouteKeyName();

        $model->{$column} = $value;
    }

}
