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

use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CreateTest extends TestCase
{

    public function test(): void
    {
        $videos = Video::factory()->count(2)->create();

        $this->actingAs(User::factory()->create(['admin' => true]));

        $tag = $this->repository->create()->store([
            'name' => 'My Tag',
            'videos' => $videos->map(fn(Video $video) => [
                'type' => 'videos',
                'id' => (string) $video->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($tag->relationLoaded('videos'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $tag->getRelation('videos'));
        $this->assertVideos($videos, $actual);

        foreach ($videos as $video) {
            $this->assertDatabaseHas('taggables', [
                'approved' => true,
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }
    }

    public function testEmpty(): void
    {
        $tag = $this->repository->create()->store([
            'name' => 'My Tag',
            'videos' => [],
        ]);

        $this->assertTrue($tag->relationLoaded('videos'));
        $this->assertEquals(new EloquentCollection(), $tag->getRelation('videos'));
    }

    /**
     * The spec says:
     * "If a given type and id is already in the relationship, the server MUST NOT add it again."
     *
     * This test checks that duplicate ids are not added.
     */
    public function testWithDuplicates(): void
    {
        $videos = Video::factory()->count(2)->create();

        $tag = $this->repository->create()->store([
            'name' => 'My Tag',
            'videos' => collect($videos)->push($videos[1])->map(fn(Video $video) => [
                'type' => 'videos',
                'id' => (string) $video->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($tag->relationLoaded('videos'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $tag->getRelation('videos'));
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

    public function testWithCount(): void
    {
        $videos = Video::factory()->count(2)->create();

        $this->actingAs(User::factory()->create(['admin' => true]));

        $tag = $this->repository->create()->withCount('videos')->store([
            'name' => 'My Tag',
            'videos' => $videos->map(fn(Video $video) => [
                'type' => 'videos',
                'id' => (string) $video->getRouteKey(),
            ])->all(),
        ]);

        $this->assertEquals(count($videos), $tag->videos_count);
    }
}
