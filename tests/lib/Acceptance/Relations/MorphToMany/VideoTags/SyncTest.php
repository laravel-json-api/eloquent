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

class SyncTest extends TestCase
{

    public function test(): void
    {
        /** @var Video $video */
        $video = Video::factory()
            ->has(Tag::factory()->count(3))
            ->create();

        $existing = $video->tags()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Tag::factory()->create()
        );

        $actual = $this->repository->modifyToMany($video, 'tags')->sync(
            $expected->map(fn(Tag $tag) => [
                'type' => 'tags',
                'id' => (string) $tag->getRouteKey(),
            ])->all()
        );

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertTags($expected, $actual);

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(count($expected), $video->tags_count);

        $this->assertTrue($video->relationLoaded('tags'));
        $this->assertSame($actual, $video->getRelation('tags'));

        foreach ($expected as $tag) {
            $this->assertDatabaseHas('taggables', [
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }

        $this->assertDatabaseMissing('taggables', [
            'tag_id' => $remove->getKey(),
            'taggable_id' => $video->getKey(),
            'taggable_type' => Video::class,
        ]);
    }

    public function testEmpty(): void
    {
        $video = Video::factory()
            ->has(Tag::factory()->count(3))
            ->create();

        $existing = $video->tags()->get();

        $actual = $this->repository
            ->modifyToMany($video, 'tags')
            ->sync([]);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertEquals(new EloquentCollection(), $actual);
        $this->assertSame(0, $video->tags()->count());

        $this->assertTrue($video->relationLoaded('tags'));
        $this->assertSame($actual, $video->getRelation('tags'));

        foreach ($existing as $tag) {
            $this->assertDatabaseMissing('taggables', [
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $video = Video::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $ids = $tags->map(fn(Tag $tag) => [
            'type' => 'tags',
            'id' => (string) $tag->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'tags')
            ->with('posts')
            ->sync($ids);

        $this->assertCount(3, $actual);
        $this->assertTrue($actual->every(fn(Tag $tag) => $tag->relationLoaded('posts')));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(TagSchema::class, 'posts');

        $video = Video::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $ids = $tags->map(fn(Tag $tag) => [
            'type' => 'tags',
            'id' => (string) $tag->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'tags')
            ->sync($ids);

        $this->assertCount(3, $actual);
        $this->assertTrue($actual->every(fn(Tag $tag) => $tag->relationLoaded('posts')));
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
        $tags = Tag::factory()->count(3)->create();

        $video->tags()->attach($tags[2]);

        $ids = collect($tags)->push($tags[1])->map(fn(Tag $tag) => [
            'type' => 'tags',
            'id' => (string) $tag->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($video, 'tags')
            ->sync($ids);

        $this->assertCount(3, $actual);
        $this->assertSame(3, $video->tags()->count());
        $this->assertTags($tags, $actual);
    }
}
