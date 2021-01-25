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

class SyncTest extends TestCase
{

    public function test(): void
    {
        /** @var Video $video */
        $video = Video::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $existing = $video->comments()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Comment::factory()->create()
        );

        $actual = $this->repository->modifyToMany($video, 'comments')->sync(
            $expected->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all()
        );

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertComments($expected, $actual);

        $this->assertTrue($video->relationLoaded('comments'));
        $this->assertSame($actual, $video->getRelation('comments'));

        foreach ($expected as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'commentable_id' => $video->getKey(),
                'commentable_type' => Video::class,
            ]);
        }

        $this->assertDatabaseHas('comments', [
            'id' => $remove->getKey(),
            'commentable_id' => null,
            'commentable_type' => null,
        ]);
    }

    public function testEmpty(): void
    {
        $video = Video::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $existing = $video->comments()->get();

        $actual = $this->repository
            ->modifyToMany($video, 'comments')
            ->sync([]);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertEquals(new EloquentCollection(), $actual);
        $this->assertSame(0, $video->comments()->count());

        $this->assertTrue($video->relationLoaded('comments'));
        $this->assertSame($actual, $video->getRelation('comments'));

        foreach ($existing as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'commentable_id' => null,
                'commentable_type' => null,
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $video = Video::factory()->create();
        $comments = Comment::factory()->count(3)->create();

        $ids = $comments->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'comments')
            ->with('user')
            ->sync($ids);

        $this->assertCount(3, $actual);
        $this->assertTrue($actual->every(fn(Comment $comment) => $comment->relationLoaded('user')));
    }

    /**
     * The spec says:
     * "If a given type and id is already in the relationship, the server MUST NOT add it again."
     *
     * This test checks that duplicate ids are not added.
     */
    public function testWithDuplicates(): void
    {
        /** @var Video $video */
        $video = Video::factory()->create();
        $comments = Comment::factory()->count(3)->create();

        $comments[1]->commentable()->associate($video)->save();

        $ids = collect($comments)->push($comments[1])->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'comments')
            ->sync($ids);

        $this->assertCount(3, $actual);
        $this->assertSame(3, $video->comments()->count());
        $this->assertComments($comments, $actual);
    }
}
