<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
