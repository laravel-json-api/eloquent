<?php
/*
 * Copyright 2022 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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