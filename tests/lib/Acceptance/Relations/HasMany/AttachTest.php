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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\HasMany;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Schemas\CommentSchema;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class AttachTest extends TestCase
{

    public function test(): void
    {
        $user = User::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        /** We force the relation to be loaded before the change, so that we can test it is unset. */
        $existing = clone $user->comments;
        $expected = Comment::factory()->count(2)->create();

        $ids = $expected->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($user, 'comments')
            ->attach($ids);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertModels($expected, $actual);
        $this->assertSame(5, $user->comments()->count());

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(5, $user->comments_count);

        /**
         * We expect the relation to be unloaded because we know it has changed in the
         * database, but we don't know what it now is in its entirety.
         */
        $this->assertFalse($user->relationLoaded('comments'));

        foreach ($existing->merge($expected) as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'user_id' => $user->getKey(),
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $user = User::factory()->create();
        $comments = Comment::factory()->count(2)->create();

        $ids = $comments->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($user, 'comments')
            ->with('user')
            ->attach($ids);

        $this->assertModels($comments, $actual);
        $this->assertTrue($actual->every(fn(Comment $comment) => $comment->relationLoaded('user')));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(CommentSchema::class, 'commentable');

        $user = User::factory()->create();
        $comments = Comment::factory()
            ->count(2)
            ->for($post = Post::factory()->create(), 'commentable')
            ->create();

        $ids = $comments->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($user, 'comments')
            ->attach($ids);

        $this->assertModels($comments, $actual);
        $this->assertTrue($actual->every(
            fn(Comment $comment) => $comment->relationLoaded('commentable') && $post->is($comment->commentable)
        ));
    }

    public function testWithDefaultEagerLoadingAndIncludePaths(): void
    {
        $this->createSchemaWithDefaultEagerLoading(CommentSchema::class, 'commentable');

        $user = User::factory()->create();
        $comments = Comment::factory()
            ->count(2)
            ->for($post = Post::factory()->create(), 'commentable')
            ->create();

        $ids = $comments->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($user, 'comments')
            ->with('user')
            ->attach($ids);

        $this->assertModels($comments, $actual);

        $this->assertTrue($actual->every(
            fn(Comment $comment) => $comment->relationLoaded('commentable') && $post->is($comment->commentable)
        ));

        $this->assertTrue($actual->every(
            fn(Comment $comment) => $comment->relationLoaded('user') && $user->is($comment->user)
        ));
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
        $user = User::factory()->create();
        $comments = Comment::factory()->count(2)->create();

        $comments[0]->user()->associate($user)->save();

        $ids = collect($comments)->push($comments[0])->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($user, 'comments')
            ->attach($ids);

        $this->assertModels($comments, $actual);
        $this->assertSame(2, $user->comments()->count());
    }
}
