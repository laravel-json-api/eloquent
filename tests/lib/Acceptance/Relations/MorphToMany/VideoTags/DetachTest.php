<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphToMany\VideoTags;

use App\Models\Tag;
use App\Models\Video;
use App\Schemas\TagSchema;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class DetachTest extends TestCase
{

    public function test(): void
    {
        $video = Video::factory()
            ->has(Tag::factory()->count(3))
            ->create();

        /** We force the relation to be loaded before the change, so that we can test it is unset. */
        $existing = clone $video->tags;
        $remove = $existing->take(2);
        $keep = $existing->last();

        $ids = $remove->map(fn(Tag $tag) => [
            'type' => 'tags',
            'id' => (string) $tag->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'tags')
            ->detach($ids);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertTags($remove, $actual);
        $this->assertSame(1, $video->tags()->count());

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(1, $video->tags_count);

        /**
         * We expect the relation to be unloaded because we know it has changed in the
         * database, but we don't know what it now is in its entirety.
         */
        $this->assertFalse($video->relationLoaded('tags'));

        $this->assertDatabaseHas('taggables', [
            'tag_id' => $keep->getKey(),
            'taggable_id' => $video->getKey(),
            'taggable_type' => Video::class,
        ]);

        foreach ($remove as $tag) {
            $this->assertDatabaseMissing('taggables', [
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $video = Video::factory()
            ->has(Tag::factory()->count(3))
            ->create();

        $roles = clone $video->tags;

        $ids = $roles->map(fn(Tag $tag) => [
            'type' => 'tags',
            'id' => (string) $tag->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'tags')
            ->with('posts')
            ->detach($ids);

        $this->assertTags($roles, $actual);
        $this->assertTrue($actual->every(fn(Tag $tag) => $tag->relationLoaded('posts')));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(TagSchema::class, 'posts');

        $video = Video::factory()
            ->has(Tag::factory()->count(3))
            ->create();

        $roles = clone $video->tags;

        $ids = $roles->map(fn(Tag $tag) => [
            'type' => 'tags',
            'id' => (string) $tag->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'tags')
            ->detach($ids);

        $this->assertTags($roles, $actual);
        $this->assertTrue($actual->every(fn(Tag $tag) => $tag->relationLoaded('posts')));
    }
}
