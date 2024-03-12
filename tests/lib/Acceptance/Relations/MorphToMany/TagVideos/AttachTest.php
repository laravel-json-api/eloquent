<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Acceptance\Relations\MorphToMany\TagVideos;

use App\Models\Tag;
use App\Models\User;
use App\Models\Video;
use App\Schemas\VideoSchema;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class AttachTest extends TestCase
{

    public function test(): void
    {
        $this->actingAs(User::factory()->create(['admin' => true]));

        $tag = Tag::factory()
            ->hasAttached(Video::factory()->count(3), ['approved' => false])
            ->create();

        /** We force the relation to be loaded before the change, so that we can test it is unset. */
        $existing = clone $tag->videos;
        $expected = Video::factory()->count(2)->create();

        $ids = $expected->map(fn(Video $video) => [
            'type' => 'videos',
            'id' => (string) $video->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($tag, 'videos')
            ->attach($ids);

        $this->assertInstanceOf(EloquentCollection::class, $actual);
        $this->assertVideos($expected, $actual);
        $this->assertSame(5, $tag->videos()->count());

        // as the relationship is countable, we expect the count to be loaded so the relationship meta is complete.
        $this->assertEquals(5, $tag->videos_count);

        /**
         * We expect the relation to be unloaded because we know it has changed in the
         * database, but we don't know what it now is in its entirety.
         */
        $this->assertFalse($tag->relationLoaded('videos'));

        foreach ($existing as $video) {
            $this->assertDatabaseHas('taggables', [
                'approved' => false, // we expect existing to keep their value
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }

        foreach ($expected as $video) {
            $this->assertDatabaseHas('taggables', [
                'approved' => true, // newly added should have the calculated value.
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }
    }

    public function testWithIncludePaths(): void
    {
        $tag = Tag::factory()->create();
        $videos = Video::factory()->count(2)->create();

        $ids = $videos->map(fn(Video $video) => [
            'type' => 'videos',
            'id' => (string) $video->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($tag, 'videos')
            ->with('comments')
            ->attach($ids);

        $this->assertVideos($videos, $actual);
        $this->assertTrue($actual->every(fn(Video $video) => $video->relationLoaded('comments')));
    }

    public function testWithDefaultEagerLoading(): void
    {
        $this->createSchemaWithDefaultEagerLoading(VideoSchema::class, 'comments');

        $tag = Tag::factory()->create();
        $videos = Video::factory()->count(2)->create();

        $ids = $videos->map(fn(Video $video) => [
            'type' => 'videos',
            'id' => (string) $video->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($tag, 'videos')
            ->attach($ids);

        $this->assertVideos($videos, $actual);
        $this->assertTrue($actual->every(fn(Video $video) => $video->relationLoaded('comments')));
    }

    /**
     * The spec says:
     * "If a given type and id is already in the relationship, the server MUST NOT add it again."
     *
     * This test checks that duplicate ids are not added.
     */
    public function testWithDuplicates(): void
    {
        /** @var Tag $tag */
        $tag = Tag::factory()->create();
        $videos = Video::factory()->count(2)->create();

        $tag->videos()->attach($videos[1]);

        $ids = collect($videos)->push($videos[0], $videos[1])->map(fn(Video $video) => [
            'type' => 'videos',
            'id' => (string) $video->getRouteKey(),
        ])->all();

        $actual = $this->repository
            ->modifyToMany($tag, 'videos')
            ->attach($ids);

        $this->assertVideos($videos, $actual);
        $this->assertSame(2, $tag->videos()->count());

        foreach ($videos as $video) {
            $this->assertDatabaseHas('taggables', [
                'approved' => false,
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }
    }
}
