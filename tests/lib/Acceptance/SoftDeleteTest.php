<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance;

use App\Models\Post;
use App\Models\User;
use App\Schemas\PostSchema;
use Carbon\Carbon;

class SoftDeleteTest extends TestCase
{

    /**
     * @var PostSchema
     */
    private PostSchema $schema;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->schema = $this->app->make(PostSchema::class);

        Post::creating(function (Post $post) {
            $post->user()->associate(User::factory()->create());
        });
    }

    /**
     * @return array
     */
    public static function trashedProvider(): array
    {
        return [
            'trashed' => [new Carbon()],
            'not_trashed' => [null],
        ];
    }

    /**
     * @param $deletedAt
     * @dataProvider trashedProvider
     */
    public function testFind($deletedAt): void
    {
        $post = Post::factory()->create(['deleted_at' => $deletedAt]);

        $actual = $this->schema
            ->repository()
            ->find((string) $post->getRouteKey());

        $this->assertTrue($post->is($actual));
    }

    /**
     * @param $deletedAt
     * @dataProvider trashedProvider
     */
    public function testExists($deletedAt): void
    {
        $post = Post::factory()->create(['deleted_at' => $deletedAt]);

        $actual = $this->schema
            ->repository()
            ->exists((string) $post->getRouteKey());

        $this->assertTrue($actual);
    }

    public function testFindMany(): void
    {
        $posts = Post::factory()->count(3)->sequence(
            ['deleted_at' => null],
            ['deleted_at' => new Carbon()],
        )->create();

        Post::factory()->create(['deleted_at' => null]);
        Post::factory()->create(['deleted_at' => new Carbon()]);

        $ids = $posts
            ->map(fn(Post $post) => (string) $post->getRouteKey())
            ->all();

        $actual = $this->schema
            ->repository()
            ->findMany($ids);

        $this->assertCount(count($posts), $actual);
    }

    /**
     * @param $deletedAt
     * @dataProvider trashedProvider
     */
    public function testQueryOne($deletedAt): void
    {
        $post = Post::factory()->create(['deleted_at' => $deletedAt]);

        $actual = $this->schema
            ->repository()
            ->queryOne((string) $post->getRouteKey())
            ->first();

        $this->assertTrue($post->is($actual));
    }

    /**
     * @param $deletedAt
     * @dataProvider trashedProvider
     */
    public function testItForceDeletesModel($deletedAt): void
    {
        $forceDeleted = false;

        Post::forceDeleted(function () use (&$forceDeleted) {
            $forceDeleted = true;
        });

        $post = Post::factory()->create(['deleted_at' => $deletedAt]);

        $this->schema->repository()->delete((string) $post->getRouteKey());

        $this->assertModelMissing($post);
        $this->assertTrue($forceDeleted);
    }

    public function testItDoesNotRestoreOnCreate(): void
    {
        $post = Post::factory()->make();

        $data = [
            'content' => $post->content,
            'deletedAt' => null,
            'slug' => $post->slug,
            'title' => $post->title,
        ];

        $this->willNotRestore()
            ->willNotSoftDelete()
            ->willNotForceDelete();

        $actual = $this->schema
            ->repository()
            ->create()
            ->store($data);

        $this->assertFalse($actual->trashed());

        $this->assertDatabaseHas('posts', [
            $post->getKeyName() => $actual->getKey(),
            'content' => $post->content,
            'deleted_at' => null,
            'slug' => $post->slug,
            'title' => $post->title,
        ]);
    }

    /**
     * We cannot soft delete on create because the model `delete()` method
     * does not run if the model does not exist. This means Laravel does not
     * allow us to soft delete a model we are creating.
     *
     * If the client provides a value for the soft delete column when creating,
     * we expect the model to be created in a trashed state, but without any
     * deleting events firing (as Laravel does not allow that).
     *
     * If the developer wants to prevent a client from soft-deleting the
     * model on create, they should use validation rules to reject the
     * request: or omit the deleted column value by not validating it on a
     * create request.
     */
    public function testItDoesNotSoftDeleteOnCreate(): void
    {
        $expected = Carbon::now()->subHour()->startOfSecond();

        $post = Post::factory()->make();

        $data = [
            'content' => $post->content,
            'deletedAt' => $expected->toJSON(),
            'slug' => $post->slug,
            'title' => $post->title,
        ];

        $this->willNotRestore()
            ->willNotSoftDelete()
            ->willNotForceDelete();

        $actual = $this->schema
            ->repository()
            ->create()
            ->store($data);

        $this->assertTrue($actual->trashed());

        $this->assertDatabaseHas('posts', [
            $post->getKeyName() => $actual->getKey(),
            'content' => $post->content,
            'deleted_at' => $expected->toDateTimeString(),
            'slug' => $post->slug,
            'title' => $post->title,
        ]);
    }

    public function testItSoftDeletesOnUpdate(): void
    {
        $deleted = false;

        Post::deleted(function () use (&$deleted) {
            $deleted = true;
        });

        $expected = Carbon::yesterday()->startOfSecond();
        $post = Post::factory()->create(['deleted_at' => null]);

        $data = [
            'deletedAt' => $expected->toJSON(),
        ];

        $this->willNotRestore()->willNotForceDelete();

        $actual = $this->schema
            ->repository()
            ->update($post)
            ->store($data);

        $this->assertTrue($actual->trashed());
        $this->assertTrue($deleted);
        $this->assertSoftDeleted($post);
    }

    public function testItSoftDeletesAndUpdatesOtherFields(): void
    {
        $deleted = false;

        Post::deleted(function () use (&$deleted) {
            $deleted = true;
        });

        $expected = Carbon::yesterday()->startOfSecond();
        $post = Post::factory()->create(['deleted_at' => null]);

        $data = [
            'deletedAt' => $expected->toJSON(),
            'title' => 'Hello World!',
        ];

        $this->willNotRestore()->willNotForceDelete();

        $actual = $this->schema
            ->repository()
            ->update($post)
            ->store($data);

        $this->assertTrue($actual->trashed());
        $this->assertTrue($deleted);

        $this->assertDatabaseHas('posts', array_merge($post->getOriginal(), [
            'deleted_at' => $expected->toDateTimeString(),
            'title' => $data['title'],
        ]));
    }

    public function testItSoftDeletesOnUpdateWithBoolean(): void
    {
        $this->asBoolean();

        $deleted = false;

        Post::deleted(function () use (&$deleted) {
            $deleted = true;
        });

        $post = Post::factory()->create(['deleted_at' => null]);

        $data = [
            'deletedAt' => true,
        ];

        $this->willNotRestore()->willNotForceDelete();

        $actual = $this->schema
            ->repository()
            ->update($post)
            ->store($data);

        $this->assertTrue($actual->trashed());
        $this->assertTrue($deleted);
        $this->assertSoftDeleted($post);
    }

    public function testItDoesNotSoftDeleteOnUpdate(): void
    {
        $post = Post::factory()->create(['deleted_at' => null]);

        $data = ['deletedAt' => null, 'title' => 'Hello World!'];

        $this->willNotSoftDelete()
            ->willNotForceDelete()
            ->willNotRestore();

        $actual = $this->schema
            ->repository()
            ->update($post)
            ->store($data);

        $this->assertFalse($actual->trashed());

        $this->assertDatabaseHas('posts', array_merge($post->getOriginal(), [
            'deleted_at' => null,
            'title' => $data['title'],
        ]));
    }

    public function testItDoesNotSoftDeleteOnUpdateIfListenerReturnsFalse(): void
    {
        $post = Post::factory()->create(['deleted_at' => null]);

        $data = ['deletedAt' => now()->toJSON(), 'title' => 'Hello World!'];

        Post::deleting(fn() => false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to soft delete model - App\Models\Post:' . $post->getKey());

        $this->schema
            ->repository()
            ->update($post)
            ->store($data);
    }

    public function testItRestores(): void
    {
        $restored = false;

        Post::restored(function () use (&$restored) {
            $restored = true;
        });

        $post = Post::factory()->create(['deleted_at' => Carbon::now()]);

        $data = [
            'deletedAt' => null,
        ];

        $this->willNotSoftDelete()->willNotForceDelete();

        $actual = $this->schema
            ->repository()
            ->update($post)
            ->store($data);

        $this->assertFalse($actual->trashed());
        $this->assertTrue($restored);
        $this->assertDatabaseHas('posts', array_merge($post->getOriginal(), [
            'deleted_at' => null,
        ]));
    }

    public function testItRestoresAndUpdatesOtherFields(): void
    {
        $restored = false;

        Post::restored(function () use (&$restored) {
            $restored = true;
        });

        $post = Post::factory()->create(['deleted_at' => Carbon::now()]);

        $data = [
            'deletedAt' => null,
            'title' => 'Hello World!',
        ];

        $this->willNotSoftDelete()->willNotForceDelete();

        $actual = $this->schema
            ->repository()
            ->update($post)
            ->store($data);

        $this->assertFalse($actual->trashed());
        $this->assertTrue($restored);

        $this->assertDatabaseHas('posts', array_merge($post->getOriginal(), [
            'deleted_at' => null,
            'title' => $data['title'],
        ]));
    }

    public function testItRestoresWithBoolean(): void
    {
        $this->asBoolean();

        $restored = false;

        Post::restored(function () use (&$restored) {
            $restored = true;
        });

        $post = Post::factory()->create(['deleted_at' => Carbon::now()]);

        $data = [
            'deletedAt' => false,
        ];

        $this->willNotSoftDelete()->willNotForceDelete();

        $actual = $this->schema
            ->repository()
            ->update($post)
            ->store($data);

        $this->assertFalse($actual->trashed());
        $this->assertTrue($restored);
        $this->assertDatabaseHas('posts', array_merge($post->getOriginal(), [
            'deleted_at' => null,
        ]));
    }

    /**
     * If the model is already trashed, the soft delete events should not be triggered
     * even if the client is changing the date on which it was trashed.
     */
    public function testItDoesNotRestoreOnUpdate(): void
    {
        $expected = Carbon::now()->subWeek()->startOfSecond();
        $post = Post::factory()->create(['deleted_at' => Carbon::now()]);

        $data = ['deletedAt' => $expected->toJSON(), 'title' => 'Hello World!'];

        $this->willNotSoftDelete()
            ->willNotForceDelete()
            ->willNotRestore();

        $actual = $this->schema
            ->repository()
            ->update($post)
            ->store($data);

        $this->assertTrue($actual->trashed());

        $this->assertDatabaseHas('posts', array_merge($post->getOriginal(), [
            'deleted_at' => $expected->toDateTimeString(),
            'title' => $data['title'],
        ]));
    }

    public function testWithTrashedIsTrue(): void
    {
        $posts = Post::factory()->count(5)->sequence(
            ['deleted_at' => null],
            ['deleted_at' => Carbon::now()],
        )->create();

        $actual = $this->schema
            ->repository()
            ->queryAll()
            ->filter(['withTrashed' => 'true'])
            ->get();

        $this->assertPosts($posts, $actual);
    }

    public function testWithTrashedIsFalse(): void
    {
        $posts = Post::factory()->count(5)->sequence(
            ['deleted_at' => null],
            ['deleted_at' => Carbon::now()],
        )->create();

        $expected = $posts->reject(fn(Post $post) => $post->trashed());

        $actual = $this->schema
            ->repository()
            ->queryAll()
            ->filter(['withTrashed' => 'false'])
            ->get();

        $this->assertPosts($expected, $actual);
    }

    public function testOnlyTrashedIsTrue(): void
    {
        $posts = Post::factory()->count(5)->sequence(
            ['deleted_at' => null],
            ['deleted_at' => Carbon::now()],
        )->create();

        $expected = $posts->filter(fn(Post $post) => $post->trashed());

        $actual = $this->schema
            ->repository()
            ->queryAll()
            ->filter(['trashed' => 'true'])
            ->get();

        $this->assertPosts($expected, $actual);
    }

    public function testOnlyTrashedIsFalse(): void
    {
        $posts = Post::factory()->count(5)->sequence(
            ['deleted_at' => null],
            ['deleted_at' => Carbon::now()],
        )->create();

        $expected = $posts->reject(fn(Post $post) => $post->trashed());

        $actual = $this->schema
            ->repository()
            ->queryAll()
            ->filter(['trashed' => 'false'])
            ->get();

        $this->assertPosts($expected, $actual);
    }

    /**
     * @param $expected
     * @param $actual
     */
    private function assertPosts($expected, $actual): void
    {
        $this->assertCount(count($expected), $actual);

        $this->assertSame(
            collect($expected)->sortBy('id')->pluck('id')->all(),
            collect($actual)->sortBy('id')->pluck('id')->all()
        );
    }

    /**
     * @return $this
     */
    private function willNotSoftDelete(): self
    {
        Post::deleted(function () {
            throw new \LogicException('Not expecting a restore event.');
        });

        return $this;
    }

    /**
     * @return $this
     */
    private function willNotRestore(): self
    {
        Post::restored(function () {
            throw new \LogicException('Not expecting a restore event.');
        });

        return $this;
    }

    /**
     * @return $this
     */
    private function willNotForceDelete(): self
    {
        Post::forceDeleted(function () {
            throw new \LogicException('Not expecting a restore event.');
        });

        return $this;
    }

    /**
     * Set the schema as using a boolean for soft deletes.
     *
     * @return void
     */
    private function asBoolean(): void
    {
        $this->schema->attribute('deletedAt')->asBoolean();
    }
}
