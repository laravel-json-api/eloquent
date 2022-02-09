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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\PolymorphicToMany;

use App\Models\Image;
use App\Models\Video;
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
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->schemas()->schemaFor('posts')->repository();
    }

    /**
     * @param $expected
     * @param $actual
     * @return void
     */
    protected function assertImages($expected, $actual): void
    {
        $expected = collect($expected)
            ->map($fn = fn(Image $model) => $model->getKey())
            ->sort()
            ->values()
            ->all();

        $actual = collect($actual)->map($fn)->sort()->values()->all();

        $this->assertSame($expected, $actual);
    }

    /**
     * @param $expected
     * @param $actual
     * @return void
     */
    protected function assertVideos($expected, $actual): void
    {
        $expected = collect($expected)
            ->map($fn = fn(Video $model) => $model->getKey())
            ->sort()
            ->values()
            ->all();

        $actual = collect($actual)->map($fn)->sort()->values()->all();

        $this->assertSame($expected, $actual);
    }

    /**
     * @param $expected
     * @param $actual
     * @return void
     */
    protected function assertMedia($expected, $actual): void
    {
        $expected = collect($expected)
            ->groupBy(fn ($model) => get_class($model))
            ->map(fn ($models) => collect($models)->map(fn(Model $model) => $model->getKey())->sort()->values())
            ->toArray();

        $actual = collect($actual)
            ->groupBy(fn ($model) => get_class($model))
            ->map(fn ($models) => collect($models)->map(fn(Model $model) => $model->getKey())->sort()->values())
            ->toArray();

        $this->assertSame($expected, $actual);
    }
}
