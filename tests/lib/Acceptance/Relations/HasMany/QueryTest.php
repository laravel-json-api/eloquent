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

class QueryTest extends TestCase
{

    public function test(): void
    {
        $user = User::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        // should be ignored.
        Comment::factory()->create();

        $actual = $this->repository
            ->queryToMany($user, 'comments')
            ->cursor();

        $this->assertComments($user->comments()->get(), $actual);
        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(3, $user->comments_count);
    }

    public function testWithIncludePaths(): void
    {
        $user = User::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $actual = $this->repository
            ->queryToMany($user, 'comments')
            ->with('commentable')
            ->cursor();

        $this->assertComments($user->comments()->get(), $actual);
        $this->assertTrue($actual->every(fn(Comment $comment) => $comment->relationLoaded('commentable')));
    }

    public function testDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(CommentSchema::class, 'user');

        $user = User::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $actual = $this->repository
            ->queryToMany($user, 'comments')
            ->cursor();

        $this->assertComments($user->comments()->get(), $actual);
        $this->assertTrue($actual->every(
            fn(Comment $comment) => $comment->relationLoaded('user') && $user->is($comment->user)
        ));
    }

    public function testDefaultEagerLoadingAndIncludePaths(): void
    {
        $this->createSchemaWithDefaultEagerLoading(CommentSchema::class, 'user');

        $user = User::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $actual = $this->repository
            ->queryToMany($user, 'comments')
            ->with('commentable')
            ->cursor();

        $this->assertComments($user->comments()->get(), $actual);

        $this->assertTrue($actual->every(
            fn(Comment $comment) => $comment->relationLoaded('user')
        ));

        $this->assertTrue($actual->every(
            fn(Comment $comment) => $comment->relationLoaded('commentable')
        ));
    }

    public function testWithFilter(): void
    {
        $user = User::factory()->create();

        $comments = Comment::factory()
            ->count(3)
            ->create(['user_id' => $user]);

        $expected = $comments->take(2);
        $ids = $expected->map(fn (Comment $comment) => (string) $comment->getRouteKey())->all();

        $actual = $this->repository
            ->queryToMany($user, 'comments')
            ->filter(['id' => $ids])
            ->cursor();

        $this->assertComments($expected, $actual);
    }

    public function testWithSort(): void
    {
        $user = User::factory()->create();

        $comments = Comment::factory()
            ->count(3)
            ->create(['user_id' => $user]);

        $expected = $comments->sortByDesc('id');

        $actual = $this->repository
            ->queryToMany($user, 'comments')
            ->sort('-id')
            ->cursor();

        $this->assertComments($expected, $actual);
    }

}
