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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\HasMany;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class UpdateTest extends TestCase
{

    public function testItUpdatesAndKeepsDetachedModels(): void
    {
        /** @var User $user */
        $user = User::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $existing = $user->comments()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Comment::factory()->create()
        );

        $this->repository->update($user)->store([
            'comments' => $expected->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($user->relationLoaded('comments'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $user->getRelation('comments'));
        $this->assertComments($expected, $actual);

        foreach ($expected as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'user_id' => $user->getKey(),
            ]);
        }

        $this->assertDatabaseHas('comments', [
            'id' => $remove->getKey(),
            'user_id' => null,
        ]);
    }

    public function testItUpdatesAndDeletesDetachedModels(): void
    {
        $this->schema->relationship('comments')->deleteDetachedModels();

        /** @var User $user */
        $user = User::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $existing = $user->comments()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Comment::factory()->create()
        );

        $this->repository->update($user)->store([
            'comments' => $expected->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($user->relationLoaded('comments'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $user->getRelation('comments'));
        $this->assertComments($expected, $actual);

        foreach ($expected as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'user_id' => $user->getKey(),
            ]);
        }

        $this->assertDatabaseMissing('comments', [
            $remove->getKeyName() => $remove->getKey(),
        ]);
    }

    public function testEmpty(): void
    {
        $user = User::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $existing = $user->comments()->get();

        $this->repository->update($user)->store([
            'comments' => [],
        ]);

        $this->assertTrue($user->relationLoaded('comments'));
        $this->assertEquals(new EloquentCollection(), $user->getRelation('comments'));

        foreach ($existing as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'user_id' => null,
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
        /** @var User $user */
        $user = User::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $existing = $user->comments()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Comment::factory()->create()
        );

        $this->repository->update($user)->store([
            'comments' => collect($expected)->push($expected[1])->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($user->relationLoaded('comments'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $user->getRelation('comments'));
        $this->assertComments($expected, $actual);

        foreach ($expected as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'user_id' => $user->getKey(),
            ]);
        }

        $this->assertDatabaseHas('comments', [
            'id' => $remove->getKey(),
            'user_id' => null,
        ]);
    }

    public function testWithCount(): void
    {
        /** @var User $user */
        $user = User::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $existing = $user->comments()->get();

        $expected = $existing->take(2)->push(
            Comment::factory()->create()
        );

        $this->repository->update($user)->withCount('comments')->store([
            'comments' => $expected->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all(),
        ]);

        $this->assertEquals(count($expected), $user->comments_count);
    }
}
