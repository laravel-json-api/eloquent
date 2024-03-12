<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\HasMany;

use App\Models\Comment;
use App\Models\User;
use App\Schemas\CommentSchema;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class SyncTest extends TestCase
{

    public function testItSyncsAndKeepsDetachedModels(): void
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

        $actual = $this->repository->modifyToMany($user, 'comments')->sync(
            $expected->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all()
        );

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertComments($expected, $actual);

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(count($expected), $user->comments_count);

        $this->assertTrue($user->relationLoaded('comments'));
        $this->assertSame($actual, $user->getRelation('comments'));

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

    public function testItSyncsAndDeletesDetachedModels(): void
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

        $actual = $this->repository->modifyToMany($user, 'comments')->sync(
            $expected->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all()
        );

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertComments($expected, $actual);

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(count($expected), $user->comments_count);

        $this->assertTrue($user->relationLoaded('comments'));
        $this->assertSame($actual, $user->getRelation('comments'));

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

        $actual = $this->repository
            ->modifyToMany($user, 'comments')
            ->sync([]);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertEquals(new EloquentCollection(), $actual);
        $this->assertSame(0, $user->comments()->count());

        $this->assertTrue($user->relationLoaded('comments'));
        $this->assertSame($actual, $user->getRelation('comments'));

        foreach ($existing as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'user_id' => null,
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $user = User::factory()->create();
        $comments = Comment::factory()->count(3)->create();

        $ids = $comments->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($user, 'comments')
            ->with('commentable')
            ->sync($ids);

        $this->assertCount(3, $actual);
        $this->assertTrue($actual->every(fn(Comment $comment) => $comment->relationLoaded('commentable')));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(CommentSchema::class, 'user');

        $user = User::factory()->create();
        $comments = Comment::factory()->count(3)->create();

        $ids = $comments->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($user, 'comments')
            ->sync($ids);

        $this->assertCount(3, $actual);
        $this->assertTrue($actual->every(fn(Comment $comment) => $comment->relationLoaded('user')));
    }

    public function testWithDefaultEagerLoadingAndIncludePaths(): void
    {
        $this->createSchemaWithDefaultEagerLoading(CommentSchema::class, 'user');

        $user = User::factory()->create();
        $comments = Comment::factory()->count(3)->create();

        $ids = $comments->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($user, 'comments')
            ->with('commentable')
            ->sync($ids);

        $this->assertCount(3, $actual);
        $this->assertTrue($actual->every(fn(Comment $comment) => $comment->relationLoaded('user')));
        $this->assertTrue($actual->every(fn(Comment $comment) => $comment->relationLoaded('commentable')));
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
        $comments = Comment::factory()->count(3)->create();

        $comments[1]->user()->associate($user)->save();

        $ids = collect($comments)->push($comments[1])->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($user, 'comments')
            ->sync($ids);

        $this->assertCount(3, $actual);
        $this->assertSame(3, $user->comments()->count());
        $this->assertComments($comments, $actual);
    }
}
