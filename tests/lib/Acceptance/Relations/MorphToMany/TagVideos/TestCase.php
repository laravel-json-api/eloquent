<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphToMany\TagVideos;

use App\Models\Video;
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

        $this->repository = $this->schemas()->schemaFor('tags')->repository();
    }

    /**
     * @param iterable $expected
     * @param iterable $actual
     * @return void
     */
    protected function assertVideos(iterable $expected, iterable $actual): void
    {
        $expected = collect($expected)
            ->map($fn = fn(Video $video) => $video->getKey())
            ->sort()
            ->values()
            ->all();

        $actual = collect($actual)->map($fn)->sort()->values()->all();

        $this->assertSame($expected, $actual);
    }
}
