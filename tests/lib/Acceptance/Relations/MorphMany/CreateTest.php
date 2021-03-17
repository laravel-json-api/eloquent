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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CreateTest extends TestCase
{

    public function test(): void
    {
        $comments = Comment::factory()->count(2)->create();

        $video = $this->repository->create()->store([
            'comments' => $comments->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all(),
            'slug' => 'my-first-video',
            'title' => 'Video 123',
            'url' => 'http://example.com/videos/123.mov',
        ]);

        $this->assertTrue($video->relationLoaded('comments'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $video->getRelation('comments'));
        $this->assertComments($comments, $actual);

        foreach ($comments as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'commentable_id' => $video->getKey(),
                'commentable_type' => Video::class,
            ]);
        }
    }

    public function testEmpty(): void
    {
        $video = $this->repository->create()->store([
            'comments' => [],
            'slug' => 'my-first-video',
            'title' => 'Video 123',
            'url' => 'http://example.com/videos/123.mov',
        ]);

        $this->assertTrue($video->relationLoaded('comments'));
        $this->assertEquals(new EloquentCollection(), $video->getRelation('comments'));
    }


    /**
     * The spec says:
     * "If a given type and id is already in the relationship, the server MUST NOT add it again."
     *
     * This test checks that duplicate ids are not added.
     */
    public function testWithDuplicates(): void
    {
        $comments = Comment::factory()->count(2)->create();

        $video = $this->repository->create()->store([
            'comments' => collect($comments)->push($comments[1])->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all(),
            'slug' => 'my-first-video',
            'title' => 'Video 123',
            'url' => 'http://example.com/videos/123.mov',
        ]);

        $this->assertTrue($video->relationLoaded('comments'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $video->getRelation('comments'));
        $this->assertComments($comments, $actual);
        $this->assertSame(2, $video->comments()->count());

        foreach ($comments as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'commentable_id' => $video->getKey(),
                'commentable_type' => Video::class,
            ]);
        }
    }

    public function testWithCount(): void
    {
        $comments = Comment::factory()->count(2)->create();

        $video = $this->repository->create()->withCount('comments')->store([
            'comments' => $comments->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all(),
            'slug' => 'my-first-video',
            'title' => 'Video 123',
            'url' => 'http://example.com/videos/123.mov',
        ]);

        $this->assertEquals(count($comments), $video->comments_count);
    }
}
