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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphMany;

use App\Models\Comment;
use App\Models\Video;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class UpdateTest extends TestCase
{

    public function testItUpdatesAndKeepsDetachedModels(): void
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

        $this->repository->update($video)->store([
            'comments' => $expected->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($video->relationLoaded('comments'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $video->getRelation('comments'));
        $this->assertComments($expected, $actual);

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

    public function testItUpdatesAndDeletesDetachedModels(): void
    {
        $this->schema->relationship('comments')->deleteDetachedModels();

        /** @var Video $video */
        $video = Video::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $existing = $video->comments()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Comment::factory()->create()
        );

        $this->repository->update($video)->store([
            'comments' => $expected->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($video->relationLoaded('comments'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $video->getRelation('comments'));
        $this->assertComments($expected, $actual);

        foreach ($expected as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'commentable_id' => $video->getKey(),
                'commentable_type' => Video::class,
            ]);
        }

        $this->assertDatabaseMissing('comments', [
            $remove->getKeyName() => $remove->getKey(),
        ]);
    }

    public function testEmpty(): void
    {
        $video = Video::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $existing = $video->comments()->get();

        $this->repository->update($video)->store([
            'comments' => [],
        ]);

        $this->assertTrue($video->relationLoaded('comments'));
        $this->assertEquals(new EloquentCollection(), $video->getRelation('comments'));

        foreach ($existing as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'commentable_id' => null,
                'commentable_type' => null,
            ]);
        }
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
        $video = Video::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $existing = $video->comments()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Comment::factory()->create()
        );

        $this->repository->update($video)->store([
            'comments' => collect($expected)->push($expected[1])->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($video->relationLoaded('comments'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $video->getRelation('comments'));
        $this->assertComments($expected, $actual);

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

    public function testWithCount(): void
    {
        /** @var Video $video */
        $video = Video::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $existing = $video->comments()->get();

        $expected = $existing->take(2)->push(
            Comment::factory()->create()
        );

        $this->repository->update($video)->withCount('comments')->store([
            'comments' => $expected->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all(),
        ]);

        $this->assertEquals(count($expected), $video->comments_count);
    }
}
