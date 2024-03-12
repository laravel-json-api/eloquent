<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphMany;

use App\Models\Comment;
use App\Models\Video;
use App\Schemas\CommentSchema;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class AttachTest extends TestCase
{

    public function test(): void
    {
        $video = Video::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        /** We force the relation to be loaded before the change, so that we can test it is unset. */
        $existing = clone $video->comments;
        $expected = Comment::factory()->count(2)->create();

        $ids = $expected->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'comments')
            ->attach($ids);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertComments($expected, $actual);
        $this->assertSame(5, $video->comments()->count());

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(5, $video->comments_count);

        /**
         * We expect the relation to be unloaded because we know it has changed in the
         * database, but we don't know what it now is in its entirety.
         */
        $this->assertFalse($video->relationLoaded('comments'));

        foreach ($existing->merge($expected) as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'commentable_id' => $video->getKey(),
                'commentable_type' => Video::class,
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $video = Video::factory()->create();
        $comments = Comment::factory()->count(2)->create();

        $ids = $comments->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'comments')
            ->with('user')
            ->attach($ids);

        $this->assertComments($comments, $actual);
        $this->assertTrue($actual->every(fn(Comment $comment) => $comment->relationLoaded('user')));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(CommentSchema::class, 'commentable');

        $video = Video::factory()->create();
        $comments = Comment::factory()->count(2)->create();

        $ids = $comments->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'comments')
            ->attach($ids);

        $this->assertComments($comments, $actual);
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
        /** @var Video $video */
        $video = Video::factory()->create();
        $comments = Comment::factory()->count(2)->create();

        $comments[0]->commentable()->associate($video)->save();

        $ids = collect($comments)->push($comments[0])->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'comments')
            ->attach($ids);

        $this->assertComments($comments, $actual);
        $this->assertSame(2, $video->comments()->count());
    }
}
