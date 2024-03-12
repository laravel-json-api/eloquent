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

class CreateTest extends TestCase
{

    public function test(): void
    {
        $tags = Tag::factory()->count(2)->create();

        $this->actingAs(User::factory()->create(['admin' => true]));

        $video = $this->repository->create()->store([
            'slug' => 'my-first-video',
            'tags' => $tags->map(fn(Tag $tag) => [
                'type' => 'tags',
                'id' => (string) $tag->getRouteKey(),
            ])->all(),
            'title' => 'Video 123',
            'url' => 'http://example.com/videos/123.mov',
        ]);

        $this->assertTrue($video->relationLoaded('tags'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $video->getRelation('tags'));
        $this->assertTags($tags, $actual);

        foreach ($tags as $tag) {
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
        $video = $this->repository->create()->store([
            'slug' => 'my-first-video',
            'tags' => [],
            'title' => 'Video 123',
            'url' => 'http://example.com/videos/123.mov',
        ]);

        $this->assertTrue($video->relationLoaded('tags'));
        $this->assertEquals(new EloquentCollection(), $video->getRelation('tags'));
    }

    /**
     * The spec says:
     * "If a given type and id is already in the relationship, the server MUST NOT add it again."
     *
     * This test checks that duplicate ids are not added.
     */
    public function testWithDuplicates(): void
    {
        $tags = Tag::factory()->count(2)->create();

        $video = $this->repository->create()->store([
            'slug' => 'my-first-video',
            'tags' => collect($tags)->push($tags[1])->map(fn(Tag $tag) => [
                'type' => 'tags',
                'id' => (string) $tag->getRouteKey(),
            ])->all(),
            'title' => 'Video 123',
            'url' => 'http://example.com/videos/123.mov',
        ]);

        $this->assertTrue($video->relationLoaded('tags'));
        $this->assertInstanceOf(EloquentCollection::class, $actual = $video->getRelation('tags'));
        $this->assertTags($tags, $actual);
        $this->assertSame(2, $video->tags()->count());

        foreach ($tags as $tag) {
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
        $tags = Tag::factory()->count(2)->create();

        $this->actingAs(User::factory()->create(['admin' => true]));

        $video = $this->repository->create()->withCount('tags')->store([
            'slug' => 'my-first-video',
            'tags' => $tags->map(fn(Tag $tag) => [
                'type' => 'tags',
                'id' => (string) $tag->getRouteKey(),
            ])->all(),
            'title' => 'Video 123',
            'url' => 'http://example.com/videos/123.mov',
        ]);

        $this->assertEquals(count($tags), $video->tags_count);
    }
}
