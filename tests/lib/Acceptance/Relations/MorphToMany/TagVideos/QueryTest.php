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

use App\Models\Tag;
use App\Models\Video;

class QueryTest extends TestCase
{

    public function test(): void
    {
        $tag = Tag::factory()
            ->has(Video::factory()->count(3))
            ->create();

        // should be ignored.
        Tag::factory()
            ->has(Video::factory()->count(2))
            ->create();

        $actual = $this->repository
            ->queryToMany($tag, 'videos')
            ->cursor();

        $this->assertVideos($tag->videos()->get(), $actual);

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(count($actual), $tag->videos_count);
    }

    public function testWithIncludePaths(): void
    {
        $tag = Tag::factory()
            ->has(Video::factory()->count(3))
            ->create();

        $actual = $this->repository
            ->queryToMany($tag, 'videos')
            ->with('comments')
            ->cursor();

        $this->assertVideos($tag->videos()->get(), $actual);
        $this->assertTrue($actual->every(fn(Video $video) => $video->relationLoaded('comments')));
    }

    public function testWithFilter(): void
    {
        $tag = Tag::factory()
            ->has(Video::factory()->count(3))
            ->create();

        $videos = $tag->videos()->get();

        $expected = $videos->take(2);
        $ids = $expected->map(fn (Video $video) => $video->getRouteKey())->all();

        $actual = $this->repository
            ->queryToMany($tag, 'videos')
            ->filter(['id' => $ids])
            ->cursor();

        $this->assertVideos($expected, $actual);
    }

    public function testWithPivotFilter(): void
    {
        /** @var Tag $tag */
        $tag = Tag::factory()
            ->hasAttached(Video::factory()->count(2), ['approved' => true])
            ->create();

        $expected = $tag->videos()->get();

        $notApproved = Video::factory()->count(2)->create();
        $tag->videos()->saveMany($notApproved, ['approved' => false]);

        $actual = $this->repository
            ->queryToMany($tag, 'videos')
            ->filter(['approved' => true])
            ->cursor();

        $this->assertVideos($expected, $actual);
    }

    public function testWithSort(): void
    {
        $tag = Tag::factory()
            ->has(Video::factory()->count(3))
            ->create();

        $videos = $tag->videos()->get();

        $expected = $videos->sortByDesc('id');

        $actual = $this->repository
            ->queryToMany($tag, 'videos')
            ->sort('-id')
            ->cursor();

        $this->assertVideos($expected, $actual);
    }

}
