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
        $ids = $expected->map(fn (Comment $comment) => $comment->getRouteKey())->all();

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
