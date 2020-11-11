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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class RemoveTest extends TestCase
{

    public function test(): void
    {
        $post = Post::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        /** We force the relation to be loaded before the change, so that we can test it is unset. */
        $existing = clone $post->comments;
        $remove = $existing->take(2);
        $keep = $existing->last();

        $ids = $remove->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($post, 'comments')
            ->remove($ids);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertComments($remove, $actual);
        $this->assertSame(1, $post->comments()->count());

        /**
         * We expect the relation to be unloaded because we know it has changed in the
         * database, but we don't know what it now is in its entirety.
         */
        $this->assertFalse($post->relationLoaded('comments'));

        $this->assertDatabaseHas('comments', [
            'id' => $keep->getKey(),
            'post_id' => $post->getKey(),
        ]);

        foreach ($remove as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'post_id' => null,
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $post = Post::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $comments = clone $post->comments;

        $ids = $comments->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($post, 'comments')
            ->with('user')
            ->remove($ids);

        $this->assertComments($comments, $actual);
        $this->assertTrue($actual->every(fn(Comment $comment) => $comment->relationLoaded('user')));
    }
}
