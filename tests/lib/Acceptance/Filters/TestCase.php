<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Filters;

use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use LaravelJsonApi\Eloquent\Tests\Acceptance\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * Assert the two iterables contain the same filtered models.
     *
     * As we are just asserting filtering, we do not care what order the models are in -
     * just that the two iterables contain the same models by id.
     *
     * @param iterable $expected
     * @param iterable $actual
     * @return void
     */
    protected function assertFilteredModels(iterable $expected, iterable $actual): void
    {
        $expected = Collection::make($expected)
            ->map(static fn(Model $model) => $model->getKey())
            ->sort()
            ->values()
            ->all();

        $actual = Collection::make($actual)
            ->map(static fn(Model $model) => $model->getKey())
            ->sort()
            ->values()
            ->all();

        $this->assertSame($expected, $actual);
    }
}