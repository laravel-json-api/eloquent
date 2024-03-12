<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphMany;

use App\Models\Comment;
use App\Models\Video;

class QueryTest extends TestCase
{

    public function test(): void
    {
        $video = Video::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        // should be ignored.
        Comment::factory()->create();

        $actual = $this->repository
            ->queryToMany($video, 'comments')
            ->cursor();

        $this->assertComments($video->comments()->get(), $actual);

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(count($actual), $video->comments_count);
    }

    public function testWithIncludePaths(): void
    {
        $video = Video::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $actual = $this->repository
            ->queryToMany($video, 'comments')
            ->with('user')
            ->cursor();

        $this->assertComments($video->comments()->get(), $actual);
        $this->assertTrue($actual->every(fn(Comment $comment) => $comment->relationLoaded('user')));
    }

    public function testWithFilter(): void
    {
        $video = Video::factory()->create();

        $comments = Comment::factory()
            ->count(3)
            ->create(['commentable_id' => $video->getKey(), 'commentable_type' => Video::class]);

        $expected = $comments->take(2);
        $ids = $expected->map(fn (Comment $comment) => (string) $comment->getRouteKey())->all();

        $actual = $this->repository
            ->queryToMany($video, 'comments')
            ->filter(['id' => $ids])
            ->cursor();

        $this->assertComments($expected, $actual);
    }

    public function testWithSort(): void
    {
        $video = Video::factory()->create();

        $comments = Comment::factory()
            ->count(3)
            ->create(['commentable_id' => $video->getKey(), 'commentable_type' => Video::class]);

        $expected = $comments->sortByDesc('id');

        $actual = $this->repository
            ->queryToMany($video, 'comments')
            ->sort('-id')
            ->cursor();

        $this->assertComments($expected, $actual);
    }

}
