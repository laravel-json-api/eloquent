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

        $this->assertModels($user->comments()->get(), $actual);
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

        $this->assertModels($user->comments()->get(), $actual);
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

        $this->assertModels($user->comments()->get(), $actual);
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

        $this->assertModels($user->comments()->get(), $actual);

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

        $this->assertModels($expected, $actual);
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

        $this->assertModels($expected, $actual);
    }

}
