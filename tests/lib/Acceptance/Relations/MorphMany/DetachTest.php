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

class DetachTest extends TestCase
{

    public function testItKeepsDetachedModels(): void
    {
        $video = Video::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        /** We force the relation to be loaded before the change, so that we can test it is unset. */
        $existing = clone $video->comments;
        $remove = $existing->take(2);
        $keep = $existing->last();

        $ids = $remove->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'comments')
            ->detach($ids);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertComments($remove, $actual);
        $this->assertSame(1, $video->comments()->count());

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(1, $video->comments_count);

        /**
         * We expect the relation to be unloaded because we know it has changed in the
         * database, but we don't know what it now is in its entirety.
         */
        $this->assertFalse($video->relationLoaded('comments'));

        $this->assertDatabaseHas('comments', [
            'id' => $keep->getKey(),
            'commentable_id' => $video->getKey(),
            'commentable_type' => Video::class,
        ]);

        foreach ($remove as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'commentable_id' => null,
                'commentable_type' => null,
            ]);
        }
    }

    public function testItDeletesDetachedModels(): void
    {
        $this->schema->relationship('comments')->deleteDetachedModels();

        $video = Video::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        /** We force the relation to be loaded before the change, so that we can test it is unset. */
        $existing = clone $video->comments;
        $remove = $existing->take(2);
        $keep = $existing->last();

        $ids = $remove->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'comments')
            ->detach($ids);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertComments($remove, $actual);
        $this->assertSame(1, $video->comments()->count());

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(1, $video->comments_count);

        /**
         * We expect the relation to be unloaded because we know it has changed in the
         * database, but we don't know what it now is in its entirety.
         */
        $this->assertFalse($video->relationLoaded('comments'));

        $this->assertDatabaseHas('comments', [
            'id' => $keep->getKey(),
            'commentable_id' => $video->getKey(),
            'commentable_type' => Video::class,
        ]);

        foreach ($remove as $comment) {
            $this->assertDatabaseMissing('comments', [
                $comment->getKeyName() => $comment->getKey(),
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $video = Video::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $comments = clone $video->comments;

        $ids = $comments->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'comments')
            ->with('user')
            ->detach($ids);

        $this->assertComments($comments, $actual);
        $this->assertTrue($actual->every(fn(Comment $comment) => $comment->relationLoaded('user')));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(CommentSchema::class, 'user');

        $video = Video::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $comments = clone $video->comments;

        $ids = $comments->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'comments')
            ->detach($ids);

        $this->assertComments($comments, $actual);
        $this->assertTrue($actual->every(fn(Comment $comment) => $comment->relationLoaded('user')));
    }
}
