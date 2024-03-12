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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class UpdateTest extends TestCase
{

    public function test(): void
    {
        $this->actingAs(User::factory()->create(['admin' => true]));

        /** @var Tag $tag */
        $tag = Tag::factory()
            ->has(Video::factory()->count(3))
            ->create();

        $existing = $tag->videos()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Video::factory()->create()
        );

        $this->repository->update($tag)->store([
            'videos' => $expected->map(fn(Video $video) => [
                'type' => 'videos',
                'id' => (string) $video->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($tag->relationLoaded('videos'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $tag->getRelation('videos'));
        $this->assertVideos($expected, $actual);

        foreach ($expected as $video) {
            $this->assertDatabaseHas('taggables', [
                'approved' => true,
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }

        $this->assertDatabaseMissing('taggables', [
            'tag_id' => $tag->getKey(),
            'taggable_id' => $remove->getKey(),
            'taggable_type' => Video::class,
        ]);
    }

    public function testEmpty(): void
    {
        $tag = Tag::factory()
            ->has(Video::factory()->count(3))
            ->create();

        $existing = $tag->videos()->get();

        $this->repository->update($tag)->store([
            'videos' => [],
        ]);

        $this->assertTrue($tag->relationLoaded('videos'));
        $this->assertEquals(new EloquentCollection(), $tag->getRelation('videos'));

        foreach ($existing as $video) {
            $this->assertDatabaseMissing('taggables', [
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }
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
        $tag = Tag::factory()
            ->has(Video::factory()->count(3))
            ->create();

        $existing = $tag->videos()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Video::factory()->create()
        );

        $this->repository->update($tag)->store([
            'videos' => collect($expected)->push($expected[1])->map(fn(Video $video) => [
                'type' => 'videos',
                'id' => (string) $video->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($tag->relationLoaded('videos'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $tag->getRelation('videos'));
        $this->assertVideos($expected, $actual);

        foreach ($expected as $video) {
            $this->assertDatabaseHas('taggables', [
                'approved' => false,
                'tag_id' => $tag->getKey(),
                'taggable_id' => $video->getKey(),
                'taggable_type' => Video::class,
            ]);
        }

        $this->assertDatabaseMissing('taggables', [
            'tag_id' => $tag->getKey(),
            'taggable_id' => $remove->getKey(),
            'taggable_type' => Video::class,
        ]);
    }

    public function testWithCount(): void
    {
        $this->actingAs(User::factory()->create(['admin' => true]));

        /** @var Tag $tag */
        $tag = Tag::factory()
            ->has(Video::factory()->count(3))
            ->create();

        $existing = $tag->videos()->get();

        $expected = $existing->take(2)->push(
            Video::factory()->create()
        );

        $this->repository->update($tag)->withCount('videos')->store([
            'videos' => $expected->map(fn(Video $video) => [
                'type' => 'videos',
                'id' => (string) $video->getRouteKey(),
            ])->all(),
        ]);

        $this->assertEquals(count($expected), $tag->videos_count);
    }
}
