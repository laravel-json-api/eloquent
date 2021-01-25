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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CreateTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        User::creating(static fn (User $user) => $user->password = 'secret');
    }

    public function test(): void
    {
        $comments = Comment::factory()->count(2)->create();

        $user = $this->repository->create()->store([
            'comments' => $comments->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all(),
            'email' => 'john.doe@example.com',
            'name' => 'John Doe',
        ]);

        $this->assertTrue($user->relationLoaded('comments'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $user->getRelation('comments'));
        $this->assertComments($comments, $actual);

        foreach ($comments as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'user_id' => $user->getKey(),
            ]);
        }
    }

    public function testEmpty(): void
    {
        $user = $this->repository->create()->store([
            'comments' => [],
            'email' => 'john.doe@example.com',
            'name' => 'John Doe',
        ]);

        $this->assertTrue($user->relationLoaded('comments'));
        $this->assertEquals(new EloquentCollection(), $user->getRelation('comments'));
    }


    /**
     * The spec says:
     * "If a given type and id is already in the relationship, the server MUST NOT add it again."
     *
     * This test checks that duplicate ids are not added.
     */
    public function testWithDuplicates(): void
    {
        $comments = Comment::factory()->count(2)->create();

        $user = $this->repository->create()->store([
            'comments' => collect($comments)->push($comments[1])->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all(),
            'email' => 'john.doe@example.com',
            'name' => 'John Doe',
        ]);

        $this->assertTrue($user->relationLoaded('comments'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $user->getRelation('comments'));
        $this->assertComments($comments, $actual);
        $this->assertSame(2, $user->comments()->count());

        foreach ($comments as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'user_id' => $user->getKey(),
            ]);
        }
    }
}
