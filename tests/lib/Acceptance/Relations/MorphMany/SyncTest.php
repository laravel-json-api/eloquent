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

class SyncTest extends TestCase
{

    public function testItSyncsAndKeepsDetachedModels(): void
    {
        /** @var Video $video */
        $video = Video::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $existing = $video->comments()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Comment::factory()->create()
        );

        $actual = $this->repository->modifyToMany($video, 'comments')->sync(
            $expected->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all()
        );

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertComments($expected, $actual);

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(count($expected), $video->comments_count);

        $this->assertTrue($video->relationLoaded('comments'));
        $this->assertSame($actual, $video->getRelation('comments'));

        foreach ($expected as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'commentable_id' => $video->getKey(),
                'commentable_type' => Video::class,
            ]);
        }

        $this->assertDatabaseHas('comments', [
            'id' => $remove->getKey(),
            'commentable_id' => null,
            'commentable_type' => null,
        ]);
    }

    public function testItSyncsAndDeletesDetachedModels(): void
    {
        $this->schema->relationship('comments')->deleteDetachedModels();

        /** @var Video $video */
        $video = Video::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $existing = $video->comments()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Comment::factory()->create()
        );

        $actual = $this->repository->modifyToMany($video, 'comments')->sync(
            $expected->map(fn(Comment $comment) => [
                'type' => 'comments',
                'id' => (string) $comment->getRouteKey(),
            ])->all()
        );

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertComments($expected, $actual);

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(count($expected), $video->comments_count);

        $this->assertTrue($video->relationLoaded('comments'));
        $this->assertSame($actual, $video->getRelation('comments'));

        foreach ($expected as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'commentable_id' => $video->getKey(),
                'commentable_type' => Video::class,
            ]);
        }

        $this->assertDatabaseMissing('comments', [
            $remove->getKeyName() => $remove->getKey(),
        ]);
    }

    public function testEmpty(): void
    {
        $video = Video::factory()
            ->has(Comment::factory()->count(3))
            ->create();

        $existing = $video->comments()->get();

        $actual = $this->repository
            ->modifyToMany($video, 'comments')
            ->sync([]);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertEquals(new EloquentCollection(), $actual);
        $this->assertSame(0, $video->comments()->count());

        $this->assertTrue($video->relationLoaded('comments'));
        $this->assertSame($actual, $video->getRelation('comments'));

        foreach ($existing as $comment) {
            $this->assertDatabaseHas('comments', [
                'id' => $comment->getKey(),
                'commentable_id' => null,
                'commentable_type' => null,
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $video = Video::factory()->create();
        $comments = Comment::factory()->count(3)->create();

        $ids = $comments->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'comments')
            ->with('user')
            ->sync($ids);

        $this->assertCount(3, $actual);
        $this->assertTrue($actual->every(fn(Comment $comment) => $comment->relationLoaded('user')));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(CommentSchema::class, 'user');

        $video = Video::factory()->create();
        $comments = Comment::factory()->count(3)->create();

        $ids = $comments->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'comments')
            ->sync($ids);

        $this->assertCount(3, $actual);
        $this->assertTrue($actual->every(fn(Comment $comment) => $comment->relationLoaded('user')));
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
        $comments = Comment::factory()->count(3)->create();

        $comments[1]->commentable()->associate($video)->save();

        $ids = collect($comments)->push($comments[1])->map(fn(Comment $comment) => [
            'type' => 'comments',
            'id' => (string) $comment->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'comments')
            ->sync($ids);

        $this->assertCount(3, $actual);
        $this->assertSame(3, $video->comments()->count());
        $this->assertComments($comments, $actual);
    }
}
