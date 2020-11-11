<?php
/*
 * Copyright 2020 Cloud Creativity Limited
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

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\OneToMany;

use App\Models\Comment;
use App\Models\Post;

class QueryTest extends TestCase
{

    public function test(): void
    {
        $post = Post::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        // should be ignored.
        Comment::factory()->create();

        $actual = $this->repository
            ->queryToMany($post, 'comments')
            ->cursor();

        $this->assertComments($post->comments()->get(), $actual);
    }

    public function testWithIncludePaths(): void
    {
        $post = Post::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $actual = $this->repository
            ->queryToMany($post, 'comments')
            ->with('user')
            ->cursor();

        $this->assertComments($post->comments()->get(), $actual);
        $this->assertTrue($actual->every(fn(Comment $comment) => $comment->relationLoaded('user')));
    }

    public function testWithFilter(): void
    {
        $post = Post::factory()->create();

        $comments = Comment::factory()
            ->count(3)
            ->create(['post_id' => $post]);

        $expected = $comments->take(2);
        $ids = $expected->map(fn (Comment $comment) => $comment->getRouteKey())->all();

        $actual = $this->repository
            ->queryToMany($post, 'comments')
            ->filter(['id' => $ids])
            ->cursor();

        $this->assertComments($expected, $actual);
    }

    public function testWithSort(): void
    {
        $post = Post::factory()->create();

        $comments = Comment::factory()
            ->count(3)
            ->create(['post_id' => $post]);

        $expected = $comments->sortByDesc('id');

        $actual = $this->repository
            ->queryToMany($post, 'comments')
            ->sort('-id')
            ->cursor();

        $this->assertComments($expected, $actual);
    }

}
