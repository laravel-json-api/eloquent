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
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class UpdateTest extends TestCase
{

    public function test(): void
    {
        $this->actingAs(User::factory()->create(['admin' => true]));

        /** @var Video $video */
        $video = Video::factory()
            ->has(Tag::factory()->count(3))
            ->create();

        $existing = $video->tags()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Tag::factory()->create()
        );

        $this->repository->update($video)->store([
            'tags' => $expected->map(fn(Tag $tag) => [
                'type' => 'tags',
                'id' => (string) $tag->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($video->relationLoaded('tags'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $video->getRelation('tags'));
        $this->assertTags($expected, $actual);

        foreach ($expected as $tag) {
            $this->assertDatabaseHas('taggables', [
                'approved' => true,
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

        $this->repository->update($video)->store([
            'tags' => [],
        ]);

        $this->assertTrue($video->relationLoaded('tags'));
        $this->assertEquals(new EloquentCollection(), $video->getRelation('tags'));

        foreach ($existing as $tag) {
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
        /** @var Video $video */
        $video = Video::factory()
            ->has(Tag::factory()->count(3))
            ->create();

        $existing = $video->tags()->get();
        $remove = $existing->last();

        $expected = $existing->take(2)->push(
            Tag::factory()->create()
        );

        $this->repository->update($video)->store([
            'tags' => collect($expected)->push($expected[1])->map(fn(Tag $tag) => [
                'type' => 'tags',
                'id' => (string) $tag->getRouteKey(),
            ])->all(),
        ]);

        $this->assertTrue($video->relationLoaded('tags'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $video->getRelation('tags'));
        $this->assertTags($expected, $actual);

        foreach ($expected as $tag) {
            $this->assertDatabaseHas('taggables', [
                'approved' => false,
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

    public function testWithCount(): void
    {
        $this->actingAs(User::factory()->create(['admin' => true]));

        /** @var Video $video */
        $video = Video::factory()
            ->has(Tag::factory()->count(3))
            ->create();

        $existing = $video->tags()->get();

        $expected = $existing->take(2)->push(
            Tag::factory()->create()
        );

        $this->repository->update($video)->withCount('tags')->store([
            'tags' => $expected->map(fn(Tag $tag) => [
                'type' => 'tags',
                'id' => (string) $tag->getRouteKey(),
            ])->all(),
        ]);

        $this->assertEquals(count($expected), $video->tags_count);
    }
}
