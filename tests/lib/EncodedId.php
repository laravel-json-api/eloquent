<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests;

use Illuminate\Support\Str;
use LaravelJsonApi\Contracts\Schema\ID;
use LaravelJsonApi\Contracts\Schema\IdEncoder;

class EncodedId implements ID, IdEncoder
{
    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return 'id';
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
    public function key(): ?string
    {
        // TODO: Implement key() method.
    }

    /**
     * @inheritDoc
     */
    public function pattern(): string
    {
        return '^TEST-';
    }

    /**
     * @inheritDoc
     */
    public function match(string $value): bool
    {
        return 1 === preg_match('/^TEST-/', $value);
    }

    /**
     * @inheritDoc
     */
    public function acceptsClientIds(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isSortable(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function encode($modelKey): string
    {
        return "TEST-{$modelKey}";
    }

    /**
     * @inheritDoc
     */
    public function decode(string $resourceId)
    {
        return Str::after($resourceId, 'TEST-');
    }
}
