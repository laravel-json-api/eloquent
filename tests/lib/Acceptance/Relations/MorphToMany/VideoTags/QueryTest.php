<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphToMany\VideoTags;

use App\Models\Tag;
use App\Models\Video;

class QueryTest extends TestCase
{

    public function test(): void
    {
        $video = Video::factory()
            ->has(Tag::factory()->count(3))
            ->create();

        // should be ignored.
        Video::factory()
            ->has(Tag::factory()->count(2))
            ->create();

        $actual = $this->repository
            ->queryToMany($video, 'tags')
            ->cursor();

        $this->assertTags($video->tags()->get(), $actual);

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(count($actual), $video->tags_count);
    }

    public function testWithIncludePaths(): void
    {
        $video = Video::factory()
            ->has(Tag::factory()->count(3))
            ->create();

        $actual = $this->repository
            ->queryToMany($video, 'tags')
            ->with('posts')
            ->cursor();

        $this->assertTags($video->tags()->get(), $actual);
        $this->assertTrue($actual->every(fn(Tag $tag) => $tag->relationLoaded('posts')));
    }

    public function testWithFilter(): void
    {
        $video = Video::factory()
            ->has(Tag::factory()->count(3))
            ->create();

        $tags = $video->tags()->get();

        $expected = $tags->take(2);
        $ids = $expected->map(fn (Tag $tag) => (string) $tag->getRouteKey())->all();

        $actual = $this->repository
            ->queryToMany($video, 'tags')
            ->filter(['id' => $ids])
            ->cursor();

        $this->assertTags($expected, $actual);
    }

    public function testWithPivotFilter(): void
    {
        /** @var Video $video */
        $video = Video::factory()
            ->hasAttached(Tag::factory()->count(2), ['approved' => true])
            ->create();

        $expected = $video->tags()->get();

        $notApproved = Tag::factory()->count(2)->create();
        $video->tags()->saveMany($notApproved, ['approved' => false]);

        $actual = $this->repository
            ->queryToMany($video, 'tags')
            ->filter(['approved' => true])
            ->cursor();

        $this->assertTags($expected, $actual);
    }

    public function testWithSort(): void
    {
        $video = Video::factory()
            ->has(Tag::factory()->count(3))
            ->create();

        $tags = $video->tags()->get();

        $expected = $tags->sortByDesc('id');

        $actual = $this->repository
            ->queryToMany($video, 'tags')
            ->sort('-id')
            ->cursor();

        $this->assertTags($expected, $actual);
    }

}
