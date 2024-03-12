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

    public function testWithCount(): void
    {
        $comments = Comment::factory()->count(2)->create();

        $user = $this->repository->create()->withCount('comments')->store([
            'comments' => $comments->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all(),
            'email' => 'john.doe@example.com',
            'name' => 'John Doe',
        ]);

        $this->assertEquals(count($comments), $user->comments_count);
    }
}
