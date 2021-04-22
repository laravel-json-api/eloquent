<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\HasMany;

use Illuminate\Database\Eloquent\Model;
use LaravelJsonApi\Eloquent\Repository;
use LaravelJsonApi\Eloquent\Tests\Acceptance\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{

    /**
     * @var Repository
     */
    protected Repository $repository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->schemas()->schemaFor('users')->repository();
    }

    /**
     * @param iterable $expected
     * @param iterable $actual
     * @return void
     */
    protected function assertModels(iterable $expected, iterable $actual): void
    {
        $expected = collect($expected)
            ->map($fn = fn(Model $model) => $model->getKey())
            ->values()
            ->all();

        $actual = collect($actual)->map($fn)->values()->all();

        $this->assertSame($expected, $actual);
    }
}
