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

class UpdateTest extends TestCase
{

    public function test(): void
    {
        /** @var Post $post */
        $post = Post::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $existing = $post->comments()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Comment::factory()->create()
        );

        $this->repository->update($post)->store([
            'comments' => $expected->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($post->relationLoaded('comments'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $post->getRelation('comments'));
        $this->assertComments($expected, $actual);

        foreach ($expected as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'post_id' => $post->getKey(),
            ]);
        }

        $this->assertDatabaseHas('comments', [
            'id' => $remove->getKey(),
            'post_id' => null,
        ]);
    }

    public function testEmpty(): void
    {
        $post = Post::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $existing = $post->comments()->get();

        $this->repository->update($post)->store([
            'comments' => [],
        ]);

        $this->assertTrue($post->relationLoaded('comments'));
        $this->assertEquals(new EloquentCollection(), $post->getRelation('comments'));

        foreach ($existing as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'post_id' => null,
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
        /** @var Post $post */
        $post = Post::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $existing = $post->comments()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Comment::factory()->create()
        );

        $this->repository->update($post)->store([
            'comments' => collect($expected)->push($expected[1])->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($post->relationLoaded('comments'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $post->getRelation('comments'));
        $this->assertComments($expected, $actual);

        foreach ($expected as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'post_id' => $post->getKey(),
            ]);
        }

        $this->assertDatabaseHas('comments', [
            'id' => $remove->getKey(),
            'post_id' => null,
        ]);
    }
}
